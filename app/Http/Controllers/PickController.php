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

        // Check if game week is still open for picks
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

        // Get available teams (not yet picked by this user in this tournament)
        $availableTeams = Pick::getAvailableTeamsForUser($user->id, $tournament->id);
        
        // Get teams already used by this user in this tournament
        $usedTeamIds = Pick::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->pluck('team_id');
        $usedTeams = Team::whereIn('id', $usedTeamIds)->get();
        
        // Get games for this gameweek
        $gameWeekGames = $gameWeek->games()->with(['homeTeam', 'awayTeam'])->get();

        return Inertia::render('Tournaments/SelectTeam', [
            'tournament' => $tournament,
            'gameWeek' => $gameWeek,
            'availableTeams' => $availableTeams,
            'usedTeams' => $usedTeams,
            'gameWeekGames' => $gameWeekGames,
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
        ]);

        // Check if user is participant
        if (!$tournament->hasParticipant($user->id)) {
            abort(403, 'You must be a participant in this tournament to make picks.');
        }

        // Check if game week is still open
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

        // Check if user can pick this team (hasn't picked it before in this tournament)
        if (!Pick::canUserPickTeam($user->id, $tournament->id, $validated['team_id'])) {
            return back()->withErrors(['team_id' => 'You have already picked this team in this tournament.']);
        }

        // Create the pick
        Pick::create([
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'game_week_id' => $gameWeek->id,
            'team_id' => $validated['team_id'],
            'picked_at' => now(),
        ]);

        $team = Team::find($validated['team_id']);

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', "You have picked {$team->name} for {$gameWeek->name}!");
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
        $availableTeams = Pick::getAvailableTeamsForUser($user->id, $tournament->id);

        return Inertia::render('Tournaments/MyPicks', [
            'tournament' => $tournament,
            'userPicks' => $userPicks,
            'availableTeams' => $availableTeams,
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
