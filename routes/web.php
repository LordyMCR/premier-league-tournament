<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\PickController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
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
    Route::patch('/picks/{pick}/result', [PickController::class, 'updateResult'])->name('picks.update-result');
});

require __DIR__.'/auth.php';
