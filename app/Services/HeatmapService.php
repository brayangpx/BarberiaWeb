<?php

namespace App\Services;

use App\Models\Appointment;

class HeatmapService
{
    public function matriz(): array
    {
        $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $horas = ['08:00','09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
        $matriz = [];

        foreach ($dias as $dia) {
            foreach ($horas as $hora) {
                $matriz[$dia][$hora] = 0;
            }
        }

        $citas = Appointment::query()
            ->whereIn('status', ['completed', 'confirmed'])
            ->get();

        foreach ($citas as $cita) {
            $numeroDia = (int) date('N', strtotime($cita->appointment_date));
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
