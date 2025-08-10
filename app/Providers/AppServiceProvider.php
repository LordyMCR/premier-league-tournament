<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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

        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Ensure storage symlink exists at runtime (Heroku dynos may not persist release step)
        try {
            $storageLink = public_path('storage');
            if (!is_link($storageLink) && !file_exists($storageLink)) {
                Artisan::call('storage:link');
                Log::info('AppServiceProvider: storage symlink created at runtime.');
            }
        } catch (\Throwable $e) {
            Log::warning('AppServiceProvider: failed to create storage symlink', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
