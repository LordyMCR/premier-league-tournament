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
        'start_game_week',
        'total_game_weeks',
        'max_participants',
        'join_code',
        'is_private',
    ];

    protected $casts = [
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

    /**
     * Get the end gameweek number for this tournament
     */
    public function getEndGameWeekAttribute()
    {
        return $this->start_game_week + $this->total_game_weeks - 1;
    }

    /**
     * Get the actual start date from the gameweek
     */
    public function getStartDateAttribute()
    {
        $gameWeek = GameWeek::where('week_number', $this->start_game_week)->first();
        return $gameWeek ? $gameWeek->start_date : null;
    }

    /**
     * Get the actual end date from the final gameweek
     */
    public function getEndDateAttribute()
    {
        $endGameWeek = $this->start_game_week + $this->total_game_weeks - 1;
        $gameWeek = GameWeek::where('week_number', $endGameWeek)->first();
        return $gameWeek ? $gameWeek->end_date : null;
    }

    /**
     * Get all gameweeks for this tournament
     */
    public function gameWeeks()
    {
        $endGameWeek = $this->start_game_week + $this->total_game_weeks - 1;
        return GameWeek::whereBetween('week_number', [$this->start_game_week, $endGameWeek])
                      ->orderBy('week_number');
    }

    /**
     * Get current gameweek model
     */
    public function currentGameWeek()
    {
        return GameWeek::where('week_number', $this->current_game_week)->first();
    }

    /**
     * Check if tournament is currently active (between start and end gameweeks)
     */
    public function isActiveForCurrentGameWeek()
    {
        $currentGameWeekNumber = GameWeek::where('is_completed', false)->min('week_number') ?? 1;
        return $currentGameWeekNumber >= $this->start_game_week && 
               $currentGameWeekNumber <= $this->end_game_week;
    }

    /**
     * Get remaining gameweeks count
     */
    public static function getRemainingGameWeeksCount()
    {
        return GameWeek::where('is_completed', false)->count();
    }

    /**
     * Get next available gameweek number
     */
    public static function getNextGameWeekNumber()
    {
        $currentGameWeek = GameWeek::where('is_completed', false)->orderBy('week_number')->first();
        return $currentGameWeek ? $currentGameWeek->week_number : 1;
    }
}
