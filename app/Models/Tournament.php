<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'status',
        'current_game_week',
        'start_date',
        'end_date',
        'max_participants',
        'join_code',
        'is_private',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_private' => 'boolean',
    ];

    /**
     * Boot the model to generate join code
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tournament) {
            if (empty($tournament->join_code)) {
                $tournament->join_code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the creator of the tournament
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all participants in this tournament
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'tournament_participants')
                    ->withPivot('joined_at', 'total_points')
                    ->withTimestamps();
    }

    /**
     * Get participant records
     */
    public function participantRecords()
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    /**
     * Get all picks in this tournament
     */
    public function picks()
    {
        return $this->hasMany(Pick::class);
    }

    /**
     * Get picks for a specific game week
     */
    public function picksForGameWeek($gameWeekId)
    {
        return $this->picks()->where('game_week_id', $gameWeekId);
    }

    /**
     * Get leaderboard for this tournament
     */
    public function getLeaderboard()
    {
        return $this->participantRecords()
                    ->with('user')
                    ->orderBy('total_points', 'desc')
                    ->get();
    }

    /**
     * Check if user is participating in this tournament
     */
    public function hasParticipant($userId)
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Add a participant to the tournament
     */
    public function addParticipant($userId)
    {
        if (!$this->hasParticipant($userId) && $this->participants()->count() < $this->max_participants) {
            return $this->participants()->attach($userId, [
                'joined_at' => now(),
                'total_points' => 0
            ]);
        }
        return false;
    }
}
