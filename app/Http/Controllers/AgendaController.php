<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\InternalNotificationService;

class AgendaController extends Controller
{
    public function index(InternalNotificationService $notificationService)
    {
        $hoy = now()->toDateString();

        $citas = Appointment::query()
            ->with(['cliente', 'corte'])
            ->whereDate('appointment_date', $hoy)
            ->orderBy('start_time')
            ->get()
            ->map(function (Appointment $cita) {
                $cita->setAttribute('client_name', $cita->cliente?->name);
                $cita->setAttribute('haircut_name', $cita->corte?->name);

                return $cita;
            });

        $baseResumen = Appointment::query()->whereDate('appointment_date', $hoy);

        $resumen = [
            'ingresos' => (clone $baseResumen)
                ->where('status', 'completed')
                ->sum('final_price'),

            'servicios' => (clone $baseResumen)
                ->count(),

            'rapidos' => (clone $baseResumen)
                ->where('appointment_type', 'quick')
                ->count(),

            'conCliente' => (clone $baseResumen)
                ->whereNotNull('client_shared_id')
                ->count(),
        ];

        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('agenda', [
            'citas' => $citas,
            'resumen' => $resumen,
            'hoy' => $hoy,
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }
}
