<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Player;
use Illuminate\Console\Command;

class SquadStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'squad:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show squad data status for all teams';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Squad Data Status Report');
        $this->info('========================');

        $totalPlayers = Player::count();
        $this->info("Total players in database: {$totalPlayers}");
        $this->newLine();

        $teams = Team::withCount('players')->orderBy('name')->get();

        $headers = ['Team', 'Players', 'Last Update'];
        $rows = [];

        foreach ($teams as $team) {
            $lastUpdate = $team->players()->max('last_profile_update');
            $lastUpdateFormatted = $lastUpdate ? 
                \Carbon\Carbon::parse($lastUpdate)->diffForHumans() : 
                'Never';

            $rows[] = [
                $team->name,
                $team->players_count,
                $lastUpdateFormatted
            ];
        }

        $this->table($headers, $rows);

        // Summary stats
        $teamsWithPlayers = $teams->where('players_count', '>', 0)->count();
        $teamsWithoutPlayers = $teams->where('players_count', 0)->count();

        $this->newLine();
        $this->info("Summary:");
        $this->info("- Teams with squad data: {$teamsWithPlayers}");
        $this->info("- Teams without squad data: {$teamsWithoutPlayers}");

        return 0;
    }
}
