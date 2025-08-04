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

// Schedule daily football data updates
Schedule::command('football:update')->dailyAt('03:00')->withoutOverlapping();

// Schedule squad data fetching (daily to get latest squad info and injury updates)
Schedule::command('squad:fetch')->dailyAt('04:00')->withoutOverlapping();

// Schedule player statistics updates (after matches and data updates)
Schedule::command('squad:update-stats')->dailyAt('05:00')->withoutOverlapping();

// Schedule auto-assignment of missing picks
// Run every hour to catch any recently passed deadlines
Schedule::command('picks:auto-assign')->hourly()->withoutOverlapping();

// Alternative: Run at specific times when deadlines typically occur
// Schedule::command('picks:auto-assign')->dailyAt('11:30')->withoutOverlapping(); // Before most Saturday games
// Schedule::command('picks:auto-assign')->dailyAt('16:30')->withoutOverlapping(); // Before evening games
// Schedule::command('picks:auto-assign')->weeklyOn(1, '19:30')->withoutOverlapping(); // Monday evening games
