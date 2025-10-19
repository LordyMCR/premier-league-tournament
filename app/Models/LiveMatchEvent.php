<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveMatchEvent extends Model
{
    protected $fillable = [
        'game_id',
        'home_score',
        'away_score',
        'status',
        'minute',
        'events',
        'last_updated',
    ];

    protected $casts = [
        'events' => 'array',
        'last_updated' => 'datetime',
    ];

    /**
     * Get the game this live event belongs to
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Check if the data is stale (older than 15 minutes)
     */
    public function isStale(): bool
    {
        return $this->last_updated->diffInMinutes(now()) > 15;
    }

    /**
     * Scope for live/in-play matches
     */
    public function scopeLive($query)
    {
        return $query->where(function($q) {
            $q->whereIn('status', ['LIVE', 'IN_PLAY', 'PAUSED'])
              ->orWhere(function($subQ) {
                  // Also consider matches as live if they're past kickoff time and not finished
                  $subQ->where('status', 'TIMED')
                       ->whereHas('game', function($gameQuery) {
                           $gameQuery->where('kick_off_time', '<', now())
                                    ->where('status', '!=', 'FINISHED');
                       });
              });
        });
    }

    /**
     * Scope for finished matches
     */
    public function scopeFinished($query)
    {
        return $query->where('status', 'FINISHED');
    }
}
