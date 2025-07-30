<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestApiResponse extends Command
{
    protected $signature = 'test:api-response';
    protected $description = 'Test raw API response from APIFootball.com';

    public function handle()
    {
        $apiKey = config('services.football_api.api_key');
        
        if (!$apiKey) {
            $this->error('FOOTBALL_API_KEY not configured');
            return 1;
        }
        
        $this->info('🔧 Testing raw API response from APIFootball.com...');
        $this->info('API Key: ' . substr($apiKey, 0, 10) . '...');
        
        try {
            // Test the teams endpoint for Premier League
            $url = 'https://apiv3.apifootball.com';
            $response = Http::get($url, [
                'action' => 'get_teams',
                'league_id' => 152, // Premier League
                'APIkey' => $apiKey
            ]);
            
            $this->info('Response Status: ' . $response->status());
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->info('📄 Raw response structure:');
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                
                if (is_array($data) && count($data) > 0) {
                    // Check if this is an error response
                    if (isset($data['error'])) {
                        $this->error('❌ API Error: ' . $data['error']);
                        if (isset($data['message'])) {
                            $this->error('Message: ' . $data['message']);
                        }
                        return 1;
                    }
                    
                    $this->info('✅ API response successful! Found ' . count($data) . ' teams');
                    
                    // Show structure of available data
                    $this->info('📋 Available teams:');
                    foreach ($data as $index => $team) {
                        $teamName = $team['team_name'] ?? 'Unknown';
                        $playersCount = isset($team['players']) && is_array($team['players']) ? count($team['players']) : 0;
                        $this->line("  {$index}: {$teamName} ({$playersCount} players)");
                    }
                    
                    // Show first team structure if available
                    if (isset($data[0])) {
                        $firstTeam = $data[0];
                        $this->info('📋 First team structure:');
                        $this->line('Team: ' . ($firstTeam['team_name'] ?? 'Unknown'));
                        $this->line('Team Key: ' . ($firstTeam['team_key'] ?? 'Unknown'));
                        
                        if (isset($firstTeam['players']) && is_array($firstTeam['players'])) {
                            $this->info('👥 Players found: ' . count($firstTeam['players']));
                            
                            // Look for any injured players in all teams
                            $injuredFound = false;
                            foreach ($data as $team) {
                                if (isset($team['players']) && is_array($team['players'])) {
                                    foreach ($team['players'] as $player) {
                                        if (isset($player['player_injured']) && $player['player_injured'] === 'Yes') {
                                            if (!$injuredFound) {
                                                $this->info('🩹 Found injured players:');
                                                $injuredFound = true;
                                            }
                                            $this->line("- {$player['player_name']} ({$team['team_name']}) - {$player['player_type']}");
                                        }
                                    }
                                }
                            }
                            
                            if (!$injuredFound) {
                                $this->comment('✅ No injured players found in any team (good news!)');
                            }
                            
                            // Show a sample player structure
                            if (!empty($firstTeam['players'])) {
                                $firstPlayer = $firstTeam['players'][0];
                                $this->info('👤 Sample player structure:');
                                foreach ($firstPlayer as $key => $value) {
                                    $this->line("  {$key}: {$value}");
                                }
                            }
                        } else {
                            $this->warning('⚠️ No players data found in first team');
                        }
                    }
                } else {
                    $this->error('❌ API returned empty or invalid data');
                    $this->line('Response: ' . $response->body());
                }
            } else {
                $this->error('❌ API request failed');
                $this->line('Response: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
