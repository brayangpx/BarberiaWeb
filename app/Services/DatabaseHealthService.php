<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseHealthService
{
    public function estaDisponible(string $conexion): bool
    {
        try {
            DB::connection($conexion)->select('select 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function conexionLectura(): string
    {
        return $this->estaDisponible('mysql') ? 'mysql' : 'mysql_secondary';
    }

    public function conexionesDisponibles(): array
    {
        $conexiones = [];

        foreach (['mysql', 'mysql_secondary'] as $conexion) {
            if ($this->estaDisponible($conexion)) {
                $conexiones[] = $conexion;
            }
        }

        return $conexiones;
    }
}
