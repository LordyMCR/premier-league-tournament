<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\PickController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\UserStatistic;
use App\Models\Game;
use App\Models\Team;
use Carbon\Carbon;

// Include debug routes
require __DIR__.'/debug.php';

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/dashboard', function () {
    $user = Auth::user();
    $stat = UserStatistic::firstOrNew(['user_id' => $user->id]);
    $upcomingFixtures = Game::with(['homeTeam','awayTeam'])
        ->where('status', 'SCHEDULED')
        ->orderBy('kick_off_time')
        ->limit(3)
        ->get()
        ->map(fn($game) => [
            'home' => $game->homeTeam->name,
            'home_short' => $game->homeTeam->short_name,
            'home_crest' => $game->homeTeam->logo_url,
            'away' => $game->awayTeam->name,
            'away_short' => $game->awayTeam->short_name,
            'away_crest' => $game->awayTeam->logo_url,
            'when' => Carbon::parse($game->kick_off_time)->diffForHumans(),
        ]);
    // Also fetch fixtures for the user's favorite team
    $favoriteFixtures = Game::with(['homeTeam','awayTeam'])
        ->where('status', 'SCHEDULED')
        ->where(function($q) use ($user) {
            $q->where('home_team_id', $user->favorite_team_id)
              ->orWhere('away_team_id', $user->favorite_team_id);
        })
        ->orderBy('kick_off_time')
        ->limit(3)
        ->get()
        ->map(fn($game) => [
            'home' => $game->homeTeam->name,
            'away' => $game->awayTeam->name,
            'when' => Carbon::parse($game->kick_off_time)->diffForHumans(),
        ]);

    // Calculate Premier League standings
    $teams = Team::all();
    $standings = [];
    
    foreach ($teams as $team) {
        $homeGames = Game::where('home_team_id', $team->id)->where('status', 'FINISHED')->get();
        $awayGames = Game::where('away_team_id', $team->id)->where('status', 'FINISHED')->get();
        
        $played = $homeGames->count() + $awayGames->count();
        $wins = 0;
        $draws = 0;
        $losses = 0;
        $goalsFor = 0;
        $goalsAgainst = 0;
        
        // Home games
        foreach ($homeGames as $game) {
            $goalsFor += $game->home_score;
            $goalsAgainst += $game->away_score;
            
            if ($game->home_score > $game->away_score) {
                $wins++;
            } elseif ($game->home_score == $game->away_score) {
                $draws++;
            } else {
                $losses++;
            }
        }
        
        // Away games  
        foreach ($awayGames as $game) {
            $goalsFor += $game->away_score;
            $goalsAgainst += $game->home_score;
            
            if ($game->away_score > $game->home_score) {
                $wins++;
            } elseif ($game->home_score == $game->away_score) {
                $draws++;
            } else {
                $losses++;
            }
        }
        
        $goalDifference = $goalsFor - $goalsAgainst;
        $points = ($wins * 3) + $draws;
        
        // Get recent form (last 5 games)
        $allGames = $homeGames->concat($awayGames)->sortByDesc('kick_off_time')->take(5);
        $form = [];
        
        foreach ($allGames as $game) {
            if ($game->home_team_id == $team->id) {
                // Home game
                if ($game->home_score > $game->away_score) {
                    $form[] = 'W';
                } elseif ($game->home_score == $game->away_score) {
                    $form[] = 'D';
                } else {
                    $form[] = 'L';
                }
            } else {
                // Away game
                if ($game->away_score > $game->home_score) {
                    $form[] = 'W';
                } elseif ($game->home_score == $game->away_score) {
                    $form[] = 'D';
                } else {
                    $form[] = 'L';
                }
            }
        }
        
        // Pad form with empty slots if less than 5 games
        while (count($form) < 5) {
            $form[] = null;
        }
        
        $standings[] = [
            'position' => 0, // Will be set after sorting
            'team' => $team->name,
            'team_short' => $team->short_name ?? substr($team->name, 0, 3),
            'team_id' => $team->id,
            'team_crest' => $team->logo_url,
            'played' => $played,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'goals_for' => $goalsFor,
            'goals_against' => $goalsAgainst,
            'goal_difference' => $goalDifference,
            'points' => $points,
            'form' => $form, // Add form data
        ];
    }
    
    // Sort by points (desc), then goal difference (desc), then goals for (desc)
    usort($standings, function($a, $b) {
        if ($a['points'] != $b['points']) {
            return $b['points'] - $a['points'];
        }
        if ($a['goal_difference'] != $b['goal_difference']) {
            return $b['goal_difference'] - $a['goal_difference'];
        }
        return $b['goals_for'] - $a['goals_for'];
    });
    
    // Set positions
    foreach ($standings as $key => $standing) {
        $standings[$key]['position'] = $key + 1;
    }
    
    // Limit to top 10 for dashboard
    $topStandings = array_slice($standings, 0, 10);

    return Inertia::render('Dashboard', [
        'stats'            => [
            'tournaments'   => $stat->total_tournaments,
            'wins'          => $stat->tournaments_won,
            'total_points'  => $stat->total_points,
            'win_rate'      => round($stat->win_percentage) . '%',
        ],
        'upcomingFixtures' => $upcomingFixtures,
        'favoriteFixtures' => $favoriteFixtures,
        'favoriteTeam'     => $user->favoriteTeam?->name,
        'standings'        => $topStandings,
    ]);
})->middleware(['auth', 'verified', 'user.approval'])->name('dashboard');

// Public profile routes (accessible to all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/users/{user:id}', [ProfileController::class, 'show'])->name('profile.show');
});

Route::middleware(['auth', 'user.approval'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/extended', [ProfileController::class, 'updateExtended'])->name('profile.update.extended');
    Route::patch('/profile/privacy', [ProfileController::class, 'updatePrivacy'])->name('profile.update.privacy');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Tournament routes
    Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/tournaments/create', [TournamentController::class, 'create'])->name('tournaments.create');
    Route::post('/tournaments', [TournamentController::class, 'store'])->name('tournaments.store');
    Route::get('/tournaments/join', [TournamentController::class, 'joinForm'])->name('tournaments.join-form');
    Route::post('/tournaments/join', [TournamentController::class, 'join'])->name('tournaments.join');
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
    Route::get('/tournaments/{tournament}/manage', [TournamentController::class, 'manage'])->name('tournaments.manage');
    Route::put('/tournaments/{tournament}', [TournamentController::class, 'update'])->name('tournaments.update');
    Route::get('/tournaments/{tournament}/leaderboard', [TournamentController::class, 'leaderboard'])->name('tournaments.leaderboard');
    Route::post('/tournaments/{tournament}/toggle-favorite', [TournamentController::class, 'toggleFavorite'])->name('tournaments.toggle-favorite');
    Route::delete('/tournaments/{tournament}/participants/{participant}', [TournamentController::class, 'removeParticipant'])->name('tournaments.participants.remove');
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');
    
    // Pick routes
    Route::get('/tournaments/{tournament}/picks', [PickController::class, 'index'])->name('tournaments.picks');
    Route::get('/tournaments/{tournament}/gameweeks/{gameWeek}/pick', [PickController::class, 'create'])->name('tournaments.gameweeks.picks.create');
    Route::post('/tournaments/{tournament}/gameweeks/{gameWeek}/pick', [PickController::class, 'store'])->name('tournaments.gameweeks.picks.store');
    
    // Schedule routes
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/schedule/standings', [ScheduleController::class, 'standings'])->name('schedule.standings');
    Route::get('/schedule/gameweek/{gameweek}', [ScheduleController::class, 'gameweek'])->name('schedule.gameweek');
    Route::get('/schedule/team/{team}', [ScheduleController::class, 'team'])->name('schedule.team');
    Route::get('/schedule/match/{game}', [ScheduleController::class, 'match'])->name('schedule.match');
    Route::patch('/picks/{pick}/result', [PickController::class, 'updateResult'])->name('picks.update-result');
    
    // Live Match routes
    Route::get('/api/live-matches', [App\Http\Controllers\LiveMatchController::class, 'index'])->name('api.live-matches');
    Route::get('/api/live-matches/{game}', [App\Http\Controllers\LiveMatchController::class, 'show'])->name('api.live-matches.show');
    Route::get('/api/tournaments/{tournament}/live-picks', [App\Http\Controllers\TournamentLiveController::class, 'getLivePicks'])->name('api.tournaments.live-picks');
});

require __DIR__.'/auth.php';

// Temporary S3 test route
Route::get('/test-s3', function () {
    try {
        // Test AWS SDK directly
        $config = [
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION', 'eu-west-2'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ];
        
        $s3Client = new \Aws\S3\S3Client($config);
        
        // Test basic bucket access
        $result = $s3Client->listObjectsV2([
            'Bucket' => env('AWS_BUCKET'),
            'Prefix' => 'avatars/',
            'MaxKeys' => 10,
        ]);
        
        $objects = [];
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                $objects[] = $object['Key'];
            }
        }
        
        // Test write operation
        $s3Client->putObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => 'avatars/test.txt',
            'Body' => 'Hello S3 Direct!',
            'ACL' => 'public-read',
        ]);
        
        // Test read operation
        $getResult = $s3Client->getObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => 'avatars/test.txt',
        ]);
        
        $content = (string) $getResult['Body'];
        
        // Clean up
        $s3Client->deleteObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => 'avatars/test.txt',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Direct S3 SDK working!',
            'config' => [
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_BUCKET'),
                'key_set' => env('AWS_ACCESS_KEY_ID') ? 'yes' : 'no',
                'secret_set' => env('AWS_SECRET_ACCESS_KEY') ? 'yes' : 'no',
            ],
            'objects_found' => $objects,
            'test_content' => $content,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'config_debug' => [
                'region' => env('AWS_DEFAULT_REGION'),
                'bucket' => env('AWS_BUCKET'),
                'key_set' => env('AWS_ACCESS_KEY_ID') ? 'yes' : 'no',
                'secret_set' => env('AWS_SECRET_ACCESS_KEY') ? 'yes' : 'no',
            ]
        ], 500);
    }
});
