<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingRegistrationsDigest extends Notification
{
    use Queueable;

    public function __construct(public int $pendingCount) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("PL Picks: {$this->pendingCount} pending registration".($this->pendingCount === 1 ? '' : 's'))
            ->greeting('Registration digest')
            ->line('There '.($this->pendingCount === 1 ? 'is' : 'are')." {$this->pendingCount} unapproved account".($this->pendingCount === 1 ? '' : 's').' waiting in the admin panel.')
            ->line('Public signups are no longer emailed one-by-one, to avoid spam floods.')
            ->action('Review pending users', route('admin.index'))
            ->line('Unapproved accounts with no tournament activity are removed automatically after 7 days.');
    }
}
