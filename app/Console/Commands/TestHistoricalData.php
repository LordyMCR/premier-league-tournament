<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HistoricalDataService;
use Illuminate\Support\Facades\Storage;

class TestHistoricalData extends Command
{
    protected $signature = 'test:historical-data';
    protected $description = 'Test if historical data JSON files can be loaded';

    public function handle()
    {
        $this->info('Testing historical data access...');
        
        // Test 1: Check if files exist
        $this->info('1. Checking if JSON files exist...');
        $files = Storage::disk('local')->files('private/historical_data');
        $this->info('Found files: ' . json_encode($files));
        
        // Test 2: Try to read one file
        if (!empty($files)) {
            $firstFile = $files[0];
            $this->info('2. Trying to read: ' . $firstFile);
            try {
                $content = Storage::disk('local')->get($firstFile);
                $data = json_decode($content, true);
                if ($data) {
                    $this->info('✓ Successfully loaded JSON file');
                    $this->info('Season: ' . ($data['season'] ?? 'Unknown'));
                    $this->info('Teams count: ' . count($data['teams'] ?? []));
                    $this->info('Matches count: ' . count($data['matches'] ?? []));
                } else {
                    $this->error('✗ Failed to decode JSON');
                }
            } catch (\Exception $e) {
                $this->error('✗ Error reading file: ' . $e->getMessage());
            }
        }
        
        // Test 3: Test HistoricalDataService
        $this->info('3. Testing HistoricalDataService...');
        try {
            $service = new HistoricalDataService();
            $seasons = $service->getAvailableSeasons();
            $this->info('Available seasons: ' . count($seasons));
            foreach ($seasons as $season) {
                $this->info('- ' . $season['season'] . ' (' . $season['matches_count'] . ' matches)');
            }
            
            // Test team stats
            $this->info('4. Testing team stats for Arsenal...');
            $stats = $service->getTeamStats('Arsenal');
            $this->info('Arsenal stats: ' . json_encode($stats));
            
        } catch (\Exception $e) {
            $this->error('✗ Error with HistoricalDataService: ' . $e->getMessage());
        }
        
        $this->info('Test complete!');
        
        return 0;
    }
}
