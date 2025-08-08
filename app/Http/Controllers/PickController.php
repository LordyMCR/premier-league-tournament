<?php

namespace App\Http\Controllers;

use App\Models\Pick;
use App\Models\Tournament;
use App\Models\GameWeek;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PickController extends Controller
{
    /**
     * Show team selection page for a game week
     */
    public function create(Tournament $tournament, GameWeek $gameWeek)
    {
        $user = Auth::user();

        // Check if user is participant
        if (!$tournament->hasParticipant($user->id)) {
            abort(403, 'You must be a participant in this tournament to make picks.');
        }

        // Check if selection window is open for this gameweek (and the gameweek is inside tournament bounds)
        if (!$gameWeek->isSelectionWindowOpen() ||
            $gameWeek->week_number < $tournament->start_game_week ||
            $gameWeek->week_number > $tournament->end_game_week) {
            $message = 'Selection window is not currently open for this gameweek.';
            
            if ($gameWeek->selection_deadline && now() > $gameWeek->selection_deadline) {
                $message = 'Selection deadline has passed for this gameweek.';
            } elseif ($gameWeek->selection_opens && now() < $gameWeek->selection_opens) {
                $message = 'Selection window has not opened yet for this gameweek.';
            }
            
            return redirect()->route('tournaments.show', $tournament)
                ->withErrors(['error' => $message]);
        }

        // Check if game week is still open for picks (legacy check)
        if ($gameWeek->hasPassed()) {
            return redirect()->route('tournaments.show', $tournament)
                ->withErrors(['error' => 'This game week has already passed.']);
        }

        // Check if user already made a pick for this game week
        $existingPick = Pick::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->where('game_week_id', $gameWeek->id)
            ->first();

        if ($existingPick) {
            return redirect()->route('tournaments.show', $tournament)
                ->with('info', 'You have already made your pick for this game week.');
        }

        // Get available teams (considering home/away logic if applicable)
        if ($tournament->allowsHomeAwayPicks()) {
            $availableTeams = Pick::getAvailableTeamsForGameWeek($user->id, $tournament->id, $gameWeek->id);
        } else {
            $availableTeams = Pick::getAvailableTeamsForUser($user->id, $tournament->id, $gameWeek->id);
        }
        
        // Get teams already used by this user in this tournament
        $userPicks = Pick::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->with(['team', 'gameWeek'])
            ->get();
            
        $usedTeams = $userPicks->map(function ($pick) {
            $team = $pick->team;
            $team->home_away = $pick->home_away;
            $team->game_week = $pick->gameWeek->name;
            return $team;
        });
        
        // Get games for this gameweek
        $gameWeekGames = $gameWeek->games()->with(['homeTeam', 'awayTeam'])->get();

        return Inertia::render('Tournaments/SelectTeam', [
            'tournament' => $tournament,
            'gameWeek' => $gameWeek,
            'availableTeams' => $availableTeams,
            'usedTeams' => $usedTeams,
            'gameWeekGames' => $gameWeekGames,
            'allowsHomeAwayPicks' => $tournament->allowsHomeAwayPicks(),
            'selectionStrategy' => $tournament->getSelectionStrategy(),
        ]);
    }

    /**
     * Store a team pick
     */
    public function store(Request $request, Tournament $tournament, GameWeek $gameWeek)
    {
        $user = Auth::user();

        // Validate request
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'home_away' => 'nullable|in:home,away',
        ]);

        // Check if user is participant
        if (!$tournament->hasParticipant($user->id)) {
            abort(403, 'You must be a participant in this tournament to make picks.');
        }

        // Check if selection window is open for this gameweek (and the gameweek is inside tournament bounds)
        if (!$gameWeek->isSelectionWindowOpen() ||
            $gameWeek->week_number < $tournament->start_game_week ||
            $gameWeek->week_number > $tournament->end_game_week) {
            $message = 'Selection window is not currently open for this gameweek.';
            
            if ($gameWeek->selection_deadline && now() > $gameWeek->selection_deadline) {
                $message = 'Selection deadline has passed for this gameweek.';
            } elseif ($gameWeek->selection_opens && now() < $gameWeek->selection_opens) {
                $message = 'Selection window has not opened yet for this gameweek.';
            }
            
            return back()->withErrors(['error' => $message]);
        }

        // Check if game week is still open (legacy check)
        if ($gameWeek->hasPassed()) {
            return back()->withErrors(['error' => 'This game week has already passed.']);
        }

        // Check if user already made a pick for this game week
        $existingPick = Pick::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->where('game_week_id', $gameWeek->id)
            ->first();

        if ($existingPick) {
            return back()->withErrors(['error' => 'You have already made your pick for this game week.']);
        }

        // Check if user can pick this team considering home/away logic
        if ($tournament->allowsHomeAwayPicks()) {
            $homeAway = $validated['home_away'] ?? null;
            
            if (!$homeAway) {
                return back()->withErrors(['home_away' => 'You must specify if the team is playing home or away.']);
            }
            
            if (!Pick::canUserPickTeamHomeAway($user->id, $tournament->id, $validated['team_id'], $homeAway)) {
                $homeAwayText = $homeAway === 'home' ? 'at home' : 'away';
                return back()->withErrors(['team_id' => "You have already picked this team playing {$homeAwayText} in this tournament."]);
            }
        } else {
            if (!Pick::canUserPickTeam($user->id, $tournament->id, $validated['team_id'])) {
                return back()->withErrors(['team_id' => 'You have already picked this team in this tournament.']);
            }
        }

        // Create the pick
        Pick::create([
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'game_week_id' => $gameWeek->id,
            'team_id' => $validated['team_id'],
            'home_away' => $validated['home_away'] ?? null,
            'picked_at' => now(),
        ]);

        $team = Team::find($validated['team_id']);
        $homeAwayText = '';
        $homeAwayValue = $validated['home_away'] ?? null;
        if ($homeAwayValue) {
            $homeAwayText = ' (' . ($homeAwayValue === 'home' ? 'Home' : 'Away') . ')';
        }

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', "You have picked {$team->name}{$homeAwayText} for {$gameWeek->name}!");
    }

    /**
     * Show user's picks for a tournament
     */
    public function index(Tournament $tournament)
    {
        $user = Auth::user();

        if (!$tournament->hasParticipant($user->id)) {
            abort(403, 'You must be a participant in this tournament to view picks.');
        }

        $userPicks = Pick::getUserPicksInTournament($user->id, $tournament->id);
        
        if ($tournament->allowsHomeAwayPicks()) {
            $availableTeams = []; // For home/away tournaments, available teams depend on gameweek
        } else {
            $availableTeams = Pick::getAvailableTeamsForUser($user->id, $tournament->id);
        }

        return Inertia::render('Tournaments/MyPicks', [
            'tournament' => $tournament,
            'userPicks' => $userPicks,
            'availableTeams' => $availableTeams,
            'allowsHomeAwayPicks' => $tournament->allowsHomeAwayPicks(),
            'selectionStrategy' => $tournament->getSelectionStrategy(),
        ]);
    }

    /**
     * Update pick result (admin function)
     */
    public function updateResult(Request $request, Pick $pick)
    {
        $validated = $request->validate([
            'result' => 'required|in:win,draw,loss',
        ]);

        $points = $pick->setResult($validated['result']);

        return response()->json([
            'success' => true,
            'points' => $points,
            'message' => "Result updated: {$validated['result']} ({$points} points)"
        ]);
    }
}
