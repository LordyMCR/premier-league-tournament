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
            'tournaments' => $myTournaments,
            'createdTournaments' => $createdTournaments,
        ]);
    }

    /**
     * Show tournament creation form
     */
    public function create()
    {
        $currentGameWeek = GameWeek::getCurrentGameWeek();
        $nextGameWeekNumber = $currentGameWeek ? $currentGameWeek->week_number : 1;
        $remainingGameWeeks = Tournament::getRemainingGameWeeksCount();
        
        // Get all available gameweeks for custom mode (only future/incomplete ones)
        $availableGameWeeks = GameWeek::where('week_number', '>=', $nextGameWeekNumber)
            ->where('is_completed', false)
            ->orderBy('week_number')
            ->get(['week_number', 'name', 'start_date', 'end_date']);
        
        // Calculate dynamic ranges
        $fullSeasonEnd = min(38, $nextGameWeekNumber + $remainingGameWeeks - 1);
        $halfSeasonEnd = min($nextGameWeekNumber + 18, 38);
        
        return Inertia::render('Tournaments/Create', [
            'currentGameWeek' => $currentGameWeek,
            'nextGameWeekNumber' => $nextGameWeekNumber,
            'remainingGameWeeks' => $remainingGameWeeks,
            'availableGameWeeks' => $availableGameWeeks,
            'fullSeasonEnd' => $fullSeasonEnd,
            'halfSeasonEnd' => $halfSeasonEnd,
        ]);
    }

    /**
     * Store a new tournament
     */
    public function store(Request $request)
    {
        $currentGameWeek = GameWeek::getCurrentGameWeek();
        $nextGameWeekNumber = $currentGameWeek ? $currentGameWeek->week_number : 1;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'max_participants' => 'required|integer|min:2|max:100',
            'is_private' => 'boolean',
            'tournament_mode' => 'required|in:full_season,half_season,custom',
            'start_game_week' => 'required|integer|min:1',
            'end_game_week' => 'required|integer|min:1',
        ]);

        // Calculate dynamic ranges
        $fullSeasonEnd = min(38, $nextGameWeekNumber + 37); // From current to end of season
        $halfSeasonEnd = min($nextGameWeekNumber + 18, 38); // From current + 18 weeks

        // Validate game week ranges based on mode
        if ($validated['tournament_mode'] === 'full_season') {
            if ($validated['start_game_week'] !== $nextGameWeekNumber || $validated['end_game_week'] !== $fullSeasonEnd) {
                return back()->withErrors(['tournament_mode' => "Full season must be from gameweek {$nextGameWeekNumber} to {$fullSeasonEnd}."]);
            }
        } elseif ($validated['tournament_mode'] === 'half_season') {
            if ($validated['start_game_week'] !== $nextGameWeekNumber || $validated['end_game_week'] !== $halfSeasonEnd) {
                return back()->withErrors(['tournament_mode' => "Half season must be from gameweek {$nextGameWeekNumber} to {$halfSeasonEnd}."]);
            }
        } else { // custom mode
            if ($validated['start_game_week'] >= $validated['end_game_week']) {
                return back()->withErrors(['end_game_week' => 'End game week must be after start game week.']);
            }
            
            $totalWeeks = $validated['end_game_week'] - $validated['start_game_week'] + 1;
            
            // Custom tournaments can now exceed 20 gameweeks (home/away picks will be used)
            if ($totalWeeks > 38) {
                return back()->withErrors(['end_game_week' => 'Custom tournaments cannot exceed the entire season (38 gameweeks).']);
            }
            
            // Ensure custom tournaments start from current or future gameweeks
            if ($validated['start_game_week'] < $nextGameWeekNumber) {
                return back()->withErrors(['start_game_week' => "Tournaments cannot start before gameweek {$nextGameWeekNumber}."]);
            }
        }

        $totalGameWeeks = $validated['end_game_week'] - $validated['start_game_week'] + 1;

        $tournament = Tournament::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'creator_id' => Auth::id(),
            // Tournaments become playable immediately after creation
            'status' => 'active',
            'start_game_week' => $validated['start_game_week'],
            'total_game_weeks' => $totalGameWeeks,
            'current_game_week' => $validated['start_game_week'],
            'max_participants' => $validated['max_participants'],
            'is_private' => (bool)($validated['is_private'] ?? false),
            'tournament_mode' => $validated['tournament_mode'],
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
        // Include participant count for frontend display
        $tournament->loadCount('participants');

        $isParticipant = $tournament->hasParticipant($user->id);
        $leaderboard = $tournament->getLeaderboard();
        
        // Compute tournament gameweek bounds
        $tournamentStart = $tournament->start_game_week;
        $tournamentEnd = $tournament->end_game_week; // accessor on model

        // Get current gameweek within this tournament's range
        $currentGameweek = GameWeek::whereBetween('week_number', [$tournamentStart, $tournamentEnd])
            ->where('is_completed', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('week_number')
            ->first();

        // Get the selection gameweek that is currently open within the tournament range
        $selectionGameweek = GameWeek::whereBetween('week_number', [$tournamentStart, $tournamentEnd])
            ->where('is_completed', false)
            ->whereNotNull('selection_opens')
            ->whereNotNull('selection_deadline')
            ->where('selection_opens', '<=', now())
            ->where('selection_deadline', '>=', now())
            ->orderBy('week_number')
            ->first();

        // Fallback to the next selection gameweek within range when none are open
        $nextSelectionGameweek = GameWeek::whereBetween('week_number', [$tournamentStart, $tournamentEnd])
            ->where('is_completed', false)
            ->where('selection_opens', '>', now())
            ->orderBy('selection_opens')
            ->first();

        // If no date-range current gameweek matched, prefer the one with an open selection window
        if (!$currentGameweek && $selectionGameweek) {
            $currentGameweek = $selectionGameweek;
        }
        // Otherwise, if still null, choose the earliest not-completed upcoming gameweek in range
        if (!$currentGameweek) {
            $currentGameweek = GameWeek::whereBetween('week_number', [$tournamentStart, $tournamentEnd])
                ->where('is_completed', false)
                ->orderBy('week_number')
                ->first();
        }
        
        // Get user's picks if participant
        $userPicks = null;
        $currentPick = null;
        $availableTeams = [];
        $allParticipantPicks = [];
        
        if ($isParticipant) {
            $userPicksRaw = Pick::getUserPicksInTournament($user->id, $tournament->id);
            // Normalize shape for frontend
            $userPicks = $userPicksRaw->map(function ($pick) {
                return [
                    'id' => $pick->id,
                    'team' => [
                        'id' => $pick->team->id,
                        'name' => $pick->team->name,
                        'short_name' => $pick->team->short_name,
                        'primary_color' => $pick->team->primary_color,
                        'logo_url' => $pick->team->logo_url,
                    ],
                    'gameweek' => [
                        'id' => $pick->gameWeek->id ?? null,
                        'week_number' => $pick->gameWeek->week_number ?? null,
                        'name' => $pick->gameWeek->name ?? null,
                    ],
                    'result' => $pick->result,
                    'points' => $pick->points_earned,
                    'home_away' => $pick->home_away,
                    'picked_at' => $pick->picked_at,
                ];
            });
            
            if ($selectionGameweek) {
                $currentPickModel = Pick::where('tournament_id', $tournament->id)
                    ->where('user_id', $user->id)
                    ->where('game_week_id', $selectionGameweek->id)
                    ->with(['team','gameWeek'])
                    ->first();

                // Normalize current pick for frontend (consistent keys)
                if ($currentPickModel) {
                    $currentPick = [
                        'id' => $currentPickModel->id,
                        'team' => [
                            'id' => $currentPickModel->team->id,
                            'name' => $currentPickModel->team->name,
                            'short_name' => $currentPickModel->team->short_name,
                            'primary_color' => $currentPickModel->team->primary_color,
                            'logo_url' => $currentPickModel->team->logo_url,
                        ],
                        'gameweek' => [
                            'id' => $selectionGameweek->id,
                            'week_number' => $selectionGameweek->week_number,
                            'name' => $selectionGameweek->name,
                        ],
                        'home_away' => $currentPickModel->home_away,
                    ];
                }

                // Get available teams for the current selection gameweek
                if (!$currentPickModel) {
                    if ($tournament->allowsHomeAwayPicks()) {
                        $availableTeams = Pick::getAvailableTeamsForGameWeek($user->id, $tournament->id, $selectionGameweek->id);
                    } else {
                        $availableTeams = Pick::getAvailableTeamsForUser($user->id, $tournament->id, $selectionGameweek->id);
                    }
                }
            }
        }
        
        // Get all participants' picks for display (grouped by gameweek)
        $allParticipantPicks = Pick::where('tournament_id', $tournament->id)
            ->with(['user', 'team', 'gameWeek'])
            ->orderBy('game_week_id')
            ->orderBy('picked_at')
            ->get()
            ->groupBy('game_week_id')
            ->map(function ($picks) {
                return $picks->map(function ($pick) {
                    return [
                        'id' => $pick->id,
                        'user' => [
                            'id' => $pick->user->id,
                            'name' => $pick->user->name,
                            'avatar_url' => $pick->user->avatar_url,
                        ],
                        'team' => [
                            'id' => $pick->team->id,
                            'name' => $pick->team->name,
                            'short_name' => $pick->team->short_name,
                            'primary_color' => $pick->team->primary_color,
                            'logo_url' => $pick->team->logo_url,
                        ],
                        'gameweek' => [
                            'id' => $pick->gameWeek->id,
                            'week_number' => $pick->gameWeek->week_number,
                            'name' => $pick->gameWeek->name,
                        ],
                        'result' => $pick->result,
                        'points' => $pick->points_earned,
                        'home_away' => $pick->home_away,
                        'picked_at' => $pick->picked_at,
                    ];
                });
            });

        return Inertia::render('Tournaments/Show', [
            'tournament' => $tournament,
            'isParticipant' => $isParticipant,
            'leaderboard' => $leaderboard,
            'currentGameweek' => $currentGameweek,
            'selectionGameweek' => $selectionGameweek,
            'nextSelectionGameweek' => $nextSelectionGameweek,
            'userPicks' => $userPicks,
            'currentPick' => $currentPick,
            'availableTeams' => $availableTeams,
            'allTeams' => Team::all(),
            'allParticipantPicks' => $allParticipantPicks,
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
