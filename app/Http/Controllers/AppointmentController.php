<?php

namespace App\Http\Controllers;

use App\Services\AppointmentService;
use App\Services\DatabaseHealthService;
use App\Services\InternalNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentService $appointments,
        private DatabaseHealthService $health
    ) {
    }

    public function index(Request $request, InternalNotificationService $notificationService)
    {
        $citas = $this->buscarCitas($request->input('q'));
        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('appointments.index', [
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
        $conexion = $this->health->conexionLectura();

        $clientes = DB::connection($conexion)->table('clients')
            ->orderBy('name')
            ->get();

        $cortes = DB::connection($conexion)->table('haircut_styles')
            ->orderBy('name')
            ->get();

        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('appointments.create', [
            'clientes' => $clientes,
            'cortes' => $cortes,
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'final_price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->appointments->crearDesdeRequest($request);

        return redirect()
            ->route('agenda.index')
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
        $conexion = $this->health->conexionLectura();

        $consulta = DB::connection($conexion)->table('appointments')
            ->leftJoin('clients', 'appointments.client_shared_id', '=', 'clients.shared_id')
            ->leftJoin('haircut_styles', 'appointments.haircut_style_shared_id', '=', 'haircut_styles.shared_id')
            ->select(
                'appointments.*',
                'clients.name as client_name',
                'haircut_styles.name as haircut_name'
            )
            ->orderByDesc('appointments.appointment_date')
            ->orderByDesc('appointments.start_time');

        if ($texto) {
            $consulta->where(function ($subconsulta) use ($texto) {
                $subconsulta->where('clients.name', 'like', "%{$texto}%")
                    ->orWhere('haircut_styles.name', 'like', "%{$texto}%");
            });
        }

        return $consulta->limit(50)->get();
    }
}
