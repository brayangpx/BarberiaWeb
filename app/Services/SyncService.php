<?php

namespace App\Services;

use App\Models\PendingSync;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SyncService
{
    public function __construct(private DatabaseHealthService $health)
    {
    }

    public function sincronizarPendientes(): int
    {
        $sincronizados = 0;

        foreach (['mysql', 'mysql_secondary'] as $conexionOrigen) {
            if (! $this->health->estaDisponible($conexionOrigen)) {
                continue;
            }

            $pendientes = PendingSync::on($conexionOrigen)->where('status', 'pending')
            ->orderBy('id')->get();

            foreach ($pendientes as $pendiente) {
                try {
                    $registroOrigen = DB::connection($conexionOrigen)->table($pendiente->table_name)
                    ->where('shared_id', $pendiente->record_shared_id)->first();

                    if (! $registroOrigen) {
                        throw new RuntimeException('No se encontro el registro origen para sincronizar.');
                    }

                    $datos = (array) $registroOrigen;
                    unset($datos['id']);

                    DB::connection($pendiente->target_connection)->table($pendiente->table_name)
                    ->updateOrInsert(['shared_id' => $pendiente->record_shared_id], $datos);

                    $pendiente->update([
                        'status' => 'synced',
                    ]);

                    $sincronizados++;
                } catch (Throwable $e) {
                }
            }
        }

        return $sincronizados;
    }
}
