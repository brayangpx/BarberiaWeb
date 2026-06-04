<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\InternalNotificationService;

class AgendaController extends Controller
{
    public function index(InternalNotificationService $notificationService)
    {
        $hoy = now()->toDateString();
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('agenda', [
            'citas' => $this->citasDelDia($hoy),
            'resumen' => $this->resumenDelDia($hoy),
            'hoy' => $hoy,
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }

    private function citasDelDia(string $fecha)
    {
        return Appointment::query()->with(['cliente', 'corte'])
            ->whereDate('appointment_date', $fecha)->orderBy('start_time')
            ->get()->map(function (Appointment $cita) {
                $cita->setAttribute('client_name', $cita->cliente?->name);
                $cita->setAttribute('haircut_name', $cita->corte?->name);

                return $cita;
            });
    }

    private function resumenDelDia(string $fecha): array
    {
        $citas = Appointment::query()->whereDate('appointment_date', $fecha)
        ->get();

        return [
            'ingresos' => $citas->where('status', 'completed')->sum('final_price'),
            'servicios' => $citas->count(),
            'rapidos' => $citas->where('appointment_type', 'quick')->count(),
            'conCliente' => $citas->whereNotNull('client_shared_id')->count(),
        ];
    }
}
