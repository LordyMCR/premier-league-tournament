<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameWeek extends Model
{
    use HasFactory;

    protected $fillable = [
        'week_number',
        'name',
        'start_date',
        'end_date',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get all picks for this game week
     */
    public function picks()
    {
        return $this->hasMany(Pick::class);
    }

    /**
     * Get all games for this game week
     */
    public function games()
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Check if this game week is currently active (within date range)
     */
    public function isActive()
    {
        $now = now()->toDateString();
        return $now >= $this->start_date && $now <= $this->end_date && !$this->is_completed;
    }

    /**
     * Check if this game week is upcoming
     */
    public function isUpcoming()
    {
        return now()->toDateString() < $this->start_date;
    }

    /**
     * Check if this game week has passed
     */
    public function hasPassed()
    {
        return now()->toDateString() > $this->end_date || $this->is_completed;
    }

    /**
     * Mark this game week as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now()
        ]);
    }

    /**
     * Get the current active game week
     */
    public static function getCurrentGameWeek()
    {
        return static::where('is_completed', false)
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now())
                     ->first();
    }

    /**
     * Get the next upcoming game week
     */
    public static function getNextGameWeek()
    {
        return static::where('start_date', '>', now())
                     ->orderBy('start_date')
                     ->first();
    }
}
