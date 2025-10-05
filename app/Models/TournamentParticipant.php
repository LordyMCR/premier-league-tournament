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
        'is_favorite',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'is_favorite' => 'boolean',
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
     * Calculate and update total points for this tournament
     */
    public function updateTotalPoints()
    {
        $totalPoints = $this->picks()->whereNotNull('points_earned')->sum('points_earned');
        $this->update(['total_points' => $totalPoints]);
        return $totalPoints;
    }

    /**
     * Set this tournament as favorite for the user (and unset others)
     */
    public function setAsFavorite()
    {
        // Remove favorite status from all other tournaments for this user
        static::where('user_id', $this->user_id)
              ->where('id', '!=', $this->id)
              ->update(['is_favorite' => false]);
        
        // Set this one as favorite
        $this->update(['is_favorite' => true]);
    }

    /**
     * Remove favorite status from this tournament
     */
    public function removeAsFavorite()
    {
        $this->update(['is_favorite' => false]);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite()
    {
        if ($this->is_favorite) {
            $this->removeAsFavorite();
        } else {
            $this->setAsFavorite();
        }
    }
}
