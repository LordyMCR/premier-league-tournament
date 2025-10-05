<?php

namespace App\Http\Controllers;

use App\Models\LiveMatchEvent;
use App\Models\Pick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveMatchController extends Controller
{
    /**
     * Get live match data for the current user
     * NO external API calls - all from cached database!
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get all live matches from our cached database
        $liveMatches = LiveMatchEvent::live()
            ->with(['game.homeTeam', 'game.awayTeam', 'game.gameWeek'])
            ->orderBy('minute', 'desc') // Show matches furthest along first
            ->get()
            ->map(function($event) {
                return [
                    'game_id' => $event->game_id,
                    'home_team' => $event->game->homeTeam->short_name,
                    'home_team_full' => $event->game->homeTeam->name,
                    'away_team' => $event->game->awayTeam->short_name,
                    'away_team_full' => $event->game->awayTeam->name,
                    'home_crest' => $event->game->homeTeam->logo_url,
                    'away_crest' => $event->game->awayTeam->logo_url,
                    'home_score' => $event->home_score,
                    'away_score' => $event->away_score,
                    'minute' => $event->minute,
                    'status' => $event->status,
                    'gameweek' => $event->game->gameWeek->week_number,
                    'last_updated' => $event->last_updated->diffForHumans(),
                ];
            });

        // Get user's active picks that are currently live
        $userPicksStatus = $this->getUserLivePicksStatus($user);

        // Get stats: how many live matches, how many user is involved in
        $stats = [
            'total_live_matches' => $liveMatches->count(),
            'user_picks_live' => $userPicksStatus->count(),
            'has_live_data' => $liveMatches->count() > 0,
        ];

        return response()->json([
            'live_matches' => $liveMatches,
            'user_picks' => $userPicksStatus,
            'stats' => $stats,
        ]);
    }

    /**
     * Get the user's picks that are currently live
     */
    private function getUserLivePicksStatus($user)
    {
        // Get all tournaments user participates in
        $userTournamentIds = $user->tournaments()->pluck('tournaments.id');

        // Get picks for games that are currently live
        $livePicks = Pick::whereIn('tournament_id', $userTournamentIds)
            ->where('user_id', $user->id)
            ->whereHas('game.liveEvent', function($query) {
                $query->live();
            })
            ->with(['game.liveEvent', 'game.homeTeam', 'game.awayTeam', 'team', 'tournament'])
            ->get();

        return $livePicks->map(function($pick) {
            $liveEvent = $pick->game->liveEvent;
            $result = $this->calculateCurrentResult($pick->team_id, $liveEvent, $pick->game);
            
            return [
                'pick_id' => $pick->id,
                'tournament_name' => $pick->tournament->name,
                'team_picked' => $pick->team->short_name,
                'team_logo' => $pick->team->logo_url,
                'match' => "{$pick->game->homeTeam->short_name} vs {$pick->game->awayTeam->short_name}",
                'score' => "{$liveEvent->home_score} - {$liveEvent->away_score}",
                'minute' => $liveEvent->minute,
                'current_result' => $result['status'], // 'winning', 'drawing', 'losing'
                'projected_points' => $result['points'],
                'status_color' => $result['color'],
            ];
        });
    }

    /**
     * Calculate what the current result means for the user's pick
     */
    private function calculateCurrentResult($pickedTeamId, $liveEvent, $game): array
    {
        $isHome = ($pickedTeamId == $game->home_team_id);
        $myScore = $isHome ? $liveEvent->home_score : $liveEvent->away_score;
        $theirScore = $isHome ? $liveEvent->away_score : $liveEvent->home_score;

        if ($myScore > $theirScore) {
            return [
                'status' => 'winning',
                'points' => 3,
                'color' => 'green',
            ];
        } elseif ($myScore < $theirScore) {
            return [
                'status' => 'losing',
                'points' => 0,
                'color' => 'red',
            ];
        } else {
            return [
                'status' => 'drawing',
                'points' => 1,
                'color' => 'yellow',
            ];
        }
    }

    /**
     * Get detailed live events for a specific match
     */
    public function show($gameId)
    {
        $liveEvent = LiveMatchEvent::with(['game.homeTeam', 'game.awayTeam'])
            ->whereHas('game', function($query) use ($gameId) {
                $query->where('id', $gameId);
            })
            ->first();

        if (!$liveEvent) {
            return response()->json([
                'error' => 'No live data available for this match'
            ], 404);
        }

        return response()->json([
            'match' => [
                'home_team' => $liveEvent->game->homeTeam->name,
                'away_team' => $liveEvent->game->awayTeam->name,
                'home_score' => $liveEvent->home_score,
                'away_score' => $liveEvent->away_score,
                'minute' => $liveEvent->minute,
                'status' => $liveEvent->status,
            ],
            'events' => $liveEvent->events,
            'last_updated' => $liveEvent->last_updated->toIso8601String(),
        ]);
    }
}
