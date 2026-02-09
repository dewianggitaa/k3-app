<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\AssetMappingController;
use App\Http\Controllers\P3kController;
use App\Http\Controllers\AparController;
use App\Http\Controllers\HydrantController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\InspectionController;
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

    Route::put('/floors/{id}', [FloorController::class, 'update'])->name('floors.update');
    Route::resource('floors', App\Http\Controllers\FloorController::class);
    Route::get('/floors/{floor}/mapping', [FloorController::class, 'mapping'])->name('floors.mapping');

    Route::post('/assets/{type}/update-location', [AssetMappingController::class, 'updateLocation'])
        ->name('assets.update-location');
    
    Route::put('/rooms/update-coordinates', [RoomController::class, 'updateCoordinates'])->name('rooms.update-coordinates');
    Route::resource('rooms', App\Http\Controllers\RoomController::class);

    Route::get('/mapping/assets/{floor}', [AssetMappingController::class, 'index'])
        ->name('assets.mapping');
    Route::post('/mapping/assets/update', [AssetMappingController::class, 'updateLocation'])
        ->name('assets.update-location');

    Route::resource('p3ks', App\Http\Controllers\P3kController::class);
    Route::resource('apars', App\Http\Controllers\AparController::class);
    Route::resource('hydrants', App\Http\Controllers\HydrantController::class);

    Route::resource('schedules', App\Http\Controllers\ScheduleController::class);
    Route::resource('inspections', App\Http\Controllers\InspectionController::class);
});

require __DIR__.'/auth.php';
