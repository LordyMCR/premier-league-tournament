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
                    'id' => $event->game_id,
                    'game_id' => $event->game_id,
                    'home_team' => [
                        'name' => $event->game->homeTeam->name,
                        'short_name' => $event->game->homeTeam->short_name,
                        'logo_url' => $event->game->homeTeam->logo_url,
                    ],
                    'away_team' => [
                        'name' => $event->game->awayTeam->name,
                        'short_name' => $event->game->awayTeam->short_name,
                        'logo_url' => $event->game->awayTeam->logo_url,
                    ],
                    'home_score' => $event->home_score,
                    'away_score' => $event->away_score,
                    'live_event' => [
                        'home_score' => $event->home_score,
                        'away_score' => $event->away_score,
                        'minute' => $event->minute,
                        'status' => $event->status,
                    ],
                    'minute' => $event->minute,
                    'status' => $event->status,
                    'gameweek' => $event->game->gameWeek->week_number,
                    'last_updated' => $event->last_updated->diffForHumans(),
                ];
            });

        // Get user's active picks that are currently live
        $userPicksStatus = $this->getUserLivePicksStatus($user);

        // Aggregate stats used by the frontend summary widgets
        $winning = 0;
        $drawing = 0;
        $losing = 0;
        $projectedPoints = 0;

        foreach ($userPicksStatus as $pick) {
            if (($pick['current_result'] ?? null) === 'winning') {
                $winning++;
                $projectedPoints += 3;
            } elseif (($pick['current_result'] ?? null) === 'drawing') {
                $drawing++;
                $projectedPoints += 1;
            } elseif (($pick['current_result'] ?? null) === 'losing') {
                $losing++;
            }
        }

        $stats = [
            'total_live_matches' => $liveMatches->count(),
            'user_picks_live' => count($userPicksStatus),
            'has_live_data' => $liveMatches->count() > 0,
            // New fields consumed by the component
            'winning_picks' => $winning,
            'drawing_picks' => $drawing,
            'losing_picks' => $losing,
            'projected_points' => $projectedPoints,
        ];

        return response()->json([
            'live_matches' => $liveMatches,
            'user_picks' => $userPicksStatus,
            'stats' => $stats,
        ]);
    }

    /**
     * Get the user's picks that are currently live (from their favorite tournament)
     */
    private function getUserLivePicksStatus($user)
    {
        // Get user's favorite tournament
        $favoriteTournament = $user->favoriteTournament();
        
        if (!$favoriteTournament) {
            // No favorite tournament, return empty collection
            return collect();
        }

        // Get user's picks from their favorite tournament only
        $picks = Pick::where('tournament_id', $favoriteTournament->id)
            ->where('user_id', $user->id)
            ->with(['gameWeek', 'team', 'tournament'])
            ->get();

        // Find picks where the team has a live match in that gameweek
        $livePicks = $picks->filter(function($pick) {
            // Find games in this gameweek where the picked team is playing
            $game = \App\Models\Game::where('game_week_id', $pick->game_week_id)
                ->where(function($query) use ($pick) {
                    $query->where('home_team_id', $pick->team_id)
                          ->orWhere('away_team_id', $pick->team_id);
                })
                ->whereHas('liveEvent', function($query) {
                    $query->live();
                })
                ->with(['liveEvent', 'homeTeam', 'awayTeam'])
                ->first();
            
            if ($game) {
                // Attach the game to the pick for easy access in mapping
                $pick->matchGame = $game;
                return true;
            }
            return false;
        });

        return $livePicks->map(function($pick) {
            $game = $pick->matchGame;
            $liveEvent = $game->liveEvent;
            $result = $this->calculateCurrentResult($pick->team_id, $liveEvent, $game);
            
            return [
                'pick_id' => $pick->id,
                'tournament_name' => $pick->tournament->name,
                'team_picked' => $pick->team->short_name,
                'team_logo' => $pick->team->logo_url,
                'match' => "{$game->homeTeam->short_name} vs {$game->awayTeam->short_name}",
                'score' => "{$liveEvent->home_score} - {$liveEvent->away_score}",
                'minute' => $liveEvent->minute,
                'current_result' => $result['status'], // 'winning', 'drawing', 'losing'
                'projected_points' => $result['points'],
                'status_color' => $result['color'],
            ];
        })->values()->toArray(); // Convert to array to ensure JSON array format
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
