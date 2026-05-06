<div class="avances-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h3 style="margin: 0; color: var(--color-texto-claro);">Visualiza el progreso de nuestro condominio</h3>
    </div>
    <?php if (esAdministrador()): ?>
        <a href="avance_controller.php?accion=crear" class="btn btn-primario">
            <i class="fas fa-plus"></i> Nuevo Avance
        </a>
    <?php endif; ?>
</div>

<?php if (empty($avances)): ?>
    <div class="card" style="text-align: center; padding: 3rem;">
        <i class="fas fa-images" style="font-size: 3rem; color: var(--color-borde); margin-bottom: 1rem;"></i>
        <p>Aún no hay avances publicados.</p>
    </div>
<?php else: ?>
    <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
        <?php foreach ($avances as $idx => $avance): ?>
            <div class="card avance-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s ease;">
                
                <!-- CARRUSEL -->
                <div class="carousel-container" id="carousel-<?php echo $avance['id']; ?>" style="position: relative; height: 250px; background: #000; overflow: hidden;">
                    <div class="carousel-slides" style="display: flex; width: 100%; height: 100%; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);">
                        <?php if (empty($avance['imagenes'])): ?>
                            <div class="slide" style="min-width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #fff;">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php else: ?>
                            <?php
                            // Construir array JS de URLs para este avance
                            $urls_js = implode("','", array_map(
                                fn($i) => APP_URL . '/' . $i['ruta_imagen'],
                                $avance['imagenes']
                            ));
                        ?>
                        <?php foreach ($avance['imagenes'] as $imgIdx => $img): ?>
                                <div class="slide" style="min-width: 100%; height: 100%;">
                                    <img src="<?php echo APP_URL . '/' . $img['ruta_imagen']; ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;"
                                         onclick="openLightbox(['<?php echo $urls_js; ?>'], <?php echo $imgIdx; ?>)">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (count($avance['imagenes'] ?? []) > 1): ?>
                        <button class="carousel-btn prev" onclick="moveCarousel(<?php echo $avance['id']; ?>, -1)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; z-index: 10;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-btn next" onclick="moveCarousel(<?php echo $avance['id']; ?>, 1)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; z-index: 10;">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="carousel-dots" style="position: absolute; bottom: 10px; left: 0; right: 0; display: flex; justify-content: center; gap: 5px;">
                            <?php foreach ($avance['imagenes'] as $i => $img): ?>
                                <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5);"></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="avance-date" style="position: absolute; top: 10px; right: 10px; background: rgba(var(--color-primario-rgb), 0.85); color: #fff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; z-index: 5; backdrop-filter: blur(4px);">
                        <?php echo date('d M, Y', strtotime($avance['fecha_publicacion'])); ?>
                    </div>
                </div>

                <div class="card-body" style="padding: 1.5rem; flex: 1; display: flex; flex-direction: column;">
                    <h3 style="margin-top: 0; margin-bottom: 0.75rem; color: var(--color-primario); font-size: 1.25rem; font-weight: 600;"><?php echo $avance['titulo']; ?></h3>
                    <p style="font-size: 0.95rem; color: var(--color-texto-claro); line-height: 1.6; margin-bottom: 1.5rem; flex: 1;">
                        <?php echo nl2br($avance['descripcion']); ?>
                    </p>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--color-borde); padding-top: 1rem; font-size: 0.85rem;">
                        <span style="color: var(--color-texto-muy-claro);">
                            <i class="fas fa-user-circle"></i> Administrador: <strong><?php echo $avance['administrador']; ?></strong>
                        </span>
                        <?php if (esAdministrador()): ?>
                            <a href="avance_controller.php?accion=eliminar&id=<?php echo $avance['id']; ?>" 
                               class="btn btn-peligro btn-sm" 
                               style="padding: 0.4rem 0.6rem;"
                               onclick="return confirm('¿Estás seguro de eliminar este avance y todas sus imágenes?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Lightbox con carrusel -->
<div id="lightbox"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.93);
            z-index:9999;justify-content:center;align-items:center;flex-direction:column;">

    <!-- Cerrar -->
    <button onclick="closeLightbox()"
            style="position:absolute;top:16px;right:20px;background:none;border:none;
                   color:#fff;font-size:2rem;cursor:pointer;line-height:1;z-index:10;"
            title="Cerrar (Esc)">&times;</button>

    <!-- Contador -->
    <div id="lb-counter"
         style="position:absolute;top:18px;left:50%;transform:translateX(-50%);
                color:rgba(255,255,255,.75);font-size:.9rem;letter-spacing:.05em;"></div>

    <!-- Imagen -->
    <img id="lightbox-img" src=""
         style="max-width:92vw;max-height:85vh;object-fit:contain;
                border-radius:6px;box-shadow:0 0 40px rgba(0,0,0,.6);
                user-select:none;transition:opacity .15s;">

    <!-- Flecha izquierda -->
    <button id="lb-prev" onclick="lightboxMove(-1)"
            style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                   background:rgba(255,255,255,.15);border:none;color:#fff;
                   width:46px;height:46px;border-radius:50%;font-size:1.2rem;
                   cursor:pointer;display:flex;align-items:center;justify-content:center;
                   transition:background .2s;"
            onmouseover="this.style.background='rgba(255,255,255,.3)'"
            onmouseout="this.style.background='rgba(255,255,255,.15)'"
            title="Anterior (←)">
        <i class="fas fa-chevron-left"></i>
    </button>

    <!-- Flecha derecha -->
    <button id="lb-next" onclick="lightboxMove(1)"
            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                   background:rgba(255,255,255,.15);border:none;color:#fff;
                   width:46px;height:46px;border-radius:50%;font-size:1.2rem;
                   cursor:pointer;display:flex;align-items:center;justify-content:center;
                   transition:background .2s;"
            onmouseover="this.style.background='rgba(255,255,255,.3)'"
            onmouseout="this.style.background='rgba(255,255,255,.15)'"
            title="Siguiente (→)">
        <i class="fas fa-chevron-right"></i>
    </button>

    <!-- Miniaturas -->
    <div id="lb-thumbs"
         style="position:absolute;bottom:14px;left:50%;transform:translateX(-50%);
                display:flex;gap:6px;max-width:90vw;overflow-x:auto;padding:4px 0;"></div>
</div>

<style>
.avance-card:hover { transform:translateY(-8px); box-shadow:0 15px 30px rgba(0,0,0,.12); }
.dot.active { background:#fff !important; width:20px !important; border-radius:10px !important; }
.carousel-btn:hover { background:var(--color-primario) !important; }
#lightbox img { pointer-events:none; }
</style>

<script>
// ── Carrusel de tarjetas ──
const carouselStates = {};
function moveCarousel(id, direction) {
    if (!carouselStates[id]) carouselStates[id] = 0;
    const container  = document.getElementById(`carousel-${id}`);
    const slides     = container.querySelector('.carousel-slides');
    const dots       = container.querySelectorAll('.dot');
    const slideCount = slides.querySelectorAll('.slide').length;
    carouselStates[id] = (carouselStates[id] + direction + slideCount) % slideCount;
    slides.style.transform = `translateX(-${carouselStates[id] * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === carouselStates[id]));
}

// ── Lightbox con navegación ──
let lbImages = [];   // array de URLs del avance abierto
let lbIndex  = 0;

function openLightbox(imgs, index) {
    lbImages = imgs;
    lbIndex  = index;
    document.getElementById('lightbox').style.display = 'flex';
    renderLightbox();
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
}

function lightboxMove(dir) {
    lbIndex = (lbIndex + dir + lbImages.length) % lbImages.length;
    renderLightbox();
}

function renderLightbox() {
    const img     = document.getElementById('lightbox-img');
    const counter = document.getElementById('lb-counter');
    const thumbs  = document.getElementById('lb-thumbs');
    const prev    = document.getElementById('lb-prev');
    const next    = document.getElementById('lb-next');

    // Fade rápido
    img.style.opacity = '0';
    setTimeout(() => {
        img.src = lbImages[lbIndex];
        img.style.opacity = '1';
    }, 100);

    // Contador
    counter.textContent = lbImages.length > 1 ? `${lbIndex + 1} / ${lbImages.length}` : '';

    // Flechas solo si hay más de 1
    prev.style.display = next.style.display = lbImages.length > 1 ? 'flex' : 'none';

    // Miniaturas
    thumbs.innerHTML = '';
    if (lbImages.length > 1) {
        lbImages.forEach((src, i) => {
            const t = document.createElement('img');
            t.src   = src;
            t.style.cssText = `width:52px;height:38px;object-fit:cover;border-radius:4px;
                cursor:pointer;opacity:${i === lbIndex ? '1' : '0.45'};
                border:2px solid ${i === lbIndex ? '#fff' : 'transparent'};
                transition:opacity .15s,border-color .15s;flex-shrink:0;`;
            t.addEventListener('click', () => { lbIndex = i; renderLightbox(); });
            thumbs.appendChild(t);
        });
        // Scroll a la miniatura activa
        thumbs.children[lbIndex]?.scrollIntoView({ inline: 'center', behavior: 'smooth' });
    }
}

// ── Teclado ──
document.addEventListener('keydown', e => {
    if (document.getElementById('lightbox').style.display !== 'flex') return;
    if (e.key === 'ArrowRight') lightboxMove(1);
    if (e.key === 'ArrowLeft')  lightboxMove(-1);
    if (e.key === 'Escape')     closeLightbox();
});

// ── Clic en fondo cierra ──
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});
</script>
