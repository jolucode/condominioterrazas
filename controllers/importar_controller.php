<?php
/**
 * CONTROLADOR DE IMPORTACIÓN MASIVA DE CLIENTES
 * Usa SheetJS (client-side) para leer el Excel — sin dependencias PHP
 */
require_once __DIR__ . '/../config/autoload.php';
requireAdmin();

$accion = $_GET['accion'] ?? 'index';

switch ($accion) {
    case 'index':       mostrarFormulario(); break;
    case 'previsualizar': previsualizar();   break;
    case 'importar':    importar();          break;
    default: redirigir('controllers/importar_controller.php');
}

// ─────────────────────────────────────────────
function mostrarFormulario() {
    $titulo      = 'Importar Clientes';
    $subtitulo   = 'Carga masiva desde Excel (.xlsx)';
    $pagina_actual = 'clientes';

    ob_start(); ?>

    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fas fa-file-excel"></i> Importar Clientes desde Excel</h3>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar"
               class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Formato esperado:</strong> Archivo <code>.xlsx</code>,
                hoja llamada <strong>propietarios</strong> (o la primera hoja),
                con estas columnas en orden:
                <br><br>
                <code>A: DNI &nbsp;|&nbsp; B: Nombres &nbsp;|&nbsp; C: Apellidos &nbsp;|&nbsp;
                      D: Teléfono &nbsp;|&nbsp; E: Correo &nbsp;|&nbsp;
                      F: Etapa &nbsp;|&nbsp; G: Manzana &nbsp;|&nbsp; H: Lote</code>
                <br><br>
                <i class="fas fa-check-circle" style="color:var(--color-exito)"></i>
                Si un propietario ya existe con el mismo <strong>DNI + Lote + Manzana + Etapa</strong>,
                se omitirá automáticamente. Solo se insertarán los registros nuevos.
            </div>

            <!-- Zona de carga -->
            <div id="drop-zone"
                 style="border:2px dashed var(--color-borde);border-radius:var(--radio);
                        padding:3rem;text-align:center;cursor:pointer;
                        transition:background .2s;margin-bottom:1.5rem;">
                <i class="fas fa-cloud-upload-alt" style="font-size:3rem;color:var(--color-primario);margin-bottom:1rem;display:block;"></i>
                <p style="font-size:1.1rem;margin:0 0 .5rem;">Arrastra tu archivo Excel aquí</p>
                <p style="color:var(--color-texto-claro);font-size:.9rem;margin:0 0 1rem;">o</p>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('file-input').click()">
                    <i class="fas fa-folder-open"></i> Seleccionar archivo
                </button>
                <input type="file" id="file-input" accept=".xlsx,.xls" style="display:none;">
                <p id="file-name" style="margin-top:1rem;color:var(--color-texto-claro);font-size:.85rem;"></p>
            </div>

            <!-- Indicador de carga -->
            <div id="loading" style="display:none;text-align:center;padding:2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primario);"></i>
                <p style="margin-top:.5rem;">Procesando archivo...</p>
            </div>

            <!-- Resumen -->
            <div id="resumen" style="display:none;" class="mb-3"></div>

            <!-- Tabla de previsualización -->
            <div id="preview-container" style="display:none;">
                <div class="table-container">
                    <table class="table" id="preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>DNI</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Etapa</th>
                                <th>Manzana</th>
                                <th>Lote</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="preview-body"></tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" id="btn-importar" class="btn btn-primary" disabled>
                        <i class="fas fa-upload"></i> Confirmar e Importar
                    </button>
                    <button type="button" id="btn-limpiar" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>

            <!-- Resultado final -->
            <div id="resultado" style="display:none;"></div>

        </div>
    </div>

    <!-- SheetJS desde CDN -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <script>
    (function() {
        const dropZone   = document.getElementById('drop-zone');
        const fileInput  = document.getElementById('file-input');
        const loading    = document.getElementById('loading');
        const resumen    = document.getElementById('resumen');
        const preview    = document.getElementById('preview-container');
        const tbody      = document.getElementById('preview-body');
        const btnImportar = document.getElementById('btn-importar');
        const btnLimpiar = document.getElementById('btn-limpiar');
        const resultado  = document.getElementById('resultado');
        const fileName   = document.getElementById('file-name');
        const baseUrl    = '<?php echo APP_URL; ?>';

        let parsedRows = [];

        // Drag & drop
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.background = 'var(--color-fondo)'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.background = ''; });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.style.background = '';
            if (e.dataTransfer.files.length) processFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', () => { if (fileInput.files.length) processFile(fileInput.files[0]); });

        function processFile(file) {
            if (!file.name.match(/\.(xlsx|xls)$/i)) {
                showAlert('Solo se aceptan archivos .xlsx o .xls', 'danger');
                return;
            }
            fileName.textContent = file.name;
            loading.style.display = 'block';
            preview.style.display = 'none';
            resumen.style.display = 'none';
            resultado.style.display = 'none';

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });

                    // Buscar hoja "propietarios" o usar la primera
                    const sheetName = wb.SheetNames.includes('propietarios')
                        ? 'propietarios'
                        : wb.SheetNames[0];
                    const ws = wb.Sheets[sheetName];

                    // Obtener filas como arrays (header:1 = sin mapear)
                    const rawRows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

                    if (rawRows.length < 2) {
                        loading.style.display = 'none';
                        showAlert('El archivo está vacío o solo tiene cabeceras.', 'warning');
                        return;
                    }

                    // Saltar fila 0 (cabeceras), mapear datos
                    const rows = rawRows.slice(1).filter(r => r.some(c => c !== ''));
                    const mapped = rows.map(r => ({
                        dni:          normalizar(r[0], 'dni'),
                        nombres:      normalizar(r[1], 'texto'),
                        apellidos:    normalizar(r[2], 'texto'),
                        telefono:     normalizar(r[3], 'texto'),
                        correo:       normalizar(r[4], 'email'),
                        etapa:        normalizar(r[5], 'texto'),
                        manzana:      normalizar(r[6], 'texto'),
                        numero_lote:  normalizar(r[7], 'texto'),
                    }));

                    // Verificar en servidor cuáles ya existen
                    verificarDuplicados(mapped);

                } catch (ex) {
                    loading.style.display = 'none';
                    showAlert('Error al leer el archivo: ' + ex.message, 'danger');
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function normalizar(val, tipo) {
            if (val === null || val === undefined) return '';
            let s = String(val).trim();

            if (tipo === 'dni') {
                // Puede venir como número (47753898), asegurar que sea string de 8 dígitos
                s = s.replace(/\.0$/, ''); // quitar .0 si viene como float
                s = s.padStart(8, '0').substring(0, 8);
            }
            if (tipo === 'email') {
                s = s.toLowerCase();
            }
            if (tipo === 'texto') {
                s = s.replace(/\.0$/, ''); // números sin decimales
                // Capitalizar primera letra para manzana (A, B, C)
            }
            return s;
        }

        function verificarDuplicados(rows) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch(`${baseUrl}/controllers/importar_controller.php?accion=previsualizar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ rows })
            })
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                parsedRows = data.rows;
                renderPreview(data);
            })
            .catch(() => {
                loading.style.display = 'none';
                showAlert('Error al comunicar con el servidor.', 'danger');
            });
        }

        function renderPreview(data) {
            tbody.innerHTML = '';
            let nuevos = 0, existentes = 0;

            data.rows.forEach((row, i) => {
                const tr = document.createElement('tr');
                const esNuevo = row.estado === 'nuevo';
                if (esNuevo) nuevos++; else existentes++;

                tr.style.background = esNuevo ? '' : 'rgba(255,193,7,.08)';
                tr.innerHTML = `
                    <td>${i + 1}</td>
                    <td>${esc(row.dni)}</td>
                    <td>${esc(row.nombres)}</td>
                    <td>${esc(row.apellidos)}</td>
                    <td>${esc(row.telefono)}</td>
                    <td>${esc(row.correo)}</td>
                    <td>${esc(row.etapa)}</td>
                    <td>${esc(row.manzana)}</td>
                    <td>${esc(row.numero_lote)}</td>
                    <td>${esNuevo
                        ? '<span class="badge badge-success">Nuevo</span>'
                        : '<span class="badge badge-warning">Ya existe</span>'}</td>`;
                tbody.appendChild(tr);
            });

            // Resumen
            resumen.style.display = 'block';
            resumen.innerHTML = `
                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <div style="padding:.75rem 1.25rem;background:var(--color-exito-claro,#e8f5e9);
                                border-radius:var(--radio);display:flex;align-items:center;gap:.5rem;">
                        <i class="fas fa-check-circle" style="color:var(--color-exito);"></i>
                        <span><strong>${nuevos}</strong> nuevos a insertar</span>
                    </div>
                    <div style="padding:.75rem 1.25rem;background:#fff8e1;
                                border-radius:var(--radio);display:flex;align-items:center;gap:.5rem;">
                        <i class="fas fa-minus-circle" style="color:var(--color-advertencia);"></i>
                        <span><strong>${existentes}</strong> ya tienen ese lote registrado (se omitirán)</span>
                    </div>
                </div>`;

            preview.style.display = 'block';
            btnImportar.disabled  = nuevos === 0;
            if (nuevos === 0) {
                btnImportar.title = 'No hay registros nuevos que importar';
            }
        }

        btnImportar.addEventListener('click', () => {
            const nuevos = parsedRows.filter(r => r.estado === 'nuevo');
            if (!nuevos.length) return;

            btnImportar.disabled = true;
            btnImportar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';

            const csrfToken2 = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch(`${baseUrl}/controllers/importar_controller.php?accion=importar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken2 },
                body: JSON.stringify({ rows: nuevos })
            })
            .then(r => r.json())
            .then(data => {
                preview.style.display  = 'none';
                resumen.style.display  = 'none';
                resultado.style.display = 'block';
                if (data.ok) {
                    resultado.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Importación completada.</strong>
                            Se insertaron <strong>${data.insertados}</strong> clientes nuevos.
                            ${data.errores > 0 ? `<br><span style="color:var(--color-advertencia)">${data.errores} registros tuvieron errores y fueron omitidos.</span>` : ''}
                        </div>
                        <a href="${baseUrl}/controllers/cliente_controller.php?accion=listar" class="btn btn-primary">
                            <i class="fas fa-users"></i> Ver listado de clientes
                        </a>`;
                } else {
                    resultado.innerHTML = `<div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error: ${esc(data.mensaje)}
                    </div>`;
                }
            })
            .catch(() => {
                btnImportar.disabled = false;
                btnImportar.innerHTML = '<i class="fas fa-upload"></i> Confirmar e Importar';
                showAlert('Error al comunicar con el servidor.', 'danger');
            });
        });

        btnLimpiar.addEventListener('click', () => {
            parsedRows = [];
            tbody.innerHTML = '';
            preview.style.display  = 'none';
            resumen.style.display  = 'none';
            resultado.style.display = 'none';
            fileInput.value        = '';
            fileName.textContent   = '';
            btnImportar.disabled   = true;
        });

        function esc(s) {
            return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
        function showAlert(msg, tipo) {
            resumen.style.display = 'block';
            resumen.innerHTML = `<div class="alert alert-${tipo}"><i class="fas fa-exclamation-circle"></i> ${msg}</div>`;
        }
    })();
    </script>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}

// ─────────────────────────────────────────────
function previsualizar() {
    header('Content-Type: application/json');

    $body = json_decode(file_get_contents('php://input'), true);
    if (!isset($body['rows']) || !is_array($body['rows'])) {
        echo json_encode(['error' => 'Datos inválidos']);
        exit;
    }

    $modelo  = new Cliente();
    $filas   = [];

    foreach ($body['rows'] as $row) {
        $dni        = sanear($row['dni']         ?? '');
        $nombres    = sanear($row['nombres']     ?? '');
        $apellidos  = sanear($row['apellidos']   ?? '');
        $telefono   = sanear($row['telefono']    ?? '');
        $correo     = sanear($row['correo']      ?? '');
        $etapa      = sanear($row['etapa']       ?? '');
        $manzana    = sanear($row['manzana']     ?? '');
        $lote       = sanear($row['numero_lote'] ?? '');

        if (empty($dni) || empty($nombres) || empty($apellidos) || empty($lote)) continue;

        $existe = $modelo->loteExiste($lote, $manzana ?: null, $etapa ?: null);

        $filas[] = [
            'dni'         => $dni,
            'nombres'     => $nombres,
            'apellidos'   => $apellidos,
            'telefono'    => $telefono,
            'correo'      => $correo,
            'etapa'       => $etapa,
            'manzana'     => $manzana,
            'numero_lote' => $lote,
            'estado'      => $existe ? 'existe' : 'nuevo',
        ];
    }

    echo json_encode(['rows' => $filas]);
    exit;
}

// ─────────────────────────────────────────────
function importar() {
    header('Content-Type: application/json');

    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validarTokenCSRF($token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'mensaje' => 'Token de seguridad inválido.']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!isset($body['rows']) || !is_array($body['rows'])) {
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
        exit;
    }

    $modelo    = new Cliente();
    $db        = Database::getInstance()->getConnection();
    $insertados = 0;
    $errores    = 0;

    foreach ($body['rows'] as $row) {
        $dni       = sanear($row['dni']         ?? '');
        $nombres   = sanear($row['nombres']     ?? '');
        $apellidos = sanear($row['apellidos']   ?? '');
        $telefono  = sanear($row['telefono']    ?? '');
        $correo    = sanear($row['correo']      ?? '');
        $etapa     = sanear($row['etapa']       ?? '');
        $manzana   = sanear($row['manzana']     ?? '');
        $lote      = sanear($row['numero_lote'] ?? '');

        if (empty($dni) || empty($nombres) || empty($apellidos) || empty($lote)) {
            $errores++;
            continue;
        }

        // Doble verificación por si acaso
        if ($modelo->loteExiste($lote, $manzana ?: null, $etapa ?: null)) {
            continue;
        }

        try {
            $id = $modelo->insertar([
                'nombres'     => $nombres,
                'apellidos'   => $apellidos,
                'dni'         => $dni,
                'telefono'    => $telefono ?: null,
                'correo'      => $correo   ?: null,
                'numero_lote' => $lote,
                'manzana'     => $manzana  ?: null,
                'etapa'       => $etapa    ?: null,
                'estado'      => 'activo',
            ]);

            if ($id) {
                registrarAuditoria($db, 'create', 'clientes', $id,
                    "Importado: {$nombres} {$apellidos} — Lote {$lote} Mz {$manzana} Etapa {$etapa}");
                $insertados++;
            } else {
                $errores++;
            }
        } catch (Exception $e) {
            $errores++;
        }
    }

    echo json_encode([
        'ok'         => true,
        'insertados' => $insertados,
        'errores'    => $errores,
    ]);
    exit;
}
