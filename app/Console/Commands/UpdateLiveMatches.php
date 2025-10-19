<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\LiveMatchEvent;
use App\Services\ApiKeyManager;
use App\Services\FootballDataApiKeyManager;
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
    protected $description = 'Fetch live match updates using Football-Data.org (primary) with API-Football.com enhancement';

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
            // First, check Football-Data.org for live matches (more reliable detection)
            $liveCount = $this->updateMatchesFromFootballData();
            
            // If we found live matches, try to enhance with API-Football.com data
            if ($liveCount > 0) {
                $this->info('🔴 Found live matches! Attempting to enhance with API-Football.com data...');
                $enhancedMatches = $this->getLiveMatchesFromApiFootball();
                
                if (!empty($enhancedMatches)) {
                    $this->info('✅ Enhanced live data with API-Football.com');
                    $this->updateLiveMatchesFromApiFootball($enhancedMatches);
                } else {
                    $this->info('📅 API-Football.com has no live data, using Football-Data.org only');
                }
            } else {
                $this->info('📅 No live matches detected');
            }
            
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
        
        // Validate data freshness - reject stale data for live matches
        if (in_array($status, ['IN_PLAY', 'LIVE', 'PAUSED'])) {
            $lastUpdated = isset($apiMatch['lastUpdated']) ? \Carbon\Carbon::parse($apiMatch['lastUpdated']) : null;
            
            if ($lastUpdated && $lastUpdated->diffInMinutes(now()) > 30) {
                $this->warn("⚠️  Rejecting stale data for live match {$game->homeTeam->short_name} vs {$game->awayTeam->short_name}. Last updated: {$lastUpdated}");
                return false;
            }
            
            // For live matches, ensure we have actual scores (not 0-0 unless it's actually 0-0)
            if ($homeScore === 0 && $awayScore === 0 && $lastUpdated && $lastUpdated->diffInMinutes(now()) > 5) {
                $this->warn("⚠️  Rejecting 0-0 score for live match that's been running for 5+ minutes. Last updated: {$lastUpdated}");
                return false;
            }
        }

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
     * Get live matches from API-Football.com with fallback keys
     */
    private function getLiveMatchesFromApiFootball(): array
    {
        $apiKeyManager = new ApiKeyManager();
        
        if ($apiKeyManager->getTotalKeys() === 0) {
            $this->warn('⚠️  No API-Football.com keys configured, falling back to Football-Data.org');
            return [];
        }

        $this->info("🔄 Trying API-Football.com with {$apiKeyManager->getTotalKeys()} available keys...");

        $data = $apiKeyManager->makeRequest('https://v3.football.api-sports.io/fixtures', [
            'live' => 'all',
            'league' => 39, // Premier League
            'season' => 2024
        ]);

        if ($data === null) {
            $this->warn('⚠️  All API-Football.com keys failed, falling back to Football-Data.org');
            return [];
        }

        $this->info("✅ API-Football.com request successful");
        return $data['response'] ?? [];
    }

    /**
     * Update live matches using API-Football.com data
     */
    private function updateLiveMatchesFromApiFootball(array $liveMatches): int
    {
        $updatedCount = 0;

        foreach ($liveMatches as $match) {
            // Find the game by external ID (we'll need to map API-Football IDs to our database)
            $game = Game::where('external_id', $match['fixture']['id'])->first();

            if (!$game) {
                // Try to find by team names as fallback
                $game = $this->findGameByTeamNames(
                    $match['teams']['home']['name'],
                    $match['teams']['away']['name']
                );
            }

            if (!$game) {
                $this->warn("⚠️  Live match not found in database: {$match['teams']['home']['name']} vs {$match['teams']['away']['name']}");
                continue;
            }

            // Extract scores and status
            $homeScore = $match['goals']['home'] ?? 0;
            $awayScore = $match['goals']['away'] ?? 0;
            $status = $this->mapApiFootballStatus($match['fixture']['status']['short']);
            $minute = $match['fixture']['status']['elapsed'] ?? null;

            // Update or create live event
            LiveMatchEvent::updateOrCreate(
                ['game_id' => $game->id],
                [
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'status' => $status,
                    'minute' => $minute,
                    'events' => $this->extractApiFootballEvents($match),
                    'last_updated' => now(),
                ]
            );

            // Also update the main Game table
            $game->update([
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'status' => $status,
            ]);

            $this->line("🔴 {$game->homeTeam->short_name} {$homeScore} - {$awayScore} {$game->awayTeam->short_name} ({$minute}')");
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Update matches from Football-Data.org using key manager
     */
    private function updateMatchesFromFootballData(): int
    {
        $keyManager = new FootballDataApiKeyManager();
        
        if ($keyManager->getTotalKeys() === 0) {
            $this->error('❌ No Football-Data.org API keys configured');
            return 0;
        }

        $this->info("🔄 Using Football-Data.org with {$keyManager->getTotalKeys()} available keys...");

        $data = $keyManager->makeRequest('https://api.football-data.org/v4/competitions/PL/matches', [
            'dateFrom' => now()->startOfDay()->toDateString(),
            'dateTo' => now()->endOfDay()->toDateString(),
        ]);

        if ($data === null) {
            $this->error('❌ All Football-Data.org API keys failed');
            return 0;
        }

        $matches = $data['matches'] ?? [];
        $updatedCount = 0;
        $liveCount = 0;

        foreach ($matches as $apiMatch) {
            $updated = $this->updateMatchCache($apiMatch);
            if ($updated) {
                $updatedCount++;
                
                // Check if this is a live match
                $kickoff = isset($apiMatch['utcDate']) ? \Carbon\Carbon::parse($apiMatch['utcDate']) : null;
                $isAfterKickoff = $kickoff ? $kickoff->isPast() : false;
                $isWithinLiveWindow = $kickoff ? now()->between($kickoff, $kickoff->copy()->addMinutes(120)) : false;
                $isLive = in_array($apiMatch['status'], ['LIVE', 'IN_PLAY', 'PAUSED']) ||
                         ($apiMatch['status'] === 'TIMED' && $isAfterKickoff && $isWithinLiveWindow);
                
                if ($isLive) {
                    $liveCount++;
                }
            }
        }

        $this->info("✅ Updated {$updatedCount} matches ({$liveCount} live) using Football-Data.org");
        return $liveCount;
    }

    /**
     * Find game by team names (fallback method)
     */
    private function findGameByTeamNames(string $homeTeamName, string $awayTeamName): ?Game
    {
        return Game::whereHas('homeTeam', function($query) use ($homeTeamName) {
            $query->where('name', 'like', "%{$homeTeamName}%");
        })
        ->whereHas('awayTeam', function($query) use ($awayTeamName) {
            $query->where('name', 'like', "%{$awayTeamName}%");
        })
        ->whereDate('kick_off_time', today())
        ->first();
    }

    /**
     * Map API-Football status to our internal status
     */
    private function mapApiFootballStatus(string $apiStatus): string
    {
        return match($apiStatus) {
            '1H', '2H', 'HT', 'ET' => 'LIVE',
            'PEN' => 'LIVE',
            'FT', 'AET' => 'FINISHED',
            'NS' => 'TIMED',
            'CANC', 'ABD' => 'CANCELLED',
            'SUSP' => 'POSTPONED',
            default => 'TIMED'
        };
    }

    /**
     * Extract events from API-Football response
     */
    private function extractApiFootballEvents(array $match): array
    {
        return [
            'goals' => $match['events'] ?? [],
            'lineups' => [
                'home' => $match['lineups']['home'] ?? [],
                'away' => $match['lineups']['away'] ?? []
            ],
            'statistics' => [
                'home' => $match['statistics']['home'] ?? [],
                'away' => $match['statistics']['away'] ?? []
            ]
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

