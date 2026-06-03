<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\InternalNotification;
use Illuminate\Support\Collection;

class InternalNotificationService
{
    public function __construct(
        private FailoverWriteService $writeService,
        private SharedIdService $sharedIds
    ) {
    }

    public function ultimas(int $limite = 5): Collection
    {
        $this->cancelarCitasNotificadasVencidas();
        $this->generarDeCitasProximas();

        return InternalNotification::query()
            ->whereHas('cita', function ($consulta) {
                $consulta->where('status', 'pending');
            })
            ->orderByDesc('generated_at')
            ->limit($limite)
            ->get();
    }

    private function cancelarCitasNotificadasVencidas(): void
    {
        $ahora = now();

        $notificaciones = InternalNotification::query()
            ->with('cita')
            ->whereHas('cita', function ($consulta) {
                $consulta->whereNotIn('status', ['completed', 'cancelled']);
            })
            ->get();

        foreach ($notificaciones as $notificacion) {
            $cita = $notificacion->cita;

            if (! $cita) {
                continue;
            }

            $fecha = $cita->appointment_date->toDateString();
            $termino = now()->parse($fecha . ' ' . $cita->start_time)
                ->addMinutes((int) ($cita->duration_minutes ?: 0));

            if ($termino->lt($ahora)) {
                $this->writeService->actualizar(Appointment::class, $cita->shared_id, [
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function generarDeCitasProximas(): void
    {
        $ahora = now();
        $limite = now()->addMinutes(5);

        $citas = Appointment::query()
            ->with('cliente')
            ->where('status', 'pending')
            ->whereDoesntHave('notificacion')
            ->get();

        foreach ($citas as $cita) {
            $fecha = $cita->appointment_date->toDateString();
            $inicio = now()->parse($fecha . ' ' . $cita->start_time);

            if ($inicio->lt($ahora) || $inicio->gt($limite)) {
                continue;
            }

            $cliente = $cita->cliente?->name ?: 'un cliente';
            $hora = $inicio->format('g:i A');

            $this->writeService->insertar(InternalNotification::class, [
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
