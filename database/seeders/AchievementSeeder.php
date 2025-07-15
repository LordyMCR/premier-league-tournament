<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            // Tournament Achievements - Common
            [
                'name' => 'First Steps',
                'slug' => 'first-steps',
                'description' => 'Join your first tournament',
                'icon' => 'fas fa-baby',
                'color' => '#10B981',
                'category' => 'tournament',
                'rarity' => 'common',
                'criteria' => ['tournaments_joined' => 1],
                'points' => 10,
                'order' => 1,
            ],
            [
                'name' => 'Tournament Regular',
                'slug' => 'tournament-regular',
                'description' => 'Complete 5 tournaments',
                'icon' => 'fas fa-medal',
                'color' => '#3B82F6',
                'category' => 'tournament',
                'rarity' => 'common',
                'criteria' => ['tournaments_completed' => 5],
                'points' => 50,
                'order' => 2,
            ],
            [
                'name' => 'Dedicated Player',
                'slug' => 'dedicated-player',
                'description' => 'Complete 20 tournaments',
                'icon' => 'fas fa-trophy',
                'color' => '#8B5CF6',
                'category' => 'tournament',
                'rarity' => 'rare',
                'criteria' => ['tournaments_completed' => 20],
                'points' => 200,
                'order' => 3,
            ],
            
            // Tournament Achievements - Rare & Epic
            [
                'name' => 'First Victory',
                'slug' => 'first-victory',
                'description' => 'Win your first tournament',
                'icon' => 'fas fa-crown',
                'color' => '#F59E0B',
                'category' => 'tournament',
                'rarity' => 'rare',
                'criteria' => ['tournaments_won' => 1],
                'points' => 100,
                'order' => 4,
            ],
            [
                'name' => 'Champion',
                'slug' => 'champion',
                'description' => 'Win 5 tournaments',
                'icon' => 'fas fa-trophy',
                'color' => '#F59E0B',
                'category' => 'tournament',
                'rarity' => 'epic',
                'criteria' => ['tournaments_won' => 5],
                'points' => 500,
                'order' => 5,
            ],
            [
                'name' => 'League Legend',
                'slug' => 'league-legend',
                'description' => 'Win 15 tournaments',
                'icon' => 'fas fa-crown',
                'color' => '#FFD700',
                'category' => 'tournament',
                'rarity' => 'legendary',
                'criteria' => ['tournaments_won' => 15],
                'points' => 1500,
                'order' => 6,
            ],
            
            // Picks Achievements
            [
                'name' => 'Lucky Pick',
                'slug' => 'lucky-pick',
                'description' => 'Make your first winning pick',
                'icon' => 'fas fa-four-leaf-clover',
                'color' => '#10B981',
                'category' => 'picks',
                'rarity' => 'common',
                'criteria' => ['winning_picks' => 1],
                'points' => 10,
                'order' => 7,
            ],
            [
                'name' => 'Consistent Picker',
                'slug' => 'consistent-picker',
                'description' => 'Make 50 picks',
                'icon' => 'fas fa-hand-point-up',
                'color' => '#3B82F6',
                'category' => 'picks',
                'rarity' => 'common',
                'criteria' => ['total_picks' => 50],
                'points' => 75,
                'order' => 8,
            ],
            [
                'name' => 'Sharp Eye',
                'slug' => 'sharp-eye',
                'description' => 'Achieve 70% win rate (minimum 20 picks)',
                'icon' => 'fas fa-eye',
                'color' => '#8B5CF6',
                'category' => 'picks',
                'rarity' => 'epic',
                'criteria' => ['win_percentage' => 70, 'total_picks' => 20],
                'points' => 300,
                'order' => 9,
            ],
            [
                'name' => 'Nostradamus',
                'slug' => 'nostradamus',
                'description' => 'Achieve 85% win rate (minimum 50 picks)',
                'icon' => 'fas fa-crystal-ball',
                'color' => '#FFD700',
                'category' => 'picks',
                'rarity' => 'legendary',
                'criteria' => ['win_percentage' => 85, 'total_picks' => 50],
                'points' => 1000,
                'order' => 10,
            ],
            
            // Streak Achievements
            [
                'name' => 'Hot Streak',
                'slug' => 'hot-streak',
                'description' => 'Win 3 picks in a row',
                'icon' => 'fas fa-fire',
                'color' => '#EF4444',
                'category' => 'streak',
                'rarity' => 'common',
                'criteria' => ['win_streak' => 3],
                'points' => 30,
                'order' => 11,
            ],
            [
                'name' => 'On Fire',
                'slug' => 'on-fire',
                'description' => 'Win 7 picks in a row',
                'icon' => 'fas fa-fire-flame-curved',
                'color' => '#F97316',
                'category' => 'streak',
                'rarity' => 'rare',
                'criteria' => ['win_streak' => 7],
                'points' => 150,
                'order' => 12,
            ],
            [
                'name' => 'Unstoppable',
                'slug' => 'unstoppable',
                'description' => 'Win 15 picks in a row',
                'icon' => 'fas fa-rocket',
                'color' => '#DC2626',
                'category' => 'streak',
                'rarity' => 'legendary',
                'criteria' => ['win_streak' => 15],
                'points' => 800,
                'order' => 13,
            ],
            
            // Points Achievements
            [
                'name' => 'Point Scorer',
                'slug' => 'point-scorer',
                'description' => 'Score 100 total points',
                'icon' => 'fas fa-bullseye',
                'color' => '#10B981',
                'category' => 'tournament',
                'rarity' => 'common',
                'criteria' => ['total_points' => 100],
                'points' => 25,
                'order' => 14,
            ],
            [
                'name' => 'High Scorer',
                'slug' => 'high-scorer',
                'description' => 'Score 500 total points',
                'icon' => 'fas fa-chart-line',
                'color' => '#3B82F6',
                'category' => 'tournament',
                'rarity' => 'rare',
                'criteria' => ['total_points' => 500],
                'points' => 100,
                'order' => 15,
            ],
            [
                'name' => 'Point Machine',
                'slug' => 'point-machine',
                'description' => 'Score 2000 total points',
                'icon' => 'fas fa-bolt',
                'color' => '#FFD700',
                'category' => 'tournament',
                'rarity' => 'legendary',
                'criteria' => ['total_points' => 2000],
                'points' => 500,
                'order' => 16,
            ],
            
            // Social Achievements
            [
                'name' => 'Profile Complete',
                'slug' => 'profile-complete',
                'description' => 'Complete your profile with avatar, bio, and favorite team',
                'icon' => 'fas fa-user-check',
                'color' => '#10B981',
                'category' => 'social',
                'rarity' => 'common',
                'criteria' => ['profile_complete' => true],
                'points' => 20,
                'order' => 17,
            ],
            [
                'name' => 'Popular',
                'slug' => 'popular',
                'description' => 'Receive 100 profile views',
                'icon' => 'fas fa-star',
                'color' => '#F59E0B',
                'category' => 'social',
                'rarity' => 'rare',
                'criteria' => ['profile_views' => 100],
                'points' => 50,
                'order' => 18,
            ],
            
            // Special Achievements
            [
                'name' => 'Perfect Tournament',
                'slug' => 'perfect-tournament',
                'description' => 'Win every pick in a tournament (minimum 10 gameweeks)',
                'icon' => 'fas fa-gem',
                'color' => '#A855F7',
                'category' => 'special',
                'rarity' => 'legendary',
                'criteria' => ['perfect_tournament' => true],
                'points' => 1000,
                'order' => 19,
            ],
            [
                'name' => 'Comeback King',
                'slug' => 'comeback-king',
                'description' => 'Win a tournament after being in last place at gameweek 10',
                'icon' => 'fas fa-phoenix-rising',
                'color' => '#DC2626',
                'category' => 'special',
                'rarity' => 'epic',
                'criteria' => ['comeback_victory' => true],
                'points' => 400,
                'order' => 20,
            ],
            [
                'name' => 'All Teams Picker',
                'slug' => 'all-teams-picker',
                'description' => 'Pick every Premier League team at least once',
                'icon' => 'fas fa-palette',
                'color' => '#8B5CF6',
                'category' => 'special',
                'rarity' => 'epic',
                'criteria' => ['all_teams_picked' => true],
                'points' => 300,
                'order' => 21,
            ],
            [
                'name' => 'Early Bird',
                'slug' => 'early-bird',
                'description' => 'One of the first 100 users to join the platform',
                'icon' => 'fas fa-seedling',
                'color' => '#10B981',
                'category' => 'special',
                'rarity' => 'rare',
                'criteria' => ['early_adopter' => true],
                'points' => 100,
                'order' => 22,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['slug' => $achievement['slug']],
                $achievement
            );
        }
    }
}
