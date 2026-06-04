const formularioServicio = document.querySelector('[data-preview-url]');

if (formularioServicio) {
    const inputFoto = document.getElementById('preview_image');
    const btnSeleccionarFoto = document.getElementById('btnSeleccionarFoto');
    const btnGenerar = document.getElementById('btnGenerar');
    const mensajePreview = document.getElementById('mensajePreview');

    const imagenOriginal = document.getElementById('imagenOriginal');
    const imagenGenerada = document.getElementById('imagenGenerada');
    const textoOriginal = document.getElementById('textoOriginal');
    const textoGenerada = document.getElementById('textoGenerada');

    const corteSelect = document.getElementById('haircut_style_shared_id');
    const clienteSelect = document.getElementById('client_shared_id');
    const appointmentType = document.getElementById('appointment_type');

    const originalPathInput = document.getElementById('original_image_temp_path');
    const generatedPathInput = document.getElementById('generated_image_temp_path');
    const promptInput = document.getElementById('preview_prompt');

    btnSeleccionarFoto.addEventListener('click', function () {
        inputFoto.click();
    });

    inputFoto.addEventListener('change', function () {
        const archivo = inputFoto.files[0];

        if (!archivo) {
            mensajePreview.textContent = 'No se ha seleccionado ninguna imagen.';
            return;
        }

        mensajePreview.textContent = 'Imagen seleccionada: ' + archivo.name;

        const urlTemporal = URL.createObjectURL(archivo);
        imagenOriginal.src = urlTemporal;
        imagenOriginal.classList.remove('d-none');
        textoOriginal.classList.add('d-none');
    });

    clienteSelect.addEventListener('change', function () {
        if (clienteSelect.value) {
            appointmentType.value = 'scheduled';
        }
    });

    btnGenerar.addEventListener('click', function () {
        const archivo = inputFoto.files[0];
        const opcionCorte = corteSelect.options[corteSelect.selectedIndex];
        const nombreCorte = opcionCorte ? opcionCorte.dataset.name : '';

        if (!archivo) {
            mensajePreview.textContent = 'Primero selecciona una foto.';
            return;
        }

        if (!nombreCorte) {
            mensajePreview.textContent = 'Primero selecciona un corte.';
            return;
        }

        mensajePreview.textContent = 'Generando previsualizacion...';
        btnGenerar.disabled = true;

        const datos = new FormData();
        datos.append('preview_image', archivo);
        datos.append('haircut_name', nombreCorte);
        datos.append('_token', formularioServicio.dataset.csrfToken);

        fetch(formularioServicio.dataset.previewUrl, {
            method: 'POST',
            body: datos
        })
            .then(response => response.json())
            .then(data => {
                if (!data.ok) {
                    btnGenerar.disabled = false;
                    mensajePreview.textContent = data.error || 'No se pudo generar la previsualizacion.';
                    return;
                }

                revisarEstadoPreview(data.job_id);
            })
            .catch(() => {
                btnGenerar.disabled = false;
                mensajePreview.textContent = 'Ocurrio un error al generar la previsualizacion.';
            });
    });

    function revisarEstadoPreview(jobId) {
        if (!jobId) {
            btnGenerar.disabled = false;
            mensajePreview.textContent = 'No se pudo iniciar la previsualizacion.';
            return;
        }

        fetch(formularioServicio.dataset.previewStatusUrl + '/' + jobId)
            .then(response => response.json())
            .then(data => {
                if (!data.ok) {
                    btnGenerar.disabled = false;
                    mensajePreview.textContent = data.error || 'No se pudo generar la previsualizacion.';
                    return;
                }

                if (data.status === 'pending' || data.status === 'processing') {
                    mensajePreview.textContent = 'Generando previsualizacion...';
                    setTimeout(function () {
                        revisarEstadoPreview(jobId);
                    }, 2000);
                    return;
                }

                btnGenerar.disabled = false;
                mensajePreview.textContent = 'Previsualizacion generada correctamente.';

                originalPathInput.value = data.original_image_temp_path || '';
                generatedPathInput.value = data.generated_image_temp_path || '';
                promptInput.value = data.preview_prompt || '';

                if (data.generated_image_url) {
                    imagenGenerada.src = data.generated_image_url;
                    imagenGenerada.classList.remove('d-none');
                    textoGenerada.classList.add('d-none');
                }
            })
            .catch(() => {
                btnGenerar.disabled = false;
                mensajePreview.textContent = 'Ocurrio un error al revisar la previsualizacion.';
            });
    }
}
