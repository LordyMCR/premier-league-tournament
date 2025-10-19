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
        // Get current gameweek by date
        $currentGameweek = GameWeek::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
        
        if (!$currentGameweek) {
            return response()->json([
                'picks' => [],
                'gameweek' => null
            ]);
        }
        
        // Get all picks for this tournament's participants for the current gameweek
        $picks = Pick::where('game_week_id', $currentGameweek->id)
            ->where('tournament_id', $tournament->id)
            ->with(['user:id,name', 'team:id,name,short_name'])
            ->get()
            ->map(function ($pick) {
                // Find the game for this pick
                $game = \App\Models\Game::where('game_week_id', $pick->game_week_id)
                    ->where(function($q) use ($pick) {
                        $q->where('home_team_id', $pick->team_id)
                          ->orWhere('away_team_id', $pick->team_id);
                    })
                    ->first();
                
                return [
                    'user_id' => $pick->user_id,
                    'user_name' => $pick->user->name,
                    'team_id' => $pick->team_id,
                    'team_name' => $pick->team->short_name,
                    'game_id' => $game ? $game->id : null,
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
