<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DualWriteService
{
    private array $conexiones = ['mysql', 'mysql_secondary'];

    public function __construct(private SharedIdService $sharedIds)
    {
    }

    public function insertar(string $tabla, array $datos): void
    {
        $seGuardo = false;

        foreach ($this->conexiones as $conexion) {
            try {
                DB::connection($conexion)->table($tabla)->insert($datos);
                $seGuardo = true;
            } catch (Throwable $e) {
                $this->registrarPendiente($conexion, $tabla, 'insert', $datos['shared_id'] ?? null, $datos, $e->getMessage());
            }
        }

        if (! $seGuardo) {
            throw new RuntimeException('No hay bases de datos disponibles para guardar el registro.');
        }
    }

    public function actualizar(string $tabla, string $sharedId, array $datos): void
    {
        $seActualizo = false;

        foreach ($this->conexiones as $conexion) {
            try {
                DB::connection($conexion)->table($tabla)->where('shared_id', $sharedId)->update($datos);
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
            'payload' => json_encode($datos),
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
                DB::connection($conexion)->table('pending_syncs')->insert($pendiente);
                return;
            } catch (Throwable $e) {
            }
        }
    }
}
