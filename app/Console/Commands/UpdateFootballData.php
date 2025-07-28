<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\GameWeek;
use App\Models\Game;
use App\Models\Pick;
use App\Services\FootballDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateFootballData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:update 
                            {--teams : Only update teams data}
                            {--gameweeks : Only update gameweeks data}
                            {--games : Only update games data}
                            {--results : Only update game results and calculate points}
                            {--force : Force update even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Premier League teams, gameweeks, games and results data from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $footballService = new FootballDataService();

        if (!$footballService->isConfigured()) {
            $this->error('Football Data API key not configured. Please set FOOTBALL_DATA_API_KEY in your .env file.');
            $this->info('You can get a free API key from: https://www.football-data.org/');
            return Command::FAILURE;
        }

        $teamsOnly = $this->option('teams');
        $gameweeksOnly = $this->option('gameweeks');
        $gamesOnly = $this->option('games');
        $resultsOnly = $this->option('results');
        $force = $this->option('force');

        // If no specific option is provided, update everything
        if (!$teamsOnly && !$gameweeksOnly && !$gamesOnly && !$resultsOnly) {
            $teamsOnly = $gameweeksOnly = $gamesOnly = $resultsOnly = true;
        }

        if ($teamsOnly) {
            $this->updateTeams($footballService, $force);
        }

        if ($gameweeksOnly) {
            $this->updateGameweeks($footballService, $force);
        }

        if ($gamesOnly) {
            $this->updateGames($footballService, $force);
        }

        if ($resultsOnly) {
            $this->updateGameResults($footballService, $force);
        }

        $this->info('Football data update completed!');
        return Command::SUCCESS;
    }

    /**
     * Update teams data
     */
    private function updateTeams(FootballDataService $footballService, bool $force): void
    {
        $this->info('Updating Premier League teams...');

        if (!$force && Team::count() > 0) {
            if (!$this->confirm('Teams data already exists. Do you want to continue?')) {
                $this->info('Teams update skipped.');
                return;
            }
        }

        $teams = $footballService->getPremierLeagueTeams();

        if (empty($teams)) {
            $this->error('Failed to fetch teams from API.');
            return;
        }

        $progressBar = $this->output->createProgressBar(count($teams));
        $progressBar->start();

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['short_name' => $team['short_name']],
                $team
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Successfully updated ' . count($teams) . ' teams.');
    }

    /**
     * Update gameweeks data
     */
    private function updateGameweeks(FootballDataService $footballService, bool $force): void
    {
        $this->info('Updating Premier League gameweeks...');

        if (!$force && GameWeek::count() > 0) {
            if (!$this->confirm('Gameweeks data already exists. Do you want to continue?')) {
                $this->info('Gameweeks update skipped.');
                return;
            }
        }

        $gameweeks = $footballService->getPremierLeagueFixtures();

        if (empty($gameweeks)) {
            $this->error('Failed to fetch gameweeks from API.');
            return;
        }

        $progressBar = $this->output->createProgressBar(count($gameweeks));
        $progressBar->start();

        foreach ($gameweeks as $gameweek) {
            GameWeek::updateOrCreate(
                ['week_number' => $gameweek['week_number']],
                $gameweek
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Successfully updated ' . count($gameweeks) . ' gameweeks.');
    }

    /**
     * Update games data
     */
    private function updateGames(FootballDataService $footballService, bool $force): void
    {
        $this->info('Updating Premier League games...');

        if (!$force && Game::count() > 0) {
            if (!$this->confirm('Games data already exists. Do you want to continue?')) {
                $this->info('Games update skipped.');
                return;
            }
        }

        $gamesData = $footballService->getPremierLeagueGames();

        if (empty($gamesData)) {
            $this->error('Failed to fetch games from API.');
            return;
        }

        $this->info('Processing ' . count($gamesData) . ' games...');

        // Create lookup maps for teams and gameweeks
        $teams = Team::all()->keyBy('external_id');
        $gameWeeks = GameWeek::all()->keyBy('week_number');

        $progressBar = $this->output->createProgressBar(count($gamesData));
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($gamesData as $gameData) {
            try {
                // Find the teams
                $homeTeam = $teams->get($gameData['home_team_external_id']);
                $awayTeam = $teams->get($gameData['away_team_external_id']);
                $gameWeek = $gameWeeks->get($gameData['matchday']);

                if (!$homeTeam || !$awayTeam || !$gameWeek) {
                    $errorCount++;
                } else {
                    // Create or update the game
                    Game::updateOrCreate(
                        ['external_id' => $gameData['external_id']],
                        [
                            'game_week_id' => $gameWeek->id,
                            'home_team_id' => $homeTeam->id,
                            'away_team_id' => $awayTeam->id,
                            'kick_off_time' => $gameData['kick_off_time'],
                            'home_score' => $gameData['home_score'],
                            'away_score' => $gameData['away_score'],
                            'status' => $gameData['status'],
                        ]
                    );
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Error updating game", [
                    'game_id' => $gameData['external_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Successfully updated {$successCount} games. Errors: {$errorCount}");
    }

    /**
     * Update game results and calculate points
     */
    private function updateGameResults(FootballDataService $footballService, bool $force): void
    {
        $this->info('Updating game results and calculating points...');
        
        $gamesData = $footballService->getPremierLeagueGames();
        
        if (empty($gamesData)) {
            $this->error('No games data received from API.');
            return;
        }

        $this->info('Processing ' . count($gamesData) . ' games...');

        $updatedCount = 0;
        $newResultsCount = 0;

        foreach ($gamesData as $gameData) {
            try {
                $game = Game::where('external_id', $gameData['external_id'])->first();
                
                if (!$game) {
                    continue;
                }

                // Check if result has changed
                $hasNewResult = (
                    $game->status !== $gameData['status'] ||
                    $game->home_score !== $gameData['home_score'] ||
                    $game->away_score !== $gameData['away_score']
                );

                if ($hasNewResult) {
                    $oldStatus = $game->status;
                    
                    // Update game
                    $game->update([
                        'home_score' => $gameData['home_score'],
                        'away_score' => $gameData['away_score'],
                        'status' => $gameData['status'],
                    ]);

                    $this->line("Updated: {$game->homeTeam->name} vs {$game->awayTeam->name} - {$gameData['status']}");
                    $updatedCount++;

                    // If game is finished and wasn't before, calculate pick results
                    if ($gameData['status'] === 'FINISHED' && $oldStatus !== 'FINISHED') {
                        $this->calculatePickResults($game);
                        $newResultsCount++;
                    }
                }

            } catch (\Exception $e) {
                Log::error("Error updating game results", [
                    'game_id' => $gameData['external_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update gameweek completion status
        $this->updateGameweekCompletion();

        $this->info("Results update complete! Updated: {$updatedCount} games, Processed results: {$newResultsCount}");
    }

    /**
     * Calculate pick results for a finished game
     */
    private function calculatePickResults(Game $game): void
    {
        $result = $game->getResult();
        
        // Get all picks for teams in this game for this gameweek
        $homeTeamPicks = Pick::where('game_week_id', $game->game_week_id)
            ->where('team_id', $game->home_team_id)
            ->whereNull('points_earned')
            ->with('user')
            ->get();
            
        $awayTeamPicks = Pick::where('game_week_id', $game->game_week_id)
            ->where('team_id', $game->away_team_id)
            ->whereNull('points_earned')
            ->with('user')
            ->get();

        $picksProcessed = 0;

        // Process home team picks
        foreach ($homeTeamPicks as $pick) {
            $pickResult = match($result) {
                'HOME_WIN' => 'win',
                'AWAY_WIN' => 'loss',
                'DRAW' => 'draw',
                default => null
            };

            if ($pickResult) {
                $pick->setResult($pickResult);
                $picksProcessed++;
                $this->line("  → {$pick->user->name}: {$game->homeTeam->name} - {$pickResult} ({$pick->points_earned} pts)");
            }
        }

        // Process away team picks
        foreach ($awayTeamPicks as $pick) {
            $pickResult = match($result) {
                'AWAY_WIN' => 'win',
                'HOME_WIN' => 'loss', 
                'DRAW' => 'draw',
                default => null
            };

            if ($pickResult) {
                $pick->setResult($pickResult);
                $picksProcessed++;
                $this->line("  → {$pick->user->name}: {$game->awayTeam->name} - {$pickResult} ({$pick->points_earned} pts)");
            }
        }

        if ($picksProcessed > 0) {
            $this->info("  Processed {$picksProcessed} picks for this game");
        }
    }

    /**
     * Update gameweek completion status
     */
    private function updateGameweekCompletion(): void
    {
        $gameweeks = GameWeek::where('is_completed', false)->get();
        
        foreach ($gameweeks as $gameweek) {
            $allGamesFinished = $gameweek->games()
                ->whereIn('status', ['FINISHED', 'CANCELLED', 'POSTPONED'])
                ->count() === $gameweek->games()->count();
                
            if ($allGamesFinished && $gameweek->games()->count() > 0) {
                $gameweek->markAsCompleted();
                $this->info("Marked {$gameweek->name} as completed");
            }
        }
    }
}
