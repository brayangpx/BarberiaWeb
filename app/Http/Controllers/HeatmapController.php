<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\InternalNotificationService;
use Carbon\Carbon;

class HeatmapController extends Controller
{
    public function index(InternalNotificationService $notificationService)
    {
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('mapa-calor', array_merge($this->matrizMapaCalor(), [
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]));
    }

    private function matrizMapaCalor(): array
    {
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $horas = ['11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];
        $matriz = [];

        foreach ($dias as $dia) {
            foreach ($horas as $hora) {
                $matriz[$dia][$hora] = 0;
            }
        }

        $citas = Appointment::query()->whereIn('status', ['completed', 'confirmed'])
        ->get();

        foreach ($citas as $cita) {
            $numeroDia = Carbon::parse($cita->appointment_date)->dayOfWeekIso;
            $dia = $dias[$numeroDia - 1] ?? null;
            $hora = substr($cita->start_time, 0, 2) . ':00';

            if ($dia && isset($matriz[$dia][$hora])) {
                $matriz[$dia][$hora]++;
            }
        }

        return [
            'dias' => $dias,
            'horas' => $horas,
            'matriz' => $matriz,
        ];
    }
}
