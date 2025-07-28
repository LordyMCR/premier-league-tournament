<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tournament;
use App\Models\GameWeek;

class ActivateTournament extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tournament:activate {tournament? : Tournament ID}';

    /**
     * The console command description.
     */
    protected $description = 'Activate a tournament and start it';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tournamentId = $this->argument('tournament');
        
        if ($tournamentId) {
            $tournament = Tournament::find($tournamentId);
        } else {
            // Get the first pending tournament
            $tournament = Tournament::where('status', 'pending')->first();
        }
        
        if (!$tournament) {
            $this->error('No tournament found to activate.');
            return Command::FAILURE;
        }
        
        $this->info("Activating tournament: {$tournament->name}");
        $this->info("Start Gameweek: {$tournament->start_game_week}");
        
        // Check if the start gameweek exists and is available
        $startGameweek = GameWeek::where('week_number', $tournament->start_game_week)->first();
        
        if (!$startGameweek) {
            $this->error("Gameweek {$tournament->start_game_week} not found. Make sure gameweeks are seeded.");
            return Command::FAILURE;
        }
        
        // Update the gameweek dates to make it current (for testing)
        $startGameweek->update([
            'start_date' => now()->subDays(1)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'is_completed' => false,
        ]);
        
        // Activate the tournament
        $tournament->update(['status' => 'active']);
        
        $this->info("✅ Tournament activated successfully!");
        $this->info("✅ Gameweek {$tournament->start_game_week} is now active");
        $this->info("   Start Date: {$startGameweek->start_date}");
        $this->info("   End Date: {$startGameweek->end_date}");
        
        return Command::SUCCESS;
    }
}
