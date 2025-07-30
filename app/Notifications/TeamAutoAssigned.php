<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Tournament;
use App\Models\GameWeek;
use App\Models\Team;

class TeamAutoAssigned extends Notification
{
    use Queueable;

    protected $tournament;
    protected $gameweek;
    protected $team;

    /**
     * Create a new notification instance.
     */
    public function __construct(Tournament $tournament, GameWeek $gameweek, Team $team)
    {
        $this->tournament = $tournament;
        $this->gameweek = $gameweek;
        $this->team = $team;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database']; // Store in database for in-app notifications
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'team_auto_assigned',
            'tournament_id' => $this->tournament->id,
            'tournament_name' => $this->tournament->name,
            'gameweek_id' => $this->gameweek->id,
            'gameweek_name' => $this->gameweek->name,
            'team_id' => $this->team->id,
            'team_name' => $this->team->name,
            'team_short_name' => $this->team->short_name,
            'message' => "A team was automatically assigned for {$this->gameweek->name} in {$this->tournament->name}",
            'action_url' => route('tournaments.show', $this->tournament->id)
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the mail representation of the notification (optional).
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject("Team Auto-Assigned: {$this->tournament->name}")
                    ->line("You missed the selection deadline for {$this->gameweek->name}.")
                    ->line("We've automatically assigned {$this->team->name} as your pick.")
                    ->action('View Tournament', route('tournaments.show', $this->tournament->id))
                    ->line('Make sure to submit your picks before the deadline next time!');
    }
}
