<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Pick;
use App\Models\GameWeek;
use Illuminate\Http\Request;

class TournamentLiveController extends Controller
{
    /**
     * Get live picks for tournament participants (current gameweek only)
     * This is a lightweight endpoint - NO external API calls!
     * Only queries the database for picks
     */
    public function getLivePicks(Tournament $tournament)
    {
        // Get current gameweek
        $currentGameweek = GameWeek::where('is_current', true)->first();
        
        if (!$currentGameweek) {
            return response()->json([
                'picks' => [],
                'gameweek' => null
            ]);
        }
        
        // Get all picks for this tournament's participants for the current gameweek
        $picks = Pick::where('gameweek_id', $currentGameweek->id)
            ->whereHas('tournamentParticipant', function ($query) use ($tournament) {
                $query->where('tournament_id', $tournament->id);
            })
            ->with(['user:id,name', 'team:id,name,short_name', 'game:id,home_team_id,away_team_id'])
            ->get()
            ->map(function ($pick) {
                return [
                    'user_id' => $pick->user_id,
                    'user_name' => $pick->user->name,
                    'team_id' => $pick->team_id,
                    'team_name' => $pick->team->name,
                    'game_id' => $pick->game_id,
                ];
            });
        
        return response()->json([
            'picks' => $picks,
            'gameweek' => [
                'id' => $currentGameweek->id,
                'week_number' => $currentGameweek->week_number,
                'name' => $currentGameweek->name
            ]
        ]);
    }
}
