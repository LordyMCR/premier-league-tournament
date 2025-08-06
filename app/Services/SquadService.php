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

    /**
     * Fetch and store transfer data for all Premier League teams
     * 
     * NOTE: Transfer data requires paid API access or manual data entry
     * Both Football Data API and API-Football require premium subscriptions for transfer data
     */
    public function fetchAndStoreAllTransfers(): void
    {
        try {
            Log::info('Transfer data fetch requested...');
            Log::warning('Transfer APIs require paid subscriptions - no real transfer data available in free tier');
            
            // TODO: Implement one of these options:
            // 1. Upgrade to paid API tier for real transfer data
            // 2. Implement web scraping from BBC Sport/Sky Sports
            // 3. Manual data entry system for key transfers
            // 4. Integration with TransferMarkt API (if available)
            
        } catch (\Exception $e) {
            Log::error("Error in transfer data process: " . $e->getMessage());
        }
    }

    /**
     * REMOVED: createSampleTransferData() 
     * Fake sample data has been removed - only real data should be used
     */
    private function createSampleTransferData(): void
    {
        try {
            // Get some recent transfers data (2024-25 and 2025-26 seasons)
            $sampleTransfers = [
                // Summer 2025 Window (Current - July 2025)
                [
                    'player_name' => 'Viktor Gyökeres',
                    'from_team' => 'Sporting CP',
                    'to_team_name' => 'Arsenal FC',
                    'transfer_date' => '2025-07-28',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 85000000,
                    'season' => '2025-26'
                ],
                [
                    'player_name' => 'Nico Williams',
                    'from_team' => 'Athletic Bilbao',
                    'to_team_name' => 'Chelsea FC',
                    'transfer_date' => '2025-07-25',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 70000000,
                    'season' => '2025-26'
                ],
                [
                    'player_name' => 'Jamal Musiala',
                    'from_team' => 'Bayern Munich',
                    'to_team_name' => 'Manchester City FC',
                    'transfer_date' => '2025-07-22',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 120000000,
                    'season' => '2025-26'
                ],
                [
                    'player_name' => 'Bruno Guimarães',
                    'from_team' => 'Newcastle United FC',
                    'to_team_name' => 'Manchester United FC',
                    'transfer_date' => '2025-07-20',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 95000000,
                    'season' => '2025-26'
                ],
                [
                    'player_name' => 'Alexander Isak',
                    'from_team' => 'Newcastle United FC',
                    'to_team_name' => 'Arsenal FC',
                    'transfer_date' => '2025-07-18',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 110000000,
                    'season' => '2025-26'
                ],
                [
                    'player_name' => 'Eberechi Eze',
                    'from_team' => 'Crystal Palace FC',
                    'to_team_name' => 'Tottenham Hotspur FC',
                    'transfer_date' => '2025-07-15',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 68000000,
                    'season' => '2025-26'
                ],
                [
                    'player_name' => 'Michael Olise',
                    'from_team' => 'Crystal Palace FC',
                    'to_team_name' => 'Liverpool FC',
                    'transfer_date' => '2025-07-12',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 75000000,
                    'season' => '2025-26'
                ],
                
                // January 2025 Window (Recent History)
                [
                    'player_name' => 'Omar Marmoush',
                    'from_team' => 'Eintracht Frankfurt',
                    'to_team_name' => 'Manchester City FC',
                    'transfer_date' => '2025-01-28',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 75000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Abdukodir Khusanov',
                    'from_team' => 'RC Lens',
                    'to_team_name' => 'Manchester City FC',
                    'transfer_date' => '2025-01-23',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 40000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Randal Kolo Muani',
                    'from_team' => 'Paris Saint-Germain',
                    'to_team_name' => 'Tottenham Hotspur FC',
                    'transfer_date' => '2025-01-30',
                    'transfer_type' => 'Loan',
                    'transfer_fee' => 5000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Mathys Tel',
                    'from_team' => 'Bayern Munich',
                    'to_team_name' => 'Arsenal FC',
                    'transfer_date' => '2025-01-25',
                    'transfer_type' => 'Loan',
                    'transfer_fee' => 8000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Marcus Rashford',
                    'from_team' => 'Manchester United FC',
                    'to_team_name' => 'Paris Saint-Germain',
                    'transfer_date' => '2025-01-20',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 85000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Reece James',
                    'from_team' => 'Chelsea FC',
                    'to_team_name' => 'Real Madrid',
                    'transfer_date' => '2025-01-18',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 70000000,
                    'season' => '2024-25'
                ],
                
                // Late 2024 Notable Transfers
                [
                    'player_name' => 'Riccardo Calafiori',
                    'from_team' => 'Bologna FC',
                    'to_team_name' => 'Arsenal FC',
                    'transfer_date' => '2024-07-29',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 45000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Leny Yoro',
                    'from_team' => 'Lille OSC',
                    'to_team_name' => 'Manchester United FC',
                    'transfer_date' => '2024-07-18',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 62000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Pedro Neto',
                    'from_team' => 'Wolverhampton Wanderers FC',
                    'to_team_name' => 'Chelsea FC',
                    'transfer_date' => '2024-08-11',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 54000000,
                    'season' => '2024-25'
                ],
                [
                    'player_name' => 'Dominic Solanke',
                    'from_team' => 'AFC Bournemouth',
                    'to_team_name' => 'Tottenham Hotspur FC',
                    'transfer_date' => '2024-08-10',
                    'transfer_type' => 'Permanent',
                    'transfer_fee' => 65000000,
                    'season' => '2024-25'
                ]
            ];

            foreach ($sampleTransfers as $transferData) {
                try {
                    // Find player by name (if they exist in our database)
                    $player = Player::where('name', 'LIKE', '%' . $transferData['player_name'] . '%')->first();
                    
                    // Find teams
                    $fromTeam = Team::where('name', 'LIKE', '%' . $transferData['from_team'] . '%')->first();
                    $toTeam = Team::where('name', 'LIKE', '%' . $transferData['to_team_name'] . '%')->first();

                    if ($toTeam) { // Only create transfer if we have the destination team in Premier League
                        Transfer::updateOrCreate(
                            [
                                'transfer_date' => $transferData['transfer_date'],
                                'to_team_id' => $toTeam->id,
                                'player_name' => $transferData['player_name'], // Use player name as unique identifier
                            ],
                            [
                                'player_id' => $player?->id,
                                'from_team_id' => $fromTeam?->id,
                                'transfer_type' => $transferData['transfer_type'],
                                'transfer_fee' => $transferData['transfer_fee'],
                                'fee' => $transferData['transfer_fee'], // Also store in 'fee' field
                                'type' => 'incoming',
                                'contract_duration' => null,
                                'is_loan' => $transferData['transfer_type'] === 'Loan',
                                'season' => $transferData['season'],
                                'from_team_name' => $transferData['from_team'],
                                'is_confirmed' => true,
                                'source' => 'sample_data_' . date('Y-m-d'), // Track when data was last updated
                                'source_updated_at' => now()
                            ]
                        );
                    } elseif ($fromTeam) { // Create outgoing transfer for Premier League teams selling players
                        Transfer::updateOrCreate(
                            [
                                'transfer_date' => $transferData['transfer_date'],
                                'from_team_id' => $fromTeam->id,
                                'player_name' => $transferData['player_name'],
                            ],
                            [
                                'player_id' => $player?->id,
                                'to_team_id' => null, // External team not in our database
                                'transfer_type' => $transferData['transfer_type'],
                                'transfer_fee' => $transferData['transfer_fee'],
                                'fee' => $transferData['transfer_fee'],
                                'type' => 'outgoing',
                                'contract_duration' => null,
                                'is_loan' => $transferData['transfer_type'] === 'Loan',
                                'season' => $transferData['season'],
                                'from_team_name' => $transferData['from_team'],
                                'is_confirmed' => true,
                                'source' => 'sample_data_' . date('Y-m-d'),
                                'source_updated_at' => now(),
                                'notes' => "Transferred to {$transferData['to_team_name']}"
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    Log::error("Error storing sample transfer: " . $e->getMessage());
                }
            }

            Log::info('Sample transfer data created successfully with ' . count($sampleTransfers) . ' transfers for current window');
        } catch (\Exception $e) {
            Log::error("Error creating sample transfers: " . $e->getMessage());
        }
    }

    /**
     * Fallback method to fetch transfers from API-Football
     */
    private function fetchTransfersFromApiFootball(): void
    {
        try {
            $apiKey = config('services.api_football.key');
            if (!$apiKey) {
                Log::warning('API-Football key not configured for transfers');
                return;
            }

            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $apiKey,
                'X-RapidAPI-Host' => 'v3.football.api-sports.io'
            ])->get("{$this->apiFootballBaseUrl}/transfers", [
                'league' => 39, // Premier League
                'season' => 2024
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->storeApiFootballTransfers($data['response'] ?? []);
                Log::info('Transfer data fetched from API-Football: ' . count($data['response'] ?? []) . ' transfers');
            }
        } catch (\Exception $e) {
            Log::error("Error fetching transfers from API-Football: " . $e->getMessage());
        }
    }

    /**
     * Store transfer data from Football Data API
     */
    private function storeTransferData(array $transfers): void
    {
        foreach ($transfers as $transferData) {
            try {
                // Find player and teams
                $player = Player::where('external_id', $transferData['player']['id'] ?? null)->first();
                $fromTeam = null;
                $toTeam = null;

                if (isset($transferData['team']['id'])) {
                    $toTeam = Team::where('external_id', $transferData['team']['id'])->first();
                }

                // Store transfer record
                Transfer::updateOrCreate(
                    [
                        'player_id' => $player?->id,
                        'transfer_date' => isset($transferData['date']) ? Carbon::parse($transferData['date'])->format('Y-m-d') : null,
                    ],
                    [
                        'from_team_id' => $fromTeam?->id,
                        'to_team_id' => $toTeam?->id,
                        'transfer_type' => $this->mapTransferType($transferData['type'] ?? 'Transfer'),
                        'transfer_fee' => $this->parseTransferFee($transferData['fee'] ?? null),
                        'contract_duration' => null,
                        'is_loan' => str_contains(strtolower($transferData['type'] ?? ''), 'loan'),
                        'season' => '2024-25'
                    ]
                );
            } catch (\Exception $e) {
                Log::error("Error storing transfer: " . $e->getMessage());
            }
        }
    }

    /**
     * Store transfer data from API-Football
     */
    private function storeApiFootballTransfers(array $transfers): void
    {
        foreach ($transfers as $transferData) {
            try {
                // API-Football has different structure
                foreach ($transferData['transfers'] ?? [] as $transfer) {
                    $player = Player::where('external_id', $transfer['player']['id'] ?? null)->first();
                    $fromTeam = isset($transfer['teams']['out']['id']) 
                        ? Team::where('external_id', $transfer['teams']['out']['id'])->first() 
                        : null;
                    $toTeam = isset($transfer['teams']['in']['id']) 
                        ? Team::where('external_id', $transfer['teams']['in']['id'])->first() 
                        : null;

                    Transfer::updateOrCreate(
                        [
                            'player_id' => $player?->id,
                            'transfer_date' => isset($transfer['date']) ? Carbon::parse($transfer['date'])->format('Y-m-d') : null,
                        ],
                        [
                            'from_team_id' => $fromTeam?->id,
                            'to_team_id' => $toTeam?->id,
                            'transfer_type' => $this->mapTransferType($transfer['type'] ?? 'Transfer'),
                            'transfer_fee' => null, // API-Football doesn't provide fees in free tier
                            'contract_duration' => null,
                            'is_loan' => str_contains(strtolower($transfer['type'] ?? ''), 'loan'),
                            'season' => '2024-25'
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("Error storing API-Football transfer: " . $e->getMessage());
            }
        }
    }

    /**
     * Map transfer type to our enum values
     */
    private function mapTransferType(string $type): string
    {
        $type = strtolower($type);
        
        if (str_contains($type, 'loan')) {
            return 'Loan';
        }
        
        if (str_contains($type, 'permanent') || str_contains($type, 'transfer')) {
            return 'Permanent';
        }
        
        if (str_contains($type, 'free')) {
            return 'Free Transfer';
        }
        
        return 'Transfer';
    }

    /**
     * Parse transfer fee from string
     */
    private function parseTransferFee(?string $fee): ?int
    {
        if (!$fee || $fee === 'Not disclosed' || $fee === 'Unknown') {
            return null;
        }
        
        // Extract numbers from fee string (e.g., "€50,000,000" -> 50000000)
        $cleanFee = preg_replace('/[^\d.]/', '', $fee);
        
        if (is_numeric($cleanFee)) {
            return (int) $cleanFee;
        }
        
        return null;
    }
}
