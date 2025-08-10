<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistration extends Notification
{
    use Queueable;

    public $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
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
            ->subject('New User Registration - Approval Required')
            ->greeting('New User Registration Alert')
            ->line('A new user has registered on PL Tournament and requires approval.')
            ->line('**User Details:**')
            ->line('• **Name:** ' . $this->user->name)
            ->line('• **Email:** ' . $this->user->email)
            ->line('• **Registered:** ' . $this->user->created_at->format('F j, Y \a\t g:i A'))
            ->line('• **Approval Token:** ' . $this->user->approval_token)
            ->line('')
            ->line('**Actions Required:**')
            ->line('To approve this user, run the following command:')
            ->line('`php artisan user:approve approve ' . $this->user->email . '`')
            ->line('')
            ->line('To disapprove this user, run:')
            ->line('`php artisan user:approve disapprove ' . $this->user->email . '`')
            ->line('')
            ->action('View User in Database', config('app.url'))
            ->line('This is an automated notification from your PL Tournament application.');
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
