<?php

namespace App\Http\Controllers;

use App\Models\GameWeek;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Display the Premier League schedule
     */
    public function index(Request $request)
    {
        $currentDate = now();
        
        // Get all gameweeks with their games
        $gameweeks = GameWeek::with([
            'games' => function ($query) {
                $query->with(['homeTeam', 'awayTeam'])
                      ->orderBy('kick_off_time');
            }
        ])
        ->orderBy('week_number')
        ->get();

        // Get current and upcoming gameweeks
        $currentGameweek = GameWeek::getCurrentGameWeek();
        $nextGameweek = GameWeek::getNextGameWeek();
        
        // Get teams for filtering/reference
        $teams = Team::orderBy('name')->get();
        
        // Calculate some statistics
        $totalGames = Game::count();
        $completedGames = Game::where('status', 'FINISHED')->count();
        $upcomingGames = Game::where('status', 'SCHEDULED')->count();
        
        // Get recent and upcoming games for highlights
        $recentGames = Game::with(['homeTeam', 'awayTeam'])
            ->where('status', 'FINISHED')
            ->orderBy('kick_off_time', 'desc')
            ->limit(6)
            ->get();
            
        $upcomingHighlights = Game::with(['homeTeam', 'awayTeam'])
            ->where('status', 'SCHEDULED')
            ->where('kick_off_time', '>', $currentDate)
            ->orderBy('kick_off_time')
            ->limit(6)
            ->get();

        return Inertia::render('Schedule/Index', [
            'gameweeks' => $gameweeks,
            'currentGameweek' => $currentGameweek,
            'nextGameweek' => $nextGameweek,
            'teams' => $teams,
            'stats' => [
                'total_games' => $totalGames,
                'completed_games' => $completedGames,
                'upcoming_games' => $upcomingGames,
                'completion_percentage' => $totalGames > 0 ? round(($completedGames / $totalGames) * 100, 1) : 0
            ],
            'recentGames' => $recentGames,
            'upcomingHighlights' => $upcomingHighlights,
        ]);
    }

    /**
     * Display a specific gameweek
     */
    public function gameweek(GameWeek $gameweek)
    {
        $gameweek->load([
            'games' => function ($query) {
                $query->with(['homeTeam', 'awayTeam'])
                      ->orderBy('kick_off_time');
            }
        ]);

        // Get previous and next gameweeks for navigation
        $previousGameweek = GameWeek::where('week_number', '<', $gameweek->week_number)
            ->orderBy('week_number', 'desc')
            ->first();
            
        $nextGameweek = GameWeek::where('week_number', '>', $gameweek->week_number)
            ->orderBy('week_number')
            ->first();

        return Inertia::render('Schedule/Gameweek', [
            'gameweek' => $gameweek,
            'previousGameweek' => $previousGameweek,
            'nextGameweek' => $nextGameweek,
        ]);
    }

    /**
     * Display fixtures for a specific team
     */
    public function team(Team $team)
    {
        $games = Game::with(['homeTeam', 'awayTeam', 'gameWeek'])
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->orderBy('kick_off_time')
            ->get();

        // Separate into completed and upcoming
        $completedGames = $games->where('status', 'FINISHED');
        $upcomingGames = $games->where('status', 'SCHEDULED');

        // Calculate team statistics
        $stats = [
            'games_played' => $completedGames->count(),
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
        ];

        foreach ($completedGames as $game) {
            if ($game->home_team_id == $team->id) {
                $stats['goals_for'] += $game->home_score ?? 0;
                $stats['goals_against'] += $game->away_score ?? 0;
                
                if (($game->home_score ?? 0) > ($game->away_score ?? 0)) {
                    $stats['wins']++;
                } elseif (($game->home_score ?? 0) < ($game->away_score ?? 0)) {
                    $stats['losses']++;
                } else {
                    $stats['draws']++;
                }
            } else {
                $stats['goals_for'] += $game->away_score ?? 0;
                $stats['goals_against'] += $game->home_score ?? 0;
                
                if (($game->away_score ?? 0) > ($game->home_score ?? 0)) {
                    $stats['wins']++;
                } elseif (($game->away_score ?? 0) < ($game->home_score ?? 0)) {
                    $stats['losses']++;
                } else {
                    $stats['draws']++;
                }
            }
        }

        $stats['points'] = ($stats['wins'] * 3) + $stats['draws'];
        $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];

        return Inertia::render('Schedule/Team', [
            'team' => $team,
            'completedGames' => $completedGames,
            'upcomingGames' => $upcomingGames,
            'stats' => $stats,
        ]);
    }
}
