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

    <style>
    @media (max-width: 700px) {
        .import-info-grid { grid-template-columns: 1fr !important; }
    }
    </style>

    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fas fa-file-excel"></i> Importar Clientes desde Excel</h3>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar"
               class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">

            <!-- Instrucciones de formato -->
            <div class="import-info-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">

                <!-- Card: estructura del archivo -->
                <div style="background:var(--color-fondo);border:1px solid var(--color-borde);
                            border-radius:var(--radio);padding:1.25rem;">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
                        <i class="fas fa-table" style="color:var(--color-primario);font-size:1.1rem;"></i>
                        <strong>Estructura del archivo</strong>
                    </div>
                    <p style="font-size:.85rem;color:var(--color-texto-claro);margin:0 0 .75rem;">
                        Hoja: <code>propietarios</code> (o la primera hoja) &nbsp;·&nbsp; Formato: <code>.xlsx</code>
                    </p>
                    <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                        <thead>
                            <tr style="background:var(--color-primario);color:#fff;">
                                <th style="padding:.35rem .6rem;text-align:center;border-radius:4px 0 0 0;width:40px;">Col.</th>
                                <th style="padding:.35rem .6rem;text-align:left;">Campo</th>
                                <th style="padding:.35rem .6rem;text-align:center;border-radius:0 4px 0 0;">¿Req.?</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cols = [
                                ['A', 'DNI',              true],
                                ['B', 'Nombres',          true],
                                ['C', 'Apellidos',        true],
                                ['D', 'Teléfono',         true],
                                ['E', 'Correo',           true],
                                ['F', 'Fecha (D/MM/YYYY)',true],
                                ['G', 'Etapa',            true],
                                ['H', 'Manzana',          true],
                                ['I', 'Lote',             true],
                            ];
                            foreach ($cols as $i => [$col, $campo, $req]):
                                $bg = $i % 2 === 0 ? 'transparent' : 'rgba(0,0,0,.03)';
                            ?>
                            <tr style="background:<?php echo $bg; ?>">
                                <td style="padding:.3rem .6rem;text-align:center;">
                                    <span style="display:inline-block;width:22px;height:22px;line-height:22px;
                                                 text-align:center;background:var(--color-primario);color:#fff;
                                                 border-radius:4px;font-weight:700;font-size:.8rem;">
                                        <?php echo $col; ?>
                                    </span>
                                </td>
                                <td style="padding:.3rem .6rem;font-weight:500;"><?php echo $campo; ?></td>
                                <td style="padding:.3rem .6rem;text-align:center;">
                                    <?php if ($req): ?>
                                        <span style="color:var(--color-peligro);font-size:.75rem;font-weight:600;">Sí</span>
                                    <?php else: ?>
                                        <span style="color:var(--color-texto-claro);font-size:.75rem;">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Card: reglas de importación -->
                <div style="background:var(--color-fondo);border:1px solid var(--color-borde);
                            border-radius:var(--radio);padding:1.25rem;">
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem;">
                        <i class="fas fa-shield-alt" style="color:var(--color-exito);font-size:1.1rem;"></i>
                        <strong>Reglas de importación</strong>
                    </div>
                    <ul style="list-style:none;padding:0;margin:0;font-size:.9rem;display:flex;flex-direction:column;gap:.75rem;">
                        <li style="display:flex;gap:.6rem;align-items:flex-start;">
                            <i class="fas fa-check-circle" style="color:var(--color-exito);margin-top:2px;flex-shrink:0;"></i>
                            <span>Un propietario puede tener <strong>varios lotes</strong>. El mismo DNI aparece tantas veces como lotes tenga.</span>
                        </li>
                        <li style="display:flex;gap:.6rem;align-items:flex-start;">
                            <i class="fas fa-ban" style="color:var(--color-peligro);margin-top:2px;flex-shrink:0;"></i>
                            <span>Un <strong>lote físico</strong> (Lote + Manzana + Etapa) solo puede tener <strong>un propietario</strong>. Si ya existe, se omite.</span>
                        </li>
                        <li style="display:flex;gap:.6rem;align-items:flex-start;">
                            <i class="fas fa-sync-alt" style="color:var(--color-info);margin-top:2px;flex-shrink:0;"></i>
                            <span>Puedes reimportar el mismo Excel cuantas veces quieras. Solo se insertarán los <strong>registros nuevos</strong>.</span>
                        </li>
                        <li style="display:flex;gap:.6rem;align-items:flex-start;">
                            <i class="fas fa-eye" style="color:var(--color-advertencia);margin-top:2px;flex-shrink:0;"></i>
                            <span>Siempre verás una <strong>previsualización</strong> con colores antes de confirmar la importación.</span>
                        </li>
                    </ul>
                </div>

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
                                <th>Fecha Compra</th>
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
                    const wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array', cellDates: true });

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
                        fecha_compra: normalizar(r[5], 'fecha'),
                        etapa:        normalizar(r[6], 'texto'),
                        manzana:      normalizar(r[7], 'texto'),
                        numero_lote:  normalizar(r[8], 'texto'),
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

            if (tipo === 'fecha') {
                // cellDates:true hace que SheetJS devuelva Date objects
                if (val instanceof Date && !isNaN(val)) {
                    const y = val.getUTCFullYear();
                    const m = String(val.getUTCMonth() + 1).padStart(2, '0');
                    const d = String(val.getUTCDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                }
                // Fallback: string con formato D/MM/YYYY o DD/MM/YYYY
                const s = String(val).trim();
                const match = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
                if (match) {
                    const dd = match[1].padStart(2, '0');
                    const mm = match[2].padStart(2, '0');
                    return `${match[3]}-${mm}-${dd}`;
                }
                return s;
            }

            let s = String(val).trim();
            if (tipo === 'dni') {
                s = s.replace(/\.0$/, '');
                s = s.padStart(8, '0').substring(0, 8);
            }
            if (tipo === 'email') {
                s = s.toLowerCase();
            }
            if (tipo === 'texto') {
                s = s.replace(/\.0$/, '');
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
            let nuevos = 0, existentes = 0, incompletos = 0;

            data.rows.forEach((row, i) => {
                const tr = document.createElement('tr');
                const esNuevo      = row.estado === 'nuevo';
                const esExiste     = row.estado === 'existe';
                const esIncompleto = row.estado === 'incompleto';
                const esDuplicado  = row.estado === 'duplicado';

                if (esNuevo)      nuevos++;
                if (esExiste)     existentes++;
                if (esIncompleto || esDuplicado) incompletos++;

                if (esIncompleto || esDuplicado) tr.style.background = 'rgba(211,47,47,.06)';
                else if (esExiste)               tr.style.background = 'rgba(255,193,7,.08)';

                const motivo = (row.faltantes && row.faltantes.length)
                    ? `<br><small style="color:var(--color-peligro);">${esDuplicado ? '' : 'Falta: '}${row.faltantes.map(f => esc(f)).join(', ')}</small>`
                    : '';

                let badgeEstado;
                if (esNuevo)           badgeEstado = '<span class="badge badge-success">Nuevo</span>';
                else if (esExiste)     badgeEstado = '<span class="badge badge-warning">Ya existe</span>';
                else if (esDuplicado)  badgeEstado = `<span class="badge badge-danger">Lote duplicado en Excel</span>${motivo}`;
                else                   badgeEstado = `<span class="badge badge-danger">Incompleto</span>${motivo}`;

                // Formatear fecha para display: YYYY-MM-DD → DD/MM/YYYY
                const fechaDisplay = row.fecha_compra
                    ? row.fecha_compra.replace(/^(\d{4})-(\d{2})-(\d{2})$/, '$3/$2/$1')
                    : '';

                tr.innerHTML = `
                    <td>${i + 1}</td>
                    <td>${esc(row.dni)}</td>
                    <td>${esc(row.nombres)}</td>
                    <td>${esc(row.apellidos)}</td>
                    <td>${esc(row.telefono)}</td>
                    <td>${esc(row.correo)}</td>
                    <td>${esc(fechaDisplay)}</td>
                    <td>${esc(row.etapa)}</td>
                    <td>${esc(row.manzana)}</td>
                    <td>${esc(row.numero_lote)}</td>
                    <td>${badgeEstado}</td>`;
                tbody.appendChild(tr);
            });

            // Resumen
            resumen.style.display = 'block';
            resumen.innerHTML = `<div style="display:flex;gap:1rem;flex-wrap:wrap;">
                <div style="padding:.75rem 1.25rem;background:#e8f5e9;border-radius:var(--radio);display:flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-check-circle" style="color:var(--color-exito);"></i>
                    <span><strong>${nuevos}</strong> nuevos a insertar</span>
                </div>
                <div style="padding:.75rem 1.25rem;background:#fff8e1;border-radius:var(--radio);display:flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-minus-circle" style="color:var(--color-advertencia);"></i>
                    <span><strong>${existentes}</strong> ya tienen ese lote (se omitirán)</span>
                </div>
                ${incompletos > 0 ? `
                <div style="padding:.75rem 1.25rem;background:#ffebee;border-radius:var(--radio);display:flex;align-items:center;gap:.5rem;">
                    <i class="fas fa-exclamation-circle" style="color:var(--color-peligro);"></i>
                    <span><strong>${incompletos}</strong> con error (campos faltantes o lote duplicado en Excel — no se insertarán)</span>
                </div>` : ''}
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
                    let errDetail = '';
                    if (data.errores > 0 && data.detalle_errores && data.detalle_errores.length) {
                        const items = data.detalle_errores.map(e => `<li>${esc(e)}</li>`).join('');
                        errDetail = `
                            <div class="alert alert-danger" style="margin-top:.75rem;">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>${data.errores} registro(s) no se pudieron insertar:</strong>
                                <ul style="margin:.5rem 0 0;padding-left:1.5rem;">${items}</ul>
                            </div>`;
                    }

                    resultado.innerHTML = `
                        <div class="alert alert-${data.insertados > 0 ? 'success' : 'warning'}">
                            <i class="fas fa-${data.insertados > 0 ? 'check-circle' : 'exclamation-circle'}"></i>
                            <strong>Importación completada.</strong>
                            Se insertaron <strong>${data.insertados}</strong> cliente(s) nuevo(s).
                        </div>
                        ${errDetail}
                        <a href="${baseUrl}/controllers/cliente_controller.php?accion=listar" class="btn btn-primary mt-2">
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

    $modelo      = new Cliente();
    $filas       = [];
    $lotes_batch = []; // rastrea lotes ya vistos en este mismo Excel

    foreach ($body['rows'] as $row) {
        $dni          = sanear($row['dni']          ?? '');
        $nombres      = sanear($row['nombres']      ?? '');
        $apellidos    = sanear($row['apellidos']    ?? '');
        $telefono     = sanear($row['telefono']     ?? '');
        $correo       = sanear($row['correo']       ?? '');
        $fecha_compra = sanear($row['fecha_compra'] ?? '');
        $etapa        = sanear($row['etapa']        ?? '');
        $manzana      = sanear($row['manzana']      ?? '');
        $lote         = sanear($row['numero_lote']  ?? '');

        // Validar y normalizar fecha (YYYY-MM-DD)
        $fecha_valida = '';
        if (!empty($fecha_compra)) {
            $d = DateTime::createFromFormat('Y-m-d', $fecha_compra);
            if ($d && $d->format('Y-m-d') === $fecha_compra) {
                $fecha_valida = $fecha_compra;
            }
        }

        $base = [
            'dni' => $dni, 'nombres' => $nombres, 'apellidos' => $apellidos,
            'telefono' => $telefono, 'correo' => $correo,
            'fecha_compra' => $fecha_valida,
            'etapa' => $etapa, 'manzana' => $manzana, 'numero_lote' => $lote,
        ];

        // 1. Campos faltantes
        $faltantes = [];
        if (empty($dni))          $faltantes[] = 'DNI';
        if (empty($nombres))      $faltantes[] = 'Nombres';
        if (empty($apellidos))    $faltantes[] = 'Apellidos';
        if (empty($telefono))     $faltantes[] = 'Teléfono';
        if (empty($correo))       $faltantes[] = 'Correo';
        if (empty($fecha_valida)) $faltantes[] = 'Fecha';
        if (empty($etapa))        $faltantes[] = 'Etapa';
        if (empty($manzana))      $faltantes[] = 'Manzana';
        if (empty($lote))         $faltantes[] = 'Lote';

        if (!empty($faltantes)) {
            $filas[] = array_merge($base, ['estado' => 'incompleto', 'faltantes' => $faltantes]);
            continue;
        }

        // 2. Duplicado dentro del mismo Excel
        $clave = strtoupper("{$lote}|{$manzana}|{$etapa}");
        if (isset($lotes_batch[$clave])) {
            $filas[] = array_merge($base, [
                'estado'    => 'duplicado',
                'faltantes' => ["Lote {$lote} / Mz {$manzana} / Etapa {$etapa} ya está en la fila {$lotes_batch[$clave]} de este Excel"],
            ]);
            continue;
        }

        // 3. Ya existe en la BD
        $existe = $modelo->loteExiste($lote, $manzana, $etapa);
        $fila_actual = count($filas) + 1;
        $lotes_batch[$clave] = $fila_actual;

        $filas[] = array_merge($base, [
            'estado'    => $existe ? 'existe' : 'nuevo',
            'faltantes' => [],
        ]);
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

    $modelo     = new Cliente();
    $db         = Database::getInstance()->getConnection();
    $insertados = 0;
    $errores    = 0;
    $detalle_errores = [];

    foreach ($body['rows'] as $row) {
        $dni          = sanear($row['dni']          ?? '');
        $nombres      = sanear($row['nombres']      ?? '');
        $apellidos    = sanear($row['apellidos']    ?? '');
        $telefono     = sanear($row['telefono']     ?? '');
        $correo       = sanear($row['correo']       ?? '');
        $fecha_compra = sanear($row['fecha_compra'] ?? '');
        $etapa        = sanear($row['etapa']        ?? '');
        $manzana      = sanear($row['manzana']      ?? '');
        $lote         = sanear($row['numero_lote']  ?? '');

        $identificador = "{$nombres} {$apellidos} (DNI {$dni}) Lote {$lote}";

        if (empty($dni) || empty($nombres) || empty($apellidos) || empty($telefono) ||
            empty($correo) || empty($fecha_compra) || empty($etapa) || empty($manzana) || empty($lote)) {
            $errores++;
            $detalle_errores[] = "Campos incompletos: {$identificador}";
            continue;
        }

        // Doble verificación por si acaso
        if ($modelo->loteExiste($lote, $manzana, $etapa)) {
            continue;
        }

        try {
            $id = $modelo->insertar([
                'nombres'      => $nombres,
                'apellidos'    => $apellidos,
                'dni'          => $dni,
                'telefono'     => $telefono,
                'correo'       => $correo,
                'fecha_compra' => $fecha_compra,
                'numero_lote'  => $lote,
                'manzana'      => $manzana,
                'etapa'        => $etapa,
                'estado'       => 'activo',
            ]);

            if ($id) {
                registrarAuditoria($db, 'create', 'clientes', $id,
                    "Importado: {$nombres} {$apellidos} — Lote {$lote} Mz {$manzana} Etapa {$etapa}");

                // Crear usuario de acceso: correo como usuario, DNI como contraseña
                $modelo_usuario = new Usuario();
                if (!$modelo_usuario->correoExiste($correo)) {
                    $modelo_usuario->crearUsuario([
                        'nombre_completo' => $nombres . ' ' . $apellidos,
                        'correo'          => $correo,
                        'password'        => $dni,
                        'rol'             => 'cliente',
                        'cliente_id'      => $id,
                    ]);
                }

                $insertados++;
            } else {
                $errores++;
                $detalle_errores[] = "Error al insertar: {$identificador}";
            }
        } catch (Exception $e) {
            $errores++;
            $detalle_errores[] = "Error en {$identificador}: " . $e->getMessage();
        }
    }

    echo json_encode([
        'ok'              => true,
        'insertados'      => $insertados,
        'errores'         => $errores,
        'detalle_errores' => $detalle_errores,
    ]);
    exit;
}
