<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DatabaseHealthService
{
    public function estaDisponible(string $conexion): bool
    {
        try {
            DB::purge($conexion);
            DB::connection($conexion)->select('select 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function conexionLectura(): string
    {
        if ($this->estaDisponible('mysql')) {
            return 'mysql';
        }

        if ($this->estaDisponible('mysql_secondary')) {
            return 'mysql_secondary';
        }

        throw new RuntimeException('No hay bases de datos disponibles.');
    }

}
