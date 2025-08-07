<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'bio',
        'location',
        'date_of_birth',
        'favorite_team_id',
        'supporter_since',
        'twitter_handle',
        'instagram_handle',
        'display_name',
        'show_real_name',
        'show_email',
        'show_location',
        'show_age',
        'profile_public',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'show_real_name' => 'boolean',
            'show_email' => 'boolean',
            'show_location' => 'boolean',
            'show_age' => 'boolean',
            'profile_public' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    // Profile Relationships

    /**
     * Get the user's favorite team
     */
    public function favoriteTeam()
    {
        return $this->belongsTo(Team::class, 'favorite_team_id');
    }

    /**
     * Get the user's statistics
     */
    public function statistics()
    {
        return $this->hasOne(UserStatistic::class);
    }

    /**
     * Get the user's profile settings
     */
    public function profileSettings()
    {
        return $this->hasOne(UserProfileSetting::class);
    }

    /**
     * Get the user's achievements
     */
    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withPivot('earned_at', 'progress_data', 'is_featured')
                    ->withTimestamps();
    }

    /**
     * Get featured achievements only
     */
    public function featuredAchievements()
    {
        return $this->achievements()->wherePivot('is_featured', true);
    }

    // Tournament Relationships (existing)

    /**
     * Get tournaments created by this user
     */
    public function createdTournaments()
    {
        return $this->hasMany(Tournament::class, 'creator_id');
    }

    /**
     * Get tournaments this user is participating in
     */
    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants')
                    ->withPivot('joined_at', 'total_points')
                    ->withTimestamps();
    }

    /**
     * Get participant records for this user
     */
    public function tournamentParticipations()
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    /**
     * Get all picks made by this user
     */
    public function picks()
    {
        return $this->hasMany(Pick::class);
    }

    /**
     * Get picks for a specific tournament
     */
    public function picksInTournament($tournamentId)
    {
        return $this->picks()->where('tournament_id', $tournamentId);
    }

    /**
     * Get available teams for this user in a tournament
     */
    public function getAvailableTeams($tournamentId)
    {
        return Pick::getAvailableTeamsForUser($this->id, $tournamentId);
    }

    // Profile Methods



    /**
     * Get the user's avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            // Check if file exists in public storage
            if (Storage::disk('public')->exists('avatars/' . $this->avatar)) {
                return asset('storage/avatars/' . $this->avatar);
            }
        }
        
        // Return default avatar
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=10B981&color=fff&size=150';
    }

    /**
     * Get the user's age
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the user's years supporting their favorite team
     */
    public function getYearsSupportingAttribute()
    {
        return $this->supporter_since ? now()->year - $this->supporter_since : null;
    }

    /**
     * Check if profile is viewable by another user
     */
    public function isProfileViewableBy(?User $viewer = null)
    {
        // Prefer per-user profile settings. Default to visible if not set.
        $profileVisible = $this->profileSettings?->profile_visible;

        if ($profileVisible === null) {
            return true;
        }

        if ($profileVisible === false) {
            return $viewer && $viewer->id === $this->id;
        }

        return true;
    }

    /**
     * Increment profile view count
     */
    public function incrementProfileViews(?User $viewer = null)
    {
        if ($viewer && $viewer->id !== $this->id) {
            $this->increment('profile_views');
            
            // Update viewer's statistics
            if ($viewer->statistics) {
                $viewer->statistics->increment('profile_views_given');
            }
        }
    }

    /**
     * Update last active timestamp
     */
    public function updateLastActive()
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * Get or create user statistics
     */
    public function getOrCreateStatistics()
    {
        return $this->statistics ?: $this->statistics()->create([]);
    }

    /**
     * Get or create profile settings
     */
    public function getOrCreateProfileSettings()
    {
        return $this->profileSettings ?: $this->profileSettings()->create([]);
    }

    /**
     * Check if user has earned a specific achievement
     */
    public function hasAchievement($achievementSlug)
    {
        return $this->achievements()->where('slug', $achievementSlug)->exists();
    }

    /**
     * Award an achievement to the user
     */
    public function awardAchievement($achievement, $progressData = null)
    {
        if (!$this->hasAchievement($achievement->slug)) {
            $this->achievements()->attach($achievement->id, [
                'earned_at' => now(),
                'progress_data' => $progressData ? json_encode($progressData) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Update achievement count in statistics
            $this->getOrCreateStatistics()->increment('achievements_earned');
            $this->getOrCreateStatistics()->increment('achievement_points', $achievement->points);
            
            return true;
        }
        
        return false;
    }
}
