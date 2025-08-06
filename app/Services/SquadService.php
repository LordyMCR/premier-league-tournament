<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Player;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SquadService
{
    private FootballDataService $footballDataService;
    private string $apiFootballBaseUrl = 'https://v3.football.api-sports.io';
    private string $apiFootballKey;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->footballDataService = $footballDataService;
        $this->apiFootballKey = config('services.football_api.api_key', '');
    }

    /**
     * Fetch and store squad data for all teams in bulk from API
     */
    public function fetchAndStoreAllSquadData(bool $forceUpdate = false): array
    {
        try {
            Log::info('Attempting bulk squad data fetch...');
            
            // Try bulk fetch from Football Data API first
            $allSquadData = $this->fetchAllSquadsFromFootballData();
            
            if (empty($allSquadData)) {
                Log::info('Bulk fetch failed, falling back to individual team fetches...');
                return $this->fetchIndividualTeams($forceUpdate);
            }

            $results = ['success' => 0, 'errors' => 0, 'teams' => []];
            
            foreach ($allSquadData as $teamData) {
                try {
                    $team = Team::where('external_id', $teamData['external_id'])->first();
                    if ($team && !empty($teamData['squad'])) {
                        $this->storeSquadData($team, $teamData['squad']);
                        $results['success']++;
                        $results['teams'][] = $team->name . ' ✓';
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error("Error storing squad data: " . $e->getMessage());
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error("Error in bulk squad fetch: " . $e->getMessage());
            return $this->fetchIndividualTeams($forceUpdate);
        }
    }

    /**
     * Fetch all Premier League squads in one API call
     */
    private function fetchAllSquadsFromFootballData(): array
    {
        try {
            // Get all teams with their squads in one call
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key')
            ])->get("https://api.football-data.org/v4/competitions/2021/teams");

            if ($response->successful()) {
                $data = $response->json();
                $teams = [];
                
                foreach ($data['teams'] as $teamData) {
                    $teams[] = [
                        'external_id' => $teamData['id'],
                        'name' => $teamData['name'],
                        'squad' => $this->formatFootballDataSquad($teamData['squad'] ?? [])
                    ];
                }
                
                return $teams;
            }
        } catch (\Exception $e) {
            Log::error("Bulk Football Data API fetch failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Fallback to individual team fetching
     */
    private function fetchIndividualTeams(bool $forceUpdate = false): array
    {
        $teams = Team::all();
        $results = ['success' => 0, 'errors' => 0, 'teams' => []];
        
        foreach ($teams as $team) {
            try {
                $success = $this->fetchAndStoreSquadData($team, $forceUpdate);
                if ($success) {
                    $results['success']++;
                    $results['teams'][] = $team->name . ' ✓';
                } else {
                    $results['errors']++;
                    $results['teams'][] = $team->name . ' ✗';
                }
                
                // Add delay to avoid rate limiting
                sleep(1);
            } catch (\Exception $e) {
                $results['errors']++;
                $results['teams'][] = $team->name . ' ✗ Error';
            }
        }
        
        return $results;
    }

    /**
     * Fetch and store squad data for a team from API
     */
    public function fetchAndStoreSquadData(Team $team, bool $forceUpdate = false): bool
    {
        try {
            // Check if we need to update (avoid hitting API too frequently)
            if (!$forceUpdate && $this->hasRecentSquadData($team)) {
                return true;
            }

            // Try Football Data API first
            $squadData = $this->fetchSquadFromFootballData($team);
            
            // If that fails, try API-Football as fallback
            if (empty($squadData) && $this->apiFootballKey) {
                $squadData = $this->fetchSquadFromApiFootball($team);
            }

            if (!empty($squadData)) {
                $this->storeSquadData($team, $squadData);
                return true;
            }

            Log::warning("No squad data found for team: {$team->name}");
            return false;

        } catch (\Exception $e) {
            Log::error("Error fetching squad data for team {$team->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch squad data from Football Data API
     */
    private function fetchSquadFromFootballData(Team $team): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key')
            ])->get("https://api.football-data.org/v4/teams/{$team->external_id}");

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatFootballDataSquad($data['squad'] ?? []);
            }
        } catch (\Exception $e) {
            Log::error("Football Data API squad fetch failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Fetch squad data from API-Football (fallback)
     */
    private function fetchSquadFromApiFootball(Team $team): array
    {
        try {
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiFootballKey,
                'X-RapidAPI-Host' => 'v3.football.api-sports.io'
            ])->get("{$this->apiFootballBaseUrl}/players/squads", [
                'team' => $team->external_id
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatApiFootballSquad($data['response'][0]['players'] ?? []);
            }
        } catch (\Exception $e) {
            Log::error("API-Football squad fetch failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Format squad data from Football Data API
     */
    private function formatFootballDataSquad(array $squad): array
    {
        return collect($squad)->map(function ($player) {
            return [
                'external_id' => $player['id'],
                'name' => $player['name'],
                'position' => $this->mapPosition($player['position']),
                'detailed_position' => $player['position'],
                'shirt_number' => $player['shirtNumber'] ?? null,  // Football Data API doesn't provide shirt numbers
                'date_of_birth' => isset($player['dateOfBirth']) ? Carbon::parse($player['dateOfBirth'])->format('Y-m-d') : null,
                'nationality' => $player['nationality'] ?? null,
                'photo_url' => null, // Football Data API doesn't provide photos
            ];
        })->toArray();
    }

    /**
     * Format squad data from API-Football
     */
    private function formatApiFootballSquad(array $squad): array
    {
        return collect($squad)->map(function ($player) {
            return [
                'external_id' => $player['id'],
                'name' => $player['name'],
                'position' => $this->mapPosition($player['position']),
                'detailed_position' => $player['position'],
                'date_of_birth' => isset($player['birth']['date']) ? $player['birth']['date'] : null,
                'nationality' => $player['birth']['country'] ?? null,
                'photo_url' => $player['photo'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Store squad data in database
     */
    private function storeSquadData(Team $team, array $squadData): void
    {
        foreach ($squadData as $playerData) {
            $player = Player::updateOrCreate(
                [
                    'external_id' => $playerData['external_id'],
                    'team_id' => $team->id
                ],
                array_merge($playerData, [
                    'last_profile_update' => now()
                ])
            );
        }
    }

    /**
     * Get recent transfers for a team
     */
    public function getRecentTransfers(Team $team, int $days = 90): array
    {
        // Transfer data is not available with the current API setup
        return [
            'incoming' => [],
            'outgoing' => [],
        ];
    }

    /**
     * Get squad organized by position
     */
    public function getSquadByPosition(Team $team): array
    {
        $players = $team->players()->orderBy('shirt_number')->get();
        
        return [
            'goalkeepers' => $players->where('position', 'Goalkeeper')->values(),
            'defenders' => $players->where('position', 'Defender')->values(),
            'midfielders' => $players->where('position', 'Midfielder')->values(),
            'attackers' => $players->where('position', 'Attacker')->values(),
        ];
    }

    /**
     * Check if team has recent squad data
     */
    private function hasRecentSquadData(Team $team): bool
    {
        $lastUpdate = $team->players()
                          ->max('last_profile_update');
        
        if (!$lastUpdate) {
            return false;
        }
        
        return Carbon::parse($lastUpdate)->diffInDays(now()) < 7;
    }

    /**
     * Map API position to our standardized positions
     */
    private function mapPosition(string $position): string
    {
        $position = strtolower($position);
        
        if (str_contains($position, 'goalkeeper') || str_contains($position, 'keeper')) {
            return 'Goalkeeper';
        }
        
        if (str_contains($position, 'defender') || str_contains($position, 'back')) {
            return 'Defender';
        }
        
        if (str_contains($position, 'midfielder') || str_contains($position, 'midfield')) {
            return 'Midfielder';
        }
        
        if (str_contains($position, 'forward') || str_contains($position, 'attacker') || str_contains($position, 'winger')) {
            return 'Attacker';
        }
        
        return 'Midfielder'; // Default fallback
    }

    
}
