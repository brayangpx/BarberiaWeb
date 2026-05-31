<?php

namespace App\Http\Controllers;

use App\Services\DatabaseHealthService;
use App\Services\InternalNotificationService;
use Illuminate\Support\Facades\DB;

class AgendaController extends Controller
{
    public function __construct(private DatabaseHealthService $health)
    {
    }

    public function index(InternalNotificationService $notificationService)
    {
        $conexion = $this->health->conexionLectura();
        $hoy = now()->toDateString();

        $citas = DB::connection($conexion)->table('appointments')
            ->leftJoin('clients', 'appointments.client_shared_id', '=', 'clients.shared_id')
            ->leftJoin('haircut_styles', 'appointments.haircut_style_shared_id', '=', 'haircut_styles.shared_id')
            ->whereDate('appointments.appointment_date', $hoy)
            ->orderBy('appointments.start_time')
            ->select(
                'appointments.*',
                'clients.name as client_name',
                'haircut_styles.name as haircut_name'
            )
            ->get();

        $resumen = [
            'ingresos' => DB::connection($conexion)->table('appointments')
                ->whereDate('appointment_date', $hoy)
                ->where('status', 'completed')
                ->sum('final_price'),

            'servicios' => DB::connection($conexion)->table('appointments')
                ->whereDate('appointment_date', $hoy)
                ->count(),

            'rapidos' => DB::connection($conexion)->table('appointments')
                ->whereDate('appointment_date', $hoy)
                ->where('appointment_type', 'quick')
                ->count(),

            'conClientes' => DB::connection($conexion)->table('appointments')
                ->whereDate('appointment_date', $hoy)
                ->whereNotNull('client_shared_id')
                ->count(),
        ];

        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('agenda.index', [
            'citas' => $citas,
            'resumen' => $resumen,
            'hoy' => $hoy,
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }
}
