<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Models\TournamentParticipant;
use Illuminate\Console\Command;

class RecalculateTournamentPoints extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tournament:recalculate-points {tournament_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate tournament participant points to fix incorrect calculations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tournamentId = $this->argument('tournament_id');
        
        if ($tournamentId) {
            $tournaments = Tournament::where('id', $tournamentId)->get();
            if ($tournaments->isEmpty()) {
                $this->error("Tournament with ID {$tournamentId} not found.");
                return 1;
            }
        } else {
            $tournaments = Tournament::all();
        }

        $this->info("Recalculating points for " . $tournaments->count() . " tournament(s)...");

        foreach ($tournaments as $tournament) {
            $this->info("Processing tournament: {$tournament->name} (ID: {$tournament->id})");
            
            $participants = $tournament->participantRecords;
            
            foreach ($participants as $participant) {
                $oldPoints = $participant->total_points;
                $newPoints = $participant->updateTotalPoints();
                
                if ($oldPoints !== $newPoints) {
                    $this->line("  - {$participant->user->name}: {$oldPoints} → {$newPoints} points");
                } else {
                    $this->line("  - {$participant->user->name}: {$newPoints} points (no change)");
                }
            }
        }

        $this->info("Point recalculation completed!");
        return 0;
    }
}
