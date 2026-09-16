<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile
{
    public function siteKey(): ?string
    {
        $key = config('services.turnstile.site_key');

        return filled($key) ? $key : null;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret'));
    }

    public function mustVerify(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        if ($this->isConfigured()) {
            return true;
        }

        return app()->environment('production');
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->mustVerify()) {
            return true;
        }

        if (! $this->isConfigured() || blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            return (bool) $response->json('success');
        } catch (\Throwable $e) {
            Log::warning('Turnstile verification failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
