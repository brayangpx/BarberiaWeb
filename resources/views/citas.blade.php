@extends('plantillas.sistema')

@section('titulo', 'Citas')

@section('contenido')
@php
    $estadoSiguiente = [
        'pending' => 'confirmed',
        'confirmed' => 'completed',
        'completed' => 'cancelled',
        'cancelled' => 'pending',
    ];

    $estadoTexto = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmada',
        'completed' => 'Finalizada',
        'cancelled' => 'Cancelada',
    ];

    $estadoClase = [
        'pending' => 'bg-warning text-dark',
        'confirmed' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
    ];
@endphp

<div class="card">
    <div class="card-header bg-white">
        <form action="{{ route('citas') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <h5 class="mb-0">Citas y servicios registrados</h5>
            </div>

            <div class="col-12 col-md-4">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Buscar por cliente o corte..."
                    value="{{ $busqueda ?? '' }}"
                >
            </div>

            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <div class="card-body">
        <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Cliente / Servicio</th>
                        <th>Duración</th>
                        <th>Precio</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($citas as $cita)
                        <tr>
                            <td>{{ $cita->appointment_date }}</td>
                            <td>{{ substr($cita->start_time, 0, 5) }}</td>
                            <td>
                                <strong>
                                    @if ($cita->appointment_type === 'quick')
                                        Servicio rápido
                                    @else
                                        {{ $cita->client_name ?? 'Sin cliente' }}
                                    @endif
                                </strong>

                                <br>

                                <small class="text-muted">
                                    @if ($cita->appointment_type === 'quick')
                                        {{ $cita->haircut_name ?? 'Sin corte seleccionado' }}
                                    @else
                                        {{ $cita->haircut_name ?? 'Cita programada' }}
                                    @endif
                                </small>
                            </td>
                            <td>
                                @if ($cita->duration_minutes)
                                    {{ $cita->duration_minutes }} min
                                @else
                                    -
                                @endif
                            </td>
                            <td>${{ number_format($cita->final_price, 2) }}</td>
                            <td>
                                <form action="{{ route('citas.estado', $cita->shared_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $estadoSiguiente[$cita->status] ?? 'pending' }}">
                                    <button type="submit" class="badge border-0 {{ $estadoClase[$cita->status] ?? $estadoClase['pending'] }}">
                                        {{ $estadoTexto[$cita->status] ?? $estadoTexto['pending'] }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No hay citas o servicios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-md-none">
            @forelse ($citas as $cita)
                <div class="border rounded p-3 mb-2 bg-light">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $cita->appointment_date }}</strong>
                        <span>{{ substr($cita->start_time, 0, 5) }}</span>
                    </div>

                    <div class="mt-2">
                        <strong>
                            @if ($cita->appointment_type === 'quick')
                                Servicio rápido
                            @else
                                {{ $cita->client_name ?? 'Sin cliente' }}
                            @endif
                        </strong>

                        <br>

                        <small class="text-muted">
                            @if ($cita->appointment_type === 'quick')
                                {{ $cita->haircut_name ?? 'Sin corte seleccionado' }}
                            @else
                                {{ $cita->haircut_name ?? 'Cita programada' }}
                            @endif
                        </small>
                    </div>

                    <div class="mt-2">
                        <small class="text-muted">
                            Duración:
                            @if ($cita->duration_minutes)
                                {{ $cita->duration_minutes }} min
                            @else
                                -
                            @endif
                        </small>
                    </div>

                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <strong>${{ number_format($cita->final_price, 2) }}</strong>

                        <form action="{{ route('citas.estado', $cita->shared_id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="{{ $estadoSiguiente[$cita->status] ?? 'pending' }}">
                            <button type="submit" class="badge border-0 {{ $estadoClase[$cita->status] ?? $estadoClase['pending'] }}">
                                {{ $estadoTexto[$cita->status] ?? $estadoTexto['pending'] }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">
                    No hay citas o servicios registrados.
                </p>
            @endforelse
        </div>
    </div>
</div>
@endsection
