<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\DatabaseHealthService;
use App\Services\DualWriteService;
use App\Services\InternalNotificationService;
use App\Services\SharedIdService;
use Illuminate\Http\Request;

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

        $clientes = Client::on($conexion)
            ->withCount([
                'citas as visitas' => fn ($consulta) => $consulta->where('status', 'completed'),
            ])
            ->orderBy('name')
            ->get();

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

        $this->dualWrite->insertar(Client::class, [
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
