<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatchesController;
use App\Http\Controllers\PlayoffMatchesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicTournamentController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Tournament Registration
Route::get('/tournament', [TournamentController::class, 'index'])->name('tournament.index')->middleware('auth')->middleware('auth');
Route::match(['get', 'post'], '/tournament/search', [TournamentController::class, 'search'])->name('tournament.search')->middleware('auth');
Route::get('/tournament/create', [TournamentController::class, 'create'])->name('tournament.create')->middleware('auth');
Route::post('/tournament/store', [TournamentController::class, 'store'])->name('tournament.store')->middleware('auth');
Route::get('/tournament/view/{id}', [TournamentController::class, 'view'])->name('tournament.view')->middleware('auth');
Route::get('/tournament/{id}', [TournamentController::class, 'edit'])->name('tournament.edit')->middleware('auth');
Route::put('/tournament/{id}', [TournamentController::class, 'update'])->name('tournament.update')->middleware('auth');
Route::delete('/tournament/{id}', [TournamentController::class, 'destroy'])->name('tournament.destroy')->middleware('auth');

// Teams Registration
Route::get('/teams/create/{tournament_id}', [TeamsController::class, 'create'])->name('teams.create')->middleware('auth');
Route::post('/teams/store', [TeamsController::class, 'store'])->name('teams.store')->middleware('auth');
Route::get('/teams/{id}', [TeamsController::class, 'edit'])->name('teams.edit')->middleware('auth');
Route::put('/teams/{id}', [TeamsController::class, 'update'])->name('teams.update')->middleware('auth');
Route::delete('/teams/{id}', [TeamsController::class, 'destroy'])->name('teams.destroy')->middleware('auth');

// Matches
Route::post(
    '/tournament/{tournament_id}/matches/generate',
    [MatchesController::class, 'generate']
)
    ->name('tournament.matches.generate')
    ->middleware('auth');

Route::get(
    '/tournament/{tournament_id}/matches/{team_bracket}/{team_category}',
    [MatchesController::class, 'view']
)->name('tournament.matches.view')->middleware('auth');

Route::put(
    '/tournament/match/{id}/score',
    [MatchesController::class, 'updateScore']
)
    ->name('tournament.matches.score')
    ->middleware('auth');

Route::get(
    '/tournament/{tournament_id}/standings/{team_bracket}/{team_category}',
    [MatchesController::class, 'standings']
)
    ->name('tournament.matches.standings')
    ->middleware('auth');

Route::delete(
    '/tournament/matches/bulk-delete',
    [MatchesController::class, 'bulkDestroy']
)
    ->name('tournament.matches.bulkDestroy')
    ->middleware('auth');

// Public routes for viewing matches and standings
Route::get(
    '/tournaments',
    [PublicTournamentController::class, 'index']
)
    ->name('public.tournaments');

Route::get(
    '/tournaments/filters',
    [PublicTournamentController::class, 'filters']
)
    ->name('public.tournaments.filters');

Route::get(
    '/tournaments/data',
    [PublicTournamentController::class, 'data']
)
    ->name('public.tournaments.data');

// Playoff Matches
Route::get(
    '/tournament/{tournament_id}/playoffs/{team_category}',
    [PlayoffMatchesController::class, 'index']
)
    ->name('tournament.playoffs.index')
    ->middleware('auth');

Route::post(
    '/tournament/playoffs/generate',
    [PlayoffMatchesController::class, 'generateCrossBracket']
)
    ->name('tournament.playoffs.generate')
    ->middleware('auth');

Route::post(
    '/tournament/playoffs/store',
    [PlayoffMatchesController::class, 'store']
)
    ->name('tournament.playoffs.store')
    ->middleware('auth');

Route::put(
    '/tournament/playoff/{id}/score',
    [PlayoffMatchesController::class, 'updateScore']
)
    ->name('tournament.playoffs.score')
    ->middleware('auth');

Route::delete(
    '/tournament/playoff/{id}',
    [PlayoffMatchesController::class, 'destroy']
)
    ->name('tournament.playoffs.destroy')
    ->middleware('auth');

Route::delete(
    '/tournament/playoffs/bulk-delete',
    [PlayoffMatchesController::class, 'bulkDestroy']
)
    ->name('tournament.playoffs.bulkDestroy')
    ->middleware('auth');

// usermanagement routes
Route::get('/users', [UserController::class, 'index'])
    ->name('users.index')
    ->middleware('auth');

Route::put('/users/{id}', [UserController::class, 'update'])
    ->name('users.update')
    ->middleware('auth');

// profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
