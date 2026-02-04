<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\RoomController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('buildings', App\Http\Controllers\BuildingController::class);
    Route::resource('floors', App\Http\Controllers\FloorController::class);
    Route::resource('rooms', App\Http\Controllers\RoomController::class);
});

require __DIR__.'/auth.php';
