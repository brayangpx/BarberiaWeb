<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\HaircutStyle;
use App\Services\AppointmentService;
use App\Services\InternalNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointments
    ) {
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
        $clientes = Client::query()
            ->orderBy('name')
            ->get();

        $cortes = HaircutStyle::query()
            ->orderBy('name')
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
        $reglasTelefonoCliente = ['nullable', 'max:10'];

        if ($request->filled('client_name') && ! $request->filled('client_shared_id')) {
            $reglasTelefonoCliente[] = Rule::unique('clients', 'phone');
        }

        $request->validate([
            'final_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'client_phone' => $reglasTelefonoCliente,
        ], [
            'client_phone.unique' => 'Este teléfono ya está registrado con otro cliente.',
        ]);

        $this->appointments->crearDesdeRequest($request);

        return redirect()
            ->route('agenda')
            ->with('success', 'Servicio registrado correctamente.');
    }

    public function updateStatus(Request $request, string $sharedId)
    {
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $this->appointments->cambiarEstado($sharedId, $request->input('status'));

        return back()->with('success', 'Estado actualizado.');
    }

    private function buscarCitas(?string $texto)
    {
        $consulta = Appointment::query()
            ->with(['cliente', 'corte'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time');

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
