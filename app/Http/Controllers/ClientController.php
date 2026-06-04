<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\FailoverWriteService;
use App\Services\InternalNotificationService;
use App\Services\SharedIdService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct(
        private FailoverWriteService $writeService,
        private SharedIdService $sharedIds
    ) {
    }

    public function index(InternalNotificationService $notificationService)
    {
        $clientes = Client::query()
            ->withCount([
                'citas as visitas' => function ($consulta) {
                    $consulta->where('status', 'completed');
                },
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
            'phone' => ['nullable', 'max:10', Rule::unique('clients', 'phone')],
        ], [
            'phone.unique' => 'Este teléfono ya está registrado con otro cliente.',
        ]);

        $this->writeService->insertar(Client::class, [
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
