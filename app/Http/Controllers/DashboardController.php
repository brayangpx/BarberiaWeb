<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\InternalNotificationService;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function index(InternalNotificationService $notificationService)
    {
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('dashboard', [
            'datos' => $this->dashboard->datos(),
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }
}