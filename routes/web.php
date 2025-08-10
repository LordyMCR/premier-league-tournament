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
            'away' => $game->awayTeam->name,
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
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Public profile routes (accessible to all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/users/{user:id}', [ProfileController::class, 'show'])->name('profile.show');
});

Route::middleware('auth')->group(function () {
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
    Route::get('/tournaments/{tournament}/leaderboard', [TournamentController::class, 'leaderboard'])->name('tournaments.leaderboard');
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])->name('tournaments.destroy');
    
    // Pick routes
    Route::get('/tournaments/{tournament}/picks', [PickController::class, 'index'])->name('tournaments.picks');
    Route::get('/tournaments/{tournament}/gameweeks/{gameWeek}/pick', [PickController::class, 'create'])->name('tournaments.gameweeks.picks.create');
    Route::post('/tournaments/{tournament}/gameweeks/{gameWeek}/pick', [PickController::class, 'store'])->name('tournaments.gameweeks.picks.store');
    
    // Schedule routes
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('/schedule/gameweek/{gameweek}', [ScheduleController::class, 'gameweek'])->name('schedule.gameweek');
    Route::get('/schedule/team/{team}', [ScheduleController::class, 'team'])->name('schedule.team');
    Route::get('/schedule/match/{game}', [ScheduleController::class, 'match'])->name('schedule.match');
    Route::patch('/picks/{pick}/result', [PickController::class, 'updateResult'])->name('picks.update-result');
});

require __DIR__.'/auth.php';

// Temporary S3 test route
Route::get('/test-s3', function () {
    try {
        $disk = \Storage::disk('s3');
        
        // Test 1: Can we list bucket contents?
        $files = $disk->files('avatars');
        
        // Test 2: Can we write a test file?
        $disk->put('avatars/test.txt', 'Hello S3!');
        
        // Test 3: Can we read it back?
        $content = $disk->get('avatars/test.txt');
        
        // Test 4: Clean up
        $disk->delete('avatars/test.txt');
        
        return response()->json([
            'success' => true,
            'message' => 'S3 connection working!',
            'files_in_avatars' => $files,
            'test_content' => $content
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
