<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'total_tournaments',
        'tournaments_won',
        'tournaments_completed',
        'tournaments_active',
        'total_points',
        'average_points_per_tournament',
        'highest_tournament_score',
        'lowest_tournament_score',
        'total_picks',
        'winning_picks',
        'drawing_picks',
        'losing_picks',
        'win_percentage',
        'current_win_streak',
        'longest_win_streak',
        'current_tournament_streak',
        'longest_tournament_streak',
        'team_pick_counts',
        'team_success_rates',
        'most_picked_team_id',
        'most_successful_team_id',
        'monthly_performance',
        'best_month_points',
        'best_month',
        'profile_views_received',
        'profile_views_given',
        'achievements_earned',
        'achievement_points',
        'best_tournament_position',
        'average_tournament_position',
        'global_ranking_position',
        'first_tournament_at',
        'last_tournament_at',
        'last_pick_at',
        'days_active',
    ];

    protected function casts(): array
    {
        return [
            'average_points_per_tournament' => 'decimal:2',
            'win_percentage' => 'decimal:2',
            'team_pick_counts' => 'array',
            'team_success_rates' => 'array',
            'monthly_performance' => 'array',
            'first_tournament_at' => 'datetime',
            'last_tournament_at' => 'datetime',
            'last_pick_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns these statistics
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the most picked team
     */
    public function mostPickedTeam()
    {
        return $this->belongsTo(Team::class, 'most_picked_team_id');
    }

    /**
     * Get the most successful team
     */
    public function mostSuccessfulTeam()
    {
        return $this->belongsTo(Team::class, 'most_successful_team_id');
    }
}
