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
            ->greeting('New User Registration')
            ->line('A new user has registered on PL Tournament and requires approval.')
            ->line('')
            ->line('**User Details:**')
            ->line('• **Name:** ' . $this->user->name)
            ->line('• **Email:** ' . $this->user->email)
            ->line('• **Registered:** ' . $this->user->created_at->format('F j, Y \a\t g:i A'))
            ->line('')
            ->action('Manage Users in Admin Panel', route('admin.index'))
            ->line('You can approve, disapprove, or remove this user from the Admin Panel.')
            ->line('')
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
