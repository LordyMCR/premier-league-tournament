<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\SquadService;
use Illuminate\Console\Command;

class UpdatePlayerStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'squad:update-stats 
                           {--team= : Specific team ID to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update player statistics from match data';

    /**
     * Execute the console command.
     */
    public function handle(SquadService $squadService): int
    {
        $this->info('Starting player statistics update...');

        // Get teams to process
        if ($teamId = $this->option('team')) {
            $teams = Team::where('id', $teamId)->get();
            if ($teams->isEmpty()) {
                $this->error("Team with ID {$teamId} not found.");
                return 1;
            }
        } else {
            $teams = Team::has('players')->get();
        }

        if ($teams->isEmpty()) {
            $this->error('No teams with players found to process.');
            return 1;
        }

        $this->info("Processing {$teams->count()} team(s)...");
        
        // Player statistics update functionality has been disabled
        // This feature relied on transfer/player data that requires paid API access
        $this->warn("⚠ Player statistics update is currently disabled (requires paid API access)");
        $this->info("Found {$teams->count()} team(s) with players, but skipping stats update.");
        
        $this->newLine();

        $this->info('Player statistics update completed!');
        return 0;
    }
}
