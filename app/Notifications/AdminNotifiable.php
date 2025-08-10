<?php

namespace App\Notifications;

use Illuminate\Notifications\Notifiable;

class AdminNotifiable
{
    use Notifiable;

    public $email;
    public $name;

    public function __construct()
    {
        $this->email = config('app.admin_email', 'daniel.lord18@gmail.com');
        $this->name = config('app.admin_name', 'Admin');
    }

    /**
     * Get the notification routing information for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
