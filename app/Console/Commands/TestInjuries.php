<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TeamNewsService;
use App\Models\Team;

class TestInjuries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:injuries {team?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test injury data fetching from APIFootball.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamName = $this->argument('team');
        
        if (!$teamName) {
            // Default to Arsenal for testing
            $team = Team::where('name', 'Arsenal')->first();
            if (!$team) {
                $this->error('Arsenal team not found in database');
                return 1;
            }
        } else {
            $team = Team::where('name', 'like', "%{$teamName}%")->first();
            if (!$team) {
                $this->error("Team '{$teamName}' not found in database");
                return 1;
            }
        }

        $this->info("🏥 Testing injury data for: {$team->name}");
        $this->info("Team ID: {$team->id}");
        
        $teamNewsService = new TeamNewsService();
        
        $this->line('');
        $this->info('📡 Fetching injury data from APIFootball.com...');
        
        try {
            $injuries = $teamNewsService->getTeamInjuries($team->id);
            
            $this->line('');
            if (empty($injuries)) {
                $this->comment('✅ No injuries found for ' . $team->name);
            } else {
                $this->info("🩹 Found " . count($injuries) . " injury(ies):");
                $this->line('');
                
                foreach ($injuries as $index => $injury) {
                    $this->line("#" . ($index + 1) . " - {$injury['player_name']} ({$injury['position']})");
                    $this->line("   Type: {$injury['injury_type']}");
                    $this->line("   Reason: {$injury['reason']}");
                    $this->line("   Status: {$injury['status']}");
                    if ($injury['player_number']) {
                        $this->line("   Number: #{$injury['player_number']}");
                    }
                    if ($injury['age']) {
                        $this->line("   Age: {$injury['age']}");
                    }
                    $this->line('');
                }
            }
            
            // Also test news
            $this->info('📰 Testing news data...');
            $news = $teamNewsService->getTeamNews($team->name, 3);
            
            if (empty($news)) {
                $this->comment('✅ No news found for ' . $team->name);
            } else {
                $this->info("📖 Found " . count($news) . " news article(s):");
                foreach ($news as $index => $article) {
                    $this->line("#" . ($index + 1) . " - {$article['title']}");
                    $this->line("   Source: {$article['source']}");
                    $this->line("   Published: {$article['published_at']}");
                    $this->line('');
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error testing injuries: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        $this->info('✅ Test completed successfully!');
        return 0;
    }
}
