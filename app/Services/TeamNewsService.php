<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TeamNewsService
{
    private ?string $newsApiKey;
    private ?string $footballApiKey;
    private ?string $sportsDbApiKey; // TheSportsDB (free)
    private string $newsBaseUrl = 'https://newsapi.org/v2';
    private string $footballApiBaseUrl = 'https://apiv3.apifootball.com'; // APIFootball.com (free) - corrected URL
    private string $sportsDbBaseUrl = 'https://www.thesportsdb.com/api/v1/json'; // TheSportsDB (free)

    public function __construct()
    {
        $this->newsApiKey = config('services.news_api.api_key');
        $this->footballApiKey = config('services.football_api.api_key');
        $this->sportsDbApiKey = config('services.sports_db.api_key', '123'); // Free tier uses '123'
    }

    /**
     * Get team-specific news articles
     */
    public function getTeamNews(string $teamName, int $limit = 5): array
    {
        $cacheKey = "team_news_{$teamName}_{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(2), function () use ($teamName, $limit) {
            try {
                if (is_null($this->newsApiKey) || empty($this->newsApiKey)) {
                    Log::warning('NewsAPI key not configured');
                    return $this->getMockNews($teamName, $limit);
                }

                $response = Http::withHeaders([
                    'X-API-Key' => $this->newsApiKey
                ])->get($this->newsBaseUrl . '/everything', [
                    'q' => "\"{$teamName}\" AND (Premier League OR football OR soccer)",
                    'language' => 'en',
                    'sortBy' => 'publishedAt',
                    'pageSize' => $limit,
                    'from' => Carbon::now()->subDays(7)->toDateString()
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatNewsData($data['articles'] ?? []);
                }

                Log::error('Failed to fetch team news', [
                    'team' => $teamName,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return $this->getMockNews($teamName, $limit);
            } catch (\Exception $e) {
                Log::error('Exception fetching team news', [
                    'team' => $teamName,
                    'message' => $e->getMessage()
                ]);
                return $this->getMockNews($teamName, $limit);
            }
        });
    }

    /**
     * Get player injuries for a team using APIFootball.com
     */
    public function getTeamInjuries(int $teamId): array
    {
        $cacheKey = "team_injuries_{$teamId}";
        
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($teamId) {
            try {
                if (is_null($this->footballApiKey) || empty($this->footballApiKey)) {
                    Log::warning('APIFootball key not configured');
                    return [];
                }

                // Get Premier League team data with players
                $response = Http::get($this->footballApiBaseUrl, [
                    'action' => 'get_teams',
                    'league_id' => 152, // Premier League ID in APIFootball
                    'APIkey' => $this->footballApiKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->extractInjuriesFromTeams($data, $teamId);
                }

                Log::error('Failed to fetch team data from APIFootball', [
                    'team_id' => $teamId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Exception fetching team injuries', [
                    'team_id' => $teamId,
                    'message' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Extract injury data from APIFootball teams response
     */
    private function extractInjuriesFromTeams(array $teams, int $ourTeamId): array
    {
        // Map our team names to APIFootball team names
        $teamMapping = $this->getTeamMapping();
        
        // Get our team name
        $ourTeam = \App\Models\Team::find($ourTeamId);
        if (!$ourTeam) {
            return [];
        }

        $injuries = [];
        
        foreach ($teams as $team) {
            // Check if this is our team
            if ($this->isMatchingTeam($team['team_name'], $ourTeam->name)) {
                if (isset($team['players']) && is_array($team['players'])) {
                    foreach ($team['players'] as $player) {
                        if (isset($player['player_injured']) && $player['player_injured'] === 'Yes') {
                            $injuries[] = [
                                'player_name' => $player['player_name'] ?? 'Unknown Player',
                                'position' => $player['player_type'] ?? 'Unknown',
                                'injury_type' => 'Injury',
                                'reason' => 'Currently injured',
                                'expected_return' => 'Unknown',
                                'status' => 'ongoing',
                                'severity' => 'moderate',
                                'player_number' => $player['player_number'] ?? null,
                                'age' => $player['player_age'] ?? null
                            ];
                        }
                    }
                }
                break;
            }
        }

        return $injuries;
    }

    /**
     * Check if team names match (precise matching to avoid cross-team contamination)
     */
    private function isMatchingTeam(string $apiTeamName, string $ourTeamName): bool
    {
        $apiTeamName = strtolower(trim($apiTeamName));
        $ourTeamName = strtolower(trim($ourTeamName));
        
        // Exact match first
        if ($apiTeamName === $ourTeamName) {
            return true;
        }
        
        // Use specific team mappings to avoid confusion between similar teams
        $teamMappings = [
            'manchester city' => ['manchester city fc', 'manchester city'],
            'manchester united' => ['manchester united fc', 'manchester united'],
            'manchester united fc' => ['manchester united', 'manchester united fc'],
            'arsenal fc' => ['arsenal', 'arsenal fc'],
            'chelsea fc' => ['chelsea', 'chelsea fc'],
            'liverpool fc' => ['liverpool', 'liverpool fc'],
            'tottenham hotspur fc' => ['tottenham', 'tottenham hotspur', 'spurs'],
            'newcastle united fc' => ['newcastle', 'newcastle united'],
            'brighton & hove albion fc' => ['brighton', 'brighton & hove albion'],
            'west ham united fc' => ['west ham', 'west ham united'],
        ];
        
        // Check if our team name has specific mappings
        foreach ($teamMappings as $key => $variations) {
            if (in_array($ourTeamName, $variations) && in_array($apiTeamName, $variations)) {
                return true;
            }
        }
        
        // Only allow FC suffix removal for exact matches to avoid confusion
        $apiWithoutFc = str_replace(' fc', '', $apiTeamName);
        $ourWithoutFc = str_replace(' fc', '', $ourTeamName);
        
        if ($apiWithoutFc === $ourWithoutFc) {
            return true;
        }
        
        return false;
    }

    /**
     * Get team name mapping for better matching
     */
    private function getTeamMapping(): array
    {
        return [
            'Arsenal FC' => 'Arsenal',
            'Chelsea FC' => 'Chelsea',
            'Liverpool FC' => 'Liverpool',
            'Manchester City FC' => 'Manchester City',
            'Manchester United FC' => 'Manchester United',
            'Tottenham Hotspur FC' => 'Tottenham',
            'Newcastle United FC' => 'Newcastle United',
            'Brighton & Hove Albion FC' => 'Brighton',
            'Aston Villa FC' => 'Aston Villa',
            'West Ham United FC' => 'West Ham',
            // Add more mappings as needed
        ];
    }

    /**
     * Get manager quotes and press conference highlights
     */
    public function getManagerQuotes(string $teamName, string $managerName = null, int $limit = 3): array
    {
        $cacheKey = "manager_quotes_{$teamName}_{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(4), function () use ($teamName, $managerName, $limit) {
            try {
                if (is_null($this->newsApiKey) || empty($this->newsApiKey)) {
                    return $this->getMockManagerQuotes($teamName, $limit);
                }

                $searchQuery = $managerName 
                    ? "\"{$managerName}\" AND \"{$teamName}\" AND (press conference OR interview OR quotes)"
                    : "\"{$teamName}\" AND manager AND (press conference OR interview OR quotes)";

                $response = Http::withHeaders([
                    'X-API-Key' => $this->newsApiKey
                ])->get($this->newsBaseUrl . '/everything', [
                    'q' => $searchQuery,
                    'language' => 'en',
                    'sortBy' => 'publishedAt',
                    'pageSize' => $limit,
                    'from' => Carbon::now()->subDays(3)->toDateString()
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatManagerQuotesData($data['articles'] ?? []);
                }

                return $this->getMockManagerQuotes($teamName, $limit);
            } catch (\Exception $e) {
                Log::error('Exception fetching manager quotes', [
                    'team' => $teamName,
                    'message' => $e->getMessage()
                ]);
                return $this->getMockManagerQuotes($teamName, $limit);
            }
        });
    }

    /**
     * Format news data for frontend consumption
     */
    private function formatNewsData(array $articles): array
    {
        return array_map(function ($article) {
            return [
                'title' => $article['title'] ?? 'No title',
                'description' => $article['description'] ?? '',
                'url' => $article['url'] ?? '#',
                'source' => $article['source']['name'] ?? 'Unknown',
                'published_at' => $article['publishedAt'] ? Carbon::parse($article['publishedAt'])->diffForHumans() : '',
                'image_url' => $article['urlToImage'] ?? null,
                'content_snippet' => $this->extractSnippet($article['content'] ?? $article['description'] ?? '')
            ];
        }, $articles);
    }

    /**
     * Format squad data as injury info (since free APIs don't have injury data)
     */
    private function formatSquadAsInjuries(array $data): array
    {
        // Since free APIs don't have injury data, we'll show squad info instead
        // or return empty for "no injuries"
        return []; // No injuries = healthy squad
    }

    /**
     * Format SportsDB squad data
     */
    private function formatSportsDbSquad(array $players): array
    {
        // SportsDB doesn't have injury data, so return empty (healthy squad)
        return [];
    }

    /**
     * Format injuries data for frontend consumption
     */
    private function formatInjuriesData(array $injuries): array
    {
        return array_map(function ($injury) {
            return [
                'player_name' => $injury['player']['name'] ?? 'Unknown Player',
                'injury_type' => $injury['player']['injury']['type'] ?? 'Injury',
                'reason' => $injury['player']['injury']['reason'] ?? 'Not specified',
                'expected_return' => $injury['player']['injury']['expected'] ?? 'Unknown',
                'status' => $this->getInjuryStatus($injury['player']['injury']['expected'] ?? null),
                'severity' => $this->getInjurySeverity($injury['player']['injury']['reason'] ?? ''),
                'position' => $injury['player']['position'] ?? 'Unknown'
            ];
        }, $injuries);
    }

    /**
     * Format manager quotes data
     */
    private function formatManagerQuotesData(array $articles): array
    {
        return array_map(function ($article) {
            return [
                'title' => $article['title'] ?? 'No title',
                'quote' => $this->extractQuote($article['description'] ?? $article['content'] ?? ''),
                'source' => $article['source']['name'] ?? 'Unknown',
                'published_at' => $article['publishedAt'] ? Carbon::parse($article['publishedAt'])->diffForHumans() : '',
                'url' => $article['url'] ?? '#',
                'relevance' => $this->calculateQuoteRelevance($article['title'] ?? '', $article['description'] ?? '')
            ];
        }, $articles);
    }

    /**
     * Extract meaningful snippet from content
     */
    private function extractSnippet(string $content, int $maxLength = 150): string
    {
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        return substr($content, 0, $maxLength) . '...';
    }

    /**
     * Extract quote from article content
     */
    private function extractQuote(string $content): string
    {
        // Look for quoted text
        if (preg_match('/"([^"]+)"/', $content, $matches)) {
            return '"' . $matches[1] . '"';
        }
        
        // Fallback to snippet
        return $this->extractSnippet($content, 100);
    }

    /**
     * Get injury status based on expected return date
     */
    private function getInjuryStatus(?string $expectedReturn): string
    {
        if (!$expectedReturn || $expectedReturn === 'Unknown') {
            return 'ongoing';
        }
        
        try {
            $returnDate = Carbon::parse($expectedReturn);
            if ($returnDate->isPast()) {
                return 'recovered';
            } elseif ($returnDate->diffInDays() <= 7) {
                return 'returning_soon';
            } else {
                return 'long_term';
            }
        } catch (\Exception $e) {
            return 'ongoing';
        }
    }

    /**
     * Determine injury severity
     */
    private function getInjurySeverity(string $reason): string
    {
        $severityKeywords = [
            'minor' => ['strain', 'fatigue', 'knock', 'bruise'],
            'moderate' => ['sprain', 'muscle', 'hamstring', 'calf', 'groin'],
            'major' => ['fracture', 'ligament', 'surgery', 'operation', 'ACL', 'meniscus']
        ];
        
        $reason = strtolower($reason);
        
        foreach ($severityKeywords as $severity => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($reason, $keyword) !== false) {
                    return $severity;
                }
            }
        }
        
        return 'moderate';
    }

    /**
     * Calculate quote relevance for sorting
     */
    private function calculateQuoteRelevance(string $title, string $description): int
    {
        $relevanceKeywords = [
            'press conference' => 10,
            'interview' => 8,
            'quotes' => 7,
            'says' => 5,
            'manager' => 6,
            'coach' => 6,
            'ahead of' => 4,
            'match preview' => 9
        ];
        
        $content = strtolower($title . ' ' . $description);
        $score = 0;
        
        foreach ($relevanceKeywords as $keyword => $points) {
            if (strpos($content, $keyword) !== false) {
                $score += $points;
            }
        }
        
        return $score;
    }

    /**
     * Mock news data for fallback
     */
    private function getMockNews(string $teamName, int $limit): array
    {
        $mockNews = [
            [
                'title' => "{$teamName} Prepare for Upcoming Fixture",
                'description' => "The team is working hard in training ahead of their next match.",
                'url' => '#',
                'source' => 'Team News',
                'published_at' => '2 hours ago',
                'image_url' => null,
                'content_snippet' => "Training sessions have been intense as the squad prepares for their upcoming fixture..."
            ],
            [
                'title' => "Latest Updates from {$teamName}",
                'description' => "Check out the latest news and updates from the club.",
                'url' => '#',
                'source' => 'Club News',
                'published_at' => '6 hours ago',
                'image_url' => null,
                'content_snippet' => "The club continues to work on strengthening the squad..."
            ]
        ];

        return array_slice($mockNews, 0, $limit);
    }

    /**
     * Mock injuries data for fallback
     */
    private function getMockInjuries(int $teamId): array
    {
        return [
            [
                'player_name' => 'Player Name',
                'injury_type' => 'Muscle Strain',
                'reason' => 'Hamstring strain',
                'expected_return' => '2 weeks',
                'status' => 'ongoing',
                'severity' => 'moderate',
                'position' => 'Midfielder'
            ]
        ];
    }

    /**
     * Mock manager quotes for fallback
     */
    private function getMockManagerQuotes(string $teamName, int $limit): array
    {
        $mockQuotes = [
            [
                'title' => 'Manager Speaks Ahead of Next Match',
                'quote' => '"We are focused on our next game and preparing well."',
                'source' => 'Press Conference',
                'published_at' => '1 hour ago',
                'url' => '#',
                'relevance' => 8
            ]
        ];

        return array_slice($mockQuotes, 0, $limit);
    }
}
