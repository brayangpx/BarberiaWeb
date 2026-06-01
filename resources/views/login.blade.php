@extends('plantillas.acceso')

@section('contenido')
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 justify-content-center">
        <div class="col-12 col-sm-8 col-md-5 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h3 class="mb-1">Barbería Web</h3>
                        <p class="text-muted mb-0">Acceso del barbero</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            Teléfono o contraseña incorrectos.
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                class="form-control"
                                value="{{ old('phone') }}"
                                required
                                autofocus
                            >
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Entrar
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-muted small mt-3">
                Sistema de gestión para barbería
            </p>
        </div>
    </div>
</div>
@endsection