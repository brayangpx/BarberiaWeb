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
    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');

    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/search', [AppointmentController::class, 'search'])->name('appointments.search');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/appointments/{sharedId}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');

    Route::post('/appointments/preview-temp', [HaircutPreviewController::class, 'generateTemp'])->name('appointments.preview-temp');

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/heatmap', [HeatmapController::class, 'index'])->name('heatmap.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});
