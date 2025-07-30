<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TeamNewsService;
use App\Models\Team;

class TestTeamNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'team-news:test {team? : Team name or ID to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Team News API integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $teamNewsService = app(TeamNewsService::class);
        
        // Get team to test
        $teamInput = $this->argument('team');
        
        if ($teamInput) {
            if (is_numeric($teamInput)) {
                $team = Team::find($teamInput);
            } else {
                $team = Team::where('name', 'like', '%' . $teamInput . '%')->first();
            }
            
            if (!$team) {
                $this->error("Team not found: {$teamInput}");
                return 1;
            }
        } else {
            $team = Team::first();
            if (!$team) {
                $this->error("No teams found in database");
                return 1;
            }
        }

        $this->info("Testing Team News for: {$team->name}");
        $this->line("═══════════════════════════════════════");

        // Test News
        $this->info("\n📰 Testing Team News...");
        $news = $teamNewsService->getTeamNews($team->name, 3);
        
        if (empty($news)) {
            $this->warn("No news found");
        } else {
            foreach ($news as $index => $article) {
                $this->line("\n" . ($index + 1) . ". {$article['title']}");
                $this->line("   Source: {$article['source']} | {$article['published_at']}");
                $this->line("   " . substr($article['content_snippet'], 0, 100) . "...");
            }
        }

        // Test Injuries
        $this->info("\n🏥 Testing Injury Reports...");
        $injuries = $teamNewsService->getTeamInjuries($team->external_id ?? $team->id);
        
        if (empty($injuries)) {
            $this->info("✅ No injury concerns");
        } else {
            foreach ($injuries as $injury) {
                $severityColor = match($injury['severity']) {
                    'major' => 'error',
                    'moderate' => 'warn',
                    'minor' => 'info',
                    default => 'line'
                };
                
                $this->$severityColor("🩹 {$injury['player_name']} ({$injury['position']})");
                $this->line("   {$injury['injury_type']} - Expected return: {$injury['expected_return']}");
            }
        }

        // Test Manager Quotes
        $this->info("\n💬 Testing Manager Quotes...");
        $quotes = $teamNewsService->getManagerQuotes($team->name, null, 3);
        
        if (empty($quotes)) {
            $this->warn("No manager quotes found");
        } else {
            foreach ($quotes as $index => $quote) {
                $this->line("\n" . ($index + 1) . ". {$quote['source']} | {$quote['published_at']}");
                $this->line('   "' . substr($quote['quote'], 0, 120) . '..."');
            }
        }

        $this->info("\n✅ Team News test completed!");
        $this->line("\nTo set up real APIs, check: PHASE_2B_TEAM_NEWS_SETUP.md");
        
        return 0;
    }
}
