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

    /**
     * Recalculate and update user statistics based on actual tournament participation
     */
    public function recalculateStats()
    {
        $user = $this->user;
        
        // Get all tournament participants for this user
        $participants = TournamentParticipant::where('user_id', $user->id)->get();
        
        // Tournament statistics
        $totalTournaments = $participants->count();
        $completedTournaments = $participants->whereNotNull('final_position')->count();
        $activeTournaments = $participants->whereNull('final_position')->count();
        $tournamentsWon = $participants->where('final_position', 1)->count();
        
        // Points and picks statistics
        $totalPoints = $participants->sum('total_points');
        $totalPicks = Pick::where('user_id', $user->id)->count();
        
        // Pick success rates - directly from picks table results
        $winningPicks = Pick::where('user_id', $user->id)->where('result', 'win')->count();
        $drawingPicks = Pick::where('user_id', $user->id)->where('result', 'draw')->count();
        $losingPicks = Pick::where('user_id', $user->id)->where('result', 'loss')->count();
        
        $completedPicksCount = $winningPicks + $drawingPicks + $losingPicks;
        $pickSuccessRate = $completedPicksCount > 0 ? ($winningPicks / $completedPicksCount) * 100 : 0;
        
        // Calculate average points per pick (only for picks with results)
        $avgPointsPerPick = Pick::where('user_id', $user->id)
            ->whereNotNull('points_earned')
            ->avg('points_earned') ?? 0;
        
        // Calculate percentages and averages
        $winPercentage = $completedPicksCount > 0 ? ($winningPicks / $completedPicksCount) * 100 : 0;
        $avgPointsPerTournament = $completedTournaments > 0 ? $totalPoints / $completedTournaments : 0;
        
        // Tournament positions
        $positions = $participants->whereNotNull('final_position')->pluck('final_position');
        $bestPosition = $positions->min();
        $avgPosition = $positions->count() > 0 ? $positions->avg() : null;
        
        // Team statistics
        $teamPicks = Pick::where('user_id', $user->id)
            ->selectRaw('team_id, COUNT(*) as pick_count, SUM(CASE WHEN result = ? THEN 1 ELSE 0 END) as wins', ['win'])
            ->groupBy('team_id')
            ->with('team')
            ->get();
        
        $mostPickedTeam = $teamPicks->sortByDesc('pick_count')->first();
        $mostSuccessfulTeam = $teamPicks->filter(fn($pick) => $pick->wins > 0)->sortByDesc('wins')->first();
        
        // Update the statistics
        $this->update([
            'total_tournaments' => $totalTournaments,
            'tournaments_won' => $tournamentsWon,
            'tournaments_completed' => $completedTournaments,
            'tournaments_active' => $activeTournaments,
            'total_points' => $totalPoints,
            'average_points_per_tournament' => round($avgPointsPerTournament, 2),
            'average_points_per_pick' => round($avgPointsPerPick, 2),
            'highest_tournament_score' => $participants->max('total_points') ?? 0,
            'lowest_tournament_score' => $completedTournaments > 0 ? $participants->whereNotNull('final_position')->min('total_points') : null,
            'total_picks' => $totalPicks,
            'winning_picks' => $winningPicks,
            'drawing_picks' => $drawingPicks,
            'losing_picks' => $losingPicks,
            'win_percentage' => round($winPercentage, 2),
            'best_tournament_position' => $bestPosition,
            'average_tournament_position' => $avgPosition ? round($avgPosition, 2) : null,
            'most_picked_team_id' => $mostPickedTeam?->team_id,
            'most_successful_team_id' => $mostSuccessfulTeam?->team_id,
            'first_tournament_at' => $participants->min('created_at'),
            'last_tournament_at' => $participants->max('created_at'),
            'last_pick_at' => Pick::where('user_id', $user->id)->max('created_at'),
        ]);
        
        return $this;
    }

    /**
     * Recalculate statistics for a specific user
     */
    public static function recalculateForUser($userId)
    {
        $stat = static::firstOrCreate(['user_id' => $userId]);
        return $stat->recalculateStats();
    }
}
