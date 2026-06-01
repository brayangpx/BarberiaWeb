@extends('plantillas.sistema')

@section('titulo', 'Dashboard')

@section('contenido')
@php
    $totalHorario = $datos['horarioMasActividad']['total'] ?? 0;
    $textoHorario = $datos['horarioMasActividad']['texto'] ?? 'Sin datos suficientes';
@endphp

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Ingresos Totales</small>
                <h3 class="mt-2 mb-0">
                    ${{ number_format($datos['ingresosTotales'] ?? 0, 2) }}
                </h3>
                <small class="text-muted">Registrados en el sistema</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Servicios Realizados</small>
                <h3 class="mt-2 mb-0">
                    {{ $datos['serviciosRealizados'] ?? 0 }}
                </h3>
                <small class="text-muted">Servicios finalizados</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Servicios Rápidos</small>
                <h3 class="mt-2 mb-0">
                    {{ $datos['serviciosRapidos'] ?? 0 }}
                </h3>
                <small class="text-muted">Registros rápidos</small>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <small class="text-muted">Clientes Concurrentes</small>
                <h3 class="mt-2 mb-0">
                    {{ $datos['clientesConcurrentes'] ?? 0 }}
                </h3>
                <small class="text-muted">Clientes con visitas finalizadas</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Resumen</h5>
    </div>

    <div class="card-body">
        <div class="border rounded p-3 mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-3">
                    <h6 class="mb-0">Estado de citas</h6>
                </div>

                <div class="col-6 col-md-2 text-center">
                    <small class="text-muted d-block">Pendientes</small>
                    <strong>{{ $datos['estadoCitas']['pending'] ?? 0 }}</strong>
                </div>

                <div class="col-6 col-md-2 text-center">
                    <small class="text-muted d-block">Confirmadas</small>
                    <strong>{{ $datos['estadoCitas']['confirmed'] ?? 0 }}</strong>
                </div>

                <div class="col-6 col-md-2 text-center">
                    <small class="text-muted d-block">Finalizadas</small>
                    <strong>{{ $datos['estadoCitas']['completed'] ?? 0 }}</strong>
                </div>

                <div class="col-6 col-md-2 text-center">
                    <small class="text-muted d-block">Canceladas</small>
                    <strong>{{ $datos['estadoCitas']['cancelled'] ?? 0 }}</strong>
                </div>
            </div>
        </div>

        <div class="border rounded p-3 mb-3">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-3">
                    <h6 class="mb-0">Tipo de registro</h6>
                </div>

                <div class="col-6 col-md-4 text-center">
                    <small class="text-muted d-block">Servicios rápidos</small>
                    <strong>{{ $datos['tipoRegistro']['quick'] ?? 0 }}</strong>
                </div>

                <div class="col-6 col-md-4 text-center">
                    <small class="text-muted d-block">Citas programadas</small>
                    <strong>{{ $datos['tipoRegistro']['scheduled'] ?? 0 }}</strong>
                </div>
            </div>
        </div>

        <div class="border rounded p-3">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-3">
                    <h6 class="mb-0">Horario con más actividad</h6>
                </div>

                <div class="col-12 col-md-5">
                    <strong>
                        {{ $textoHorario }}
                    </strong>
                </div>

                <div class="col-12 col-md-4">
                    <div class="border rounded p-2 text-center bg-light">
                        <small class="text-muted d-block">
                            Servicios en ese horario
                        </small>

                        <strong>
                            {{ $totalHorario }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection