<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\GameWeek;
use App\Services\FootballDataService;
use Illuminate\Console\Command;

class UpdateFootballData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:update 
                            {--teams : Only update teams data}
                            {--gameweeks : Only update gameweeks data}
                            {--force : Force update even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Premier League teams and gameweeks data from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $footballService = new FootballDataService();

        if (!$footballService->isConfigured()) {
            $this->error('Football Data API key not configured. Please set FOOTBALL_DATA_API_KEY in your .env file.');
            $this->info('You can get a free API key from: https://www.football-data.org/');
            return Command::FAILURE;
        }

        $teamsOnly = $this->option('teams');
        $gameweeksOnly = $this->option('gameweeks');
        $force = $this->option('force');

        // If no specific option is provided, update both
        if (!$teamsOnly && !$gameweeksOnly) {
            $teamsOnly = $gameweeksOnly = true;
        }

        if ($teamsOnly) {
            $this->updateTeams($footballService, $force);
        }

        if ($gameweeksOnly) {
            $this->updateGameweeks($footballService, $force);
        }

        $this->info('Football data update completed!');
        return Command::SUCCESS;
    }

    /**
     * Update teams data
     */
    private function updateTeams(FootballDataService $footballService, bool $force): void
    {
        $this->info('Updating Premier League teams...');

        if (!$force && Team::count() > 0) {
            if (!$this->confirm('Teams data already exists. Do you want to continue?')) {
                $this->info('Teams update skipped.');
                return;
            }
        }

        $teams = $footballService->getPremierLeagueTeams();

        if (empty($teams)) {
            $this->error('Failed to fetch teams from API.');
            return;
        }

        $progressBar = $this->output->createProgressBar(count($teams));
        $progressBar->start();

        foreach ($teams as $team) {
            Team::updateOrCreate(
                ['short_name' => $team['short_name']],
                $team
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Successfully updated ' . count($teams) . ' teams.');
    }

    /**
     * Update gameweeks data
     */
    private function updateGameweeks(FootballDataService $footballService, bool $force): void
    {
        $this->info('Updating Premier League gameweeks...');

        if (!$force && GameWeek::count() > 0) {
            if (!$this->confirm('Gameweeks data already exists. Do you want to continue?')) {
                $this->info('Gameweeks update skipped.');
                return;
            }
        }

        $gameweeks = $footballService->getPremierLeagueFixtures();

        if (empty($gameweeks)) {
            $this->error('Failed to fetch gameweeks from API.');
            return;
        }

        $progressBar = $this->output->createProgressBar(count($gameweeks));
        $progressBar->start();

        foreach ($gameweeks as $gameweek) {
            GameWeek::updateOrCreate(
                ['week_number' => $gameweek['week_number']],
                $gameweek
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Successfully updated ' . count($gameweeks) . ' gameweeks.');
    }
}
