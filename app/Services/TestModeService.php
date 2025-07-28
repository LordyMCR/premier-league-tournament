<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class TestModeService
{
    /**
     * Check if test mode is enabled
     */
    public static function isEnabled(): bool
    {
        return Config::get('tournament.test_mode', false);
    }

    /**
     * Get test mode settings
     */
    public static function getSettings(): array
    {
        return Config::get('tournament.test_mode_settings', []);
    }

    /**
     * Convert real gameweek timing to test mode timing
     * Takes the current time as the start point and compresses the schedule
     */
    public static function convertGameweekTiming(array $gameweeks): array
    {
        if (!self::isEnabled()) {
            return $gameweeks;
        }

        $settings = self::getSettings();
        $startTime = Carbon::now();
        
        return collect($gameweeks)->map(function ($gameweek, $index) use ($settings, $startTime) {
            // Calculate when this gameweek should start in test mode
            if ($index === 0) {
                // First gameweek: Selection opens now, deadline after selection_window_minutes, gameweek starts 1 min after that
                $selectionOpens = $startTime->copy();
                $selectionDeadline = $startTime->copy()->addMinutes($settings['selection_window_minutes']);
                $gameweekStartTime = $selectionDeadline->copy()->addMinutes(1);
                $gameweekEndTime = $gameweekStartTime->copy()->addMinutes($settings['gameweek_duration_minutes']);
            } else {
                // Subsequent gameweeks: Calculate based on adjusted first gameweek timing
                $firstGameweekEnd = $startTime->copy()
                    ->addMinutes($settings['selection_window_minutes'] + 1) // First selection window + 1 min gap
                    ->addMinutes($settings['gameweek_duration_minutes']); // + first gameweek duration
                
                $gameweekStartTime = $firstGameweekEnd->copy()->addMinutes(
                    ($index - 1) * ($settings['gameweek_duration_minutes'] + $settings['break_between_gameweeks_minutes'])
                );
                $gameweekEndTime = $gameweekStartTime->copy()->addMinutes($settings['gameweek_duration_minutes']);
                $selectionDeadline = $gameweekStartTime->copy()->subMinutes(1);
                $selectionOpens = $index === 1 
                    ? $firstGameweekEnd->copy() // Second gameweek opens when first ends
                    : $firstGameweekEnd->copy()->addMinutes(
                        ($index - 2) * ($settings['gameweek_duration_minutes'] + $settings['break_between_gameweeks_minutes'])
                    )->addMinutes($settings['gameweek_duration_minutes']); // After previous gameweek ends
            }

            return array_merge($gameweek, [
                'gameweek_start_time' => $gameweekStartTime->toDateTimeString(),
                'gameweek_end_time' => $gameweekEndTime->toDateTimeString(),
                'selection_deadline' => $selectionDeadline->toDateTimeString(),
                'selection_opens' => $selectionOpens->toDateTimeString(),
                'start_date' => $gameweekStartTime->toDateString(),
                'end_date' => $gameweekEndTime->toDateString(),
            ]);
        })->toArray();
    }

    /**
     * Convert game kick-off times to test mode timing
     */
    public static function convertGameTiming(array $games, int $gameweekNumber): array
    {
        if (!self::isEnabled()) {
            return $games;
        }

        $settings = self::getSettings();
        $startTime = Carbon::now();
        
        // Calculate when this gameweek starts (accounting for adjusted first gameweek)
        if ($gameweekNumber === 1) {
            // First gameweek starts after selection window + 1 minute gap
            $gameweekStartTime = $startTime->copy()
                ->addMinutes($settings['selection_window_minutes'] + 1);
        } else {
            // Subsequent gameweeks: Calculate based on adjusted first gameweek timing
            $firstGameweekEnd = $startTime->copy()
                ->addMinutes($settings['selection_window_minutes'] + 1) // First selection window + 1 min gap
                ->addMinutes($settings['gameweek_duration_minutes']); // + first gameweek duration
            
            $gameweekStartTime = $firstGameweekEnd->copy()->addMinutes(
                ($gameweekNumber - 2) * ($settings['gameweek_duration_minutes'] + $settings['break_between_gameweeks_minutes'])
            );
        }

        return collect($games)->map(function ($game, $index) use ($settings, $gameweekStartTime) {
            // Spread games throughout the gameweek
            $gameKickOffTime = $gameweekStartTime->copy()->addMinutes(
                $index * $settings['games_spacing_minutes']
            );

            return array_merge($game, [
                'kick_off_time' => $gameKickOffTime->toDateTimeString(),
            ]);
        })->toArray();
    }

    /**
     * Get a human-readable description of the test mode timeline
     */
    public static function getTimelineDescription(): string
    {
        if (!self::isEnabled()) {
            return 'Test mode is disabled - using real Premier League schedule';
        }

        $settings = self::getSettings();
        
        return sprintf(
            'Test mode enabled: Each gameweek lasts %d minutes, selection window %d minutes, games every %d minutes',
            $settings['gameweek_duration_minutes'],
            $settings['selection_window_minutes'], 
            $settings['games_spacing_minutes']
        );
    }

    /**
     * Calculate total tournament duration in test mode
     */
    public static function getTotalTestDuration(): string
    {
        if (!self::isEnabled()) {
            return 'N/A';
        }

        $settings = self::getSettings();
        $totalMinutes = 38 * ($settings['gameweek_duration_minutes'] + $settings['break_between_gameweeks_minutes']);
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d hours %d minutes', $hours, $minutes);
    }
}
