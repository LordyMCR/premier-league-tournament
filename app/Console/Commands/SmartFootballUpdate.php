<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SmartFootballUpdate extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'football:smart-update
                            {--force : Force update even if no games warrant it}';

    /**
     * The console command description.
     */
    protected $description = 'Smart football data update that only runs when games need checking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        // Check if we should run a results update based on game data
        $shouldUpdate = $this->shouldRunUpdate();
        
        if (!$shouldUpdate && !$force) {
            $this->info('📅 No games need checking at this time - skipping update to save API calls');
            return Command::SUCCESS;
        }
        
        if ($force) {
            $this->info('🔧 Force flag used - running update regardless');
        }
        
        // Run the results-only update
        $this->info('🎯 Running smart results update...');
        $exitCode = $this->call('football:update', ['--results' => true]);
        
        if ($exitCode === Command::SUCCESS) {
            $this->info('✅ Smart update completed successfully');
        } else {
            $this->error('❌ Smart update failed');
        }
        
        return $exitCode;
    }

    /**
     * Determine if we should run an update based on game timing
     */
    private function shouldRunUpdate(): bool
    {
        $now = now();
        
        // 1. Check for games that finished in the last 3 hours and might need result updates
        $recentlyFinishedGames = Game::where('kick_off_time', '>', $now->copy()->subHours(5))
            ->where('kick_off_time', '<=', $now->copy()->subHours(1))
            ->whereIn('status', ['IN_PLAY', 'PAUSED', 'FINISHED'])
            ->where('updated_at', '<', $now->copy()->subMinutes(30)) // Not updated in last 30 min
            ->count();

        if ($recentlyFinishedGames > 0) {
            $this->line("🔄 Found {$recentlyFinishedGames} recent games that may need updates");
            return true;
        }

        // 2. Check for games currently in progress
        $gamesInProgress = Game::whereIn('status', ['IN_PLAY', 'PAUSED'])
            ->count();

        if ($gamesInProgress > 0) {
            $this->line("⚽ Found {$gamesInProgress} games currently in progress");
            return true;
        }

        // 3. Check for games that kicked off in the last 3 hours (catch live results)
        $recentKickoffs = Game::where('kick_off_time', '>', $now->copy()->subHours(3))
            ->where('kick_off_time', '<=', $now)
            ->whereNotIn('status', ['FINISHED', 'CANCELLED', 'POSTPONED'])
            ->count();

        if ($recentKickoffs > 0) {
            $this->line("🚀 Found {$recentKickoffs} recent kickoffs that may have results");
            return true;
        }

        // 4. Check for games starting soon (within next 15 minutes) - proactive status checking
        $gamesSoonToStart = Game::where('kick_off_time', '>', $now)
            ->where('kick_off_time', '<=', $now->copy()->addMinutes(15))
            ->whereIn('status', ['SCHEDULED', 'TIMED'])
            ->count();

        if ($gamesSoonToStart > 0) {
            $this->line("🕐 Found {$gamesSoonToStart} games starting soon - checking for early status updates");
            return true;
        }

        // 5. Check for games finishing soon (within next hour) - proactive checking
        $gamesSoonToFinish = Game::where('kick_off_time', '>', $now->copy()->subMinutes(90))
            ->where('kick_off_time', '<=', $now->copy()->addMinutes(10))
            ->whereIn('status', ['TIMED', 'IN_PLAY', 'PAUSED'])
            ->count();

        if ($gamesSoonToFinish > 0) {
            $this->line("⏰ Found {$gamesSoonToFinish} games that should be finishing soon");
            return true;
        }

        // 6. Weekend priority - run more frequently on Saturdays and Sundays
        if ($now->isWeekend()) {
            $todaysGames = Game::whereDate('kick_off_time', $now->toDateString())
                ->whereNotIn('status', ['FINISHED', 'CANCELLED', 'POSTPONED'])
                ->count();
                
            if ($todaysGames > 0) {
                $this->line("📅 Weekend with {$todaysGames} scheduled games - running proactive update");
                return true;
            }
        }

        $this->line("😴 No urgent games found - skipping update");
        return false;
    }
}
