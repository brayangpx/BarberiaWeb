<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private DatabaseHealthService $health)
    {
    }

    public function datos(): array
    {
        $conexion = $this->health->conexionLectura();
        $base = DB::connection($conexion)->table('appointments');

        $ingresos = (clone $base)->where('status', 'completed')->sum('final_price');
        $servicios = (clone $base)->where('status', 'completed')->count();
        $rapidos = (clone $base)->where('appointment_type', 'quick')->count();
        $clientes = DB::connection($conexion)->table('appointments')
            ->whereNotNull('client_shared_id')
            ->distinct('client_shared_id')
            ->count('client_shared_id');

        $estados = [
            'Pendientes' => (clone $base)->where('status', 'pending')->count(),
            'Confirmadas' => (clone $base)->where('status', 'confirmed')->count(),
            'Finalizadas' => (clone $base)->where('status', 'completed')->count(),
            'Canceladas' => (clone $base)->where('status', 'cancelled')->count(),
        ];

        $programadas = (clone $base)->where('appointment_type', 'scheduled')->count();

        return [
            'ingresos' => $ingresos,
            'servicios' => $servicios,
            'rapidos' => $rapidos,
            'clientes' => $clientes,
            'estados' => $estados,
            'tipoRegistro' => [
                'Servicios rápidos' => $rapidos,
                'Citas programadas' => $programadas,
            ],
            'horarioActivo' => $this->horarioMasActivo($conexion),
        ];
    }

    private function horarioMasActivo(string $conexion): string
    {
        $citas = DB::connection($conexion)->table('appointments')
            ->where('status', 'completed')
            ->get();

        $conteo = [];

        foreach ($citas as $cita) {
            $hora = substr($cita->start_time, 0, 2) . ':00';
            $dia = date('N', strtotime($cita->appointment_date));
            $dias = ['1' => 'Lunes', '2' => 'Martes', '3' => 'Miércoles', '4' => 'Jueves', '5' => 'Viernes', '6' => 'Sábado', '7' => 'Domingo'];
            $clave = ($dias[$dia] ?? 'Día') . ' ' . $hora;
            $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
        }

        arsort($conteo);

        return array_key_first($conteo) ?: 'Sin datos suficientes';
    }
}
