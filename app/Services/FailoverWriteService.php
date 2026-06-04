<?php

namespace App\Services;

use App\Models\PendingSync;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class FailoverWriteService
{
    public function __construct(
        private SharedIdService $sharedIds,
        private DatabaseHealthService $health
    ) {
    }

    public function insertar(string $modelo, array $datos): void
    {
        $tabla = $this->nombreTabla($modelo);
        $conexionActiva = $this->health->conexionLectura();

        try {
            $this->modeloEnConexion($modelo, $conexionActiva)
                ->newQuery()
                ->create($datos);
        } catch (Throwable $e) {
            throw new RuntimeException('No hay bases de datos disponibles para guardar el registro.');
        }

        try {
            $this->registrarPendiente(
                $conexionActiva,
                $this->otraConexion($conexionActiva),
                $tabla,
                'insert',
                $datos['shared_id'] ?? null
            );
        } catch (Throwable $e) {
        }
    }

    public function actualizar(string $modelo, string $sharedId, array $datos): void
    {
        $tabla = $this->nombreTabla($modelo);
        $conexionActiva = $this->health->conexionLectura();

        try {
            $this->modeloEnConexion($modelo, $conexionActiva)
                ->newQuery()
                ->where('shared_id', $sharedId)
                ->update($datos);
        } catch (Throwable $e) {
            throw new RuntimeException('No hay bases de datos disponibles para actualizar el registro.');
        }

        try {
            $this->registrarPendiente(
                $conexionActiva,
                $this->otraConexion($conexionActiva),
                $tabla,
                'update',
                $sharedId
            );
        } catch (Throwable $e) {
        }
    }

    private function registrarPendiente(string $conexionOrigen, string $conexionDestino, string $tabla, string $operacion, ?string $sharedIdRegistro): void
    {
        if ($tabla === 'pending_syncs' || ! $sharedIdRegistro) {
            return;
        }

        $pendiente = PendingSync::on($conexionOrigen)
            ->where('target_connection', $conexionDestino)
            ->where('table_name', $tabla)
            ->where('record_shared_id', $sharedIdRegistro)
            ->first();

        if ($pendiente) {
            $pendiente->update([
                'operation' => $operacion,
                'status' => 'pending',
            ]);

            return;
        }

        PendingSync::on($conexionOrigen)->create([
            'shared_id' => $this->sharedIds->crear('sync'),
            'target_connection' => $conexionDestino,
            'table_name' => $tabla,
            'operation' => $operacion,
            'record_shared_id' => $sharedIdRegistro,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function modeloEnConexion(string $modelo, string $conexion): Model
    {
        if (! class_exists($modelo) || ! is_subclass_of($modelo, Model::class)) {
            throw new RuntimeException('El modelo indicado no es valido.');
        }

        return (new $modelo)->setConnection($conexion);
    }

    private function nombreTabla(string $modelo): string
    {
        return (new $modelo)->getTable();
    }

    private function otraConexion(string $conexion): string
    {
        return $conexion === 'mysql' ? 'mysql_secondary' : 'mysql';
    }
}
