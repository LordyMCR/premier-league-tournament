<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfileSetting extends Model
{
    protected $fillable = [
        'user_id',
        'profile_visible',
        'show_real_name',
        'show_email',
        'show_location',
        'show_age',
        'show_favorite_team',
        'show_supporter_since',
        'show_social_links',
        'show_tournament_history',
        'show_statistics',
        'show_achievements',
        'show_current_tournaments',
        'show_pick_history',
        'show_team_preferences',
        'show_last_active',
        'show_join_date',
        'allow_profile_views',
        'count_profile_views',
        'show_profile_view_count',
        'notify_profile_views',
        'notify_achievements',
        'notify_tournament_invites',
        'notify_weekly_summary',
        'theme_preference',
        'featured_achievements',
        'profile_badge_color',
        'max_featured_achievements',
        'searchable_by_email',
        'searchable_by_name',
        'allow_tournament_invites',
        'public_leaderboard_participation',
    ];

    protected function casts(): array
    {
        return [
            'profile_visible' => 'boolean',
            'show_real_name' => 'boolean',
            'show_email' => 'boolean',
            'show_location' => 'boolean',
            'show_age' => 'boolean',
            'show_favorite_team' => 'boolean',
            'show_supporter_since' => 'boolean',
            'show_social_links' => 'boolean',
            'show_tournament_history' => 'boolean',
            'show_statistics' => 'boolean',
            'show_achievements' => 'boolean',
            'show_current_tournaments' => 'boolean',
            'show_pick_history' => 'boolean',
            'show_team_preferences' => 'boolean',
            'show_last_active' => 'boolean',
            'show_join_date' => 'boolean',
            'allow_profile_views' => 'boolean',
            'count_profile_views' => 'boolean',
            'show_profile_view_count' => 'boolean',
            'notify_profile_views' => 'boolean',
            'notify_achievements' => 'boolean',
            'notify_tournament_invites' => 'boolean',
            'notify_weekly_summary' => 'boolean',
            'featured_achievements' => 'array',
            'searchable_by_email' => 'boolean',
            'searchable_by_name' => 'boolean',
            'allow_tournament_invites' => 'boolean',
            'public_leaderboard_participation' => 'boolean',
        ];
    }

    /**
     * Get the user that owns these settings
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
