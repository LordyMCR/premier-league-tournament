<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tournament;
use App\Models\GameWeek;
use App\Models\Pick;
use App\Models\TournamentParticipant;
use App\Models\Team;
use App\Notifications\TeamAutoAssigned;
use Illuminate\Support\Facades\Log;

class AutoAssignMissingPicks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'picks:auto-assign {gameweek? : Gameweek ID} {--tournament= : Specific tournament ID}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically assign random teams to users who missed the selection deadline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gameweekId = $this->argument('gameweek');
        $tournamentId = $this->option('tournament');
        
        // Get the gameweek to process
        if ($gameweekId) {
            $gameweek = GameWeek::find($gameweekId);
            if (!$gameweek) {
                $this->error("Gameweek with ID {$gameweekId} not found.");
                return Command::FAILURE;
            }
        } else {
            // Get the most recent gameweek where selection deadline has passed
            $gameweek = GameWeek::where('selection_deadline', '<', now())
                               ->where('selection_deadline', '>', now()->subHours(24)) // Only within last 24 hours
                               ->where('is_completed', false)
                               ->orderBy('selection_deadline', 'desc')
                               ->first();
            
            if (!$gameweek) {
                $this->info('No gameweeks with recently passed selection deadlines found.');
                return Command::SUCCESS;
            }
        }

        $this->info("Processing auto-assignments for {$gameweek->name} (Deadline: {$gameweek->selection_deadline})");

        // Get tournaments to process
        $tournaments = Tournament::where('status', 'active');
        
        if ($tournamentId) {
            $tournaments = $tournaments->where('id', $tournamentId);
        }
        
        $tournaments = $tournaments->get();

        if ($tournaments->isEmpty()) {
            $this->info('No active tournaments found.');
            return Command::SUCCESS;
        }

        $totalAssignments = 0;

        foreach ($tournaments as $tournament) {
            $this->info("Processing tournament: {$tournament->name}");
            
            // Check if this gameweek is within the tournament's range
            if ($gameweek->week_number < $tournament->start_game_week || 
                $gameweek->week_number > ($tournament->start_game_week + $tournament->total_game_weeks - 1)) {
                $this->info("  Skipping - gameweek {$gameweek->week_number} is outside tournament range");
                continue;
            }

            $assignedCount = $this->autoAssignForTournament($tournament, $gameweek);
            $totalAssignments += $assignedCount;
            
            $this->info("  Assigned {$assignedCount} random picks");
        }

        $this->info("Auto-assignment complete. Total assignments: {$totalAssignments}");
        
        // Log the results
        if ($totalAssignments > 0) {
            Log::info("Auto-assigned {$totalAssignments} picks for {$gameweek->name}", [
                'gameweek_id' => $gameweek->id,
                'gameweek_name' => $gameweek->name,
                'assignments' => $totalAssignments
            ]);
        }
        
        return Command::SUCCESS;
    }

    /**
     * Auto-assign missing picks for a specific tournament and gameweek
     */
    private function autoAssignForTournament(Tournament $tournament, GameWeek $gameweek)
    {
        // Get all participants in this tournament
        $participants = TournamentParticipant::where('tournament_id', $tournament->id)
                                            ->with('user')
                                            ->get();

        $assignedCount = 0;

        foreach ($participants as $participant) {
            // Check if user already has a pick for this gameweek
            $existingPick = Pick::where('tournament_id', $tournament->id)
                              ->where('user_id', $participant->user_id)
                              ->where('game_week_id', $gameweek->id)
                              ->first();

            if ($existingPick) {
                continue; // User already has a pick
            }

            // Get available teams for this user (teams they haven't picked before in this tournament)
            $availableTeams = Pick::getAvailableTeamsForUser($participant->user_id, $tournament->id);

            if ($availableTeams->isEmpty()) {
                $this->warn("    No available teams for user {$participant->user->name} - they may have picked all teams");
                continue;
            }

            // Randomly select a team
            $randomTeam = $availableTeams->random();

            // Create the pick
            Pick::create([
                'tournament_id' => $tournament->id,
                'user_id' => $participant->user_id,
                'game_week_id' => $gameweek->id,
                'team_id' => $randomTeam->id,
                'picked_at' => now(),
            ]);

            // Send notification to user
            $participant->user->notify(new TeamAutoAssigned($tournament, $gameweek, $randomTeam));

            $this->line("    Auto-assigned {$randomTeam->name} to {$participant->user->name}");
            $assignedCount++;
        }

        return $assignedCount;
    }
}
