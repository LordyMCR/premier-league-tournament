<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FootballDataService;
use App\Models\Game;
use App\Models\GameWeek;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class GamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $footballService = new FootballDataService();
        
        if (!$footballService->isConfigured()) {
            $this->command->error('Football Data API key not configured. Please set FOOTBALL_DATA_API_KEY in your .env file.');
            return;
        }

        $this->command->info('Fetching Premier League games from API...');
        
        $gamesData = $footballService->getPremierLeagueGames();
        
        if (empty($gamesData)) {
            $this->command->error('No games data received from API.');
            return;
        }

        $this->command->info('Seeding ' . count($gamesData) . ' games...');

        // Create lookup maps for teams and gameweeks
        $teams = Team::all()->keyBy('external_id');
        $gameWeeks = GameWeek::all()->keyBy('week_number');

        $successCount = 0;
        $errorCount = 0;

        foreach ($gamesData as $gameData) {
            try {
                // Find the teams
                $homeTeam = $teams->get($gameData['home_team_external_id']);
                $awayTeam = $teams->get($gameData['away_team_external_id']);
                $gameWeek = $gameWeeks->get($gameData['matchday']);

                if (!$homeTeam || !$awayTeam || !$gameWeek) {
                    $this->command->warn("Skipping game - missing team or gameweek data for external ID: {$gameData['external_id']}");
                    $errorCount++;
                    continue;
                }

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
            } catch (\Exception $e) {
                $this->command->error("Error seeding game {$gameData['external_id']}: " . $e->getMessage());
                Log::error("Error seeding game", [
                    'game_id' => $gameData['external_id'],
                    'error' => $e->getMessage()
                ]);
                $errorCount++;
            }
        }

        $this->command->info("Games seeded successfully! Created/updated: {$successCount}, Errors: {$errorCount}");
    }
}
