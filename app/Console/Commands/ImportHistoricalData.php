<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballDataService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportHistoricalData extends Command
{
    protected $signature = 'football:import-historical {--seasons=10 : Number of seasons to import}';
    protected $description = 'Import historical Premier League data for the last N seasons';

    private $footballService;
    private $premierLeagueId = 2021;

    public function __construct(FootballDataService $footballService)
    {
        parent::__construct();
        $this->footballService = $footballService;
    }

    public function handle()
    {
        $seasonsToImport = (int) $this->option('seasons');
        $currentYear = date('Y');
        $startYear = $currentYear - $seasonsToImport;

        $this->info("Importing {$seasonsToImport} seasons of Premier League data...");

        for ($year = $startYear; $year < $currentYear; $year++) {
            $this->importSeason($year);
            
            // Rate limiting - wait 6 seconds between seasons (10 requests per minute limit)
            if ($year < $currentYear - 1) {
                $this->info("Waiting 6 seconds to respect API rate limits...");
                sleep(6);
            }
        }

        $this->info("Historical data import complete!");
    }

    private function importSeason($year)
    {
        $this->info("Importing season {$year}-" . ($year + 1) . "...");

        try {
            $apiKey = config('services.football_data.api_key');
            
            // Fetch matches for this season
            $response = Http::withHeaders([
                'X-Auth-Token' => $apiKey
            ])->get("https://api.football-data.org/v4/competitions/{$this->premierLeagueId}/matches", [
                'season' => $year,
                'status' => 'FINISHED'
            ]);

            if (!$response->successful()) {
                $this->error("Failed to fetch data for season {$year}: " . $response->status());
                return;
            }

            $data = $response->json();
            $matches = $data['matches'] ?? [];

            if (empty($matches)) {
                $this->warn("No matches found for season {$year}");
                return;
            }

            // Process and save the data
            $processedData = $this->processSeasonData($matches, $year);
            
            // Save to storage
            $nextYear = $year + 1;
            $filename = "historical_data/premier_league_{$year}_{$nextYear}.json";
            Storage::disk('local')->put($filename, json_encode($processedData, JSON_PRETTY_PRINT));
            
            $this->info("✓ Season {$year}-{$nextYear} imported successfully (" . count($matches) . " matches)");

        } catch (\Exception $e) {
            $this->error("Error importing season {$year}: " . $e->getMessage());
            Log::error("Historical import error for season {$year}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function processSeasonData($matches, $season)
    {
        $teams = [];
        $processedMatches = [];

        foreach ($matches as $match) {
            // Extract team data
            $homeTeam = $match['homeTeam'];
            $awayTeam = $match['awayTeam'];
            
            // Store team info
            $teams[$homeTeam['id']] = [
                'id' => $homeTeam['id'],
                'name' => $homeTeam['name'],
                'short_name' => $homeTeam['shortName'] ?? $homeTeam['name'],
                'tla' => $homeTeam['tla'] ?? null,
                'crest' => $homeTeam['crest'] ?? null
            ];
            
            $teams[$awayTeam['id']] = [
                'id' => $awayTeam['id'],
                'name' => $awayTeam['name'],
                'short_name' => $awayTeam['shortName'] ?? $awayTeam['name'],
                'tla' => $awayTeam['tla'] ?? null,
                'crest' => $awayTeam['crest'] ?? null
            ];

            // Process match data
            $processedMatches[] = [
                'id' => $match['id'],
                'home_team_id' => $homeTeam['id'],
                'away_team_id' => $awayTeam['id'],
                'home_team_name' => $homeTeam['name'],
                'away_team_name' => $awayTeam['name'],
                'home_score' => $match['score']['fullTime']['home'] ?? 0,
                'away_score' => $match['score']['fullTime']['away'] ?? 0,
                'kick_off_time' => $match['utcDate'],
                'matchday' => $match['matchday'] ?? null,
                'status' => $match['status'],
                'venue' => $match['venue'] ?? null
            ];
        }

        // Calculate team statistics for this season
        $teamStats = $this->calculateTeamStats($processedMatches, $teams);

        return [
            'season' => "{$season}-" . ($season + 1),
            'year' => $season,
            'teams' => array_values($teams),
            'matches' => $processedMatches,
            'team_stats' => $teamStats,
            'imported_at' => now()->toISOString()
        ];
    }

    private function calculateTeamStats($matches, $teams)
    {
        $stats = [];

        // Initialize stats for all teams
        foreach ($teams as $teamId => $team) {
            $stats[$teamId] = [
                'team_id' => $teamId,
                'team_name' => $team['name'],
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'points' => 0,
                'home_wins' => 0,
                'home_draws' => 0,
                'home_losses' => 0,
                'away_wins' => 0,
                'away_draws' => 0,
                'away_losses' => 0,
                'clean_sheets' => 0
            ];
        }

        // Process each match
        foreach ($matches as $match) {
            $homeId = $match['home_team_id'];
            $awayId = $match['away_team_id'];
            $homeScore = $match['home_score'];
            $awayScore = $match['away_score'];

            // Update stats for both teams
            $stats[$homeId]['played']++;
            $stats[$awayId]['played']++;
            
            $stats[$homeId]['goals_for'] += $homeScore;
            $stats[$homeId]['goals_against'] += $awayScore;
            $stats[$awayId]['goals_for'] += $awayScore;
            $stats[$awayId]['goals_against'] += $homeScore;

            // Clean sheets
            if ($awayScore === 0) $stats[$homeId]['clean_sheets']++;
            if ($homeScore === 0) $stats[$awayId]['clean_sheets']++;

            // Results
            if ($homeScore > $awayScore) {
                // Home win
                $stats[$homeId]['wins']++;
                $stats[$homeId]['home_wins']++;
                $stats[$homeId]['points'] += 3;
                
                $stats[$awayId]['losses']++;
                $stats[$awayId]['away_losses']++;
            } elseif ($homeScore < $awayScore) {
                // Away win
                $stats[$awayId]['wins']++;
                $stats[$awayId]['away_wins']++;
                $stats[$awayId]['points'] += 3;
                
                $stats[$homeId]['losses']++;
                $stats[$homeId]['home_losses']++;
            } else {
                // Draw
                $stats[$homeId]['draws']++;
                $stats[$homeId]['home_draws']++;
                $stats[$homeId]['points'] += 1;
                
                $stats[$awayId]['draws']++;
                $stats[$awayId]['away_draws']++;
                $stats[$awayId]['points'] += 1;
            }
        }

        // Calculate additional metrics
        foreach ($stats as $teamId => &$teamStat) {
            $played = $teamStat['played'];
            if ($played > 0) {
                $teamStat['win_percentage'] = round(($teamStat['wins'] / $played) * 100, 1);
                $teamStat['goals_per_game'] = round($teamStat['goals_for'] / $played, 2);
                $teamStat['goals_conceded_per_game'] = round($teamStat['goals_against'] / $played, 2);
                $teamStat['goal_difference'] = $teamStat['goals_for'] - $teamStat['goals_against'];
                $teamStat['points_per_game'] = round($teamStat['points'] / $played, 2);
            }
        }

        return array_values($stats);
    }
}
