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
