<?php

namespace App\Http\Controllers;

use App\Services\DatabaseHealthService;
use App\Services\DualWriteService;
use App\Services\InternalNotificationService;
use App\Services\SharedIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function __construct(
        private DatabaseHealthService $health,
        private DualWriteService $dualWrite,
        private SharedIdService $sharedIds
    ) {
    }

    public function index(InternalNotificationService $notificationService)
    {
        $conexion = $this->health->conexionLectura();

        $clientes = DB::connection($conexion)->table('clients')
            ->orderBy('name')
            ->get()
            ->map(function ($cliente) use ($conexion) {
                $cliente->visitas = DB::connection($conexion)->table('appointments')
                    ->where('client_shared_id', $cliente->shared_id)
                    ->where('status', 'completed')
                    ->count();

                return $cliente;
            });

        $notificaciones = $notificationService->ultimas(5);
        $totalNotificaciones = $notificaciones->count();

        return view('clientes', [
            'clientes' => $clientes,
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:120'],
            'phone' => ['nullable', 'max:30'],
        ]);

        $this->dualWrite->insertar('clients', [
            'shared_id' => $this->sharedIds->crear('client'),
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'notes' => $request->input('notes'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Cliente registrado.');
    }
}