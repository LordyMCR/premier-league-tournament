<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'joined_at',
        'total_points',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * Get the tournament this participant belongs to
     */
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get the user for this participant
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all picks for this participant in this tournament
     */
    public function picks()
    {
        return $this->hasMany(Pick::class, 'user_id', 'user_id')
                    ->where('tournament_id', $this->tournament_id);
    }

    /**
     * Calculate and update total points
     */
    public function updateTotalPoints()
    {
        $totalPoints = $this->picks()->whereNotNull('points_earned')->sum('points_earned');
        $this->update(['total_points' => $totalPoints]);
        return $totalPoints;
    }
}
