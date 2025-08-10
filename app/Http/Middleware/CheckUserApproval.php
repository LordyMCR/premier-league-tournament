<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserApproval
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check if restrictions are enabled
        if (config('app.restrictions_enabled')) {
            $user = $request->user();
            
            // If user is logged in but not approved, log them out and redirect
            if ($user && !$user->isApproved()) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')->with('status', 'Your account is pending approval. Please contact support@pl-tournament.com for assistance.');
            }
        }

        return $next($request);
    }
}
