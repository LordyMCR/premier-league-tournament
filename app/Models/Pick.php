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
        'home_away',
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
     * Get the game this pick is associated with (if applicable)
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
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
     * Get available teams for a user in a tournament with home/away consideration
     */
    public static function getAvailableTeamsForUser($userId, $tournamentId, $gameWeekId = null)
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            return collect();
        }

        $strategy = $tournament->getSelectionStrategy();
        
        if ($strategy === 'once_only') {
            // Original logic: teams can only be picked once
            $pickedTeamIds = static::where('user_id', $userId)
                                  ->where('tournament_id', $tournamentId)
                                  ->pluck('team_id');

            return Team::whereNotIn('id', $pickedTeamIds)->get();
        } else {
            // Home/away logic: teams can be picked up to twice (home and away)
            if (!$gameWeekId) {
                // If no gameweek specified, return all teams (will be filtered by availability)
                return Team::all();
            }

            return static::getAvailableTeamsForGameWeek($userId, $tournamentId, $gameWeekId);
        }
    }

    /**
     * Get available teams for a specific gameweek considering home/away status
     */
    public static function getAvailableTeamsForGameWeek($userId, $tournamentId, $gameWeekId)
    {
        $tournament = Tournament::find($tournamentId);
        $gameWeek = GameWeek::find($gameWeekId);
        
        if (!$tournament || !$gameWeek) {
            return collect();
        }

        // Get all teams playing in this gameweek with their home/away status
        $teamsPlaying = Game::where('game_week_id', $gameWeekId)
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $availableTeams = collect();

        foreach ($teamsPlaying as $game) {
            // Check home team availability
            $homeTeamAvailable = static::canUserPickTeamHomeAway(
                $userId, $tournamentId, $game->home_team_id, 'home'
            );
            
            if ($homeTeamAvailable) {
                $homeTeam = $game->homeTeam;
                $homeTeam->home_away = 'home';
                $homeTeam->game_id = $game->id;
                $availableTeams->push($homeTeam);
            }

            // Check away team availability
            $awayTeamAvailable = static::canUserPickTeamHomeAway(
                $userId, $tournamentId, $game->away_team_id, 'away'
            );
            
            if ($awayTeamAvailable) {
                $awayTeam = $game->awayTeam;
                $awayTeam->home_away = 'away';
                $awayTeam->game_id = $game->id;
                $availableTeams->push($awayTeam);
            }
        }

        return $availableTeams;
    }

    /**
     * Check if a user can pick a team in a specific home/away context
     */
    public static function canUserPickTeamHomeAway($userId, $tournamentId, $teamId, $homeAway)
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            return false;
        }

        $strategy = $tournament->getSelectionStrategy();

        if ($strategy === 'once_only') {
            // Check if team has been picked at all
            return !static::where('user_id', $userId)
                         ->where('tournament_id', $tournamentId)
                         ->where('team_id', $teamId)
                         ->exists();
        } else {
            // Check if team has been picked in this specific home/away context
            return !static::where('user_id', $userId)
                         ->where('tournament_id', $tournamentId)
                         ->where('team_id', $teamId)
                         ->where('home_away', $homeAway)
                         ->exists();
        }
    }

    /**
     * Check if a user can pick a team in a tournament (legacy method for backward compatibility)
     */
    public static function canUserPickTeam($userId, $tournamentId, $teamId)
    {
        $tournament = Tournament::find($tournamentId);
        if (!$tournament) {
            return false;
        }

        if ($tournament->getSelectionStrategy() === 'once_only') {
            return !static::where('user_id', $userId)
                         ->where('tournament_id', $tournamentId)
                         ->where('team_id', $teamId)
                         ->exists();
        } else {
            // For home/away tournaments, check if both home and away have been used
            $picks = static::where('user_id', $userId)
                          ->where('tournament_id', $tournamentId)
                          ->where('team_id', $teamId)
                          ->pluck('home_away');
            
            return $picks->count() < 2;
        }
    }
}
