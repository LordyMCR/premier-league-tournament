<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\GameWeek;
use App\Models\LiveMatchEvent;
use App\Models\Pick;
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
            
            // Process finished matches and update pick results
            $this->processFinishedMatches();
            
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
        
        // CRITICAL: Don't update games that are already properly finished
        // This prevents finished games from being reset to 0-0 or showing as live again
        if ($game->status === 'FINISHED' && $game->home_score !== null && $game->away_score !== null) {
            // Only update if the API shows a different final score (shouldn't happen but just in case)
            if ($status === 'FINISHED' && $homeScore === $game->home_score && $awayScore === $game->away_score) {
                $this->line("✅ {$game->homeTeam->short_name} {$homeScore} - {$awayScore} {$game->awayTeam->short_name} [FINISHED] (already processed)");
                return false; // Don't count as updated since it's already correct
            } else if ($status !== 'FINISHED') {
                $this->warn("⚠️  Skipping update for finished game {$game->homeTeam->short_name} vs {$game->awayTeam->short_name} - API shows {$status} but game is FINISHED");
                return false;
            }
        }
        
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

        // Only fetch matches from today and yesterday (to catch matches that finished late)
        $data = $keyManager->makeRequest('https://api.football-data.org/v4/competitions/PL/matches', [
            'dateFrom' => now()->subDay()->startOfDay()->toDateString(),
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
            // Only process matches that are relevant (live, starting soon, or recently finished)
            if (!$this->shouldProcessMatch($apiMatch)) {
                continue;
            }
            
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
     * Determine if a match should be processed (live, starting soon, or recently finished)
     */
    private function shouldProcessMatch(array $apiMatch): bool
    {
        $status = $apiMatch['status'];
        $kickoff = isset($apiMatch['utcDate']) ? \Carbon\Carbon::parse($apiMatch['utcDate']) : null;
        
        if (!$kickoff) {
            return false;
        }
        
        $now = now();
        
        // Always process live matches
        if (in_array($status, ['LIVE', 'IN_PLAY', 'PAUSED'])) {
            return true;
        }
        
        // Process finished matches that finished within the last 4 hours
        if ($status === 'FINISHED') {
            $finishTime = $kickoff->copy()->addMinutes(120); // Assume 2 hour match duration
            return $now->diffInHours($finishTime) <= 4;
        }
        
        // Process matches starting within the next 2 hours
        if ($status === 'TIMED') {
            return $kickoff->diffInHours($now, false) <= 2; // Within 2 hours (before or after)
        }
        
        return false;
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
     * Process finished matches and update pick results
     */
    private function processFinishedMatches(): void
    {
        $this->info('🔄 Checking for finished matches to process...');
        
        // Find games that have just finished (status = FINISHED and have scores)
        $finishedGames = Game::where('status', 'FINISHED')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->with(['homeTeam', 'awayTeam', 'gameWeek'])
            ->get();

        if ($finishedGames->isEmpty()) {
            $this->info('✅ No finished matches found');
            return;
        }

        $this->info("🎯 Found {$finishedGames->count()} finished match(es)...");

        $processedCount = 0;
        foreach ($finishedGames as $game) {
            $this->line("📊 Processing: {$game->homeTeam->name} {$game->home_score} - {$game->away_score} {$game->awayTeam->name}");
            
            // Calculate pick results for this finished game
            $picksProcessed = $this->calculatePickResults($game);
            if ($picksProcessed > 0) {
                $processedCount++;
            }
        }

        if ($processedCount > 0) {
            $this->info("✅ Processed picks for {$processedCount} finished match(es)");
        } else {
            $this->info("ℹ️  No picks needed processing for finished matches");
        }

        // Update gameweek completion status
        $this->updateGameweekCompletion();
    }

    /**
     * Calculate pick results for a finished game
     */
    private function calculatePickResults(Game $game): int
    {
        $result = $game->getResult();
        
        if (!$result) {
            $this->warn("  ⚠️  Cannot determine result for game {$game->id}");
            return 0;
        }
        
        // Get all picks for teams in this game for this gameweek that haven't been scored yet
        $homeTeamPicks = Pick::where('game_week_id', $game->game_week_id)
            ->where('team_id', $game->home_team_id)
            ->whereNull('points_earned')
            ->with(['user', 'tournament'])
            ->get();
            
        $awayTeamPicks = Pick::where('game_week_id', $game->game_week_id)
            ->where('team_id', $game->away_team_id)
            ->whereNull('points_earned')
            ->with(['user', 'tournament'])
            ->get();

        $picksProcessed = 0;

        // Process home team picks
        foreach ($homeTeamPicks as $pick) {
            $pickResult = match($result) {
                'HOME_WIN' => 'win',
                'AWAY_WIN' => 'loss',
                'DRAW' => 'draw',
                default => null
            };

            if ($pickResult) {
                $points = $pick->setResult($pickResult);
                $picksProcessed++;
                $this->line("  → {$pick->user->name}: {$game->homeTeam->name} - {$pickResult} ({$points} pts)");
            }
        }

        // Process away team picks
        foreach ($awayTeamPicks as $pick) {
            $pickResult = match($result) {
                'AWAY_WIN' => 'win',
                'HOME_WIN' => 'loss', 
                'DRAW' => 'draw',
                default => null
            };

            if ($pickResult) {
                $points = $pick->setResult($pickResult);
                $picksProcessed++;
                $this->line("  → {$pick->user->name}: {$game->awayTeam->name} - {$pickResult} ({$points} pts)");
            }
        }

        if ($picksProcessed > 0) {
            $this->info("  ✅ Processed {$picksProcessed} picks for this game");
        }

        return $picksProcessed;
    }

    /**
     * Update gameweek completion status
     */
    private function updateGameweekCompletion(): void
    {
        $gameweeks = GameWeek::where('is_completed', false)->get();
        
        foreach ($gameweeks as $gameweek) {
            $totalGames = $gameweek->games()->count();
            $finishedGames = $gameweek->games()
                ->whereIn('status', ['FINISHED', 'CANCELLED', 'POSTPONED'])
                ->count();
                
            if ($totalGames > 0 && $finishedGames === $totalGames) {
                $gameweek->markAsCompleted();
                $this->info("🏁 Marked {$gameweek->name} as completed ({$finishedGames}/{$totalGames} games finished)");
            }
        }
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

