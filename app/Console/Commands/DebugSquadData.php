<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugSquadData extends Command
{
    protected $signature = 'squad:debug {team_id?}';
    protected $description = 'Debug squad data from API to see shirt numbers';

    public function handle()
    {
        $teamId = $this->argument('team_id') ?? 57; // Arsenal FC
        
        $this->info("Fetching squad data for team ID: {$teamId}");
        
        try {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key')
            ])->get("https://api.football-data.org/v4/teams/{$teamId}");

            if ($response->successful()) {
                $data = $response->json();
                $squad = $data['squad'] ?? [];
                
                $this->info("Found " . count($squad) . " players");
                
                // Show first 3 players with full data structure
                foreach (array_slice($squad, 0, 3) as $i => $player) {
                    $this->info("Player " . ($i + 1) . ":");
                    $this->line("  Name: " . ($player['name'] ?? 'N/A'));
                    $this->line("  Position: " . ($player['position'] ?? 'N/A'));
                    $this->line("  Shirt Number: " . ($player['shirtNumber'] ?? 'NOT PROVIDED'));
                    $this->line("  Full data: " . json_encode($player, JSON_PRETTY_PRINT));
                    $this->line("");
                }
            } else {
                $this->error("API request failed: " . $response->status());
                $this->error($response->body());
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
