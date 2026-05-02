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
                <form action="avance_controller.php?accion=crear" method="POST" enctype="multipart/form-data">
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
                        <label for="imagenes" class="form-label">Imágenes / Fotos del Avance (Puedes seleccionar varias)</label>
                        <div class="upload-area" id="upload-area" style="border: 2px dashed var(--color-borde); padding: 2rem; text-align: center; border-radius: 8px; cursor: pointer; transition: background 0.3s ease;">
                            <i class="fas fa-images" style="font-size: 2.5rem; color: var(--color-primario); margin-bottom: 1rem;"></i>
                            <p id="upload-text">Haz clic aquí o arrastra las imágenes</p>
                            <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple required style="display: none;">
                            <div id="preview-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 1rem;">
                            </div>
                            <p id="file-count" style="font-size: 0.8rem; margin-top: 0.5rem; color: var(--color-texto-claro);"></p>
                        </div>
                    </div>

                    <div class="form-actions" style="border-top: 1px solid var(--color-borde); padding-top: 1.5rem; text-align: right;">
                        <button type="submit" class="btn btn-primario" style="width: 200px;">
                            <i class="fas fa-paper-plane"></i> Publicar Avance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('imagenes');
    const previewContainer = document.getElementById('preview-container');
    const uploadText = document.getElementById('upload-text');
    const fileCount = document.getElementById('file-count');

    uploadArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        if (this.files && this.files.length > 0) {
            uploadText.style.display = 'none';
            fileCount.textContent = `${this.files.length} imágenes seleccionadas`;
            
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '100%';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.style.border = '1px solid var(--color-borde)';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        } else {
            uploadText.style.display = 'block';
            fileCount.textContent = '';
        }
    });

    uploadArea.addEventListener('mouseenter', () => uploadArea.style.background = 'var(--color-fondo)');
    uploadArea.addEventListener('mouseleave', () => uploadArea.style.background = 'transparent');
</script>
