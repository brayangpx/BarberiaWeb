<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\HaircutPreview;
use App\Models\HaircutStyle;
use App\Models\InternalNotification;
use App\Models\PendingSync;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class SyncService
{
    private array $modelosPorTabla = [
        'appointments' => Appointment::class,
        'clients' => Client::class,
        'haircut_previews' => HaircutPreview::class,
        'haircut_styles' => HaircutStyle::class,
        'internal_notifications' => InternalNotification::class,
        'pending_syncs' => PendingSync::class,
        'users' => User::class,
    ];

    public function sincronizarPendientes(): int
    {
        $sincronizados = 0;

        foreach (['mysql', 'mysql_secondary'] as $conexionOrigen) {
            $pendientes = PendingSync::on($conexionOrigen)
                ->where('status', 'pending')
                ->get();

            foreach ($pendientes as $pendiente) {
                try {
                    $datos = is_array($pendiente->payload)
                        ? $pendiente->payload
                        : (json_decode($pendiente->payload, true) ?: []);

                    if ($pendiente->operation === 'insert') {
                        $existe = $this->modeloParaTabla($pendiente->table_name, $pendiente->target_connection)
                            ->newQuery()
                            ->where('shared_id', $pendiente->record_shared_id)
                            ->exists();

                        if (! $existe) {
                            $this->modeloParaTabla($pendiente->table_name, $pendiente->target_connection)
                                ->newQuery()
                                ->create($datos);
                        }
                    }

                    if ($pendiente->operation === 'update') {
                        $this->modeloParaTabla($pendiente->table_name, $pendiente->target_connection)
                            ->newQuery()
                            ->where('shared_id', $pendiente->record_shared_id)
                            ->update($datos);
                    }

                    $pendiente->forceFill([
                        'status' => 'synced',
                    ])->save();

                    $sincronizados++;
                } catch (Throwable $e) {
                    $pendiente->forceFill([
                        'attempts' => $pendiente->attempts + 1,
                        'error_message' => $e->getMessage(),
                    ])->save();
                }
            }
        }

        return $sincronizados;
    }

    private function modeloParaTabla(string $tabla, string $conexion): Model
    {
        $clase = $this->modelosPorTabla[$tabla] ?? null;

        if ($clase) {
            return (new $clase)->setConnection($conexion);
        }

        return (new class extends Model {
            protected $guarded = [];
        })->setTable($tabla)->setConnection($conexion);
    }
}
