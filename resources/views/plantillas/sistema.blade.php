<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbería Web</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/barberia.css') }}" rel="stylesheet">
</head>
<body @class(['bg-light', 'pagina-mapa-calor' => request()->routeIs('mapa-calor')])>

<div class="d-flex">
    @include('partes.menu-lateral')

    <main class="contenido-principal flex-grow-1">
        <div class="container-fluid p-3 p-md-4 pb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">@yield('titulo')</h4>

                <div class="d-flex gap-2 align-items-center">
                    @include('partes.campana-notificaciones')

                    @if (request()->routeIs('registrar-servicio'))
                        <a href="{{ route('agenda') }}" class="btn btn-outline-secondary btn-sm">
                            Cerrar
                        </a>
                    @else
                        <a href="{{ route('registrar-servicio') }}" class="btn btn-primary btn-sm">
                            Registrar Servicio
                        </a>
                    @endif
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    Revisa los datos del formulario.
                </div>
            @endif

            @yield('contenido')
        </div>
    </main>
</div>

@include('partes.menu-movil')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
