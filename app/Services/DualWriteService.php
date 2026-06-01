<?php

namespace App\Services;

use App\Models\PendingSync;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class DualWriteService
{
    private array $conexiones = ['mysql', 'mysql_secondary'];

    public function __construct(private SharedIdService $sharedIds)
    {
    }

    public function insertar(string $modeloOTabla, array $datos): void
    {
        $seGuardo = false;
        $tabla = $this->nombreTabla($modeloOTabla);

        foreach ($this->conexiones as $conexion) {
            try {
                $this->nuevoModelo($modeloOTabla, $conexion)->newQuery()->create($datos);
                $seGuardo = true;
            } catch (Throwable $e) {
                $this->registrarPendiente($conexion, $tabla, 'insert', $datos['shared_id'] ?? null, $datos, $e->getMessage());
            }
        }

        if (! $seGuardo) {
            throw new RuntimeException('No hay bases de datos disponibles para guardar el registro.');
        }
    }

    public function actualizar(string $modeloOTabla, string $sharedId, array $datos): void
    {
        $seActualizo = false;
        $tabla = $this->nombreTabla($modeloOTabla);

        foreach ($this->conexiones as $conexion) {
            try {
                $this->nuevoModelo($modeloOTabla, $conexion)
                    ->newQuery()
                    ->where('shared_id', $sharedId)
                    ->update($datos);

                $seActualizo = true;
            } catch (Throwable $e) {
                $this->registrarPendiente($conexion, $tabla, 'update', $sharedId, $datos, $e->getMessage());
            }
        }

        if (! $seActualizo) {
            throw new RuntimeException('No hay bases de datos disponibles para actualizar el registro.');
        }
    }

    private function registrarPendiente(string $conexionDestino, string $tabla, string $operacion, ?string $sharedIdRegistro, array $datos, string $error): void
    {
        $pendiente = [
            'shared_id' => $this->sharedIds->crear('sync'),
            'target_connection' => $conexionDestino,
            'table_name' => $tabla,
            'operation' => $operacion,
            'record_shared_id' => $sharedIdRegistro,
            'payload' => $datos,
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ($this->conexiones as $conexion) {
            if ($conexion === $conexionDestino) {
                continue;
            }

            try {
                PendingSync::on($conexion)->create($pendiente);
                return;
            } catch (Throwable $e) {
            }
        }
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
}
