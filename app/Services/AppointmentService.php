<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\HaircutPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentService
{
    public function __construct(
        private DualWriteService $dualWrite,
        private SharedIdService $sharedIds
    ) {
    }

    public function crearDesdeRequest(Request $request): string
    {
        $clienteSharedId = $request->input('client_shared_id');

        if (! $clienteSharedId && $request->filled('client_name')) {
            $clienteSharedId = $this->sharedIds->crear('client');

            $this->dualWrite->insertar(Client::class, [
                'shared_id' => $clienteSharedId,
                'name' => $request->input('client_name'),
                'phone' => $request->input('client_phone'),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $esCitaDetallada = $clienteSharedId
            || $request->filled('haircut_style_shared_id')
            || $request->filled('appointment_date')
            || $request->filled('start_time');

        $tipoCita = $esCitaDetallada ? 'scheduled' : 'quick';
        $fecha = $request->input('appointment_date') ?: now()->toDateString();
        $hora = $request->input('start_time') ?: now()->format('H:i');
        $estado = $request->input('status') ?: ($tipoCita === 'quick' ? 'completed' : 'pending');
        $sharedId = $this->sharedIds->crear('appt');
        $usuario = Auth::user();

        $datos = [
            'shared_id' => $sharedId,
            'user_shared_id' => $usuario?->shared_id,
            'client_shared_id' => $clienteSharedId,
            'haircut_style_shared_id' => $request->input('haircut_style_shared_id'),
            'appointment_type' => $tipoCita,
            'appointment_date' => $fecha,
            'start_time' => $hora,
            'duration_minutes' => $request->input('duration_minutes'),
            'final_price' => $request->input('final_price', 0),
            'status' => $estado,
            'notes' => $request->input('notes'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->dualWrite->insertar(Appointment::class, $datos);

        if ($request->filled('original_image_temp_path') && $request->filled('generated_image_temp_path')) {
            $this->dualWrite->insertar(HaircutPreview::class, [
                'shared_id' => $this->sharedIds->crear('preview'),
                'appointment_shared_id' => $sharedId,
                'original_image_url' => $request->input('original_image_temp_path'),
                'generated_image_url' => $request->input('generated_image_temp_path'),
                'prompt' => $request->input('preview_prompt'),
                'status' => 'completed',
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $sharedId;
    }

    public function cambiarEstado(string $sharedIdCita, string $estado): void
    {
        $this->dualWrite->actualizar(Appointment::class, $sharedIdCita, [
            'status' => $estado,
            'updated_at' => now(),
        ]);
    }
}
