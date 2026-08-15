<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\GameWeek;
use App\Models\LiveMatchEvent;
use App\Models\Season;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateExistingSeasons extends Command
{
    protected $signature = 'season:migrate-existing
                            {--dry-run : Report what would change without writing}
                            {--cutoff=2026-08-10 : Kick-off date separating 2025-26 from 2026-27}';

    protected $description = 'Split mixed gameweek/game data into 2025-26 and 2026-27 seasons, seed season_team, and complete old tournaments';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::parse($this->option('cutoff'))->startOfDay();

        $this->info($dryRun ? 'DRY RUN — no changes will be written.' : 'Migrating existing data into seasons...');
        $this->line("Cutoff: games with kick_off_time < {$cutoff->toDateString()} → 2025-26; otherwise → 2026-27");

        $oldGames = Game::query()
            ->with('gameWeek')
            ->where('kick_off_time', '<', $cutoff)
            ->orderBy('kick_off_time')
            ->get();
        $newGames = Game::query()
            ->with('gameWeek')
            ->where('kick_off_time', '>=', $cutoff)
            ->orderBy('kick_off_time')
            ->get();

        $this->table(['Bucket', 'Games'], [
            ['2025-26 (before cutoff)', $oldGames->count()],
            ['2026-27 (on/after cutoff)', $newGames->count()],
            ['Unclassified (null KO)', Game::whereNull('kick_off_time')->count()],
        ]);

        if ($oldGames->isEmpty() && $newGames->isEmpty()) {
            $this->warn('No games found to classify. Aborting.');
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->reportDryRun($oldGames, $newGames);
            return Command::SUCCESS;
        }

        try {
            DB::transaction(function () use ($oldGames, $newGames, $cutoff) {
                $season2526 = $this->ensureSeason(
                    name: '2025-26',
                    apiYear: 2025,
                    isCurrent: false,
                    startsOn: $oldGames->min('kick_off_time')?->toDateString() ?? '2025-08-15',
                    endsOn: $oldGames->max('kick_off_time')?->toDateString() ?? '2026-05-25'
                );

                $season2627 = $this->ensureSeason(
                    name: '2026-27',
                    apiYear: 2026,
                    isCurrent: true,
                    startsOn: $newGames->min('kick_off_time')?->toDateString() ?? $cutoff->toDateString(),
                    endsOn: $newGames->max('kick_off_time')?->toDateString()
                );

                // Existing gameweek rows become 2025-26; new season gets fresh rows.
                $existingWeeks = GameWeek::query()->orderBy('week_number')->get();
                foreach ($existingWeeks as $week) {
                    $week->update(['season_id' => $season2526->id]);
                }

                $this->rebuildGameweeksForSeason($season2526, $oldGames, preferExisting: true);
                $this->rebuildGameweeksForSeason($season2627, $newGames, preferExisting: false);

                $this->syncSeasonTeams($season2526, $oldGames);
                $this->syncSeasonTeams($season2627, $newGames);

                $tournamentsUpdated = Tournament::query()->update([
                    'season_id' => $season2526->id,
                    'status' => 'completed',
                ]);

                LiveMatchEvent::query()
                    ->whereHas('game', fn ($q) => $q->where('status', 'FINISHED'))
                    ->delete();

                Cache::forget('historical_premier_league_data');

                $this->printSanityReport($season2526, $season2627, $tournamentsUpdated);
            });
        } catch (\Throwable $e) {
            Log::error('season:migrate-existing failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Migration failed: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Season migration completed successfully.');

        return Command::SUCCESS;
    }

    private function ensureSeason(
        string $name,
        int $apiYear,
        bool $isCurrent,
        ?string $startsOn,
        ?string $endsOn
    ): Season {
        $season = Season::updateOrCreate(
            ['api_year' => $apiYear],
            [
                'name' => $name,
                'slug' => $name,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'is_current' => $isCurrent,
            ]
        );

        if ($isCurrent) {
            Season::where('id', '!=', $season->id)->update(['is_current' => false]);
            $season->update(['is_current' => true]);
        }

        return $season->fresh();
    }

    /**
     * Group games by matchday (from their current gameweek week_number), attach/create
     * season-scoped gameweeks, and re-point games.
     */
    private function rebuildGameweeksForSeason(Season $season, $games, bool $preferExisting): void
    {
        if ($games->isEmpty()) {
            $this->warn("No games for season {$season->name}; skipping gameweek rebuild.");
            return;
        }

        $byWeek = $games->groupBy(function (Game $game) {
            return optional($game->gameWeek)->week_number
                ?? $this->inferWeekNumber($game);
        });

        foreach ($byWeek as $weekNumber => $weekGames) {
            $weekNumber = (int) $weekNumber;
            if ($weekNumber < 1) {
                $this->warn("Skipping {$weekGames->count()} games with unknown week for {$season->name}");
                continue;
            }

            $kickOffs = $weekGames->pluck('kick_off_time')->filter()->sort();
            $start = $kickOffs->first();
            $end = $kickOffs->last();

            $gameweek = null;
            if ($preferExisting) {
                $gameweek = GameWeek::where('season_id', $season->id)
                    ->where('week_number', $weekNumber)
                    ->first();
            }

            if (!$gameweek) {
                $gameweek = GameWeek::updateOrCreate(
                    [
                        'season_id' => $season->id,
                        'week_number' => $weekNumber,
                    ],
                    [
                        'name' => "Gameweek {$weekNumber}",
                        'start_date' => $start?->toDateString() ?? now()->toDateString(),
                        'end_date' => $end?->toDateString() ?? now()->toDateString(),
                        'gameweek_start_time' => $start,
                        'gameweek_end_time' => $end,
                        'selection_opens' => $start ? $start->copy()->subWeek() : null,
                        'selection_deadline' => $start ? $start->copy()->subDay()->endOfDay() : null,
                        'is_completed' => $weekGames->every(fn (Game $g) => $g->status === 'FINISHED'),
                        'completed_at' => $weekGames->every(fn (Game $g) => $g->status === 'FINISHED')
                            ? ($end ?? now())
                            : null,
                    ]
                );
            } else {
                $gameweek->update([
                    'start_date' => $start?->toDateString() ?? $gameweek->start_date,
                    'end_date' => $end?->toDateString() ?? $gameweek->end_date,
                    'gameweek_start_time' => $start ?? $gameweek->gameweek_start_time,
                    'gameweek_end_time' => $end ?? $gameweek->gameweek_end_time,
                    'is_completed' => $weekGames->every(fn (Game $g) => $g->status === 'FINISHED'),
                    'completed_at' => $weekGames->every(fn (Game $g) => $g->status === 'FINISHED')
                        ? ($end ?? now())
                        : null,
                ]);
            }

            Game::whereIn('id', $weekGames->pluck('id'))
                ->update(['game_week_id' => $gameweek->id]);
        }

        // Drop empty gameweeks left on this season that have no games (e.g. leftover from split).
        if (!$preferExisting) {
            return;
        }

        $emptyIds = GameWeek::where('season_id', $season->id)
            ->whereDoesntHave('games')
            ->pluck('id');

        if ($emptyIds->isNotEmpty()) {
            // Remap picks that pointed at empty weeks? Prefer leave them if they still have picks.
            $withPicks = GameWeek::whereIn('id', $emptyIds)->whereHas('picks')->pluck('id');
            $deletable = $emptyIds->diff($withPicks);
            if ($deletable->isNotEmpty()) {
                GameWeek::whereIn('id', $deletable)->delete();
                $this->line("Deleted {$deletable->count()} empty 2025-26 gameweeks with no games/picks.");
            }
        }
    }

    private function inferWeekNumber(Game $game): int
    {
        // Fallback: use matchday from related week if still loaded, else 0
        return (int) (optional(GameWeek::find($game->game_week_id))->week_number ?? 0);
    }

    private function syncSeasonTeams(Season $season, $games): void
    {
        $teamIds = $games
            ->flatMap(fn (Game $g) => [$g->home_team_id, $g->away_team_id])
            ->unique()
            ->filter()
            ->values()
            ->all();

        $season->teams()->sync($teamIds);
        $this->line("Season {$season->name}: synced " . count($teamIds) . ' teams.');
    }

    private function reportDryRun($oldGames, $newGames): void
    {
        $oldWeeks = $oldGames->groupBy(fn (Game $g) => optional($g->gameWeek)->week_number)->keys()->sort()->values();
        $newWeeks = $newGames->groupBy(fn (Game $g) => optional($g->gameWeek)->week_number)->keys()->sort()->values();

        $oldTeams = $oldGames->flatMap(fn (Game $g) => [$g->home_team_id, $g->away_team_id])->unique()->count();
        $newTeams = $newGames->flatMap(fn (Game $g) => [$g->home_team_id, $g->away_team_id])->unique()->count();

        $sharedWeekNumbers = $oldWeeks->intersect($newWeeks)->values();

        $this->info('Would create seasons 2025-26 (archived) and 2026-27 (current).');
        $this->table(['Metric', '2025-26', '2026-27'], [
            ['Games', $oldGames->count(), $newGames->count()],
            ['Distinct week_numbers', $oldWeeks->count(), $newWeeks->count()],
            ['Distinct teams', $oldTeams, $newTeams],
            ['Tournaments to complete', Tournament::count(), '—'],
        ]);

        if ($sharedWeekNumbers->isNotEmpty()) {
            $this->warn(
                'Shared week_numbers on same rows (will be split): '
                . $sharedWeekNumbers->implode(', ')
            );
        }

        $mixedWeeks = GameWeek::withCount([
            'games as old_games_count' => fn ($q) => $q->where('kick_off_time', '<', $this->option('cutoff')),
            'games as new_games_count' => fn ($q) => $q->where('kick_off_time', '>=', $this->option('cutoff')),
        ])->get()->filter(fn ($gw) => $gw->old_games_count > 0 && $gw->new_games_count > 0);

        if ($mixedWeeks->isNotEmpty()) {
            $this->warn('Gameweeks currently mixing both seasons:');
            foreach ($mixedWeeks as $gw) {
                $this->line("  GW{$gw->week_number} (id={$gw->id}): {$gw->old_games_count} old + {$gw->new_games_count} new");
            }
        }
    }

    private function printSanityReport(Season $old, Season $new, int $tournamentsUpdated): void
    {
        $this->newLine();
        $this->info('Sanity report');
        $this->table(['Metric', '2025-26', '2026-27'], [
            [
                'Gameweeks',
                GameWeek::where('season_id', $old->id)->count(),
                GameWeek::where('season_id', $new->id)->count(),
            ],
            [
                'Games',
                Game::whereHas('gameWeek', fn ($q) => $q->where('season_id', $old->id))->count(),
                Game::whereHas('gameWeek', fn ($q) => $q->where('season_id', $new->id))->count(),
            ],
            [
                'Teams (season_team)',
                $old->teams()->count(),
                $new->teams()->count(),
            ],
            [
                'Tournaments (season / completed)',
                Tournament::where('season_id', $old->id)->where('status', 'completed')->count(),
                Tournament::where('season_id', $new->id)->count(),
            ],
        ]);

        $this->line("Tournaments updated: {$tournamentsUpdated}");

        $orphaned = Game::whereDoesntHave('gameWeek', fn ($q) => $q->whereNotNull('season_id'))->count();
        if ($orphaned > 0) {
            $this->warn("Games without a season-scoped gameweek: {$orphaned}");
        }

        $unscopedWeeks = GameWeek::whereNull('season_id')->count();
        if ($unscopedWeeks > 0) {
            $this->warn("Gameweeks still without season_id: {$unscopedWeeks}");
        }
    }
}
