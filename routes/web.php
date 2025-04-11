<?php

use App\Http\Controllers\AdminImpersonationController;
use App\Http\Controllers\OIDCSessionController;
use App\Http\Controllers\ProfileController;
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
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::post('/session/init', [OIDCSessionController::class, 'handleCallback'])->middleware('web');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/impersonate', [AdminImpersonationController::class, 'index'])->name('impersonate');
});

// API Token Management Routes
Route::middleware('auth')->prefix('api-tokens')->name('api-tokens.')->group(function () {
    Route::get('/', [App\Http\Controllers\ApiTokenController::class, 'index'])
        ->name('index');
    Route::post('/', [App\Http\Controllers\ApiTokenController::class, 'store'])
        ->name('store');
    Route::delete('/{token}', [App\Http\Controllers\ApiTokenController::class, 'destroy'])
        ->name('destroy');
});

require __DIR__ . '/auth.php';
