<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_week_id',
        'home_team_id',
        'away_team_id',
        'kick_off_time',
        'home_score',
        'away_score',
        'status',
        'external_id',
    ];

    protected $casts = [
        'kick_off_time' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];

    // Relationships
    public function gameWeek(): BelongsTo
    {
        return $this->belongsTo(GameWeek::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    // Helper methods
    public function isFinished(): bool
    {
        return $this->status === 'FINISHED';
    }

    public function isLive(): bool
    {
        return $this->status === 'LIVE';
    }

    public function hasStarted(): bool
    {
        return in_array($this->status, ['LIVE', 'FINISHED']);
    }

    public function getResult(): ?string
    {
        if (!$this->isFinished()) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return 'HOME_WIN';
        } elseif ($this->away_score > $this->home_score) {
            return 'AWAY_WIN';
        } else {
            return 'DRAW';
        }
    }

    public function getWinnerTeam(): ?Team
    {
        $result = $this->getResult();
        
        return match($result) {
            'HOME_WIN' => $this->homeTeam,
            'AWAY_WIN' => $this->awayTeam,
            default => null,
        };
    }
}
