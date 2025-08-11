<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActive
{
    /**
     * Handle an incoming request and update user's last active timestamp.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only update for authenticated users and successful responses
        if (Auth::check() && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $user = Auth::user();
            
            // Only update if it's been more than 5 minutes since last update to avoid excessive DB writes
            $lastActive = $user->last_active_at;
            $shouldUpdate = !$lastActive || $lastActive->diffInMinutes(now()) >= 5;
            
            if ($shouldUpdate) {
                // Use updateQuietly to avoid triggering model events and potential recursion
                $user->updateQuietly(['last_active_at' => now()]);
            }
        }

        return $response;
    }
}
