<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FootballDataService
{
    private string $baseUrl = 'https://api.football-data.org/v4';
    private string $apiKey;
    private int $premierLeagueId = 2021; // Premier League competition ID

    public function __construct()
    {
        $this->apiKey = config('services.football_data.api_key', '');
    }

    /**
     * Get Premier League teams for the current season
     */
    public function getPremierLeagueTeams(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey
            ])->get("{$this->baseUrl}/competitions/{$this->premierLeagueId}/teams");

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatTeamsData($data['teams'] ?? []);
            }

            Log::error('Failed to fetch Premier League teams', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception fetching Premier League teams', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get Premier League matches and generate gameweeks
     */
    public function getPremierLeagueFixtures(): array
    {
        try {
            // Get current season matches
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey
            ])->get("{$this->baseUrl}/competitions/{$this->premierLeagueId}/matches", [
                'season' => $this->getCurrentSeason()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatGameweeksData($data['matches'] ?? []);
            }

            Log::error('Failed to fetch Premier League fixtures', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception fetching Premier League fixtures', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get detailed games data for each match
     */
    public function getPremierLeagueGames(): array
    {
        try {
            // Get current season matches
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey
            ])->get("{$this->baseUrl}/competitions/{$this->premierLeagueId}/matches", [
                'season' => $this->getCurrentSeason()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatGamesData($data['matches'] ?? []);
            }

            Log::error('Failed to fetch Premier League games', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception fetching Premier League games', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Format teams data for database insertion
     */
    private function formatTeamsData(array $teams): array
    {
        return collect($teams)->map(function ($team) {
            // Extract short name from team name or use TLA
            $shortName = $team['tla'] ?? $this->generateShortName($team['name']);
            
            // Try to extract colors from crest URL or use defaults
            $colors = $this->extractTeamColors($team);

            return [
                'name' => $team['name'],
                'short_name' => $shortName,
                'primary_color' => $colors['primary'],
                'secondary_color' => $colors['secondary'],
                'logo_url' => $team['crest'] ?? null,
                'external_id' => $team['id'],
                'founded' => $team['founded'] ?? null,
                'venue' => $team['venue'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Format gameweeks data from fixtures
     */
    private function formatGameweeksData(array $matches): array
    {
        // Group matches by matchday (gameweek)
        $matchdays = collect($matches)
            ->where('stage', 'REGULAR_SEASON')
            ->groupBy('matchday')
            ->map(function ($matchdayMatches, $matchday) {
                $firstMatch = $matchdayMatches->first();
                $lastMatch = $matchdayMatches->last();
                
                // Calculate start and end dates for the gameweek
                $startDate = Carbon::parse($firstMatch['utcDate'])->startOfDay();
                $endDate = Carbon::parse($lastMatch['utcDate'])->endOfDay();
                
                // Extend the gameweek to cover a full week if needed
                if ($startDate->diffInDays($endDate) < 6) {
                    $endDate = $startDate->copy()->addDays(6);
                }

                return [
                    'week_number' => $matchday,
                    'name' => "Gameweek {$matchday}",
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'is_completed' => $this->isGameweekCompleted($matchdayMatches->toArray()),
                ];
            })
            ->values()
            ->toArray();

        return $matchdays;
    }

    /**
     * Format individual games data from fixtures
     */
    private function formatGamesData(array $matches): array
    {
        return collect($matches)
            ->where('stage', 'REGULAR_SEASON')
            ->map(function ($match) {
                // Map API status to our status
                $status = match($match['status']) {
                    'SCHEDULED', 'TIMED' => 'SCHEDULED',
                    'IN_PLAY', 'PAUSED' => 'LIVE',
                    'FINISHED' => 'FINISHED',
                    'POSTPONED' => 'POSTPONED',
                    'CANCELLED' => 'CANCELLED',
                    default => 'SCHEDULED'
                };

                return [
                    'matchday' => $match['matchday'],
                    'home_team_name' => $match['homeTeam']['name'],
                    'away_team_name' => $match['awayTeam']['name'],
                    'home_team_external_id' => $match['homeTeam']['id'],
                    'away_team_external_id' => $match['awayTeam']['id'],
                    'kick_off_time' => Carbon::parse($match['utcDate']),
                    'home_score' => $match['score']['fullTime']['home'],
                    'away_score' => $match['score']['fullTime']['away'],
                    'status' => $status,
                    'external_id' => $match['id'],
                ];
            })
            ->toArray();
    }

    /**
     * Check if a gameweek is completed
     */
    private function isGameweekCompleted(array $matches): bool
    {
        return collect($matches)->every(function ($match) {
            return $match['status'] === 'FINISHED';
        });
    }

    /**
     * Generate a short name from team name
     */
    private function generateShortName(string $teamName): string
    {
        // Remove common words and take first 3 letters of significant words
        $words = preg_split('/\s+/', $teamName);
        $significantWords = array_filter($words, function ($word) {
            return !in_array(strtolower($word), ['fc', 'united', 'city', 'town', 'rovers', 'athletic', '&']);
        });

        if (count($significantWords) >= 2) {
            return strtoupper(substr($significantWords[0], 0, 2) . substr($significantWords[1], 0, 1));
        }

        return strtoupper(substr($teamName, 0, 3));
    }

    /**
     * Extract or generate team colors
     */
    private function extractTeamColors(array $team): array
    {
        // Updated colors map based on actual 2025/26 Premier League teams from API
        $defaultColors = [
            // Current Premier League teams (2025/26 season)
            'Arsenal FC' => ['primary' => '#EF0107', 'secondary' => '#9C824A'],
            'Arsenal' => ['primary' => '#EF0107', 'secondary' => '#9C824A'],
            
            'Aston Villa FC' => ['primary' => '#95BFE5', 'secondary' => '#670E36'],
            'Aston Villa' => ['primary' => '#95BFE5', 'secondary' => '#670E36'],
            
            'Brighton & Hove Albion FC' => ['primary' => '#0057B8', 'secondary' => '#FFCD00'],
            'Brighton' => ['primary' => '#0057B8', 'secondary' => '#FFCD00'],
            
            'Chelsea FC' => ['primary' => '#034694', 'secondary' => '#DBA111'],
            'Chelsea' => ['primary' => '#034694', 'secondary' => '#DBA111'],
            
            'Crystal Palace FC' => ['primary' => '#1B458F', 'secondary' => '#A7A5A6'],
            'Crystal Palace' => ['primary' => '#1B458F', 'secondary' => '#A7A5A6'],
            
            'Everton FC' => ['primary' => '#003399', 'secondary' => '#FFFFFF'],
            'Everton' => ['primary' => '#003399', 'secondary' => '#FFFFFF'],
            
            'Fulham FC' => ['primary' => '#FFFFFF', 'secondary' => '#000000'],
            'Fulham' => ['primary' => '#FFFFFF', 'secondary' => '#000000'],
            
            'Liverpool FC' => ['primary' => '#C8102E', 'secondary' => '#F6EB61'],
            'Liverpool' => ['primary' => '#C8102E', 'secondary' => '#F6EB61'],
            
            'Manchester City FC' => ['primary' => '#6CABDD', 'secondary' => '#1C2C5B'],
            'Manchester City' => ['primary' => '#6CABDD', 'secondary' => '#1C2C5B'],
            
            'Manchester United FC' => ['primary' => '#DA020E', 'secondary' => '#FFF200'],
            'Manchester United' => ['primary' => '#DA020E', 'secondary' => '#FFF200'],
            
            'Newcastle United FC' => ['primary' => '#241F20', 'secondary' => '#FFFFFF'],
            'Newcastle' => ['primary' => '#241F20', 'secondary' => '#FFFFFF'],
            
            'Nottingham Forest FC' => ['primary' => '#DD0000', 'secondary' => '#FFFFFF'],
            'Nottingham Forest' => ['primary' => '#DD0000', 'secondary' => '#FFFFFF'],
            
            'Tottenham Hotspur FC' => ['primary' => '#132257', 'secondary' => '#FFFFFF'],
            'Tottenham' => ['primary' => '#132257', 'secondary' => '#FFFFFF'],
            
            'West Ham United FC' => ['primary' => '#7A263A', 'secondary' => '#1BB1E7'],
            'West Ham' => ['primary' => '#7A263A', 'secondary' => '#1BB1E7'],
            
            'Wolverhampton Wanderers FC' => ['primary' => '#FDB462', 'secondary' => '#231F20'],
            'Wolverhampton' => ['primary' => '#FDB462', 'secondary' => '#231F20'],
            'Wolves' => ['primary' => '#FDB462', 'secondary' => '#231F20'],
            
            'Brentford FC' => ['primary' => '#E30613', 'secondary' => '#FEE133'],
            'Brentford' => ['primary' => '#E30613', 'secondary' => '#FEE133'],
            
            'AFC Bournemouth' => ['primary' => '#DA020E', 'secondary' => '#000000'],
            'Bournemouth' => ['primary' => '#DA020E', 'secondary' => '#000000'],
            
            // Teams that appear in API but may not be current PL (keeping for completeness)
            'Sunderland AFC' => ['primary' => '#EB172B', 'secondary' => '#FFFFFF'],
            'Sunderland' => ['primary' => '#EB172B', 'secondary' => '#FFFFFF'],
            
            'Burnley FC' => ['primary' => '#6C1D45', 'secondary' => '#99D6EA'],
            'Burnley' => ['primary' => '#6C1D45', 'secondary' => '#99D6EA'],
            
            'Leeds United FC' => ['primary' => '#FFFFFF', 'secondary' => '#1D428A'],
            'Leeds' => ['primary' => '#FFFFFF', 'secondary' => '#1D428A'],
            
            // Potential promoted teams (2025/26 season)
            'Leicester City FC' => ['primary' => '#003090', 'secondary' => '#FDBE11'],
            'Leicester' => ['primary' => '#003090', 'secondary' => '#FDBE11'],
            
            'Ipswich Town FC' => ['primary' => '#4C9AE6', 'secondary' => '#FFFFFF'],
            'Ipswich' => ['primary' => '#4C9AE6', 'secondary' => '#FFFFFF'],
            
            'Southampton FC' => ['primary' => '#D71920', 'secondary' => '#FFC20E'],
            'Southampton' => ['primary' => '#D71920', 'secondary' => '#FFC20E'],
        ];

        $teamName = $team['name'];
        
        // Try exact match first
        if (isset($defaultColors[$teamName])) {
            return $defaultColors[$teamName];
        }
        
        // Try partial matches for team names
        foreach ($defaultColors as $colorTeamName => $colors) {
            if (stripos($teamName, $colorTeamName) !== false) {
                return $colors;
            }
        }

        // Default fallback colors (dark gray/white)
        return [
            'primary' => '#1f2937',
            'secondary' => '#ffffff'
        ];
    }

    /**
     * Get current football season year
     */
    private function getCurrentSeason(): int
    {
        $now = Carbon::now();
        // Football season runs from August to May
        // For 2025/2026 season, we want to return 2025
        if ($now->month >= 8) {
            return $now->year;
        }
        // If we're between January and July, we're still in the previous season
        // But since we want the UPCOMING season (2025/26), we'll return current year
        return $now->year;
    }

    /**
     * Check if API is available and configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
