<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

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
            // Capture output
            Artisan::call($command, $arguments);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Command executed successfully.',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Command execution failed: ' . $e->getMessage(),
                'output' => null,
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
