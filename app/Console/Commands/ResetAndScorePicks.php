<?php

namespace App\Console\Commands;

use App\Models\Pick;
use App\Models\Game;
use Illuminate\Console\Command;

class ResetAndScorePicks extends Command
{
    protected $signature = 'picks:reset-and-score {--season= : Season slug/name/api_year; defaults to current season only} {--all : Reset and rescore picks across every season}';
    protected $description = 'Reset pick results and rescore them based on actual game results (current season by default)';

    public function handle()
    {
        $this->info('Resetting and rescoring picks...');

        $pickQuery = Pick::query();

        if (!$this->option('all')) {
            $season = \App\Models\Season::resolveFromRequest($this->option('season'));
            if (!$season) {
                $this->error('No season found. Pass --season= or run season:migrate-existing first.');
                return Command::FAILURE;
            }

            $this->info("Scoping to season {$season->name}");
            $pickQuery->whereHas('tournament', fn ($q) => $q->where('season_id', $season->id));
        } else {
            $this->warn('Rescoring ALL seasons (--all).');
        }

        $pickIds = (clone $pickQuery)->pluck('id');

        // First, reset scoped picks
        Pick::whereIn('id', $pickIds)->update([
            'result' => null,
            'points_earned' => null
        ]);

        $this->info('Pick results have been reset for ' . $pickIds->count() . ' picks.');

        // Now rescore them based on actual finished games
        $picks = Pick::with(['team', 'gameWeek', 'user', 'tournament'])
            ->whereIn('id', $pickIds)
            ->get();

        $this->info("Processing " . $picks->count() . " picks...");

        $scoredCount = 0;

        foreach ($picks as $pick) {
            // Find the game this pick relates to in the same gameweek
            $game = Game::where('game_week_id', $pick->game_week_id)
                ->where(function ($query) use ($pick) {
                    $query->where('home_team_id', $pick->team_id)
                          ->orWhere('away_team_id', $pick->team_id);
                })
                ->where('status', 'FINISHED')
                ->with(['homeTeam', 'awayTeam'])
                ->first();

            if (!$game) {
                $this->line("No finished game found for {$pick->user->name}'s pick: {$pick->team->name} (GW{$pick->gameWeek->week_number})");
                continue;
            }

            // Determine if the picked team was home or away
            $isHomeTeam = $game->home_team_id === $pick->team_id;
            
            // Get game result using the Game model's method
            $gameResult = $game->getResult(); // Returns HOME_WIN, AWAY_WIN, or DRAW

            // Calculate pick result based on which team was picked
            $pickResult = null;
            if ($isHomeTeam) {
                // User picked the home team
                $pickResult = match($gameResult) {
                    'HOME_WIN' => 'win',
                    'AWAY_WIN' => 'loss',
                    'DRAW' => 'draw',
                    default => null
                };
            } else {
                // User picked the away team
                $pickResult = match($gameResult) {
                    'AWAY_WIN' => 'win',
                    'HOME_WIN' => 'loss',
                    'DRAW' => 'draw',
                    default => null
                };
            }

            if ($pickResult) {
                $pick->setResult($pickResult);
                $scoredCount++;
                
                $gameScore = "{$game->home_score}-{$game->away_score}";
                $homeAwayText = $isHomeTeam ? 'home' : 'away';
                $this->info("✓ {$pick->user->name} ({$pick->tournament->name}): {$pick->team->name} ({$homeAwayText}) → {$pickResult} ({$pick->points_earned} pts)");
                $this->line("   Game: {$game->homeTeam->name} {$gameScore} {$game->awayTeam->name} = {$gameResult}");
            }
        }

        $this->info("\nReset and scored {$scoredCount} picks successfully!");
        
        // Recalculate tournament points
        $this->call('tournament:recalculate-points');
        
        return Command::SUCCESS;
    }
}
