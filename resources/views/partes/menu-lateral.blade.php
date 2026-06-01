<aside class="menu-lateral d-none d-md-block bg-dark text-white p-3">
    <h5 class="mb-4">Barbería</h5>

    <nav class="nav flex-column gap-2">
        <a href="{{ route('agenda') }}" class="nav-link enlace-menu {{ request()->routeIs('agenda') ? 'active' : '' }}">
            Agenda
        </a>

        <a href="{{ route('citas') }}" class="nav-link enlace-menu {{ request()->routeIs('citas') ? 'active' : '' }}">
            Citas
        </a>

        <a href="{{ route('clientes') }}" class="nav-link enlace-menu {{ request()->routeIs('clientes') ? 'active' : '' }}">
            Clientes
        </a>

        <a href="{{ route('dashboard') }}" class="nav-link enlace-menu {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            Dashboard
        </a>

        <a href="{{ route('mapa-calor') }}" class="nav-link enlace-menu {{ request()->routeIs('mapa-calor') ? 'active' : '' }}">
            Mapa de Calor
        </a>
    </nav>

    <form action="{{ route('logout') }}" method="POST" class="mt-4">
        @csrf
        <button type="submit" class="btn btn-outline-light btn-sm w-100">
            Cerrar sesión
        </button>
    </form>
</aside>