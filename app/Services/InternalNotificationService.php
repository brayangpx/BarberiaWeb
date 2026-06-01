<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InternalNotificationService
{
    public function __construct(
        private DatabaseHealthService $health,
        private DualWriteService $dualWrite,
        private SharedIdService $sharedIds
    ) {
    }

    public function ultimas(int $limite = 5): Collection
    {
        $this->generarDeCitasProximas();
        $conexion = $this->health->conexionLectura();

        return DB::connection($conexion)->table('internal_notifications')
            ->orderByDesc('generated_at')
            ->limit($limite)
            ->get();
    }

    public function generarDeCitasProximas(): void
    {
        $conexion = $this->health->conexionLectura();
        $ahora = now();
        $limite = now()->addMinutes(5);

        $citas = DB::connection($conexion)->table('appointments')
            ->leftJoin('clients', 'appointments.client_shared_id', '=', 'clients.shared_id')
            ->where('appointments.status', 'pending')
            ->select('appointments.*', 'clients.name as client_name')
            ->get();

        foreach ($citas as $cita) {
            $inicio = now()->parse($cita->appointment_date . ' ' . $cita->start_time);

            if ($inicio->lt($ahora) || $inicio->gt($limite)) {
                continue;
            }

            $yaExiste = DB::connection($conexion)->table('internal_notifications')
                ->where('appointment_shared_id', $cita->shared_id)
                ->exists();

            if ($yaExiste) {
                continue;
            }

            $cliente = $cita->client_name ?: 'un cliente';
            $hora = $inicio->format('g:i A');

            $this->dualWrite->insertar('internal_notifications', [
                'shared_id' => $this->sharedIds->crear('notif'),
                'appointment_shared_id' => $cita->shared_id,
                'title' => 'Cita próxima',
                'message' => "Cita próxima. Tienes una cita programada con {$cliente} a las {$hora}. La cita comenzará en aproximadamente 5 minutos.",
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
