<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'team_id',
        'name',
        'first_name',
        'last_name',
        'date_of_birth',
        'nationality',
        'position',
        'detailed_position',
        'shirt_number',
        'market_value',
        'contract_until',
        'on_loan',
        'loan_from_team_id',
        'photo_url',
        'goals',
        'assists',
        'yellow_cards',
        'red_cards',
        'appearances',
        'minutes_played',
        'last_stats_update',
        'last_profile_update',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'market_value' => 'decimal:2',
        'on_loan' => 'boolean',
        'goals' => 'integer',
        'assists' => 'integer',
        'yellow_cards' => 'integer',
        'red_cards' => 'integer',
        'appearances' => 'integer',
        'minutes_played' => 'integer',
        'last_stats_update' => 'datetime',
        'last_profile_update' => 'datetime',
    ];

    /**
     * Get the team that owns the player
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the team the player is on loan from
     */
    public function loanFromTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'loan_from_team_id');
    }

    /**
     * Get the player's age
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the player's full name
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }
        return $this->name;
    }

    /**
     * Get the player's goals per game ratio
     */
    public function getGoalsPerGameAttribute(): float
    {
        return $this->appearances > 0 ? round($this->goals / $this->appearances, 2) : 0;
    }

    /**
     * Get the player's assists per game ratio
     */
    public function getAssistsPerGameAttribute(): float
    {
        return $this->appearances > 0 ? round($this->assists / $this->appearances, 2) : 0;
    }

    /**
     * Get the player's minutes per game average
     */
    public function getMinutesPerGameAttribute(): float
    {
        return $this->appearances > 0 ? round($this->minutes_played / $this->appearances, 2) : 0;
    }

    /**
     * Check if player profile data needs updating
     */
    public function needsProfileUpdate(): bool
    {
        return !$this->last_profile_update || 
               $this->last_profile_update->diffInDays(now()) > 7;
    }

    /**
     * Check if player stats need updating
     */
    public function needsStatsUpdate(): bool
    {
        return !$this->last_stats_update || 
               $this->last_stats_update->diffInHours(now()) > 24;
    }

    /**
     * Scope to get players by position
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope to get top goalscorers
     */
    public function scopeTopScorers($query, int $limit = 10)
    {
        return $query->where('goals', '>', 0)
                    ->orderBy('goals', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope to get top assists providers
     */
    public function scopeTopAssists($query, int $limit = 10)
    {
        return $query->where('assists', '>', 0)
                    ->orderBy('assists', 'desc')
                    ->limit($limit);
    }
}
