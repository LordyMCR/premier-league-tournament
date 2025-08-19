<?php

namespace App\Console\Commands;

use App\Models\Pick;
use App\Models\Game;
use Illuminate\Console\Command;

class ResetAndScorePicks extends Command
{
    protected $signature = 'picks:reset-and-score';
    protected $description = 'Reset all pick results and rescore them based on actual game results';

    public function handle()
    {
        $this->info('Resetting and rescoring all picks...');

        // First, reset all picks
        Pick::query()->update([
            'result' => null,
            'points_earned' => null
        ]);

        $this->info('All pick results have been reset.');

        // Now rescore them based on actual finished games
        $picks = Pick::with(['team', 'gameWeek', 'user', 'tournament'])->get();

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
