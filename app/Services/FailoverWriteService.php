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

    public function insertar(string $modeloOTabla, array $datos): void
    {
        $tabla = $this->nombreTabla($modeloOTabla);
        $conexionActiva = $this->health->conexionLectura();

        try {
            $this->nuevoModelo($modeloOTabla, $conexionActiva)->newQuery()->create($datos);
        } catch (Throwable $e) {
            throw new RuntimeException('No hay bases de datos disponibles para guardar el registro.');
        }

        try {
            $this->registrarPendiente(
                $conexionActiva,
                $this->otraConexion($conexionActiva),
                $tabla,
                'insert',
                $datos['shared_id'] ?? null,
                $datos
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function actualizar(string $modeloOTabla, string $sharedId, array $datos): void
    {
        $tabla = $this->nombreTabla($modeloOTabla);
        $conexionActiva = $this->health->conexionLectura();

        try {
            $this->nuevoModelo($modeloOTabla, $conexionActiva)
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
                $sharedId,
                $datos
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function registrarPendiente(string $conexionOrigen, string $conexionDestino, string $tabla, string $operacion, ?string $sharedIdRegistro, array $datos): void
    {
        if ($tabla === 'pending_syncs') {
            return;
        }

        PendingSync::on($conexionOrigen)->create([
            'shared_id' => $this->sharedIds->crear('sync'),
            'target_connection' => $conexionDestino,
            'table_name' => $tabla,
            'operation' => $operacion,
            'record_shared_id' => $sharedIdRegistro,
            'payload' => $datos,
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nuevoModelo(string $modeloOTabla, string $conexion): Model
    {
        if (class_exists($modeloOTabla) && is_subclass_of($modeloOTabla, Model::class)) {
            return (new $modeloOTabla)->setConnection($conexion);
        }

        return (new class extends Model {
            protected $guarded = [];
        })->setTable($modeloOTabla)->setConnection($conexion);
    }

    private function nombreTabla(string $modeloOTabla): string
    {
        if (class_exists($modeloOTabla) && is_subclass_of($modeloOTabla, Model::class)) {
            return (new $modeloOTabla)->getTable();
        }

        return $modeloOTabla;
    }

    private function otraConexion(string $conexion): string
    {
        return $conexion === 'mysql' ? 'mysql_secondary' : 'mysql';
    }
}
