@extends('plantillas.sistema')

@section('titulo', 'Registrar Servicio')

@section('contenido')
<form
    action="{{ route('citas.store') }}"
    method="POST"
    data-preview-url="{{ route('citas.previsualizacion') }}"
    data-csrf-token="{{ csrf_token() }}"
>
    @csrf

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Datos del servicio</h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-12 col-md-6">
                            <label for="final_price" class="form-label">Precio</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="final_price"
                                id="final_price"
                                class="form-control"
                                value="{{ old('final_price') }}"
                                required
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="duration_minutes" class="form-label">Duración</label>
                            <input
                                type="number"
                                min="1"
                                name="duration_minutes"
                                id="duration_minutes"
                                class="form-control"
                                value="{{ old('duration_minutes') }}"
                                placeholder="Minutos"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="appointment_date" class="form-label">Fecha</label>
                            <input
                                type="date"
                                name="appointment_date"
                                id="appointment_date"
                                class="form-control"
                                value="{{ old('appointment_date', now()->toDateString()) }}"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="start_time" class="form-label">Hora</label>
                            <input
                                type="time"
                                name="start_time"
                                id="start_time"
                                class="form-control @error('start_time') is-invalid @enderror"
                                value="{{ old('start_time', now()->format('H:i')) }}"
                            >
                            @error('start_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="haircut_style_shared_id" class="form-label">Corte seleccionado</label>
                            <select name="haircut_style_shared_id" id="haircut_style_shared_id" class="form-select">
                                <option value="">Sin corte seleccionado</option>

                                @foreach ($cortes as $corte)
                                    <option
                                        value="{{ $corte->shared_id }}"
                                        data-name="{{ $corte->name }}"
                                        {{ old('haircut_style_shared_id') == $corte->shared_id ? 'selected' : '' }}
                                    >
                                        {{ $corte->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label">Estado</label>
                            <select name="status" id="status" class="form-select">
                                <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>
                                    Finalizada
                                </option>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>
                                    Pendiente
                                </option>
                                <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>
                                    Confirmada
                                </option>
                                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>
                                    Cancelada
                                </option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="appointment_type" id="appointment_type" value="quick">
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Datos del cliente, opcional</h5>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="client_shared_id" class="form-label">Cliente registrado</label>
                            <select name="client_shared_id" id="client_shared_id" class="form-select">
                                <option value="">Sin cliente registrado</option>

                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->shared_id }}" {{ old('client_shared_id') == $cliente->shared_id ? 'selected' : '' }}>
                                        {{ $cliente->name }} - {{ $cliente->phone }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="client_name" class="form-label">Nombre del cliente</label>
                            <input
                                type="text"
                                name="client_name"
                                id="client_name"
                                class="form-control"
                                value="{{ old('client_name') }}"
                                placeholder="Solo si desea registrarlo"
                            >
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="client_phone" class="form-label">Teléfono</label>
                            <input
                                type="text"
                                name="client_phone"
                                id="client_phone"
                                class="form-control @error('client_phone') is-invalid @enderror"
                                value="{{ old('client_phone') }}"
                                maxlength="10"
                                placeholder="Opcional"
                            >
                            @error('client_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Observaciones</label>
                            <textarea
                                name="notes"
                                id="notes"
                                class="form-control"
                                rows="3"
                                placeholder="Notas del servicio o cita"
                            >{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('agenda') }}" class="btn btn-outline-secondary">
                    Limpiar
                </a>

                <button type="submit" class="btn btn-primary">
                    Guardar
                </button>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Previsualización con IA</h5>
                </div>

                <div class="card-body">
                    <p class="text-muted small">
                        Opcional. Sube una foto proporcionada por el cliente y genera una referencia visual antes de guardar.
                    </p>

                    <input
                        type="file"
                        id="preview_image"
                        class="d-none"
                        accept="image/*"
                    >

                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary" id="btnSeleccionarFoto">
                            Subir foto
                        </button>

                        <button type="button" class="btn btn-primary" id="btnGenerar">
                            Generar
                        </button>
                    </div>

                    <div id="mensajePreview" class="small text-muted mb-3">
                        No se ha seleccionado ninguna imagen.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto original</label>
                        <div class="border rounded bg-light p-2 text-center">
                            <img id="imagenOriginal" src="" alt="" class="img-fluid d-none">
                            <span id="textoOriginal" class="text-muted small">Sin imagen</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Resultado IA</label>
                        <div class="border rounded bg-light p-2 text-center">
                            <img id="imagenGenerada" src="" alt="" class="img-fluid d-none">
                            <span id="textoGenerada" class="text-muted small">Sin resultado</span>
                        </div>
                    </div>

                    <input type="hidden" name="original_image_temp_path" id="original_image_temp_path">
                    <input type="hidden" name="generated_image_temp_path" id="generated_image_temp_path">
                    <input type="hidden" name="preview_prompt" id="preview_prompt">
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
    <script src="{{ asset('js/registrar-servicio.js') }}"></script>

@endsection


