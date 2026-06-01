<?php

namespace App\Http\Middleware;

use App\Services\DatabaseHealthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class UseActiveDatabaseConnection
{
    public function __construct(private DatabaseHealthService $health)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $conexion = $this->health->conexionLectura();
        } catch (RuntimeException $e) {
            if ($request->isMethod('get') && ($request->routeIs('login') || $request->is('/'))) {
                return $next($request);
            }

            return response('No hay bases de datos disponibles.', 503);
        }

        config(['database.default' => $conexion]);
        DB::setDefaultConnection($conexion);

        return $next($request);
    }
}
