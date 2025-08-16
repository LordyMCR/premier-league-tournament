<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\SquadService;
use Illuminate\Console\Command;

class FetchSquadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'squad:fetch 
                           {--team= : Specific team ID to fetch}
                           {--force : Force update even if recent data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch squad data for Premier League teams from external APIs';

    /**
     * Execute the console command.
     */
    public function handle(SquadService $squadService): int
    {
        $this->info('Starting squad data fetch...');

        // Get teams to process
        if ($teamId = $this->option('team')) {
            $team = Team::find($teamId);
            if (!$team) {
                $this->error("Team with ID {$teamId} not found.");
                return 1;
            }
            
            $this->info("Fetching squad data for {$team->name}...");
            $success = $squadService->fetchAndStoreSquadData($team, $this->option('force'));
            
            if ($success) {
                // Player statistics update has been disabled (requires paid API access)
                $this->info("✓ Successfully updated squad for {$team->name}");
                return 0;
            } else {
                $this->error("✗ Failed to update squad for {$team->name}");
                return 1;
            }
        }

        // Bulk fetch all teams
        $this->info("🚀 Processing all Premier League teams in bulk...");
        $results = $squadService->fetchAndStoreAllSquadData($this->option('force'));
        
        $this->newLine();
        $this->info("📊 Bulk Squad Data Fetch Results:");
        $this->info("✅ Successful: {$results['success']} teams");
        $this->info("❌ Errors: {$results['errors']} teams");
        
        if (!empty($results['teams'])) {
            $this->newLine();
            $this->info("📋 Team Results:");
            foreach ($results['teams'] as $teamResult) {
                $this->line("  {$teamResult}");
            }
        }

        // Player statistics update has been disabled (requires paid API access)
        if ($results['success'] > 0) {
            $this->info("\n📈 Player statistics update disabled (requires paid API access)");
            $teams = Team::whereHas('players')->get();
            foreach ($teams as $team) {
                try {
                    $this->line("  ⚠ Stats update disabled for {$team->name}");
                } catch (\Exception $e) {
                    $this->warn("Failed to process {$team->name}");
                }
            }
        }

        if ($results['success'] > 0) {
            $this->info("\n🎉 Squad data fetch completed successfully!");
            return 0;
        } else {
            $this->error("\n💥 Squad data fetch failed for all teams.");
            return 1;
        }
    }
}
