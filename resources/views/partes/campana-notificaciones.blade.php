<div class="dropdown">
    <button class="btn btn-light border position-relative" type="button" data-bs-toggle="dropdown">
        🔔

        @if (($totalNotificaciones ?? 0) > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $totalNotificaciones }}
            </span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end p-3 caja-notificaciones">
        <h6 class="mb-3">Notificaciones</h6>

        @forelse (($notificaciones ?? []) as $notificacion)
            <div class="border-bottom pb-2 mb-2">
                <strong class="d-block">{{ $notificacion->title }}</strong>
                <small class="text-muted">
                    {{ $notificacion->message }}
                </small>
            </div>
        @empty
            <p class="text-muted mb-0 small">No hay notificaciones.</p>
        @endforelse
    </div>
</div>