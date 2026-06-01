@extends('plantillas.sistema')

@section('titulo', 'Clientes')

@section('contenido')
<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Clientes registrados</h5>

                    <span class="badge text-bg-light border">
                        Total: {{ $clientes->count() }}
                    </span>
                </div>
            </div>

            <div class="card-body">
                @forelse ($clientes as $cliente)
                    <div class="border rounded p-3 mb-2 bg-light">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <h6 class="mb-1">{{ $cliente->name }}</h6>

                                <small class="text-muted">
                                    Teléfono:
                                    @if ($cliente->phone)
                                        {{ $cliente->phone }}
                                    @else
                                        Sin teléfono
                                    @endif
                                </small>
                            </div>

                            <div class="col-12 col-md-5 mt-2 mt-md-0">
                                <div class="border rounded bg-white p-2 text-center">
                                    <small class="text-muted d-block">Visitas totales</small>
                                    <strong>{{ $cliente->visitas ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">
                        No hay clientes registrados.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Registrar Cliente</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('clientes.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control"
                            value="{{ old('name') }}"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            class="form-control"
                            value="{{ old('phone') }}"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Guardar Cliente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection