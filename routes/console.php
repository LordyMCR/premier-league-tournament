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

// Smart results updates - only runs when games actually need checking
// This analyzes the database to determine if updates are needed, saving API calls
Schedule::command('football:smart-update')->hourly()->withoutOverlapping(); // Checks every hour but only runs when needed

// Manual scheduling analysis (run this to see upcoming fixtures and plan updates)
Artisan::command('football:analyze-schedule', function () {
    $this->call('football:schedule-smart-updates', ['--dry-run' => true]);
})->purpose('Analyze upcoming fixtures and show optimal update times');

// Schedule player statistics updates (after matches and data updates)
Schedule::command('squad:update-stats')->dailyAt('02:00')->withoutOverlapping();

// Schedule squad data fetching (daily to get latest squad info and injury updates)
Schedule::command('squad:fetch')->dailyAt('03:00')->withoutOverlapping();

// Schedule auto-assignment of missing picks
// Run every hour to catch any recently passed deadlines
Schedule::command('picks:auto-assign')->hourly()->withoutOverlapping();

