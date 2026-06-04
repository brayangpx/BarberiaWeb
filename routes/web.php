<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HaircutPreviewController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'autenticar'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/agenda', [AgendaController::class, 'index'])
        ->name('agenda');

    Route::get('/registrar-servicio', [AppointmentController::class, 'create'])
        ->name('registrar-servicio');

    Route::get('/citas', [AppointmentController::class, 'index'])
        ->name('citas');

    Route::get('/citas/buscar', [AppointmentController::class, 'search'])
        ->name('citas.buscar');

    Route::post('/citas', [AppointmentController::class, 'store'])
        ->name('citas.store');

    Route::post('/citas/{sharedId}/estado', [AppointmentController::class, 'updateStatus'])
        ->name('citas.estado');

    Route::post('/citas/previsualizacion-temporal', [HaircutPreviewController::class, 'generateTemp'])
        ->name('citas.previsualizacion');

    Route::get('/citas/previsualizacion-temporal/{jobId}', [HaircutPreviewController::class, 'status'])
        ->name('citas.previsualizacion.estado');

    Route::get('/clientes', [ClientController::class, 'index'])
        ->name('clientes');

    Route::post('/clientes', [ClientController::class, 'store'])
        ->name('clientes.store');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/mapa-calor', [HeatmapController::class, 'index'])
        ->name('mapa-calor');

    Route::get('/notificaciones', [NotificationController::class, 'index'])
        ->name('notificaciones');
});
