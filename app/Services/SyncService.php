<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class SyncService
{
    public function sincronizarPendientes(): int
    {
        $sincronizados = 0;

        foreach (['mysql', 'mysql_secondary'] as $conexionOrigen) {
            $pendientes = DB::connection($conexionOrigen)->table('pending_syncs')
                ->where('status', 'pending')
                ->get();

            foreach ($pendientes as $pendiente) {
                try {
                    $datos = json_decode($pendiente->payload, true) ?: [];

                    if ($pendiente->operation === 'insert') {
                        $existe = DB::connection($pendiente->target_connection)
                            ->table($pendiente->table_name)
                            ->where('shared_id', $pendiente->record_shared_id)
                            ->exists();

                        if (! $existe) {
                            DB::connection($pendiente->target_connection)
                                ->table($pendiente->table_name)
                                ->insert($datos);
                        }
                    }

                    if ($pendiente->operation === 'update') {
                        DB::connection($pendiente->target_connection)
                            ->table($pendiente->table_name)
                            ->where('shared_id', $pendiente->record_shared_id)
                            ->update($datos);
                    }

                    DB::connection($conexionOrigen)->table('pending_syncs')
                        ->where('id', $pendiente->id)
                        ->update([
                            'status' => 'synced',
                            'updated_at' => now(),
                        ]);

                    $sincronizados++;
                } catch (Throwable $e) {
                    DB::connection($conexionOrigen)->table('pending_syncs')
                        ->where('id', $pendiente->id)
                        ->update([
                            'attempts' => $pendiente->attempts + 1,
                            'error_message' => $e->getMessage(),
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        return $sincronizados;
    }
}
