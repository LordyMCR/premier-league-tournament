<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserDisapproved extends Notification
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
            ->subject('Account Application Update - PL Tournament')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thank you for your interest in joining PL Tournament.')
            ->line('After reviewing your application, we are unable to approve your account at this time.')
            ->line('This may be due to:')
            ->line('• Limited capacity for new members')
            ->line('• Incomplete registration information')
            ->line('• Other administrative reasons')
            ->line('If you believe this is an error or would like to discuss your application, please feel free to contact us.')
            ->action('Contact Support', 'mailto:' . config('app.admin_email', 'support@pl-tournament.com'))
            ->line('We appreciate your understanding and interest in our platform.')
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
