<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\SquadService;
use Illuminate\Console\Command;

class TestSquadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'squad:test {team=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test squad data functionality for a team';

    /**
     * Execute the console command.
     */
    public function handle(SquadService $squadService): int
    {
        $teamId = $this->argument('team');
        $team = Team::find($teamId);

        if (!$team) {
            $this->error("Team with ID {$teamId} not found.");
            return 1;
        }

        $this->info("Testing squad data for: {$team->name}");
        $this->info("================================");

        // Test squad by position
        $squadByPosition = $squadService->getSquadByPosition($team);
        $this->info("Squad by Position:");
        foreach ($squadByPosition as $position => $players) {
            $this->info("- {$position}: {$players->count()} players");
        }

        // Test top performers
        $topPerformers = $squadService->getTopPerformers($team);
        $this->newLine();
        $this->info("Top Performers:");
        $this->info("- Top Scorers: {$topPerformers['top_scorers']->count()} players");
        $this->info("- Top Assists: {$topPerformers['top_assists']->count()} players");
        $this->info("- Most Appearances: {$topPerformers['most_appearances']->count()} players");

        // Test recent transfers
        $recentTransfers = $squadService->getRecentTransfers($team);
        $this->newLine();
        $this->info("Recent Transfers (last 90 days):");
        $this->info("- Incoming: {$recentTransfers['incoming']->count()} transfers");
        $this->info("- Outgoing: {$recentTransfers['outgoing']->count()} transfers");

        // Show sample players
        if ($squadByPosition['goalkeepers']->count() > 0) {
            $this->newLine();
            $this->info("Sample Goalkeepers:");
            foreach ($squadByPosition['goalkeepers']->take(3) as $player) {
                $this->info("- #{$player->shirt_number} {$player->name} ({$player->nationality})");
            }
        }

        return 0;
    }
}
