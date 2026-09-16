<?php

namespace App\Notifications;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteCreated extends Notification
{
    use Queueable;

    public function __construct(public Invite $invite) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expires = $this->invite->expires_at
            ? $this->invite->expires_at->timezone(config('app.timezone'))->format('j M Y \a\t H:i')
            : null;

        $mail = (new MailMessage)
            ->subject('You’re invited to PL Picks')
            ->greeting('You’re invited')
            ->line('You’ve been invited to join PL Picks, a private Premier League picks game.')
            ->line('Use this unique link to create your account. It only works once, and only with this email address.');

        if ($expires) {
            $mail->line('This invite expires on '.$expires.'.');
        }

        return $mail
            ->action('Create your account', $this->invite->url())
            ->line('If you weren’t expecting this, you can ignore the email.')
            ->salutation('See you in the group, The PL Picks Team');
    }
}
