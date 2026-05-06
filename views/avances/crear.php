<div class="row" style="justify-content: center;">
    <div class="col-md-8 col-sm-12">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0;"><i class="fas fa-upload"></i> Detalles del Nuevo Avance</h3>
                <a href="avance_controller.php?accion=listar" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <div class="card-body">
                <form action="avance_controller.php?accion=crear" method="POST" enctype="multipart/form-data" id="form-avance">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="titulo" class="form-label">Título del Avance</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" placeholder="Ej: Renovación de fachada norte" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="descripcion" class="form-label">Descripción Detallada</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="5" placeholder="Explica brevemente los trabajos realizados..." required></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label">
                            Imágenes / Fotos del Avance
                            <small style="color:var(--color-texto-claro);font-weight:normal;"> — Puedes seleccionar o arrastrar varias a la vez</small>
                        </label>

                        <!-- Zona de drop -->
                        <div id="upload-area"
                             style="border:2px dashed var(--color-borde);border-radius:8px;
                                    padding:2rem;text-align:center;transition:border-color .2s,background .2s;">
                            <i class="fas fa-images" style="font-size:2.5rem;color:var(--color-primario);display:block;margin-bottom:.75rem;"></i>
                            <p id="upload-text" style="margin:0 0 1rem;color:var(--color-texto-claro);">
                                Arrastra las imágenes aquí o
                            </p>
                            <button type="button" id="btn-agregar"
                                    class="btn btn-outline btn-sm">
                                <i class="fas fa-folder-open"></i> Seleccionar fotos
                            </button>
                            <input type="file" id="imagenes" name="imagenes[]"
                                   accept="image/*" multiple style="display:none;">
                        </div>

                        <!-- Preview acumulativo -->
                        <div id="preview-container"
                             style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));
                                    gap:10px;margin-top:1rem;"></div>

                        <!-- Contador + botón limpiar -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;">
                            <span id="file-count" style="font-size:.85rem;color:var(--color-texto-claro);"></span>
                            <button type="button" id="btn-limpiar" class="btn btn-sm btn-outline"
                                    style="display:none;font-size:.8rem;">
                                <i class="fas fa-trash"></i> Quitar todas
                            </button>
                        </div>
                    </div>

                    <div id="error-fotos" style="display:none;color:var(--color-peligro);font-size:.875rem;margin-bottom:.75rem;">
                        <i class="fas fa-exclamation-circle"></i> Debes seleccionar al menos una imagen.
                    </div>

                    <div class="form-actions" style="border-top: 1px solid var(--color-borde); padding-top: 1.5rem; text-align: right;">
                        <button type="submit" class="btn btn-primary" style="width: 200px;">
                            <i class="fas fa-paper-plane"></i> Publicar Avance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const uploadArea     = document.getElementById('upload-area');
    const fileInput      = document.getElementById('imagenes');
    const previewContainer = document.getElementById('preview-container');
    const fileCount      = document.getElementById('file-count');
    const btnAgregar     = document.getElementById('btn-agregar');
    const btnLimpiar     = document.getElementById('btn-limpiar');

    // DataTransfer acumula los archivos entre selecciones sucesivas
    const dt = new DataTransfer();

    // ── Abrir diálogo SOLO al hacer clic en el botón ──
    btnAgregar.addEventListener('click', () => fileInput.click());

    // ── Al seleccionar desde el diálogo: acumular ──
    fileInput.addEventListener('change', function() {
        agregarArchivos(this.files);
        // Limpiar el input para que el evento change dispare aunque
        // se seleccionen los mismos archivos de nuevo
        this.value = '';
    });

    // ── Drag & Drop ──
    uploadArea.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.style.borderColor  = 'var(--color-primario)';
        uploadArea.style.background   = 'var(--color-fondo)';
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = 'var(--color-borde)';
        uploadArea.style.background  = '';
    });
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--color-borde)';
        uploadArea.style.background  = '';
        agregarArchivos(e.dataTransfer.files);
    });

    // ── Limpiar todo ──
    btnLimpiar.addEventListener('click', () => {
        // Vaciar DataTransfer
        while (dt.items.length) dt.items.remove(0);
        fileInput.files = dt.files;
        previewContainer.innerHTML = '';
        actualizarContador();
    });

    // ── Función principal: agregar archivos al acumulador ──
    function agregarArchivos(nuevos) {
        Array.from(nuevos).forEach(file => {
            // Solo imágenes
            if (!file.type.startsWith('image/')) return;
            // Evitar duplicados por nombre+tamaño
            const yaExiste = Array.from(dt.files).some(
                f => f.name === file.name && f.size === file.size
            );
            if (yaExiste) return;

            dt.items.add(file);
            agregarPreview(file, dt.files.length - 1);
        });

        // Sincronizar el input con el DataTransfer acumulado
        fileInput.files = dt.files;
        actualizarContador();
    }

    function agregarPreview(file, index) {
        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'position:relative;';
        wrapper.dataset.index = index;

        const img = document.createElement('img');
        img.style.cssText = 'width:100%;height:100px;object-fit:cover;border-radius:6px;border:1px solid var(--color-borde);display:block;';

        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; };
        reader.readAsDataURL(file);

        // Botón X para quitar esta imagen
        const btnX = document.createElement('button');
        btnX.type = 'button';
        btnX.innerHTML = '&times;';
        btnX.title = 'Quitar';
        btnX.style.cssText = 'position:absolute;top:3px;right:3px;background:rgba(0,0,0,.55);color:#fff;' +
                              'border:none;border-radius:50%;width:20px;height:20px;line-height:18px;' +
                              'font-size:14px;cursor:pointer;padding:0;';
        btnX.addEventListener('click', () => {
            // Reconstruir DataTransfer sin este archivo
            const idx = Array.from(previewContainer.children).indexOf(wrapper);
            const newDt = new DataTransfer();
            Array.from(dt.files).forEach((f, i) => { if (i !== idx) newDt.items.add(f); });
            while (dt.items.length) dt.items.remove(0);
            Array.from(newDt.files).forEach(f => dt.items.add(f));
            fileInput.files = dt.files;
            wrapper.remove();
            actualizarContador();
        });

        wrapper.appendChild(img);
        wrapper.appendChild(btnX);
        previewContainer.appendChild(wrapper);
    }

    function actualizarContador() {
        const n = dt.files.length;
        fileCount.textContent = n > 0 ? `${n} imagen${n !== 1 ? 'es' : ''} seleccionada${n !== 1 ? 's' : ''}` : '';
        btnLimpiar.style.display = n > 0 ? 'inline-flex' : 'none';
        document.getElementById('upload-text').style.display = n > 0 ? 'none' : '';
        // Ocultar error si ya hay fotos
        if (n > 0) document.getElementById('error-fotos').style.display = 'none';
    }

    // ── Validar antes de enviar ──
    document.getElementById('form-avance').addEventListener('submit', function(e) {
        if (dt.files.length === 0) {
            e.preventDefault();
            document.getElementById('error-fotos').style.display = 'block';
            document.getElementById('upload-area').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>
