<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\GameWeek;
use App\Models\Pick;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    /**
     * Display tournament dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        $myTournaments = $user->tournaments()
            ->with(['creator', 'participantRecords'])
            ->withCount('participants')
            ->get();
            
        $createdTournaments = $user->createdTournaments()
            ->with('participantRecords')
            ->withCount('participants')
            ->get();

        return Inertia::render('Tournaments/Dashboard', [
            'myTournaments' => $myTournaments,
            'createdTournaments' => $createdTournaments,
        ]);
    }

    /**
     * Show tournament creation form
     */
    public function create()
    {
        $remainingGameWeeks = Tournament::getRemainingGameWeeksCount();
        $nextGameWeekNumber = Tournament::getNextGameWeekNumber();
        
        return Inertia::render('Tournaments/Create', [
            'remainingGameWeeks' => $remainingGameWeeks,
            'nextGameWeekNumber' => $nextGameWeekNumber,
            'maxGameWeeks' => min(20, $remainingGameWeeks), // Max 20 (one per team) or remaining gameweeks
        ]);
    }

    /**
     * Store a new tournament
     */
    public function store(Request $request)
    {
        $remainingGameWeeks = Tournament::getRemainingGameWeeksCount();
        $maxGameWeeks = min(20, $remainingGameWeeks);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_game_week' => 'required|integer|min:' . Tournament::getNextGameWeekNumber(),
            'total_game_weeks' => 'required|integer|min:1|max:' . $maxGameWeeks,
            'max_participants' => 'required|integer|min:2|max:100',
            'is_private' => 'boolean',
        ]);

        // Validate that the tournament doesn't exceed available gameweeks
        $endGameWeek = $validated['start_game_week'] + $validated['total_game_weeks'] - 1;
        $maxAvailableGameWeek = GameWeek::max('week_number') ?? 38;
        
        if ($endGameWeek > $maxAvailableGameWeek) {
            return back()->withErrors([
                'total_game_weeks' => 'Tournament would extend beyond available gameweeks.'
            ]);
        }

        $tournament = Tournament::create([
            ...$validated,
            'creator_id' => Auth::id(),
            'status' => 'pending',
            'current_game_week' => $validated['start_game_week'],
        ]);

        // Automatically add creator as participant
        $tournament->addParticipant(Auth::id());

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', 'Tournament created successfully! Share the join code: ' . $tournament->join_code);
    }

    /**
     * Show specific tournament
     */
    public function show(Tournament $tournament)
    {
        $user = Auth::user();
        
        // Load tournament with relationships
        $tournament->load([
            'creator',
            'participants',
            'participantRecords.user'
        ]);

        $isParticipant = $tournament->hasParticipant($user->id);
        $leaderboard = $tournament->getLeaderboard();
        
        // Get current game week and selection gameweek
        $currentGameweek = GameWeek::getCurrentGameWeek();
        $selectionGameweek = GameWeek::getCurrentSelectionGameWeek();
        $nextSelectionGameweek = GameWeek::getNextSelectionGameWeek();
        
        // Get user's picks if participant
        $userPicks = null;
        $currentPick = null;
        
        if ($isParticipant) {
            $userPicks = Pick::getUserPicksInTournament($user->id, $tournament->id);
            
            if ($selectionGameweek) {
                $currentPick = Pick::where('tournament_id', $tournament->id)
                    ->where('user_id', $user->id)
                    ->where('game_week_id', $selectionGameweek->id)
                    ->with('team')
                    ->first();
            }
        }

        return Inertia::render('Tournaments/Show', [
            'tournament' => $tournament,
            'isParticipant' => $isParticipant,
            'leaderboard' => $leaderboard,
            'currentGameweek' => $currentGameweek,
            'selectionGameweek' => $selectionGameweek,
            'nextSelectionGameweek' => $nextSelectionGameweek,
            'userPicks' => $userPicks,
            'currentPick' => $currentPick,
            'allTeams' => Team::all(),
        ]);
    }

    /**
     * Show join tournament form
     */
    public function joinForm()
    {
        return Inertia::render('Tournaments/Join');
    }

    /**
     * Join tournament with code
     */
    public function join(Request $request)
    {
        $validated = $request->validate([
            'join_code' => 'required|string|size:8',
        ]);

        $tournament = Tournament::where('join_code', strtoupper($validated['join_code']))->first();

        if (!$tournament) {
            return back()->withErrors(['join_code' => 'Invalid join code.']);
        }

        if ($tournament->status === 'completed') {
            return back()->withErrors(['join_code' => 'This tournament has already ended.']);
        }

        $user = Auth::user();

        if ($tournament->hasParticipant($user->id)) {
            return redirect()->route('tournaments.show', $tournament)
                ->with('info', 'You are already participating in this tournament.');
        }

        if ($tournament->participants()->count() >= $tournament->max_participants) {
            return back()->withErrors(['join_code' => 'This tournament is full.']);
        }

        $tournament->addParticipant($user->id);

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', 'Successfully joined tournament: ' . $tournament->name);
    }

    /**
     * Show leaderboard
     */
    public function leaderboard(Tournament $tournament)
    {
        $leaderboard = $tournament->getLeaderboard();
        $tournament->load('creator');

        return Inertia::render('Tournaments/Leaderboard', [
            'tournament' => $tournament,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Delete tournament (only creator can delete)
     */
    public function destroy(Tournament $tournament)
    {
        if ($tournament->creator_id !== Auth::id()) {
            abort(403, 'Only the tournament creator can delete this tournament.');
        }

        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }
}
