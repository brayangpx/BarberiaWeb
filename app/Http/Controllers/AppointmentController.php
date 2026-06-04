<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\HaircutStyle;
use App\Models\HaircutPreview;
use App\Services\FailoverWriteService;
use App\Services\InternalNotificationService;
use App\Services\SharedIdService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function __construct(private FailoverWriteService $writeService,
        private SharedIdService $sharedIds) {
    }

    public function index(Request $request, InternalNotificationService $notificationService)
    {
        $citas = $this->buscarCitas($request->input('q'));
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('citas', [
            'citas' => $citas,
            'busqueda' => $request->input('q'),
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }

    public function search(Request $request)
    {
        return response()->json($this->buscarCitas($request->input('query')));
    }
 
    public function create(InternalNotificationService $notificationService)
    {
        $clientes = Client::query()->orderBy('name')
        ->get();

        $cortes = HaircutStyle::query()->orderBy('name')
        ->get();
 
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('registrar-servicio', [
            'clientes' => $clientes,
            'cortes' => $cortes,
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }

    public function store(Request $request)
    {
        $this->validarFormulario($request);

        $this->guardarCita($request);

        return redirect()->route('agenda')
        ->with('success', 'Servicio registrado correctamente.');
    }

    public function updateStatus(Request $request, string $sharedId)
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $this->writeService->actualizar(Appointment::class, $sharedId, [
            'status' => $request->input('status'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Estado actualizado.');
    }

    private function validarFormulario(Request $request): void
    {
        $reglasTelefonoCliente = ['nullable', 'max:10'];

        if ($request->filled('client_name') && ! $request->filled('client_shared_id')) {
            $reglasTelefonoCliente[] = Rule::unique('clients', 'phone');
        }

        $request->validate([
            'final_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'client_phone' => $reglasTelefonoCliente,
        ], [
            'client_phone.unique' => 'Este telefono ya esta registrado con otro cliente.',
        ]);
    }

    private function guardarCita(Request $request): string
    {
        $hora = $request->input('start_time') ?: now()->format('H:i');
        $duracion = $request->input('duration_minutes');
        $tipoCita = $this->tipoCita($request);

        if ($tipoCita === 'scheduled') {
            $this->validarHorarioPermitido($hora);
        }

        $fecha = $request->input('appointment_date') ?: now()->toDateString();
        $estado = $request->input('status') ?: ($tipoCita === 'quick' ? 'completed' : 'pending');

        if ($tipoCita === 'scheduled') {
            $this->validarHorarioDisponible($fecha, $hora, (int) $duracion);
        }

        $clienteSharedId = $this->crearClienteSiHaceFalta($request);
        $sharedId = $this->sharedIds->crear('appt');
        $usuario = Auth::user();

        $this->writeService->insertar(Appointment::class, [
            'shared_id' => $sharedId,
            'user_shared_id' => $usuario?->shared_id,
            'client_shared_id' => $clienteSharedId,
            'haircut_style_shared_id' => $request->input('haircut_style_shared_id'),
            'appointment_type' => $tipoCita,
            'appointment_date' => $fecha,
            'start_time' => $hora,
            'duration_minutes' => $duracion,
            'final_price' => $request->input('final_price', 0),
            'status' => $estado,
            'notes' => $request->input('notes'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->guardarPreviewSiExiste($request, $sharedId);

        return $sharedId;
    }

    private function tipoCita(Request $request): string
    {
        return $request->filled('duration_minutes') ? 'scheduled' : 'quick';
    }

    private function crearClienteSiHaceFalta(Request $request): ?string
    {
        $clienteSharedId = $request->input('client_shared_id');

        if (! $clienteSharedId && $request->filled('client_name')) {
            $clienteSharedId = $this->sharedIds->crear('client');

            $this->writeService->insertar(Client::class, [
                'shared_id' => $clienteSharedId,
                'name' => $request->input('client_name'),
                'phone' => $request->input('client_phone'),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $clienteSharedId;
    }

    private function guardarPreviewSiExiste(Request $request, string $appointmentSharedId): void
    {
        if ($request->filled('original_image_temp_path') && $request->filled('generated_image_temp_path')) {
            $this->writeService->insertar(HaircutPreview::class, [
                'shared_id' => $this->sharedIds->crear('preview'),
                'appointment_shared_id' => $appointmentSharedId,
                'original_image_url' => $request->input('original_image_temp_path'),
                'generated_image_url' => $request->input('generated_image_temp_path'),
                'prompt' => $request->input('preview_prompt'),
                'status' => 'completed',
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function validarHorarioPermitido(string $hora): void
    {
        $horaInicioPermitido = '11:00';
        $horaFinPermitido = '21:00';

        if ($hora < $horaInicioPermitido || $hora > $horaFinPermitido) {
            throw ValidationException::withMessages([
                'start_time' => ["El servicio no se puede registrar. El horario de atencion permitido es de {$horaInicioPermitido} a {$horaFinPermitido}."],
            ]);
        }
    }

    private function validarHorarioDisponible(string $fecha, string $hora, int $duracion): void
    {
        $inicioNuevo = Carbon::parse($fecha . ' ' . $hora);
        $finNuevo = (clone $inicioNuevo)->addMinutes($duracion);

        $citas = Appointment::query()->whereDate('appointment_date', $fecha)
        ->where('appointment_type', 'scheduled')
        ->whereNotNull('duration_minutes')
        ->where('status', '!=', 'cancelled')->get();

        foreach ($citas as $cita) {
            $inicioExistente = Carbon::parse($cita->appointment_date->toDateString() . ' ' . $cita->start_time);
            $finExistente = (clone $inicioExistente)->addMinutes((int) $cita->duration_minutes);

            if ($inicioNuevo->lt($finExistente) && $finNuevo->gt($inicioExistente)) {
                throw ValidationException::withMessages([
                    'start_time' => ['Ya existe una cita registrada en ese horario.'],
                ]);
            }
        }
    }

    private function buscarCitas(?string $texto)
    {
        $consulta = Appointment::query()->with(['cliente', 'corte'])
            ->orderByDesc('appointment_date')->orderByDesc('start_time');

        if ($texto) {
            $consulta->where(function ($subconsulta) use ($texto) {
                $subconsulta->whereHas('cliente', function ($cliente) use ($texto) {
                    $cliente->where('name', 'like', "%{$texto}%");
                })->orWhereHas('corte', function ($corte) use ($texto) {
                    $corte->where('name', 'like', "%{$texto}%");
                });
            });
        }

        return $consulta->limit(50)->get()->map(function (Appointment $cita) {
            $cita->setAttribute('client_name', $cita->cliente?->name);
            $cita->setAttribute('haircut_name', $cita->corte?->name);

            return $cita;
        });
    }
}
