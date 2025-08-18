<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;

class ScheduleSmartUpdates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'football:schedule-smart-updates
                            {--dry-run : Show what would be scheduled without actually scheduling}';

    /**
     * The console command description.
     */
    protected $description = 'Intelligently schedule football data updates based on actual fixture data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - Showing what would be scheduled...');
        }

        // Get upcoming games in the next 48 hours that haven't finished
        $upcomingGames = Game::with(['homeTeam', 'awayTeam'])
            ->where('kick_off_time', '>', now())
            ->where('kick_off_time', '<=', now()->addHours(48))
            ->whereNotIn('status', ['FINISHED', 'CANCELLED', 'POSTPONED'])
            ->orderBy('kick_off_time')
            ->get();

        if ($upcomingGames->isEmpty()) {
            $this->info('📅 No upcoming games in the next 48 hours');
            return Command::SUCCESS;
        }

        $this->info("🎯 Found {$upcomingGames->count()} upcoming games:");
        
        $scheduledUpdates = [];
        $apiCallsNeeded = 0;

        foreach ($upcomingGames as $game) {
            $kickoffTime = Carbon::parse($game->kick_off_time);
            
            // Schedule result updates at strategic times after kickoff
            $updateTimes = [
                $kickoffTime->copy()->addMinutes(105), // Just after full time (90 + 15 min)
                $kickoffTime->copy()->addMinutes(135), // 2.25 hours after (catches any delays)
            ];

            $this->line("  {$game->homeTeam->name} vs {$game->awayTeam->name} - {$kickoffTime->format('D M j, H:i')}");

            foreach ($updateTimes as $index => $updateTime) {
                // Only schedule if the update time is in the future
                if ($updateTime->isFuture()) {
                    $description = $index === 0 ? 'Post-match update' : 'Late update (delays/ET)';
                    $scheduledUpdates[] = [
                        'time' => $updateTime,
                        'command' => 'football:update --results',
                        'description' => $description,
                        'game' => "{$game->homeTeam->short_name} vs {$game->awayTeam->short_name}"
                    ];
                    $apiCallsNeeded++;
                    
                    $this->line("    → {$description}: {$updateTime->format('H:i')}");
                }
            }
        }

        // Also get games that finished in the last 6 hours (catch any missing results)
        $recentlyFinishedGames = Game::with(['homeTeam', 'awayTeam'])
            ->where('kick_off_time', '>', now()->subHours(8))
            ->where('kick_off_time', '<=', now())
            ->whereIn('status', ['FINISHED'])
            ->where('updated_at', '<', now()->subHours(2)) // Haven't been updated recently
            ->get();

        if ($recentlyFinishedGames->isNotEmpty()) {
            $this->info("\n🔄 Recent games that may need result updates:");
            foreach ($recentlyFinishedGames as $game) {
                $this->line("  {$game->homeTeam->name} vs {$game->awayTeam->name} - Last updated: {$game->updated_at->diffForHumans()}");
            }
            
            // Schedule an immediate catch-up update
            $scheduledUpdates[] = [
                'time' => now()->addMinutes(2),
                'command' => 'football:update --results',
                'description' => 'Catch-up for recent games',
                'game' => 'Multiple games'
            ];
            $apiCallsNeeded++;
        }

        // Check API limit constraints
        $this->info("\n📊 API Usage Analysis:");
        $this->line("  Scheduled result updates: {$apiCallsNeeded} calls");
        $this->line("  Daily full update: 3 calls");
        $this->line("  Total daily usage: " . ($apiCallsNeeded + 3) . " calls");

        if (($apiCallsNeeded + 3) > 10) {
            $this->warn("  ⚠️  This exceeds the 10 call daily limit!");
            $this->line("  💡 Consider reducing update frequency or prioritizing most recent games");
            
            // Prioritize most recent games if we're over limit
            $scheduledUpdates = collect($scheduledUpdates)
                ->sortBy('time')
                ->take(7) // Keep room for the daily full update (3 calls)
                ->toArray();
                
            $this->info("  🎯 Reduced to " . count($scheduledUpdates) . " priority updates");
        } else {
            $this->info("  ✅ Within API limits");
        }

        if (empty($scheduledUpdates)) {
            $this->info("\n📅 No additional updates needed to be scheduled");
            return Command::SUCCESS;
        }

        // Show the schedule
        $this->info("\n📋 Scheduled Updates:");
        foreach ($scheduledUpdates as $update) {
            $this->line("  {$update['time']->format('D M j, H:i')} - {$update['description']} ({$update['game']})");
        }

        if (!$dryRun) {
            $this->info("\n🚀 This would be implemented by dynamically adding to Heroku Scheduler...");
            $this->line("💡 For now, you can manually add these times to your Heroku Scheduler dashboard");
        }

        return Command::SUCCESS;
    }
}
