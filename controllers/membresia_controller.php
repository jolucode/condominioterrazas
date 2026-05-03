<?php
/**
 * CONTROLADOR DE MEMBRESÍA PERPETUA
 * Gestiona el plan de cuotas de membresía al Club (S/. 5,000 en 20 cuotas)
 */
require_once __DIR__ . '/../config/autoload.php';
requireAdmin();

$accion         = $_GET['accion'] ?? 'listar';
$modelo_pago    = new Pago();
$modelo_cliente = new Cliente();

switch ($accion) {
    case 'listar':        listarMembresias();       break;
    case 'listar_ajax':   listarMembresiasAjax();   break;
    case 'registrar':     registrarMembresia();     break;
    case 'ver':           verCuotas();              break;
    case 'marcar_cuota':  marcarCuota();            break;
    case 'eliminar':      eliminarMembresia();      break;
    case 'generar_masivo': generarMasivo();         break;
    default: redirigir('controllers/membresia_controller.php?accion=listar');
}

// ─────────────────────────────────────────────────────
function listarMembresias() {
    global $modelo_pago;
    $modelo_pago->actualizarEstadosVencidos();

    $db = Database::getInstance()->getConnection();

    // Stats globales
    $stats = $db->query(
        "SELECT
            COUNT(DISTINCT cliente_id)                                        as clientes_enrolados,
            SUM(CASE WHEN estado='pagado'    THEN monto ELSE 0 END)           as total_recaudado,
            SUM(CASE WHEN estado!='pagado'   THEN monto ELSE 0 END)           as total_deuda,
            SUM(CASE WHEN estado='pagado'    THEN 1 ELSE 0 END)               as cuotas_pagadas,
            COUNT(*)                                                           as cuotas_total
         FROM pagos WHERE tipo_pago = 'membresia_cuota'"
    )->fetch();

    // Clientes activos sin membresía
    $sin_membresia = $db->query(
        "SELECT COUNT(*) as n FROM clientes
         WHERE estado='activo'
         AND id NOT IN (SELECT DISTINCT cliente_id FROM pagos WHERE tipo_pago='membresia_cuota')"
    )->fetch()['n'];

    $titulo      = 'Membresía Club';
    $subtitulo   = 'Membresía Perpetua — S/. ' . number_format(MONTO_MEMBRESIA, 2) . ' en ' . CUOTAS_MEMBRESIA . ' cuotas';
    $pagina_actual = 'membresia';

    ob_start(); ?>

    <!-- Stats -->
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-id-card"></i></div>
            <div class="stat-info">
                <div class="label">Propietarios Enrolados</div>
                <div class="value"><?php echo $stats['clientes_enrolados']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="label">Cuotas Pagadas</div>
                <div class="value"><?php echo $stats['cuotas_pagadas']; ?> / <?php echo $stats['cuotas_total']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-coins"></i></div>
            <div class="stat-info">
                <div class="label">Total Recaudado</div>
                <div class="value"><?php echo formatearMoneda($stats['total_recaudado']); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-info">
                <div class="label">Deuda Pendiente</div>
                <div class="value"><?php echo formatearMoneda($stats['total_deuda']); ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <h3>Propietarios con Membresía</h3>
                <?php if ($sin_membresia > 0): ?>
                    <span class="badge badge-warning"><?php echo $sin_membresia; ?> sin membresía</span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-1">
                <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=registrar"
                   class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Registrar Individual
                </a>
                <?php if ($sin_membresia > 0): ?>
                <button class="btn btn-outline btn-sm" data-modal="modal-generar-masivo">
                    <i class="fas fa-users"></i> Generar para todos
                    <span class="badge badge-warning" style="margin-left:4px;"><?php echo $sin_membresia; ?></span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <div class="filters-bar mb-3">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="busqueda-input" class="form-control"
                           placeholder="Buscar por nombre, DNI o lote...">
                </div>
                <select id="filtro-estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="activo">Con cuotas pendientes</option>
                    <option value="completo">Plan completado</option>
                </select>
                <select id="etapa-select" class="form-control">
                    <option value="">Todas las etapas</option>
                    <?php foreach ((new Cliente())->obtenerEtapas() as $et): ?>
                        <option value="<?php echo htmlspecialchars($et); ?>"><?php echo htmlspecialchars($et); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="base-url" value="<?php echo APP_URL; ?>">
            </div>

            <div id="membresia-table-container">
                <?php renderTablaMembresias(); ?>
            </div>
        </div>
    </div>

    <!-- Modal Generar Masivo -->
    <div id="modal-generar-masivo" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3><i class="fas fa-users"></i> Generar Membresías para Todos</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST"
                  action="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=generar_masivo"
                  id="form-generar-masivo">
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                <div class="modal-body">

                    <div class="alert alert-info" style="margin-bottom:1rem;">
                        <i class="fas fa-info-circle"></i>
                        Se generará el plan de cuotas para los
                        <strong><?php echo $sin_membresia; ?> propietarios</strong> que aún
                        no tienen membresía registrada. Los que ya tienen plan no serán afectados.
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>N° de Cuotas <span class="required">*</span></label>
                            <input type="number" name="total_cuotas" id="m-total-cuotas"
                                   class="form-control" min="1" max="60" required
                                   value="<?php echo CUOTAS_MEMBRESIA; ?>">
                        </div>
                        <div class="form-group">
                            <label>Monto por Cuota (S/.) <span class="required">*</span></label>
                            <input type="number" name="monto_cuota" id="m-monto-cuota"
                                   class="form-control" step="0.01" min="1" required
                                   value="<?php echo MONTO_MEMBRESIA / CUOTAS_MEMBRESIA; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Total del Plan por Propietario</label>
                        <div class="form-control" id="m-total-plan"
                             style="background:var(--color-fondo);font-weight:600;color:var(--color-primario);">
                            <?php echo formatearMoneda(MONTO_MEMBRESIA); ?>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-calendar-alt"></i>
                        La <strong>cuota 1</strong> vencerá el último día del mes actual.
                        Las siguientes cuotas se calcularán mes a mes automáticamente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Generar Planes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const busqueda  = document.getElementById('busqueda-input');
        const filtro    = document.getElementById('filtro-estado');
        const etapa     = document.getElementById('etapa-select');
        const container = document.getElementById('membresia-table-container');
        const baseUrl   = document.getElementById('base-url').value;
        let timer;

        function cargar(pagina = 1) {
            const url = `${baseUrl}/controllers/membresia_controller.php?accion=listar_ajax`
                + `&pagina=${pagina}`
                + `&busqueda=${encodeURIComponent(busqueda.value)}`
                + `&filtro=${encodeURIComponent(filtro.value)}`
                + `&etapa=${encodeURIComponent(etapa.value)}`;
            container.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            fetch(url).then(r => r.text()).then(html => { container.innerHTML = html; });
        }

        busqueda.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => cargar(1), 400); });
        filtro.addEventListener('change', () => cargar(1));
        etapa.addEventListener('change',  () => cargar(1));

        container.addEventListener('click', e => {
            const a = e.target.closest('.pagination a');
            if (a) { e.preventDefault(); cargar(new URL(a.href).searchParams.get('pagina')); }
        });

        // Calcular total en modal
        const mCuotas = document.getElementById('m-total-cuotas');
        const mMonto  = document.getElementById('m-monto-cuota');
        const mTotal  = document.getElementById('m-total-plan');
        if (mCuotas && mMonto) {
            function actualizarTotal() {
                const t = (parseFloat(mCuotas.value) || 0) * (parseFloat(mMonto.value) || 0);
                mTotal.textContent = 'S/. ' + t.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
            mCuotas.addEventListener('input', actualizarTotal);
            mMonto.addEventListener('input', actualizarTotal);
        }
    })();
    </script>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}

// ─────────────────────────────────────────────────────
function renderTablaMembresias($pagina = 1, $busqueda = '', $filtro_estado = '', $etapa_filtro = '') {
    $db      = Database::getInstance()->getConnection();
    $por_pag = 15;

    $where   = "WHERE p.tipo_pago = 'membresia_cuota'";
    $params  = [];

    if ($busqueda) {
        $where .= " AND (c.nombres LIKE :b1 OR c.apellidos LIKE :b2 OR c.dni LIKE :b3 OR c.numero_lote LIKE :b4)";
        $params[':b1'] = $params[':b2'] = $params[':b3'] = $params[':b4'] = "%{$busqueda}%";
    }
    if ($etapa_filtro) {
        $where .= " AND c.etapa = :etapa";
        $params[':etapa'] = $etapa_filtro;
    }

    $havingSQL = '';
    if ($filtro_estado === 'activo') {
        $havingSQL = 'HAVING cuotas_pendientes > 0';
    } elseif ($filtro_estado === 'completo') {
        $havingSQL = 'HAVING cuotas_pendientes = 0';
    }

    $baseSQL = "SELECT c.id, CONCAT(c.nombres,' ',c.apellidos) as cliente_nombre,
                       c.dni, c.numero_lote, c.manzana, c.etapa, c.telefono,
                       COUNT(p.id)                                                  as total_cuotas,
                       MAX(p.total_cuotas)                                          as plan_total,
                       SUM(CASE WHEN p.estado='pagado'    THEN 1 ELSE 0 END)        as cuotas_pagadas,
                       SUM(CASE WHEN p.estado!='pagado'   THEN 1 ELSE 0 END)        as cuotas_pendientes,
                       SUM(CASE WHEN p.estado='pagado'    THEN p.monto ELSE 0 END)  as total_pagado,
                       SUM(CASE WHEN p.estado!='pagado'   THEN p.monto ELSE 0 END)  as total_deuda
                FROM pagos p
                INNER JOIN clientes c ON p.cliente_id = c.id
                {$where}
                GROUP BY c.id {$havingSQL}";

    // Total para paginación
    $countSQL = "SELECT COUNT(*) as n FROM ({$baseSQL}) sub";
    $stmtC    = $db->prepare($countSQL);
    $stmtC->execute($params);
    $total = $stmtC->fetch()['n'];

    $paginacion = obtenerPaginacion($total, $por_pag, $pagina);
    $offset     = $paginacion['offset'];

    $stmt = $db->prepare("{$baseSQL} ORDER BY c.apellidos, c.nombres LIMIT {$por_pag} OFFSET {$offset}");
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    if (empty($filas)): ?>
        <div class="empty-state">
            <i class="fas fa-id-card"></i>
            <h3>No hay propietarios con membresía</h3>
            <p>Usa "Registrar Membresía" para enrolar al primer propietario</p>
        </div>
    <?php return; endif; ?>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Propietario</th>
                    <th>Lote / Mz / Etapa</th>
                    <th>Progreso</th>
                    <th>Total Pagado</th>
                    <th>Deuda</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($filas as $f):
                $pct = $f['plan_total'] > 0 ? round(($f['cuotas_pagadas'] / $f['plan_total']) * 100) : 0;
                $completo = ($f['cuotas_pendientes'] == 0);
            ?>
                <tr>
                    <td>
                        <strong><?php e($f['cliente_nombre']); ?></strong><br>
                        <small style="color:var(--color-texto-claro)"><?php e($f['dni']); ?></small>
                    </td>
                    <td>
                        Lote <?php e($f['numero_lote']); ?>
                        <?php if ($f['manzana']): ?> / Mz <?php e($f['manzana']); ?><?php endif; ?>
                        <?php if ($f['etapa']): ?>   / <?php e($f['etapa']); ?><?php endif; ?>
                    </td>
                    <td style="min-width:160px;">
                        <div style="font-size:.85rem;margin-bottom:4px;">
                            <?php echo $f['cuotas_pagadas']; ?> / <?php echo $f['plan_total']; ?> cuotas
                            <span style="float:right"><?php echo $pct; ?>%</span>
                        </div>
                        <div style="background:var(--color-borde);border-radius:4px;height:8px;overflow:hidden;">
                            <div style="width:<?php echo $pct; ?>%;background:<?php echo $completo ? 'var(--color-exito)' : 'var(--color-primario)'; ?>;height:100%;border-radius:4px;transition:width .3s;"></div>
                        </div>
                        <?php if ($completo): ?>
                            <span class="badge badge-success" style="margin-top:4px;">Completado</span>
                        <?php elseif ($f['cuotas_pendientes'] > 0): ?>
                            <span class="badge badge-warning" style="margin-top:4px;"><?php echo $f['cuotas_pendientes']; ?> pendientes</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatearMoneda($f['total_pagado']); ?></td>
                    <td>
                        <?php if ($f['total_deuda'] > 0): ?>
                            <span style="color:var(--color-peligro);font-weight:500;">
                                <?php echo formatearMoneda($f['total_deuda']); ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--color-exito);">S/. 0.00</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=ver&cliente_id=<?php echo $f['id']; ?>"
                           class="btn btn-sm btn-outline" title="Ver cuotas">
                            <i class="fas fa-list-ol"></i>
                        </a>
                        <?php if (!$completo && !empty($f['telefono'])): ?>
                            <?php
                            $msg = "Hola *{$f['cliente_nombre']}*, te saludamos de Condominio Terrazas. "
                                 . "Te recordamos que tienes *{$f['cuotas_pendientes']} cuota(s)* pendientes "
                                 . "de tu *Membresía Perpetua Club*. "
                                 . "Deuda actual: *" . formatearMoneda($f['total_deuda']) . "*. "
                                 . "Por favor regulariza tu pago. ¡Muchas gracias!";
                            $url_wa = "https://api.whatsapp.com/send?phone=51"
                                    . preg_replace('/[^0-9]/', '', $f['telefono'])
                                    . "&text=" . urlencode($msg);
                            ?>
                            <a href="<?php echo $url_wa; ?>" class="btn btn-sm btn-whatsapp"
                               target="_blank" title="Recordatorio WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!$completo): ?>
                            <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=eliminar&cliente_id=<?php echo $f['id']; ?>"
                               class="btn btn-sm btn-danger" title="Eliminar plan"
                               data-confirm-delete="¿Eliminar TODO el plan de membresía de este propietario? Solo se pueden eliminar cuotas no pagadas.">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginacion['total_paginas'] > 1): ?>
    <div class="pagination">
        <?php if ($paginacion['tiene_anterior']): ?>
            <a href="?pagina=<?php echo $paginacion['pagina_actual'] - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&filtro=<?php echo urlencode($filtro_estado); ?>">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
        <?php endif; ?>
        <span class="active"><?php echo $paginacion['pagina_actual']; ?></span>
        <?php if ($paginacion['tiene_siguiente']): ?>
            <a href="?pagina=<?php echo $paginacion['pagina_actual'] + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&filtro=<?php echo urlencode($filtro_estado); ?>">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif;
}

// ─────────────────────────────────────────────────────
function listarMembresiasAjax() {
    $pagina   = intval($_GET['pagina']   ?? 1);
    $busqueda = sanear($_GET['busqueda'] ?? '');
    $filtro   = sanear($_GET['filtro']   ?? '');
    $etapa    = sanear($_GET['etapa']    ?? '');
    ob_start();
    renderTablaMembresias($pagina, $busqueda, $filtro, $etapa);
    echo ob_get_clean();
    exit;
}

// ─────────────────────────────────────────────────────
function registrarMembresia() {
    global $modelo_pago, $modelo_cliente;
    $errores  = [];
    $clientes = $modelo_cliente->obtenerActivos();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/membresia_controller.php?accion=registrar');
        }

        $cliente_id   = intval($_POST['cliente_id']   ?? 0);
        $total_cuotas = intval($_POST['total_cuotas'] ?? CUOTAS_MEMBRESIA);
        $monto_cuota  = floatval($_POST['monto_cuota'] ?? (MONTO_MEMBRESIA / CUOTAS_MEMBRESIA));

        if (!$cliente_id)     $errores[] = 'Seleccione un propietario.';
        if ($total_cuotas < 1) $errores[] = 'El número de cuotas debe ser al menos 1.';
        if ($monto_cuota <= 0) $errores[] = 'El monto por cuota debe ser mayor a 0.';

        if (empty($errores)) {
            $creadas = $modelo_pago->generarCuotasMembresia(
                $cliente_id, $monto_cuota, $total_cuotas, $_SESSION['usuario_id']
            );

            if ($creadas > 0) {
                $db      = Database::getInstance()->getConnection();
                $cliente = $modelo_cliente->obtenerPorId($cliente_id);
                registrarAuditoria($db, 'create', 'pagos', null,
                    "Membresía generada: {$cliente['nombres']} {$cliente['apellidos']} — {$creadas} cuotas de " . formatearMoneda($monto_cuota));
                setFlashMessage('success', "Plan de membresía generado: {$creadas} cuotas de " . formatearMoneda($monto_cuota) . " para {$cliente['nombres']} {$cliente['apellidos']}.");
                redirigir('controllers/membresia_controller.php?accion=ver&cliente_id=' . $cliente_id);
            } else {
                $errores[] = 'Este propietario ya tiene un plan de membresía completo registrado.';
            }
        }
    }

    $monto_cuota_default = MONTO_MEMBRESIA / CUOTAS_MEMBRESIA;

    $titulo      = 'Registrar Membresía';
    $subtitulo   = 'Generar plan de cuotas de Membresía Perpetua';
    $pagina_actual = 'membresia';

    ob_start(); ?>

    <div class="card">
        <div class="card-header">
            <h3>Registrar Membresía Perpetua</h3>
            <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=listar"
               class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul style="margin:0;padding-left:1.5rem;">
                        <?php foreach ($errores as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Membresía Perpetua Club:</strong> Otorga acceso de por vida a todas las áreas comunes.
                El sistema generará automáticamente el plan de cuotas con las fechas de vencimiento mensuales.
            </div>

            <form method="POST" data-validate id="form-membresia">
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">

                <div class="form-group">
                    <label>Propietario <span class="required">*</span></label>
                    <select name="cliente_id" class="form-control" required>
                        <option value="">Seleccione un propietario</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?php echo $cli['id']; ?>"
                                <?php echo (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cli['id']) ? 'selected' : ''; ?>>
                                <?php e($cli['nombres'] . ' ' . $cli['apellidos']); ?>
                                — Lote <?php e($cli['numero_lote']); ?>
                                <?php echo $cli['manzana'] ? '/ Mz ' . htmlspecialchars($cli['manzana']) : ''; ?>
                                <?php echo $cli['etapa']   ? '/ ' . htmlspecialchars($cli['etapa'])   : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Número de Cuotas <span class="required">*</span></label>
                        <input type="number" name="total_cuotas" id="total-cuotas" class="form-control"
                               min="1" max="60" required
                               value="<?php echo isset($_POST['total_cuotas']) ? intval($_POST['total_cuotas']) : CUOTAS_MEMBRESIA; ?>">
                        <small style="color:var(--color-texto-claro)">Default: <?php echo CUOTAS_MEMBRESIA; ?> cuotas</small>
                    </div>
                    <div class="form-group">
                        <label>Monto por Cuota (S/.) <span class="required">*</span></label>
                        <input type="number" name="monto_cuota" id="monto-cuota" class="form-control"
                               step="0.01" min="1" required
                               value="<?php echo isset($_POST['monto_cuota']) ? floatval($_POST['monto_cuota']) : $monto_cuota_default; ?>">
                        <small style="color:var(--color-texto-claro)">Default: <?php echo formatearMoneda($monto_cuota_default); ?> por cuota</small>
                    </div>
                    <div class="form-group">
                        <label>Total del Plan</label>
                        <div class="form-control" id="total-plan"
                             style="background:var(--color-fondo);font-weight:600;color:var(--color-primario);">
                            <?php echo formatearMoneda(MONTO_MEMBRESIA); ?>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-calendar-alt"></i>
                    Las fechas de vencimiento se calcularán automáticamente: la <strong>cuota 1</strong>
                    vence el último día del mes actual, la <strong>cuota 2</strong> el siguiente mes, y así sucesivamente.
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Generar Plan de Cuotas
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=listar"
                       class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const cuotas = document.getElementById('total-cuotas');
        const monto  = document.getElementById('monto-cuota');
        const total  = document.getElementById('total-plan');
        function actualizar() {
            const t = (parseFloat(cuotas.value) || 0) * (parseFloat(monto.value) || 0);
            total.textContent = 'S/. ' + t.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        cuotas.addEventListener('input', actualizar);
        monto.addEventListener('input', actualizar);
    })();
    </script>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}

// ─────────────────────────────────────────────────────
function verCuotas() {
    global $modelo_pago, $modelo_cliente;

    $cliente_id = intval($_GET['cliente_id'] ?? 0);
    $cliente    = $modelo_cliente->obtenerPorId($cliente_id);

    if (!$cliente) {
        setFlashMessage('error', 'Propietario no encontrado.');
        redirigir('controllers/membresia_controller.php?accion=listar');
    }

    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare(
        "SELECT * FROM pagos
         WHERE cliente_id = :cid AND tipo_pago = 'membresia_cuota'
         ORDER BY cuota_numero ASC"
    );
    $stmt->execute([':cid' => $cliente_id]);
    $cuotas = $stmt->fetchAll();

    if (empty($cuotas)) {
        setFlashMessage('warning', 'Este propietario no tiene membresía registrada.');
        redirigir('controllers/membresia_controller.php?accion=listar');
    }

    $plan_total     = $cuotas[0]['total_cuotas'];
    $pagadas        = count(array_filter($cuotas, fn($c) => $c['estado'] === 'pagado'));
    $pendientes     = count($cuotas) - $pagadas;
    $total_pagado   = array_sum(array_map(fn($c) => $c['estado'] === 'pagado' ? $c['monto'] : 0, $cuotas));
    $total_deuda    = array_sum(array_map(fn($c) => $c['estado'] !== 'pagado' ? $c['monto'] : 0, $cuotas));
    $pct            = $plan_total > 0 ? round(($pagadas / $plan_total) * 100) : 0;

    $titulo      = 'Cuotas de Membresía';
    $subtitulo   = $cliente['nombres'] . ' ' . $cliente['apellidos'];
    $pagina_actual = 'membresia';

    ob_start(); ?>

    <!-- Resumen del cliente -->
    <div class="grid grid-2 mb-3">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Propietario</h3>
                <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=listar"
                   class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <div class="card-body">
                <ul class="detail-list">
                    <li><span class="label">Nombre</span>
                        <span class="value"><?php e($cliente['nombres'] . ' ' . $cliente['apellidos']); ?></span></li>
                    <li><span class="label">DNI</span>
                        <span class="value"><?php e($cliente['dni']); ?></span></li>
                    <li><span class="label">Lote</span>
                        <span class="value">
                            <?php e($cliente['numero_lote']); ?>
                            <?php if ($cliente['manzana']): ?> / Mz <?php e($cliente['manzana']); ?><?php endif; ?>
                            <?php if ($cliente['etapa']): ?>   / <?php e($cliente['etapa']); ?><?php endif; ?>
                        </span>
                    </li>
                    <li><span class="label">Teléfono</span>
                        <span class="value"><?php e($cliente['telefono'] ?: '-'); ?></span></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Estado del Plan</h3>
            </div>
            <div class="card-body">
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:6px;">
                        <span><?php echo $pagadas; ?> / <?php echo $plan_total; ?> cuotas pagadas</span>
                        <strong><?php echo $pct; ?>%</strong>
                    </div>
                    <div style="background:var(--color-borde);border-radius:6px;height:12px;overflow:hidden;">
                        <div style="width:<?php echo $pct; ?>%;background:<?php echo $pct == 100 ? 'var(--color-exito)' : 'var(--color-primario)'; ?>;height:100%;border-radius:6px;transition:width .4s;"></div>
                    </div>
                </div>
                <ul class="detail-list">
                    <li><span class="label">Total Pagado</span>
                        <span class="value" style="color:var(--color-exito);font-weight:600;"><?php echo formatearMoneda($total_pagado); ?></span></li>
                    <li><span class="label">Deuda Pendiente</span>
                        <span class="value" style="color:<?php echo $total_deuda > 0 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>;font-weight:600;"><?php echo formatearMoneda($total_deuda); ?></span></li>
                    <li><span class="label">Cuotas Pendientes</span>
                        <span class="value"><?php echo $pendientes; ?></span></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tabla de cuotas -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list-ol"></i> Detalle de Cuotas</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cuota</th>
                            <th>Monto</th>
                            <th>Vencimiento</th>
                            <th>Estado</th>
                            <th>Fecha Pago</th>
                            <th>Método</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cuotas as $cuota): ?>
                        <tr>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo $cuota['cuota_numero']; ?> / <?php echo $cuota['total_cuotas']; ?>
                                </span>
                            </td>
                            <td><?php echo formatearMoneda($cuota['monto']); ?></td>
                            <td><?php echo formatearFecha($cuota['fecha_vencimiento']); ?></td>
                            <td>
                                <span class="badge <?php echo claseEstadoPago($cuota['estado']); ?>">
                                    <?php echo textoEstadoPago($cuota['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $cuota['fecha_pago'] ? formatearFecha($cuota['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                            <td><?php echo $cuota['metodo_pago'] ? nombreMetodoPago($cuota['metodo_pago']) : '-'; ?></td>
                            <td class="actions">
                                <?php if ($cuota['estado'] !== 'pagado'): ?>
                                    <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=marcar_cuota&id=<?php echo $cuota['id']; ?>"
                                       class="btn btn-sm btn-success" title="Marcar como pagada">
                                        <i class="fas fa-check"></i> Pagar
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--color-exito);font-size:.85rem;">
                                        <i class="fas fa-check-circle"></i> Pagada
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}

// ─────────────────────────────────────────────────────
function marcarCuota() {
    global $modelo_pago, $modelo_cliente;

    $id    = intval($_GET['id'] ?? 0);
    $cuota = $modelo_pago->obtenerPorId($id);

    if (!$cuota || $cuota['tipo_pago'] !== 'membresia_cuota') {
        setFlashMessage('error', 'Cuota no encontrada.');
        redirigir('controllers/membresia_controller.php?accion=listar');
    }
    if ($cuota['estado'] === 'pagado') {
        setFlashMessage('warning', 'Esta cuota ya está pagada.');
        redirigir('controllers/membresia_controller.php?accion=ver&cliente_id=' . $cuota['cliente_id']);
    }

    $errores = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/membresia_controller.php?accion=marcar_cuota&id=' . $id);
        }

        $metodo_pago = sanear($_POST['metodo_pago'] ?? '');
        $observacion = sanear($_POST['observacion'] ?? '');

        if (empty($metodo_pago)) {
            $errores[] = 'Seleccione un método de pago.';
        } else {
            if ($modelo_pago->marcarComoPagado($id, $metodo_pago, $observacion, $_SESSION['usuario_id'])) {
                $db      = Database::getInstance()->getConnection();
                $cliente = $modelo_cliente->obtenerPorId($cuota['cliente_id']);
                registrarAuditoria($db, 'update', 'pagos', $id,
                    "Membresía cuota {$cuota['cuota_numero']}/{$cuota['total_cuotas']} pagada: {$cliente['nombres']} {$cliente['apellidos']}");
                setFlashMessage('success', "Cuota {$cuota['cuota_numero']} de {$cuota['total_cuotas']} marcada como pagada.");
                redirigir('controllers/comprobante_controller.php?accion=emitir&pago_id=' . $id);
            } else {
                $errores[] = 'Error al actualizar la cuota.';
            }
        }
    }

    $cliente     = $modelo_cliente->obtenerPorId($cuota['cliente_id']);
    $titulo      = 'Pagar Cuota de Membresía';
    $subtitulo   = "Cuota {$cuota['cuota_numero']} de {$cuota['total_cuotas']}";
    $pagina_actual = 'membresia';

    ob_start(); ?>

    <div class="card">
        <div class="card-header">
            <h3>Confirmar Pago de Cuota</h3>
            <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=ver&cliente_id=<?php echo $cuota['cliente_id']; ?>"
               class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errores as $err): ?><p style="margin:0"><?php echo $err; ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Propietario:</strong> <?php e($cliente['nombres'] . ' ' . $cliente['apellidos']); ?><br>
                <strong>Concepto:</strong> Membresía Perpetua Club —
                    Cuota <strong><?php echo $cuota['cuota_numero']; ?></strong>
                    de <strong><?php echo $cuota['total_cuotas']; ?></strong><br>
                <strong>Monto:</strong> <?php echo formatearMoneda($cuota['monto']); ?><br>
                <strong>Vencimiento:</strong> <?php echo formatearFecha($cuota['fecha_vencimiento']); ?>
            </div>

            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">

                <div class="form-group">
                    <label>Método de Pago <span class="required">*</span></label>
                    <select name="metodo_pago" class="form-control" required>
                        <option value="">Seleccione método</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="deposito">Depósito</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="3"
                              placeholder="Número de operación, referencia, etc."><?php echo $cuota['observacion'] ?? ''; ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Pago
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=ver&cliente_id=<?php echo $cuota['cliente_id']; ?>"
                       class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}

// ─────────────────────────────────────────────────────
function eliminarMembresia() {
    $cliente_id = intval($_GET['cliente_id'] ?? 0);

    if (!$cliente_id) {
        redirigir('controllers/membresia_controller.php?accion=listar');
    }

    $db = Database::getInstance()->getConnection();

    // Verificar que no haya cuotas pagadas
    $pagadas = $db->prepare(
        "SELECT COUNT(*) as n FROM pagos
         WHERE cliente_id = :cid AND tipo_pago = 'membresia_cuota' AND estado = 'pagado'"
    );
    $pagadas->execute([':cid' => $cliente_id]);
    if ($pagadas->fetch()['n'] > 0) {
        setFlashMessage('error', 'No se puede eliminar: el plan tiene cuotas ya pagadas.');
        redirigir('controllers/membresia_controller.php?accion=listar');
    }

    $stmt = $db->prepare(
        "DELETE FROM pagos WHERE cliente_id = :cid AND tipo_pago = 'membresia_cuota'"
    );
    $stmt->execute([':cid' => $cliente_id]);

    registrarAuditoria($db, 'delete', 'pagos', null,
        "Plan membresía eliminado para cliente_id={$cliente_id}");
    setFlashMessage('success', 'Plan de membresía eliminado correctamente.');
    redirigir('controllers/membresia_controller.php?accion=listar');
}

// ─────────────────────────────────────────────────────
function generarMasivo() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir('controllers/membresia_controller.php?accion=listar');
    }
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
        redirigir('controllers/membresia_controller.php?accion=listar');
    }

    $total_cuotas = intval($_POST['total_cuotas'] ?? CUOTAS_MEMBRESIA);
    $monto_cuota  = floatval($_POST['monto_cuota'] ?? (MONTO_MEMBRESIA / CUOTAS_MEMBRESIA));

    if ($total_cuotas < 1 || $monto_cuota <= 0) {
        setFlashMessage('error', 'Valores inválidos. Verifica el número de cuotas y el monto.');
        redirigir('controllers/membresia_controller.php?accion=listar');
    }

    $modelo_pago    = new Pago();
    $clientes       = (new Cliente())->obtenerActivos();
    $db             = Database::getInstance()->getConnection();
    $enrolados      = 0;
    $ya_tenian      = 0;

    foreach ($clientes as $cli) {
        // Verificar si ya tiene alguna cuota de membresía
        $stmt = $db->prepare(
            "SELECT COUNT(*) as n FROM pagos
             WHERE cliente_id = :cid AND tipo_pago = 'membresia_cuota'"
        );
        $stmt->execute([':cid' => $cli['id']]);
        if ($stmt->fetch()['n'] > 0) {
            $ya_tenian++;
            continue;
        }

        $creadas = $modelo_pago->generarCuotasMembresia(
            $cli['id'],
            $monto_cuota,
            $total_cuotas,
            $_SESSION['usuario_id']
        );
        if ($creadas > 0) $enrolados++;
    }

    registrarAuditoria(
        $db, 'create', 'pagos', null,
        "Generación masiva membresías: {$enrolados} planes creados ({$total_cuotas} cuotas × " . formatearMoneda($monto_cuota) . ")"
    );

    if ($enrolados > 0) {
        setFlashMessage('success',
            "Se generaron <strong>{$enrolados} planes</strong> de membresía "
            . "({$total_cuotas} cuotas de " . formatearMoneda($monto_cuota) . " cada una). "
            . ($ya_tenian > 0 ? "{$ya_tenian} propietario(s) ya tenían membresía y no fueron modificados." : '')
        );
    } else {
        setFlashMessage('info', 'Todos los propietarios activos ya tienen membresía registrada.');
    }

    redirigir('controllers/membresia_controller.php?accion=listar');
}
