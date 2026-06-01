@extends('plantillas.sistema')

@section('titulo', 'Agenda')

@section('contenido')
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Agenda del día</h5>
                        <small class="text-muted">{{ $hoy ?? now()->toDateString() }}</small>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Corte / Servicio</th>
                                <th>Precio</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($citas as $cita)
                                <tr>
                                    <td>{{ substr($cita->start_time, 0, 5) }}</td>
                                    <td>
                                        {{ $cita->client_name ?? 'Sin cliente' }}
                                    </td>
                                    <td>
                                        @if ($cita->appointment_type === 'quick')
                                            Servicio rápido
                                        @else
                                            {{ $cita->haircut_name ?? 'Cita programada' }}
                                        @endif
                                    </td>
                                    <td>${{ number_format($cita->final_price, 2) }}</td>
                                    <td>
                                        @if ($cita->status === 'completed')
                                            <span class="badge bg-success">Finalizada</span>
                                        @elseif ($cita->status === 'confirmed')
                                            <span class="badge bg-primary">Confirmada</span>
                                        @elseif ($cita->status === 'cancelled')
                                            <span class="badge bg-danger">Cancelada</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No hay servicios registrados para hoy.
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
                                <strong>{{ substr($cita->start_time, 0, 5) }}</strong>

                                @if ($cita->status === 'completed')
                                    <span class="badge bg-success">Finalizada</span>
                                @elseif ($cita->status === 'confirmed')
                                    <span class="badge bg-primary">Confirmada</span>
                                @elseif ($cita->status === 'cancelled')
                                    <span class="badge bg-danger">Cancelada</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                @endif
                            </div>

                            <div class="mt-2">
                                <div>{{ $cita->client_name ?? 'Sin cliente' }}</div>
                                <small class="text-muted">
                                    @if ($cita->appointment_type === 'quick')
                                        Servicio rápido
                                    @else
                                        {{ $cita->haircut_name ?? 'Cita programada' }}
                                    @endif
                                </small>
                            </div>

                            <div class="mt-2">
                                <strong>${{ number_format($cita->final_price, 2) }}</strong>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">
                            No hay servicios registrados para hoy.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Resumen del día</h5>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Ingresos</small>
                    <h4 class="mb-0">${{ number_format($resumen['ingresos'] ?? 0, 2) }}</h4>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Servicios</small>
                            <strong>{{ $resumen['servicios'] ?? 0 }}</strong>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Rápidos</small>
                            <strong>{{ $resumen['rapidos'] ?? 0 }}</strong>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="border rounded p-2 text-center">
                            <small class="text-muted d-block">Citas con cliente</small>
                            <strong>{{ $resumen['conCliente'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>

                <a href="{{ route('registrar-servicio') }}" class="btn btn-primary w-100 mt-3">
                    Registrar Servicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection