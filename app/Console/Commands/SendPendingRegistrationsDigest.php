<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PendingRegistrationsDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendPendingRegistrationsDigest extends Command
{
    protected $signature = 'users:pending-digest';

    protected $description = 'Email the admin a daily count of pending registrations (instead of one email per signup)';

    public function handle(): int
    {
        $pendingCount = User::query()
            ->where('is_approved', false)
            ->whereNull('denied_at')
            ->count();

        if ($pendingCount === 0) {
            $this->info('No pending registrations.');

            return self::SUCCESS;
        }

        $adminEmail = config('app.admin_email');
        if (blank($adminEmail)) {
            $this->warn('APP_ADMIN_EMAIL is not set; skipping digest.');

            return self::SUCCESS;
        }

        Notification::route('mail', $adminEmail)
            ->notify(new PendingRegistrationsDigest($pendingCount));

        $this->info("Sent digest for {$pendingCount} pending registration(s) to {$adminEmail}.");

        return self::SUCCESS;
    }
}
