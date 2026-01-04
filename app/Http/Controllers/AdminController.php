<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\User;
use App\Models\TournamentParticipant;
use App\Models\Pick;

class AdminController extends Controller
{
    /**
     * Display the admin panel
     */
    public function index()
    {
        return Inertia::render('Admin/Index', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Get users list (approved, pending, or all)
     */
    public function getUsers(Request $request)
    {
        $type = $request->get('type', 'all'); // 'all', 'approved', 'pending', 'denied'
        $search = $request->get('search', '');

        $query = User::query()->withTrashed(); // Include soft-deleted users for admin view

        if ($type === 'approved') {
            $query->where('is_approved', true);
        } elseif ($type === 'pending') {
            $query->where('is_approved', false)
                  ->whereNull('denied_at'); // Pending users are not approved and not denied
        } elseif ($type === 'denied') {
            $query->whereNotNull('denied_at'); // Denied users
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

            $users = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_approved' => $user->is_approved,
                    'approved_at' => $user->approved_at?->format('Y-m-d H:i:s'),
                    'denied_at' => $user->denied_at?->format('Y-m-d H:i:s'),
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'deleted_at' => $user->deleted_at?->format('Y-m-d H:i:s'),
                    'tournaments_count' => $user->tournaments()->count(),
                    'created_tournaments_count' => $user->createdTournaments()->count(),
                ];
            });

        return response()->json($users);
    }

    /**
     * Approve a user
     */
    public function approveUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'User is already approved.',
            ], 400);
        }

        $user->approve();

        return response()->json([
            'success' => true,
            'message' => "User {$user->email} has been approved successfully.",
        ]);
    }

    /**
     * Disapprove a user (for approved users only)
     */
    public function disapproveUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if (!$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'User is already not approved.',
            ], 400);
        }

        $user->disapprove();

        return response()->json([
            'success' => true,
            'message' => "User {$user->email} approval has been revoked.",
        ]);
    }

    /**
     * Deny a pending user (for users who haven't been approved yet)
     */
    public function denyUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deny an approved user. Use remove instead.',
            ], 400);
        }

        if ($user->denied_at) {
            return response()->json([
                'success' => false,
                'message' => 'User has already been denied.',
            ], 400);
        }

        $user->deny();

        return response()->json([
            'success' => true,
            'message' => "User {$user->email} has been denied. Denial email notification has been sent.",
        ]);
    }

    /**
     * Remove a user (soft delete and remove from tournaments)
     * Only allowed for approved users
     */
    public function removeUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // Only allow removing approved users
        if (!$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Can only remove approved users. Use deny for pending users.',
            ], 400);
        }

        // Don't allow removing yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove your own account.',
            ], 400);
        }

        // Don't allow removing other admins
        if ($user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove admin users.',
            ], 400);
        }

        DB::transaction(function () use ($user) {
            // Get all tournaments the user is in
            $participations = TournamentParticipant::where('user_id', $user->id)->get();

            foreach ($participations as $participation) {
                // Delete all picks for this user in this tournament
                Pick::where('tournament_id', $participation->tournament_id)
                    ->where('user_id', $user->id)
                    ->delete();

                // Remove participant record
                $participation->delete();
            }

            // If user created tournaments, we need to handle that
            // For now, we'll just soft delete the user
            // The tournaments will remain but the creator_id will point to a deleted user
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "User {$user->email} has been removed successfully.",
        ]);
    }

    /**
     * Execute an artisan command
     */
    public function executeCommand(Request $request)
    {
        $validated = $request->validate([
            'command' => 'required|string',
            'arguments' => 'nullable|array',
        ]);

        $command = $validated['command'];
        $arguments = $validated['arguments'] ?? [];

        // Whitelist of allowed commands for security
        $allowedCommands = [
            'picks:reset-and-score',
            'tournament:recalculate-points',
            'football:update',
            'users:recalculate-stats',
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
        ];

        if (!in_array($command, $allowedCommands)) {
            return response()->json([
                'success' => false,
                'message' => 'Command not allowed.',
            ], 403);
        }

        try {
            // Set up environment for non-interactive command execution
            $exitCode = Artisan::call($command, array_merge($arguments, [
                '--no-interaction' => true,
            ]));
            
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Command executed successfully.',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Command returned non-zero exit code: ' . $exitCode,
                    'output' => $output,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Command execution failed: ' . $e->getMessage(),
                'output' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Get command status/history
     */
    public function commandStatus()
    {
        // This could be expanded to show command history from logs
        return response()->json([
            'commands' => [
                [
                    'name' => 'picks:reset-and-score',
                    'description' => 'Reset all pick results and rescore them based on actual game results',
                    'category' => 'Scoring',
                ],
                [
                    'name' => 'tournament:recalculate-points',
                    'description' => 'Recalculate tournament participant points to fix incorrect calculations',
                    'category' => 'Scoring',
                ],
                [
                    'name' => 'football:update',
                    'description' => 'Update Premier League teams, gameweeks, games and results data from external API',
                    'category' => 'Data',
                ],
                [
                    'name' => 'users:recalculate-stats',
                    'description' => 'Recalculate user statistics for all users',
                    'category' => 'Statistics',
                ],
                [
                    'name' => 'cache:clear',
                    'description' => 'Clear application cache',
                    'category' => 'Maintenance',
                ],
                [
                    'name' => 'config:clear',
                    'description' => 'Clear configuration cache',
                    'category' => 'Maintenance',
                ],
                [
                    'name' => 'route:clear',
                    'description' => 'Clear route cache',
                    'category' => 'Maintenance',
                ],
                [
                    'name' => 'view:clear',
                    'description' => 'Clear compiled view files',
                    'category' => 'Maintenance',
                ],
            ],
        ]);
    }
}
