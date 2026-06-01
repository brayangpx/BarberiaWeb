<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\InternalNotification;
use Illuminate\Support\Collection;

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

        return InternalNotification::on($conexion)
            ->orderByDesc('generated_at')
            ->limit($limite)
            ->get();
    }

    public function generarDeCitasProximas(): void
    {
        $conexion = $this->health->conexionLectura();
        $ahora = now();
        $limite = now()->addMinutes(5);

        $citas = Appointment::on($conexion)
            ->with('cliente')
            ->where('status', 'pending')
            ->whereDoesntHave('notificacion')
            ->get();

        foreach ($citas as $cita) {
            $inicio = now()->parse($cita->appointment_date . ' ' . $cita->start_time);

            if ($inicio->lt($ahora) || $inicio->gt($limite)) {
                continue;
            }

            $cliente = $cita->cliente?->name ?: 'un cliente';
            $hora = $inicio->format('g:i A');

            $this->dualWrite->insertar(InternalNotification::class, [
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
