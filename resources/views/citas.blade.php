@extends('plantillas.sistema')

@section('titulo', 'Citas')

@section('contenido')
<div class="card">
    <div class="card-header bg-white">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <h5 class="mb-0">Citas y servicios registrados</h5>
            </div>

            <div class="col-12 col-md-6">
                <input
                    type="text"
                    id="busquedaCitas"
                    class="form-control"
                    placeholder="Buscar por cliente o corte..."
                    value="{{ $busqueda ?? '' }}"
                >
            </div>
        </div>
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

                <tbody id="tablaCitas">
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
                            <td colspan="6" class="text-center text-muted">
                                No hay citas o servicios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-md-none" id="tarjetasCitas">
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

@section('scripts')
<script>
    const inputBusqueda = document.getElementById('busquedaCitas');
    const tablaCitas = document.getElementById('tablaCitas');
    const tarjetasCitas = document.getElementById('tarjetasCitas');

    function textoEstado(estado) {
        if (estado === 'completed') {
            return '<span class="badge bg-success">Finalizada</span>';
        }

        if (estado === 'confirmed') {
            return '<span class="badge bg-primary">Confirmada</span>';
        }

        if (estado === 'cancelled') {
            return '<span class="badge bg-danger">Cancelada</span>';
        }

        return '<span class="badge bg-warning text-dark">Pendiente</span>';
    }

    function servicioPrincipal(cita) {
        if (cita.appointment_type === 'quick') {
            return 'Servicio rápido';
        }

        return cita.client_name ?? 'Sin cliente';
    }

    function servicioSecundario(cita) {
        if (cita.appointment_type === 'quick') {
            return cita.haircut_name ?? 'Sin corte seleccionado';
        }

        return cita.haircut_name ?? 'Cita programada';
    }

    function formatoPrecio(valor) {
        const numero = Number(valor || 0);
        return '$' + numero.toFixed(2);
    }

    function formatoHora(hora) {
        if (!hora) {
            return '';
        }

        return hora.substring(0, 5);
    }

    function cargarCitas(texto) {
        fetch(`{{ route('citas.buscar') }}?query=${encodeURIComponent(texto)}`)
            .then(response => response.json())
            .then(citas => {
                tablaCitas.innerHTML = '';
                tarjetasCitas.innerHTML = '';

                if (citas.length === 0) {
                    tablaCitas.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No se encontraron resultados.
                            </td>
                        </tr>
                    `;

                    tarjetasCitas.innerHTML = `
                        <p class="text-muted mb-0">
                            No se encontraron resultados.
                        </p>
                    `;

                    return;
                }

                citas.forEach(cita => {
                    const duracion = cita.duration_minutes ? `${cita.duration_minutes} min` : '-';

                    tablaCitas.innerHTML += `
                        <tr>
                            <td>${cita.appointment_date}</td>
                            <td>${formatoHora(cita.start_time)}</td>
                            <td>
                                <strong>${servicioPrincipal(cita)}</strong>
                                <br>
                                <small class="text-muted">${servicioSecundario(cita)}</small>
                            </td>
                            <td>${duracion}</td>
                            <td>${formatoPrecio(cita.final_price)}</td>
                            <td>${textoEstado(cita.status)}</td>
                        </tr>
                    `;

                    tarjetasCitas.innerHTML += `
                        <div class="border rounded p-3 mb-2 bg-light">
                            <div class="d-flex justify-content-between">
                                <strong>${cita.appointment_date}</strong>
                                <span>${formatoHora(cita.start_time)}</span>
                            </div>

                            <div class="mt-2">
                                <strong>${servicioPrincipal(cita)}</strong>
                                <br>
                                <small class="text-muted">${servicioSecundario(cita)}</small>
                            </div>

                            <div class="mt-2">
                                <small class="text-muted">Duración: ${duracion}</small>
                            </div>

                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <strong>${formatoPrecio(cita.final_price)}</strong>
                                ${textoEstado(cita.status)}
                            </div>
                        </div>
                    `;
                });
            });
    }

    let tiempoBusqueda = null;

    inputBusqueda.addEventListener('input', function () {
        clearTimeout(tiempoBusqueda);

        tiempoBusqueda = setTimeout(function () {
            cargarCitas(inputBusqueda.value);
        }, 300);
    });
</script>
@endsection