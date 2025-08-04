<?php

use Illuminate\Support\Facades\Route;
use App\Services\HistoricalDataService;
use Illuminate\Support\Facades\Storage;

Route::get('/debug/historical-data', function () {
    $output = [];
    
    try {
        // Test storage paths
        $output[] = '0. Storage path debugging...';
        $output[] = 'Storage local disk root: ' . Storage::disk('local')->path('');
        $output[] = 'App storage path: ' . storage_path('app');
        $output[] = 'Base path: ' . base_path();
        
        // Check different possible paths
        $paths = [
            'private/historical_data',
            'historical_data', 
            'app/private/historical_data',
            'storage/app/private/historical_data'
        ];
        
        foreach ($paths as $path) {
            $files = Storage::disk('local')->files($path);
            $output[] = "Path '{$path}': " . json_encode($files);
        }
        
        // Check if directory exists
        $output[] = 'Directory private/historical_data exists: ' . (Storage::disk('local')->exists('private/historical_data') ? 'YES' : 'NO');
        $output[] = 'Directory private exists: ' . (Storage::disk('local')->exists('private') ? 'YES' : 'NO');
        
        // List all directories in storage/app
        $allFiles = Storage::disk('local')->allFiles();
        $output[] = 'All files in storage: ' . json_encode(array_slice($allFiles, 0, 20)); // Show first 20
        
        // Test 1: Check if files exist
        $output[] = '1. Checking if JSON files exist...';
        $files = Storage::disk('local')->files('private/historical_data');
        $output[] = 'Found files: ' . json_encode($files);
        
        // Test 2: Try to read one file
        if (!empty($files)) {
            $firstFile = $files[0];
            $output[] = '2. Trying to read: ' . $firstFile;
            try {
                $content = Storage::disk('local')->get($firstFile);
                $data = json_decode($content, true);
                if ($data) {
                    $output[] = '✓ Successfully loaded JSON file';
                    $output[] = 'Season: ' . ($data['season'] ?? 'Unknown');
                    $output[] = 'Teams count: ' . count($data['teams'] ?? []);
                    $output[] = 'Matches count: ' . count($data['matches'] ?? []);
                    
                    // Show first few teams
                    $teams = array_slice($data['teams'] ?? [], 0, 5);
                    $output[] = 'First 5 teams: ' . json_encode($teams);
                } else {
                    $output[] = '✗ Failed to decode JSON';
                }
            } catch (\Exception $e) {
                $output[] = '✗ Error reading file: ' . $e->getMessage();
            }
        }
        
        // Test 3: Test HistoricalDataService
        $output[] = '3. Testing HistoricalDataService...';
        $service = new HistoricalDataService();
        $seasons = $service->getAvailableSeasons();
        $output[] = 'Available seasons: ' . count($seasons);
        foreach ($seasons as $season) {
            $output[] = '- ' . $season['season'] . ' (' . $season['matches_count'] . ' matches)';
        }
        
        // Test team stats
        $output[] = '4. Testing team stats for Arsenal...';
        $stats = $service->getTeamStats('Arsenal');
        $output[] = 'Arsenal stats: ' . json_encode($stats);
        
        // Test head to head
        $output[] = '5. Testing head-to-head: Arsenal vs Chelsea...';
        $h2h = $service->getHeadToHeadRecord('Arsenal', 'Chelsea');
        $output[] = 'Head-to-head record: ' . json_encode($h2h);
        
    } catch (\Exception $e) {
        $output[] = '✗ Error: ' . $e->getMessage();
        $output[] = 'Stack trace: ' . $e->getTraceAsString();
    }
    
    return '<pre>' . implode("\n", $output) . '</pre>';
});
