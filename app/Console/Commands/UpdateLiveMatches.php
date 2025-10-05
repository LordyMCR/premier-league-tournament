<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\LiveMatchEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateLiveMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:update-live {--force : Force update even outside match windows}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch live match updates from Football-Data API (FREE TIER optimized - single API call)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Only run during match days unless forced
        if (!$this->option('force') && !$this->isMatchWindow()) {
            $this->info('🚫 No matches scheduled in this time window. Skipping to save API calls.');
            $this->info('💡 Use --force to update anyway.');
            return;
        }

        $this->info('🔄 Checking for live Premier League matches...');

        try {
            // SINGLE API CALL - fetches ALL matches for today
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key')
            ])
            ->timeout(10)
            ->get('https://api.football-data.org/v4/competitions/PL/matches', [
                'dateFrom' => now()->startOfDay()->toDateString(),
                'dateTo' => now()->endOfDay()->toDateString(),
            ]);

            if (!$response->successful()) {
                $this->error('❌ API request failed: ' . $response->status());
                Log::error('Football-Data API failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return 1;
            }

            $matches = $response->json()['matches'] ?? [];
            
            if (empty($matches)) {
                $this->warn('⚠️  No matches found for today');
                return 0;
            }

            $liveCount = 0;
            $updatedCount = 0;

            foreach ($matches as $apiMatch) {
                $updated = $this->updateMatchCache($apiMatch);
                
                if ($updated) {
                    $updatedCount++;
                    
                    if (in_array($apiMatch['status'], ['LIVE', 'IN_PLAY', 'PAUSED'])) {
                        $liveCount++;
                    }
                }
            }

            $this->info("✅ Updated {$updatedCount} matches ({$liveCount} live)");
            $this->info("📊 API calls used: 1 (Free tier: 10/min limit)");
            
            // Clean up old finished matches
            $this->cleanupOldMatches();

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Live match update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Check if we're in a match window (to save API calls)
     * Uses intelligent database-driven scheduling instead of hardcoded days
     */
    private function isMatchWindow(): bool
    {
        $now = now();
        
        // Check if there are any games scheduled for today
        $todaysGames = Game::whereDate('kick_off_time', today())
            ->whereNotIn('status', ['CANCELLED', 'POSTPONED'])
            ->get();
        
        if ($todaysGames->isEmpty()) {
            return false; // No games today at all
        }
        
        // Find the earliest kickoff and latest expected finish time
        $earliestKickoff = $todaysGames->min('kick_off_time');
        $latestKickoff = $todaysGames->max('kick_off_time');
        
        // Start checking 1 hour before earliest kickoff
        $windowStart = Carbon::parse($earliestKickoff)->subHour();
        
        // Stop checking 3 hours after latest kickoff (match duration ~2 hours + buffer)
        $windowEnd = Carbon::parse($latestKickoff)->addHours(3);
        
        // Check if we're currently in the active window
        $inWindow = $now->between($windowStart, $windowEnd);
        
        if ($inWindow) {
            $this->line("⚽ Match window: {$windowStart->format('H:i')} - {$windowEnd->format('H:i')}");
        }
        
        return $inWindow;
    }

    /**
     * Update or create match cache entry
     */
    private function updateMatchCache(array $apiMatch): bool
    {
        // Find the game by external ID
        $game = Game::where('external_id', $apiMatch['id'])->first();

        if (!$game) {
            $this->warn("⚠️  Match {$apiMatch['id']} not found in database");
            return false;
        }

        // Extract scores (handle nulls for unstarted matches)
        $homeScore = $apiMatch['score']['fullTime']['home'] ?? 0;
        $awayScore = $apiMatch['score']['fullTime']['away'] ?? 0;
        $status = $apiMatch['status'];

        // Update or create live event
        LiveMatchEvent::updateOrCreate(
            ['game_id' => $game->id],
            [
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'status' => $status,
                'minute' => $apiMatch['minute'] ?? null,
                'events' => $this->extractEvents($apiMatch),
                'last_updated' => now(),
            ]
        );

        // Also update the main Game table for compatibility
        $game->update([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status' => $status,
        ]);

        $statusEmoji = match($status) {
            'LIVE', 'IN_PLAY' => '🔴',
            'PAUSED' => '⏸️',
            'FINISHED' => '✅',
            default => '📅'
        };

        $this->line("{$statusEmoji} {$game->homeTeam->short_name} {$homeScore} - {$awayScore} {$game->awayTeam->short_name} [{$status}]");

        return true;
    }

    /**
     * Extract match events (goals, cards, etc.)
     */
    private function extractEvents(array $apiMatch): array
    {
        return [
            'goals' => $apiMatch['goals'] ?? [],
            'bookings' => $apiMatch['bookings'] ?? [],
            'substitutions' => $apiMatch['substitutions'] ?? [],
            'penalties' => $apiMatch['penalties'] ?? [],
        ];
    }

    /**
     * Clean up old finished matches (save database space)
     */
    private function cleanupOldMatches(): void
    {
        $deleted = LiveMatchEvent::finished()
            ->where('last_updated', '<', now()->subHours(2))
            ->delete();

        if ($deleted > 0) {
            $this->info("🧹 Cleaned up {$deleted} old finished match events");
        }
    }
}

