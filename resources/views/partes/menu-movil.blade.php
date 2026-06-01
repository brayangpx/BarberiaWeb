<nav class="menu-movil d-md-none bg-white border-top">
    <a href="{{ route('agenda') }}" class="{{ request()->routeIs('agenda') ? 'active' : '' }}">
        Agenda
    </a>

    <a href="{{ route('citas') }}" class="{{ request()->routeIs('citas') ? 'active' : '' }}">
        Citas
    </a>

    <a href="{{ route('clientes') }}" class="{{ request()->routeIs('clientes') ? 'active' : '' }}">
        Clientes
    </a>

    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        Dashboard
    </a>

    <a href="{{ route('mapa-calor') }}" class="{{ request()->routeIs('mapa-calor') ? 'active' : '' }}">
        Mapa
    </a>
</nav>