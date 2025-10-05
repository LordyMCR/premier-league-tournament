<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ImportHistoricalData;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Historical data import command
Artisan::command('import:historical-data', function () {
    $command = new ImportHistoricalData();
    $command->handle();
})->purpose('Import 10 seasons of Premier League historical data');

// Clear historical data cache
Artisan::command('clear:historical-cache', function () {
    \Illuminate\Support\Facades\Cache::forget('historical_premier_league_data');
    $this->info('Historical data cache cleared!');
})->purpose('Clear the historical data cache');

// Test historical data
Artisan::command('test:historical-data', function () {
    $service = new \App\Services\HistoricalDataService();
    $seasons = $service->getAvailableSeasons();
    $this->info('Available seasons: ' . count($seasons));
    foreach ($seasons as $season) {
        $this->info('- ' . $season['season'] . ' (' . $season['matches_count'] . ' matches)');
    }
})->purpose('Test historical data loading');

// Smart Football Data Updates
// Full update once daily at 1am (teams, gameweeks, games) - 3 API calls
Schedule::command('football:update')->dailyAt('01:00')->withoutOverlapping();

// Auto-assignment of missing picks - run BEFORE smart updates to avoid race conditions
// Run every 30 minutes to catch deadline expirations faster
Schedule::command('picks:auto-assign')->everyThirtyMinutes()->withoutOverlapping();

// DEPRECATED: Old smart updates command - replaced by matches:update-live
// Commented out to avoid duplicate API calls (would exceed free tier limits)
// Schedule::command('football:smart-update')->everyTenMinutes()->withoutOverlapping();

// Live match tracking - SMART DATABASE-DRIVEN scheduling
// Automatically detects match windows by checking games table
// Only runs 1 hour before earliest kickoff through 3 hours after latest kickoff
// Updates every 15 minutes during active windows (4 calls/hour)
// Example: If matches are 3pm-5:30pm, runs 2pm-8:30pm only
// Typical match day: 4 calls/hour × ~8 hours = ~32 calls + 5 daily = 37 calls/day
// Maximum (Boxing Day): 4 calls/hour × ~11 hours = ~44 calls + 5 daily = 49 calls/day
// FREE TIER SAFE: Well within 100 calls/day limit with 50%+ safety margin
Schedule::command('matches:update-live')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Manual scheduling analysis (run this to see upcoming fixtures and plan updates)
Artisan::command('football:analyze-schedule', function () {
    $this->call('football:schedule-smart-updates', ['--dry-run' => true]);
})->purpose('Analyze upcoming fixtures and show optimal update times');

// Schedule player statistics updates (after matches and data updates)
Schedule::command('squad:update-stats')->dailyAt('02:00')->withoutOverlapping();

// Schedule squad data fetching (daily to get latest squad info and injury updates)
Schedule::command('squad:fetch')->dailyAt('03:00')->withoutOverlapping();

// Schedule automatic pick scoring after auto-assignments (daily cleanup)
Schedule::command('tournament:recalculate-points')->dailyAt('04:00')->withoutOverlapping();

