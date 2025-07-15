<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pick extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'game_week_id',
        'team_id',
        'points_earned',
        'result',
        'picked_at',
    ];

    protected $casts = [
        'picked_at' => 'datetime',
    ];

    /**
     * Get the tournament this pick belongs to
     */
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get the user who made this pick
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the game week this pick is for
     */
    public function gameWeek()
    {
        return $this->belongsTo(GameWeek::class);
    }

    /**
     * Get the team that was picked
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Set the result and calculate points
     */
    public function setResult($result)
    {
        $points = match ($result) {
            'win' => 3,
            'draw' => 1,
            'loss' => 0,
            default => 0
        };

        $this->update([
            'result' => $result,
            'points_earned' => $points
        ]);

        // Update the participant's total points
        $participant = TournamentParticipant::where('tournament_id', $this->tournament_id)
                                           ->where('user_id', $this->user_id)
                                           ->first();
        if ($participant) {
            $participant->updateTotalPoints();
        }

        return $points;
    }

    /**
     * Check if this pick has been scored
     */
    public function isScored()
    {
        return !is_null($this->points_earned);
    }

    /**
     * Get picks for a specific user in a tournament
     */
    public static function getUserPicksInTournament($userId, $tournamentId)
    {
        return static::where('user_id', $userId)
                     ->where('tournament_id', $tournamentId)
                     ->with(['team', 'gameWeek'])
                     ->orderBy('picked_at')
                     ->get();
    }

    /**
     * Get available teams for a user in a tournament (teams not yet picked)
     */
    public static function getAvailableTeamsForUser($userId, $tournamentId)
    {
        $pickedTeamIds = static::where('user_id', $userId)
                              ->where('tournament_id', $tournamentId)
                              ->pluck('team_id');

        return Team::whereNotIn('id', $pickedTeamIds)->get();
    }

    /**
     * Check if a user can pick a team in a tournament
     */
    public static function canUserPickTeam($userId, $tournamentId, $teamId)
    {
        return !static::where('user_id', $userId)
                     ->where('tournament_id', $tournamentId)
                     ->where('team_id', $teamId)
                     ->exists();
    }
}
