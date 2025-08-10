<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserApproved extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Approved - Welcome to PL Tournament!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! Your account has been approved and you now have full access to PL Tournament.')
            ->line('You can now:')
            ->line('• Create and join tournaments')
            ->line('• Make team picks for each gameweek')
            ->line('• Compete with friends and family')
            ->line('• Track your performance on leaderboards')
            ->action('Log In to PL Tournament', url('/login'))
            ->line('Thank you for joining our community!')
            ->salutation('Best regards, The PL Tournament Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
