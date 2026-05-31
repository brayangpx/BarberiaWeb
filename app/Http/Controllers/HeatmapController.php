<?php

namespace App\Http\Controllers;

use App\Services\HeatmapService;
use App\Services\InternalNotificationService;

class HeatmapController extends Controller
{
    public function __construct(private HeatmapService $heatmap)
    {
    }

    public function index(InternalNotificationService $notificationService)
    {
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('heatmap.index', array_merge(
            $this->heatmap->matriz(),
            [
                'notificaciones' => $notificaciones,
                'totalNotificaciones' => $totalNotificaciones,
            ]
        ));
    }
}
