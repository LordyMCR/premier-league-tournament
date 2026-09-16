<?php

namespace App\Providers;

use App\Models\Pick;
use App\Models\TournamentParticipant;
use App\Observers\PickObserver;
use App\Observers\TournamentParticipantObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Register model observers
        Pick::observe(PickObserver::class);
        TournamentParticipant::observe(TournamentParticipantObserver::class);

        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Ensure storage symlink exists at runtime (e.g. fresh container deploys)
        try {
            $storageLink = public_path('storage');
            if (! is_link($storageLink) && ! file_exists($storageLink)) {
                Artisan::call('storage:link');
                Log::info('AppServiceProvider: storage symlink created at runtime.');
            }
        } catch (\Throwable $e) {
            Log::warning('AppServiceProvider: failed to create storage symlink', [
                'error' => $e->getMessage(),
            ]);
        }

        // Share favorite tournament with all views
        Inertia::share([
            'favoriteTournament' => function () {
                if (Auth::check()) {
                    $user = Auth::user();

                    return $user->favoriteTournament();
                }

                return null;
            },
        ]);
    }
}
