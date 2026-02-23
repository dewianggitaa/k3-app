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
use App\Http\Controllers\InspectionExecutionController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ChecklistParameterController;
use App\Http\Controllers\ReportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/{id}/menu', [P3kController::class, 'menu'])->name('p3k.menu');    
Route::get('/{id}/usage', [P3kController::class, 'createUsage'])->name('p3k.usage');
Route::post('/{id}/usage', [P3kController::class, 'storeUsage'])->name('p3k.store-usage');
Route::get('/scan/{assetCode}', [ScanController::class, 'handleNfc'])->name('scan.asset');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('buildings', App\Http\Controllers\BuildingController::class);

    Route::put('/floors/{id}', [FloorController::class, 'update'])->name('floors.update');
    Route::resource('floors', App\Http\Controllers\FloorController::class);
    Route::get('/floors/{id}/mapping', [FloorController::class, 'mapping'])->name('floors.mapping');

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
    Route::get('/inspections/open', [InspectionController::class, 'openTasks'])->name('inspections.open');
    Route::get('/inspections/my-tasks', [InspectionController::class, 'myTasks'])->name('inspections.my-tasks');

    Route::get('/inspections/execute/{id}', [InspectionExecutionController::class, 'edit'])
        ->name('inspections.execute');
    Route::put('/inspections/execute/{id}', [InspectionExecutionController::class, 'update'])
        ->name('inspections.update-execution');
    
    Route::resource('inspections', App\Http\Controllers\InspectionController::class);
        
    Route::get('/{id}/restock', [P3kController::class, 'createRestock'])->name('p3k.restock');
    Route::post('/{id}/restock', [P3kController::class, 'storeUsage'])->name('p3k.store-restock');

    Route::resource('checklist-parameters', App\Http\Controllers\ChecklistParameterController::class);

    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'exportPdf'])->name('reports.export');
});

require __DIR__.'/auth.php';
