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
            ->with('team')
            ->first();

        // If a pick already exists, allow user to change it during the open window

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
        // When amending, hide the current gameweek's selection from the used list
        if ($existingPick) {
            $usedTeams = $usedTeams->filter(function ($team) use ($existingPick) {
                return $team->id !== $existingPick->team_id;
            })->values();
        }
        
        // Get games for this gameweek
        $gameWeekGames = $gameWeek->games()->with(['homeTeam', 'awayTeam'])->get();

        // Ensure currently selected team is present in the list for editing (all strategies)
        if ($existingPick) {
            $alreadyIncluded = $availableTeams->firstWhere('id', $existingPick->team_id);
            if (!$alreadyIncluded) {
                $team = $existingPick->team ?: $existingPick->team()->first();
                if ($team) {
                    if ($tournament->allowsHomeAwayPicks()) {
                        $team->home_away = $existingPick->home_away;
                    }
                    $team->game_id = null;
                    $availableTeams->push($team);
                }
            }
        }

        return Inertia::render('Tournaments/SelectTeam', [
            'tournament' => $tournament,
            'gameWeek' => $gameWeek,
            'availableTeams' => $availableTeams,
            'usedTeams' => $usedTeams,
            'gameWeekGames' => $gameWeekGames,
            'allowsHomeAwayPicks' => $tournament->allowsHomeAwayPicks(),
            'selectionStrategy' => $tournament->getSelectionStrategy(),
            'existingPick' => $existingPick,
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

        // Check if user already made a pick for this game week (allow update)
        $existingPick = Pick::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->where('game_week_id', $gameWeek->id)
            ->first();

        // Check if user can pick this team considering home/away logic (allow keeping same team when updating)
        if ($tournament->allowsHomeAwayPicks()) {
            $homeAway = $validated['home_away'] ?? null;
            
            if (!$homeAway) {
                return back()->withErrors(['home_away' => 'You must specify if the team is playing home or away.']);
            }
            
            $isSameAsExisting = $existingPick && (int)$existingPick->team_id === (int)$validated['team_id'] && $existingPick->home_away === $homeAway;
            if (!$isSameAsExisting && !Pick::canUserPickTeamHomeAway($user->id, $tournament->id, $validated['team_id'], $homeAway)) {
                $homeAwayText = $homeAway === 'home' ? 'at home' : 'away';
                return back()->withErrors(['team_id' => "You have already picked this team playing {$homeAwayText} in this tournament."]);
            }
        } else {
            $isSameAsExisting = $existingPick && (int)$existingPick->team_id === (int)$validated['team_id'];
            if (!$isSameAsExisting && !Pick::canUserPickTeam($user->id, $tournament->id, $validated['team_id'])) {
                return back()->withErrors(['team_id' => 'You have already picked this team in this tournament.']);
            }
        }

        if ($existingPick) {
            // Update existing pick
            $existingPick->update([
                'team_id' => $validated['team_id'],
                'home_away' => $validated['home_away'] ?? null,
                'picked_at' => now(),
            ]);
        } else {
            // Create the pick
            Pick::create([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'game_week_id' => $gameWeek->id,
                'team_id' => $validated['team_id'],
                'home_away' => $validated['home_away'] ?? null,
                'picked_at' => now(),
            ]);
        }

        $team = Team::find($validated['team_id']);
        $homeAwayText = '';
        $homeAwayValue = $validated['home_away'] ?? null;
        if ($homeAwayValue) {
            $homeAwayText = ' (' . ($homeAwayValue === 'home' ? 'Home' : 'Away') . ')';
        }

        $action = $existingPick ? 'updated' : 'picked';
        return redirect()->route('tournaments.show', $tournament)
            ->with('success', "You have {$action} {$team->name}{$homeAwayText} for {$gameWeek->name}!");
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
