<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'logo_url',
        'external_id',
        'primary_color',
        'secondary_color',
        'founded',
        'venue',
    ];

    /**
     * Get all picks for this team
     */
    public function picks()
    {
        return $this->hasMany(Pick::class);
    }

    /**
     * Get all players for this team
     */
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get players on loan to this team
     */
    public function loanPlayers()
    {
        return $this->hasMany(Player::class, 'loan_from_team_id');
    }

    /**
     * Get home games for this team
     */
    public function homeGames()
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    /**
     * Get away games for this team
     */
    public function awayGames()
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    /**
     * Get all games for this team (home and away)
     */
    public function games()
    {
        return Game::where('home_team_id', $this->id)
                  ->orWhere('away_team_id', $this->id);
    }

    /**
     * Get picks for this team in a specific tournament
     */
    public function picksInTournament($tournamentId)
    {
        return $this->picks()->where('tournament_id', $tournamentId);
    }

    /**
     * Check if this team has been picked by a user in a tournament
     */
    public function isPickedByUserInTournament($userId, $tournamentId)
    {
        return $this->picks()
            ->where('user_id', $userId)
            ->where('tournament_id', $tournamentId)
            ->exists();
    }
}
