@extends('plantillas.sistema')

@section('titulo', 'Mapa de Calor')

@section('contenido')
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Mapa de calor por día y hora</h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center tabla-mapa-calor mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Día / Hora</th>

                        @foreach ($horas as $hora)
                            <th>{{ $hora }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($dias as $dia)
                        <tr>
                            <th class="table-light text-start">
                                {{ $dia }}
                            </th>

                            @foreach ($horas as $hora)
                                @php
                                    $cantidad = $matriz[$dia][$hora] ?? 0;

                                    if ($cantidad >= 20) {
                                        $nivel = 'table-danger';
                                    } elseif ($cantidad >= 10) {
                                        $nivel = 'table-warning';
                                    } elseif ($cantidad >= 5) {
                                        $nivel = 'table-success';
                                    } else {
                                        $nivel = '';
                                    }
                                @endphp

                                <td class="{{ $nivel }}">
                                    {{ $cantidad }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <div class="d-flex gap-3 flex-wrap">
                <div>
                    <span class="badge text-bg-success">Bajo</span>
                </div>

                <div>
                    <span class="badge text-bg-warning">Medio</span>
                </div>

                <div>
                    <span class="badge text-bg-danger">Alto</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
