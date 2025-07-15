<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get tournaments created by this user
     */
    public function createdTournaments()
    {
        return $this->hasMany(Tournament::class, 'creator_id');
    }

    /**
     * Get tournaments this user is participating in
     */
    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants')
                    ->withPivot('joined_at', 'total_points')
                    ->withTimestamps();
    }

    /**
     * Get participant records for this user
     */
    public function tournamentParticipations()
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    /**
     * Get all picks made by this user
     */
    public function picks()
    {
        return $this->hasMany(Pick::class);
    }

    /**
     * Get picks for a specific tournament
     */
    public function picksInTournament($tournamentId)
    {
        return $this->picks()->where('tournament_id', $tournamentId);
    }

    /**
     * Get available teams for this user in a tournament
     */
    public function getAvailableTeams($tournamentId)
    {
        return Pick::getAvailableTeamsForUser($this->id, $tournamentId);
    }
}
