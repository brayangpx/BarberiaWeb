<?php

namespace App\Services;

use App\Models\Appointment;

class DashboardService
{
    public function __construct(private DatabaseHealthService $health)
    {
    }

    public function datos(): array
    {
        $conexion = $this->health->conexionLectura();
        $base = Appointment::on($conexion);

        $ingresosTotales = (clone $base)
            ->where('status', 'completed')
            ->sum('final_price');

        $serviciosRealizados = (clone $base)
            ->where('status', 'completed')
            ->count();

        $serviciosRapidos = (clone $base)
            ->where('appointment_type', 'quick')
            ->count();

        $clientesConcurrentes = (clone $base)
            ->whereNotNull('client_shared_id')
            ->where('status', 'completed')
            ->distinct('client_shared_id')
            ->count('client_shared_id');

        $citasProgramadas = (clone $base)
            ->where('appointment_type', 'scheduled')
            ->count();

        return [
            'ingresosTotales' => $ingresosTotales,
            'serviciosRealizados' => $serviciosRealizados,
            'serviciosRapidos' => $serviciosRapidos,
            'clientesConcurrentes' => $clientesConcurrentes,

            'estadoCitas' => [
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            ],

            'tipoRegistro' => [
                'quick' => $serviciosRapidos,
                'scheduled' => $citasProgramadas,
            ],

            'horarioMasActividad' => $this->horarioMasActivo($conexion),
        ];
    }

    private function horarioMasActivo(string $conexion): array
    {
        $citas = Appointment::on($conexion)
            ->where('status', 'completed')
            ->get();

        $conteo = [];

        $dias = [
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miércoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sábado',
            '7' => 'Domingo',
        ];

        foreach ($citas as $cita) {
            $hora = substr($cita->start_time, 0, 2) . ':00';
            $numeroDia = date('N', strtotime($cita->appointment_date));
            $dia = $dias[$numeroDia] ?? 'Día';

            $clave = $dia . ' ' . $hora;
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        arsort($conteo);

        $texto = array_key_first($conteo);

        return [
            'texto' => $texto ?: 'Sin datos suficientes',
            'total' => $texto ? $conteo[$texto] : 0,
        ];
    }
}
