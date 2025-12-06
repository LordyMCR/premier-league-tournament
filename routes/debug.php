<?php

use Illuminate\Support\Facades\Route;
use App\Services\HistoricalDataService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;

Route::get('/debug/historical-data', function () {
    $output = [];
    
    try {
        // Clear cache first
        \Illuminate\Support\Facades\Cache::forget('historical_premier_league_data');
        $output[] = 'Cache cleared!';
        
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
        $files = Storage::disk('local')->files('historical_data');
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

// Quick tournament preview with fabricated data for UI testing
Route::get('/debug/tournament-preview', function () {
    // Synthetic identities
    $users = collect([
        ['id' => 1, 'name' => 'Daniel Lord', 'avatar_url' => 'https://i.pravatar.cc/64?img=11'],
        ['id' => 2, 'name' => 'Rachel Lord', 'avatar_url' => 'https://i.pravatar.cc/64?img=32'],
        ['id' => 3, 'name' => 'Alex Green', 'avatar_url' => 'https://ui-avatars.com/api/?name=Alex+Green&background=22C55E&color=fff&size=64'],
        ['id' => 4, 'name' => 'Sam Taylor', 'avatar_url' => 'https://ui-avatars.com/api/?name=Sam+Taylor&background=22C55E&color=fff&size=64'],
        ['id' => 5, 'name' => 'Jordan Lee', 'avatar_url' => 'https://i.pravatar.cc/64?img=5'],
        ['id' => 6, 'name' => 'Charlie Fox', 'avatar_url' => 'https://ui-avatars.com/api/?name=Charlie+Fox&background=22C55E&color=fff&size=64'],
    ]);

    // Minimal team set
    // Using football-data crest URLs for quick preview logos
    $teams = collect([
        ['id' => 57, 'name' => 'Arsenal', 'short_name' => 'ARS', 'primary_color' => '#EF4444', 'logo_url' => 'https://crests.football-data.org/57.svg'],
        ['id' => 65, 'name' => 'Manchester City', 'short_name' => 'MCI', 'primary_color' => '#3B82F6', 'logo_url' => 'https://crests.football-data.org/65.svg'],
        ['id' => 64, 'name' => 'Liverpool', 'short_name' => 'LIV', 'primary_color' => '#DC2626', 'logo_url' => 'https://crests.football-data.org/64.svg'],
        ['id' => 61, 'name' => 'Chelsea', 'short_name' => 'CHE', 'primary_color' => '#1D4ED8', 'logo_url' => 'https://crests.football-data.org/61.svg'],
        ['id' => 73, 'name' => 'Tottenham', 'short_name' => 'TOT', 'primary_color' => '#111827', 'logo_url' => 'https://crests.football-data.org/73.svg'],
        ['id' => 67, 'name' => 'Newcastle', 'short_name' => 'NEW', 'primary_color' => '#10B981', 'logo_url' => 'https://crests.football-data.org/67.svg'],
    ]);

    // Gameweeks 1..6 (some completed)
    $gameweeks = collect(range(1, 6))->map(function ($n) {
        return [
            'id' => $n,
            'week_number' => $n,
            'name' => 'Gameweek ' . $n,
        ];
    });

    // Helper to random pick entries and points
    $results = ['win' => 3, 'draw' => 1, 'loss' => 0];
    $picks = collect();
    foreach ($gameweeks as $gw) {
        foreach ($users as $user) {
            $team = $teams->random();
            $resultKey = array_rand($results);
            $points = $results[$resultKey];
            $picks->push([
                'id' => Str::uuid()->toString(),
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar_url' => $user['avatar_url'],
                ],
                'team' => [
                    'id' => $team['id'],
                    'name' => $team['name'],
                    'short_name' => $team['short_name'],
                    'primary_color' => $team['primary_color'],
                    'logo_url' => $team['logo_url'] ?? null,
                ],
                'gameweek' => [
                    'id' => $gw['id'],
                    'week_number' => $gw['week_number'],
                    'name' => $gw['name'],
                ],
                'result' => $resultKey,
                'points' => $points,
                'home_away' => rand(0, 1) ? 'home' : 'away',
                'picked_at' => now()->subWeeks(7 - $gw['week_number'])->toDateTimeString(),
            ]);
        }
    }

    // Build leaderboard from synthetic picks
    $leaderboard = $users->map(function ($u) use ($picks) {
        $userPicks = $picks->filter(fn($p) => $p['user']['id'] === $u['id']);
        return [
            'id' => $u['id'],
            'user' => $u,
            'points' => $userPicks->sum('points'),
            'picks_count' => $userPicks->count(),
        ];
    })->sortByDesc('points')->values()->all();

    // Group all participants' picks by gameweek id, as expected by Show.vue
    $allParticipantPicks = $picks->groupBy(fn($p) => $p['gameweek']['id']);

    // Current, selection, next selection gameweek mock
    $currentGameweek = ['id' => 6, 'week_number' => 6, 'name' => 'Gameweek 6'];
    $selectionGameweek = ['id' => 7, 'week_number' => 7, 'name' => 'Gameweek 7'];
    $nextSelectionGameweek = [
        'id' => 7,
        'week_number' => 7,
        'name' => 'Gameweek 7',
        'selection_opens' => now()->addDays(2)->toDateTimeString(),
    ];

    // Example tournament envelope
    $tournament = [
        'id' => 999,
        'name' => 'Premier League First 20 Gameweeks',
        'description' => 'First twenty game weeks',
        'status' => 'active',
        'join_code' => 'WOAJNU1V',
        'participants_count' => $users->count(),
        'created_at' => now()->subWeeks(8)->toDateTimeString(),
    ];

    // One viewer’s picks history (user id 1)
    $userPicksHistory = $picks->filter(fn($p) => $p['user']['id'] === 1)->values()->all();

    return Inertia::render('Tournaments/Show', [
        'tournament' => $tournament,
        'isParticipant' => true,
        'leaderboard' => $leaderboard,
        'currentGameweek' => $currentGameweek,
        'selectionGameweek' => $selectionGameweek,
        'nextSelectionGameweek' => $nextSelectionGameweek,
        'userPicks' => $userPicksHistory,
        'currentPick' => null,
        'allParticipantPicks' => $allParticipantPicks,
    ]);
});

// Tournament show preview: GW1 completed, GW2 selection window open
Route::get('/debug/tournament-after-gw1', function () {
    $users = collect([
        ['id' => 1, 'name' => 'Daniel Lord', 'avatar_url' => 'https://i.pravatar.cc/64?img=11'],
        ['id' => 2, 'name' => 'Rachel Lord', 'avatar_url' => 'https://i.pravatar.cc/64?img=32'],
    ]);

    $teams = collect([
        ['id' => 57, 'name' => 'Arsenal', 'short_name' => 'ARS', 'primary_color' => '#EF4444', 'logo_url' => 'https://crests.football-data.org/57.svg'],
        ['id' => 65, 'name' => 'Manchester City', 'short_name' => 'MCI', 'primary_color' => '#3B82F6', 'logo_url' => 'https://crests.football-data.org/65.svg'],
        ['id' => 64, 'name' => 'Liverpool', 'short_name' => 'LIV', 'primary_color' => '#DC2626', 'logo_url' => 'https://crests.football-data.org/64.svg'],
        ['id' => 61, 'name' => 'Chelsea', 'short_name' => 'CHE', 'primary_color' => '#1D4ED8', 'logo_url' => 'https://crests.football-data.org/61.svg'],
        ['id' => 73, 'name' => 'Tottenham', 'short_name' => 'TOT', 'primary_color' => '#111827', 'logo_url' => 'https://crests.football-data.org/73.svg'],
        ['id' => 67, 'name' => 'Newcastle', 'short_name' => 'NEW', 'primary_color' => '#10B981', 'logo_url' => 'https://crests.football-data.org/67.svg'],
    ]);

    $resultsToPoints = ['win' => 3, 'draw' => 1, 'loss' => 0];

    // Gameweeks
    $gw1 = ['id' => 1, 'week_number' => 1, 'name' => 'Gameweek 1'];
    $gw2 = ['id' => 2, 'week_number' => 2, 'name' => 'Gameweek 2'];

    // GW1 completed picks
    $picks = collect([
        // Daniel → Arsenal win
        [
            'id' => Str::uuid()->toString(),
            'user' => $users[0],
            'team' => $teams->firstWhere('name', 'Arsenal'),
            'gameweek' => $gw1,
            'result' => 'win',
            'points' => $resultsToPoints['win'],
            'home_away' => 'home',
            'picked_at' => now()->subWeek()->toDateTimeString(),
        ],
        // Rachel → Chelsea draw
        [
            'id' => Str::uuid()->toString(),
            'user' => $users[1],
            'team' => $teams->firstWhere('name', 'Chelsea'),
            'gameweek' => $gw1,
            'result' => 'draw',
            'points' => $resultsToPoints['draw'],
            'home_away' => 'away',
            'picked_at' => now()->subWeek()->toDateTimeString(),
        ],
    ]);

    // Leaderboard from GW1 results
    $leaderboard = $users->map(function ($u) use ($picks) {
        $userPicks = $picks->filter(fn ($p) => $p['user']['id'] === $u['id']);
        return [
            'id' => $u['id'],
            'user' => $u,
            'points' => $userPicks->sum('points'),
            'picks_count' => $userPicks->count(),
        ];
    })->sortByDesc('points')->values()->all();

    $allParticipantPicks = $picks->groupBy(fn ($p) => $p['gameweek']['id']);

    // Selection window now open for GW2, no current pick yet
    $selectionGameweek = [
        'id' => $gw2['id'],
        'week_number' => $gw2['week_number'],
        'name' => $gw2['name'],
        'selection_opens' => now()->subMinutes(10)->toDateTimeString(),
        'selection_deadline' => now()->addDays(5)->toDateTimeString(),
    ];

    $tournament = [
        'id' => 1001,
        'name' => 'PL First 20 Gameweeks',
        'description' => 'First 20 gameweeks for testing',
        'status' => 'active',
        'join_code' => '12RYT8YE',
        'participants_count' => $users->count(),
        'created_at' => now()->subWeek(8)->toDateTimeString(),
    ];

    // Viewer’s picks history (user id 1)
    $userPicksHistory = $picks->filter(fn ($p) => $p['user']['id'] === 1)->values()->all();

    return Inertia::render('Tournaments/Show', [
        'tournament' => $tournament,
        'isParticipant' => true,
        'leaderboard' => $leaderboard,
        // current gameweek N/A (previous finished, next not started yet)
        'currentGameweek' => null,
        'selectionGameweek' => $selectionGameweek,
        'nextSelectionGameweek' => null,
        'userPicks' => $userPicksHistory,
        'currentPick' => null,
        'allParticipantPicks' => $allParticipantPicks,
    ]);
});

// Schedule index preview with fabricated data
Route::get('/debug/schedule-preview', function () {
    // Minimal team catalog with crests
    $teams = collect([
        ['id' => 57, 'name' => 'Arsenal', 'short_name' => 'ARS', 'primary_color' => '#EF4444', 'logo_url' => 'https://crests.football-data.org/57.svg'],
        ['id' => 65, 'name' => 'Manchester City', 'short_name' => 'MCI', 'primary_color' => '#3B82F6', 'logo_url' => 'https://crests.football-data.org/65.svg'],
        ['id' => 64, 'name' => 'Liverpool', 'short_name' => 'LIV', 'primary_color' => '#DC2626', 'logo_url' => 'https://crests.football-data.org/64.svg'],
        ['id' => 61, 'name' => 'Chelsea', 'short_name' => 'CHE', 'primary_color' => '#1D4ED8', 'logo_url' => 'https://crests.football-data.org/61.svg'],
        ['id' => 73, 'name' => 'Tottenham', 'short_name' => 'TOT', 'primary_color' => '#111827', 'logo_url' => 'https://crests.football-data.org/73.svg'],
        ['id' => 67, 'name' => 'Newcastle', 'short_name' => 'NEW', 'primary_color' => '#10B981', 'logo_url' => 'https://crests.football-data.org/67.svg'],
        ['id' => 76, 'name' => 'Wolves', 'short_name' => 'WOL', 'primary_color' => '#F59E0B', 'logo_url' => 'https://crests.football-data.org/76.svg'],
        ['id' => 1044, 'name' => 'Brentford', 'short_name' => 'BRE', 'primary_color' => '#DC2626', 'logo_url' => 'https://crests.football-data.org/402.svg'],
        ['id' => 338, 'name' => 'Leeds', 'short_name' => 'LEE', 'primary_color' => '#2563EB', 'logo_url' => 'https://crests.football-data.org/341.svg'],
        ['id' => 62, 'name' => 'Everton', 'short_name' => 'EVE', 'primary_color' => '#2563EB', 'logo_url' => 'https://crests.football-data.org/62.svg'],
    ]);

    // Helper to pick a random team not equal to other
    $pickTeam = function ($excludeId = null) use ($teams) {
        return ($excludeId === null)
            ? $teams->random()
            : $teams->where('id', '!=', $excludeId)->random();
    };

    $makeGame = function ($id, $kickoff, $status = 'SCHEDULED', $home = null, $away = null) use ($pickTeam) {
        $homeTeam = $home ?: $pickTeam();
        $awayTeam = $away ?: $pickTeam($homeTeam['id']);
        return [
            'id' => $id,
            'kick_off_time' => $kickoff,
            'status' => $status,
            'home_score' => $status === 'FINISHED' ? rand(0, 4) : null,
            'away_score' => $status === 'FINISHED' ? rand(0, 4) : null,
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
        ];
    };

    $now = now();

    // Gameweek 1 upcoming
    $gw1Games = collect(range(1, 10))->map(function ($i) use ($makeGame, $now) {
        return $makeGame($i, $now->copy()->addDays($i)->setTime(15, 0)->toDateTimeString(), 'SCHEDULED');
    })->values()->all();

    // Gameweek 2 finished
    $gw2Games = collect(range(11, 20))->map(function ($i) use ($makeGame, $now) {
        return $makeGame($i, $now->copy()->subDays(14 - ($i - 10))->setTime(15, 0)->toDateTimeString(), 'FINISHED');
    })->values()->all();

    $gameweeks = [
        ['id' => 1, 'name' => 'Gameweek 1', 'is_completed' => false, 'games' => $gw1Games],
        ['id' => 2, 'name' => 'Gameweek 2', 'is_completed' => true, 'games' => $gw2Games],
    ];

    return Inertia::render('Schedule/Index', [
        'gameweeks' => $gameweeks,
        'currentGameweek' => ['id' => 1, 'week_number' => 1],
        'nextGameweek' => ['id' => 2, 'week_number' => 2],
        'teams' => $teams->map(fn($t) => ['id' => $t['id'], 'name' => $t['name']])->values()->all(),
        'stats' => [ 'total_games' => 20 ],
        'recentGames' => [],
        'upcomingHighlights' => [],
    ]);
});

// Gameweek screen preview with fabricated data
Route::get('/debug/schedule-gameweek-preview', function () {
    $teams = [
        ['id' => 57, 'name' => 'Arsenal', 'short_name' => 'ARS', 'primary_color' => '#EF4444', 'logo_url' => 'https://crests.football-data.org/57.svg'],
        ['id' => 65, 'name' => 'Manchester City', 'short_name' => 'MCI', 'primary_color' => '#3B82F6', 'logo_url' => 'https://crests.football-data.org/65.svg'],
        ['id' => 64, 'name' => 'Liverpool', 'short_name' => 'LIV', 'primary_color' => '#DC2626', 'logo_url' => 'https://crests.football-data.org/64.svg'],
        ['id' => 73, 'name' => 'Tottenham', 'short_name' => 'TOT', 'primary_color' => '#111827', 'logo_url' => 'https://crests.football-data.org/73.svg'],
        ['id' => 61, 'name' => 'Chelsea', 'short_name' => 'CHE', 'primary_color' => '#1D4ED8', 'logo_url' => 'https://crests.football-data.org/61.svg'],
        ['id' => 67, 'name' => 'Newcastle', 'short_name' => 'NEW', 'primary_color' => '#10B981', 'logo_url' => 'https://crests.football-data.org/67.svg'],
        ['id' => 338, 'name' => 'Leeds', 'short_name' => 'LEE', 'primary_color' => '#2563EB', 'logo_url' => 'https://crests.football-data.org/341.svg'],
        ['id' => 1044, 'name' => 'Brentford', 'short_name' => 'BRE', 'primary_color' => '#DC2626', 'logo_url' => 'https://crests.football-data.org/402.svg'],
    ];

    $findTeam = fn($id) => collect($teams)->firstWhere('id', $id);
    $now = now();
    $mk = function ($id, $homeId, $awayId, $offsetDays, $status) use ($findTeam, $now) {
        return [
            'id' => $id,
            'kick_off_time' => $now->copy()->addDays($offsetDays)->setTime(15, 0)->toDateTimeString(),
            'status' => $status,
            'home_score' => $status === 'FINISHED' ? rand(0, 4) : null,
            'away_score' => $status === 'FINISHED' ? rand(0, 4) : null,
            'home_team' => $findTeam($homeId),
            'away_team' => $findTeam($awayId),
        ];
    };

    $games = [
        $mk(1, 64, 1044, 0, 'SCHEDULED'),
        $mk(2, 65, 338, 1, 'SCHEDULED'),
        $mk(3, 67, 65, 2, 'SCHEDULED'),
        $mk(4, 1044, 73, 3, 'SCHEDULED'),
        $mk(5, 67, 61, -3, 'FINISHED'),
        $mk(6, 64, 338, -2, 'FINISHED'),
        $mk(7, 61, 64, -1, 'FINISHED'),
    ];

    // Split into a few dates
    $start = $now->copy()->subDays(3);
    $end = $now->copy()->addDays(3);

    $gameweek = [
        'id' => 1,
        'name' => 'Gameweek 1',
        'start_date' => $start->toDateString(),
        'end_date' => $end->toDateString(),
        'is_completed' => false,
        'week_number' => 1,
        'selection_opens' => $start->copy()->subDays(2)->setTime(0, 0)->toDateTimeString(),
        'selection_deadline' => $start->copy()->addDays(2)->setTime(23, 59)->toDateTimeString(),
        'gameweek_start_time' => $start->copy()->setTime(20, 0)->toDateTimeString(),
        'gameweek_end_time' => $end->copy()->setTime(22, 0)->toDateTimeString(),
        'games' => $games,
    ];

    return Inertia::render('Schedule/Gameweek', [
        'gameweek' => $gameweek,
        'previousGameweek' => null,
        'nextGameweek' => ['id' => 2, 'name' => 'Gameweek 2'],
    ]);
});

// Match page preview with fabricated data (finished and scheduled variants)
Route::get('/debug/match-preview', function () {
    $home = ['id' => 76, 'name' => 'Wolverhampton Wanderers FC', 'short_name' => 'WOL', 'logo_url' => 'https://crests.football-data.org/76.svg', 'primary_color' => '#F59E0B'];
    $away = ['id' => 65, 'name' => 'Manchester City FC', 'short_name' => 'MCI', 'logo_url' => 'https://crests.football-data.org/65.svg', 'primary_color' => '#3B82F6'];

    $finishedGame = [
        'id' => 999,
        'status' => 'FINISHED',
        'home_score' => 2,
        'away_score' => 1,
        'kick_off_time' => now()->subDays(1)->setTime(17, 30)->toDateTimeString(),
        'home_team' => $home,
        'away_team' => $away,
        'game_week' => ['id' => 1, 'name' => 'Gameweek 1'],
    ];

    $scheduledGame = [
        'id' => 1000,
        'status' => 'SCHEDULED',
        'home_score' => null,
        'away_score' => null,
        'kick_off_time' => now()->addDays(2)->setTime(17, 30)->toDateTimeString(),
        'home_team' => $home,
        'away_team' => $away,
        'game_week' => ['id' => 2, 'name' => 'Gameweek 2'],
    ];

    $headToHead = [
        'total_games' => 4,
        'wins' => 1,
        'draws' => 0,
        'losses' => 3,
        'recent_meetings' => [
            ['date' => now()->subMonths(3)->toDateString(), 'score' => '1-0', 'venue' => 'H'],
            ['date' => now()->subMonths(8)->toDateString(), 'score' => '1-2', 'venue' => 'A'],
        ],
        'data_source' => 'historical',
    ];

    $stats = [
        'played' => 0,
        'points' => 0,
        'wins' => 0,
        'draws' => 0,
        'losses' => 0,
        'goals_for' => 0,
        'goals_against' => 0,
        'home_record' => ['wins' => 0, 'draws' => 0, 'losses' => 0],
        'away_record' => ['wins' => 0, 'draws' => 0, 'losses' => 0],
    ];

    $form = [
        ['result' => 'W', 'class' => 'bg-green-500'],
        ['result' => 'W', 'class' => 'bg-green-500'],
        ['result' => 'D', 'class' => 'bg-yellow-500'],
        ['result' => 'W', 'class' => 'bg-green-500'],
        ['result' => 'L', 'class' => 'bg-red-500'],
    ];

    $game = request('status') === 'scheduled' ? $scheduledGame : $finishedGame;

    return Inertia::render('Schedule/Match', [
        'game' => $game,
        'homeTeamStats' => $stats,
        'awayTeamStats' => $stats,
        'headToHead' => $headToHead,
        'homeTeamForm' => $form,
        'awayTeamForm' => $form,
    ]);
});
