<?php
/**
 * CONTROLADOR DE INSCRIPCIONES
 * Gestiona el pago único de inscripción/empadronamiento (S/. 800)
 */
require_once __DIR__ . '/../config/autoload.php';
requireAdmin();

$accion = $_GET['accion'] ?? 'listar';
$modelo_pago    = new Pago();
$modelo_cliente = new Cliente();

switch ($accion) {
    case 'listar':       listarInscripciones();    break;
    case 'listar_ajax':  listarInscripcionesAjax(); break;
    case 'registrar':    registrarInscripcion();   break;
    case 'marcar_pagado': marcarPagado();           break;
    case 'eliminar':     eliminarInscripcion();    break;
    case 'generar_masivo': generarMasivo();         break;
    default: redirigir('controllers/inscripcion_controller.php?accion=listar');
}

// ─────────────────────────────────────────────
function listarInscripciones() {
    global $modelo_pago;

    $modelo_pago->actualizarEstadosVencidos();

    // Estadísticas
    $db   = Database::getInstance()->getConnection();
    $stats = $db->query(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN estado='pagado'    THEN 1 ELSE 0 END) as pagadas,
            SUM(CASE WHEN estado='pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado='vencido'   THEN 1 ELSE 0 END) as vencidas,
            SUM(CASE WHEN estado='pagado'    THEN monto ELSE 0 END) as total_recaudado
         FROM pagos WHERE tipo_pago='inscripcion'"
    )->fetch();

    // Clientes sin inscripción (para badge informativo)
    $sin_inscripcion = $db->query(
        "SELECT COUNT(*) as n FROM clientes
         WHERE estado='activo'
         AND id NOT IN (SELECT cliente_id FROM pagos WHERE tipo_pago='inscripcion')"
    )->fetch()['n'];

    $titulo      = 'Inscripciones';
    $subtitulo   = 'Pago único de inscripción / empadronamiento';
    $pagina_actual = 'inscripciones';

    ob_start(); ?>

    <!-- Stats -->
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-file-signature"></i></div>
            <div class="stat-info">
                <div class="label">Total Registros</div>
                <div class="value"><?php echo $stats['total']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="label">Pagadas</div>
                <div class="value"><?php echo $stats['pagadas']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="label">Pendientes</div>
                <div class="value"><?php echo $stats['pendientes'] + $stats['vencidas']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-coins"></i></div>
            <div class="stat-info">
                <div class="label">Total Recaudado</div>
                <div class="value"><?php echo formatearMoneda($stats['total_recaudado']); ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <h3>Inscripciones</h3>
                <span class="badge badge-info"><?php echo $stats['total']; ?> registros</span>
                <?php if ($sin_inscripcion > 0): ?>
                    <span class="badge badge-warning" title="Clientes activos sin inscripción">
                        <?php echo $sin_inscripcion; ?> sin inscripción
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-1">
                <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=registrar"
                   class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nueva Inscripción
                </a>
                <?php if ($sin_inscripcion > 0): ?>
                <form method="POST"
                      action="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=generar_masivo"
                      style="display:inline;"
                      data-confirm="¿Generar inscripciones pendientes para los <?php echo $sin_inscripcion; ?> clientes que aún no tienen registro?">
                    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                    <button type="submit" class="btn btn-outline btn-sm">
                        <i class="fas fa-users"></i> Generar para todos
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <div class="filters-bar mb-3">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="busqueda-input" class="form-control"
                           placeholder="Buscar por nombre, DNI o lote...">
                </div>
                <select id="estado-select" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="pagado">Pagado</option>
                    <option value="vencido">Vencido</option>
                </select>
                <input type="hidden" id="base-url" value="<?php echo APP_URL; ?>">
            </div>

            <div id="inscripciones-table-container">
                <?php renderTablaInscripciones(); ?>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const busqueda = document.getElementById('busqueda-input');
        const estado   = document.getElementById('estado-select');
        const container = document.getElementById('inscripciones-table-container');
        const baseUrl  = document.getElementById('base-url').value;
        let timer;

        function cargar(pagina = 1) {
            const url = `${baseUrl}/controllers/inscripcion_controller.php?accion=listar_ajax`
                + `&pagina=${pagina}`
                + `&busqueda=${encodeURIComponent(busqueda.value)}`
                + `&estado=${encodeURIComponent(estado.value)}`;
            container.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            fetch(url).then(r => r.text()).then(html => { container.innerHTML = html; });
        }

        busqueda.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => cargar(1), 400); });
        estado.addEventListener('change', () => cargar(1));

        container.addEventListener('click', e => {
            const a = e.target.closest('.pagination a');
            if (a) { e.preventDefault(); cargar(new URL(a.href).searchParams.get('pagina')); }
        });
    })();
    </script>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}

// ─────────────────────────────────────────────
function renderTablaInscripciones($pagina = 1, $busqueda = '', $estado_filtro = '') {
    $db       = Database::getInstance()->getConnection();
    $por_pag  = 15;
    $where    = ["p.tipo_pago = 'inscripcion'"];
    $params   = [];

    if ($busqueda) {
        $where[]          = "(c.nombres LIKE :b1 OR c.apellidos LIKE :b2 OR c.dni LIKE :b3 OR c.numero_lote LIKE :b4)";
        $params[':b1']    = "%{$busqueda}%";
        $params[':b2']    = "%{$busqueda}%";
        $params[':b3']    = "%{$busqueda}%";
        $params[':b4']    = "%{$busqueda}%";
    }
    if ($estado_filtro) {
        $where[]          = "p.estado = :estado";
        $params[':estado'] = $estado_filtro;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    $total = $db->prepare("SELECT COUNT(*) as t FROM pagos p INNER JOIN clientes c ON p.cliente_id=c.id {$whereSQL}");
    $total->execute($params);
    $total = $total->fetch()['t'];

    $paginacion = obtenerPaginacion($total, $por_pag, $pagina);
    $offset     = $paginacion['offset'];

    $stmt = $db->prepare(
        "SELECT p.*, CONCAT(c.nombres,' ',c.apellidos) as cliente_nombre,
                c.dni as cliente_dni, c.numero_lote, c.manzana, c.etapa, c.telefono
         FROM pagos p INNER JOIN clientes c ON p.cliente_id=c.id
         {$whereSQL}
         ORDER BY p.id DESC
         LIMIT {$por_pag} OFFSET {$offset}"
    );
    $stmt->execute($params);
    $inscripciones = $stmt->fetchAll();

    if (empty($inscripciones)): ?>
        <div class="empty-state">
            <i class="fas fa-file-signature"></i>
            <h3>No se encontraron inscripciones</h3>
            <p>Registra la primera inscripción con el botón "Nueva Inscripción"</p>
        </div>
    <?php return; endif; ?>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>DNI</th>
                    <th>Lote / Mz / Etapa</th>
                    <th>Monto</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th>Fecha Pago</th>
                    <th>Método</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($inscripciones as $ins): ?>
                <tr>
                    <td><strong><?php echo e($ins['cliente_nombre']); ?></strong></td>
                    <td><?php echo e($ins['cliente_dni']); ?></td>
                    <td>
                        Lote <?php echo e($ins['numero_lote']); ?>
                        <?php if ($ins['manzana']): ?> / Mz <?php echo e($ins['manzana']); ?><?php endif; ?>
                        <?php if ($ins['etapa']): ?> / Etapa <?php echo e($ins['etapa']); ?><?php endif; ?>
                    </td>
                    <td><?php echo formatearMoneda($ins['monto']); ?></td>
                    <td><?php echo formatearFecha($ins['fecha_vencimiento']); ?></td>
                    <td>
                        <span class="badge <?php echo claseEstadoPago($ins['estado']); ?>">
                            <?php echo textoEstadoPago($ins['estado']); ?>
                        </span>
                    </td>
                    <td><?php echo $ins['fecha_pago'] ? formatearFecha($ins['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                    <td><?php echo $ins['metodo_pago'] ? nombreMetodoPago($ins['metodo_pago']) : '-'; ?></td>
                    <td class="actions">
                        <?php if ($ins['estado'] !== 'pagado'): ?>
                            <?php if (!empty($ins['telefono'])): ?>
                            <?php
                                $msg = "Hola *{$ins['cliente_nombre']}*, te saludamos de Condominio Terrazas. "
                                     . "Te recordamos que tu pago de *Inscripción/Empadronamiento* "
                                     . "por *" . formatearMoneda($ins['monto']) . "* "
                                     . "vence el *" . formatearFecha($ins['fecha_vencimiento']) . "*. "
                                     . "Por favor regulariza tu pago. ¡Muchas gracias!";
                                $url_wa = "https://api.whatsapp.com/send?phone=51"
                                        . preg_replace('/[^0-9]/', '', $ins['telefono'])
                                        . "&text=" . urlencode($msg);
                            ?>
                            <a href="<?php echo $url_wa; ?>" class="btn btn-sm btn-whatsapp"
                               target="_blank" title="Recordatorio WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=marcar_pagado&id=<?php echo $ins['id']; ?>"
                               class="btn btn-sm btn-success" title="Marcar como pagado">
                                <i class="fas fa-check"></i>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=eliminar&id=<?php echo $ins['id']; ?>"
                           class="btn btn-sm btn-danger" title="Eliminar"
                           data-confirm-delete="¿Eliminar esta inscripción? Esta acción no se puede deshacer.">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginacion['total_paginas'] > 1): ?>
    <div class="pagination">
        <?php if ($paginacion['tiene_anterior']): ?>
            <a href="?pagina=<?php echo $paginacion['pagina_actual'] - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo urlencode($estado_filtro); ?>">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
        <?php endif; ?>
        <span class="active"><?php echo $paginacion['pagina_actual']; ?></span>
        <?php if ($paginacion['tiene_siguiente']): ?>
            <a href="?pagina=<?php echo $paginacion['pagina_actual'] + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo urlencode($estado_filtro); ?>">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif;
}

// ─────────────────────────────────────────────
function listarInscripcionesAjax() {
    $pagina   = intval($_GET['pagina']   ?? 1);
    $busqueda = sanear($_GET['busqueda'] ?? '');
    $estado   = sanear($_GET['estado']   ?? '');
    ob_start();
    renderTablaInscripciones($pagina, $busqueda, $estado);
    echo ob_get_clean();
    exit;
}

// ─────────────────────────────────────────────
function registrarInscripcion() {
    global $modelo_pago, $modelo_cliente;

    $errores  = [];
    $clientes = $modelo_cliente->obtenerActivos();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/inscripcion_controller.php?accion=registrar');
        }

        $cliente_id        = intval($_POST['cliente_id'] ?? 0);
        $monto             = floatval($_POST['monto'] ?? MONTO_INSCRIPCION);
        $fecha_vencimiento = sanear($_POST['fecha_vencimiento'] ?? '');
        $observacion       = sanear($_POST['observacion'] ?? '');

        if (!$cliente_id)      $errores[] = 'Seleccione un cliente.';
        if ($monto <= 0)       $errores[] = 'El monto debe ser mayor a 0.';
        if (!$fecha_vencimiento) $errores[] = 'La fecha de vencimiento es requerida.';

        if ($cliente_id && $modelo_pago->existeInscripcion($cliente_id)) {
            $errores[] = 'Este cliente ya tiene una inscripción registrada.';
        }

        if (empty($errores)) {
            $id = $modelo_pago->registrarPago([
                'cliente_id'        => $cliente_id,
                'tipo_pago'         => 'inscripcion',
                'monto'             => $monto,
                'fecha_vencimiento' => $fecha_vencimiento,
                'estado'            => 'pendiente',
                'observacion'       => $observacion,
                'registrado_por'    => $_SESSION['usuario_id'],
            ]);

            if ($id) {
                $db      = Database::getInstance()->getConnection();
                $cliente = $modelo_cliente->obtenerPorId($cliente_id);
                registrarAuditoria($db, 'create', 'pagos', $id,
                    "Inscripción registrada: {$cliente['nombres']} {$cliente['apellidos']}");
                setFlashMessage('success', 'Inscripción registrada correctamente.');
                redirigir('controllers/inscripcion_controller.php?accion=listar');
            } else {
                $errores[] = 'Error al guardar la inscripción.';
            }
        }
    }

    $titulo      = 'Nueva Inscripción';
    $subtitulo   = 'Registrar pago de inscripción / empadronamiento';
    $pagina_actual = 'inscripciones';

    ob_start(); ?>

    <div class="card">
        <div class="card-header">
            <h3>Registrar Inscripción</h3>
            <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=listar"
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
                <strong>Inscripción / Empadronamiento:</strong> Pago único de
                <strong><?php echo formatearMoneda(MONTO_INSCRIPCION); ?></strong> por propietario.
                Representa la aceptación y conformidad con los costos de habilitación de áreas comunes.
            </div>

            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">

                <div class="form-group">
                    <label>Cliente <span class="required">*</span></label>
                    <select name="cliente_id" class="form-control" required>
                        <option value="">Seleccione un propietario</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?php echo $cli['id']; ?>"
                                <?php echo (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cli['id']) ? 'selected' : ''; ?>>
                                <?php echo e($cli['nombres'] . ' ' . $cli['apellidos']); ?>
                                — Lote <?php echo e($cli['numero_lote']); ?>
                                <?php echo $cli['manzana'] ? '/ Mz ' . e($cli['manzana']) : ''; ?>
                                <?php echo $cli['etapa']   ? '/ ' . e($cli['etapa']) : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Monto (S/.) <span class="required">*</span></label>
                        <input type="number" name="monto" class="form-control"
                               step="0.01" min="0" required
                               value="<?php echo isset($_POST['monto']) ? $_POST['monto'] : MONTO_INSCRIPCION; ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Vencimiento <span class="required">*</span></label>
                        <input type="date" name="fecha_vencimiento" class="form-control" required
                               value="<?php echo isset($_POST['fecha_vencimiento'])
                                   ? $_POST['fecha_vencimiento']
                                   : date('Y-m-t'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="3"
                              placeholder="Número de operación, referencia, etc."><?php echo isset($_POST['observacion']) ? $_POST['observacion'] : ''; ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Inscripción
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=listar"
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

// ─────────────────────────────────────────────
function marcarPagado() {
    global $modelo_pago;

    $id  = intval($_GET['id'] ?? 0);
    $ins = $modelo_pago->obtenerPorId($id);

    if (!$ins || $ins['tipo_pago'] !== 'inscripcion') {
        setFlashMessage('error', 'Inscripción no encontrada.');
        redirigir('controllers/inscripcion_controller.php?accion=listar');
    }
    if ($ins['estado'] === 'pagado') {
        setFlashMessage('warning', 'Esta inscripción ya está marcada como pagada.');
        redirigir('controllers/inscripcion_controller.php?accion=listar');
    }

    $errores = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/inscripcion_controller.php?accion=marcar_pagado&id=' . $id);
        }

        $metodo_pago = sanear($_POST['metodo_pago'] ?? '');
        $observacion = sanear($_POST['observacion'] ?? '');

        if (empty($metodo_pago)) {
            $errores[] = 'Seleccione un método de pago.';
        } else {
            if ($modelo_pago->marcarComoPagado($id, $metodo_pago, $observacion, $_SESSION['usuario_id'])) {
                $db      = Database::getInstance()->getConnection();
                $cliente = (new Cliente())->obtenerPorId($ins['cliente_id']);
                registrarAuditoria($db, 'update', 'pagos', $id,
                    "Inscripción pagada: {$cliente['nombres']} {$cliente['apellidos']}");
                setFlashMessage('success', 'Inscripción marcada como pagada.');
                redirigir('controllers/comprobante_controller.php?accion=emitir&pago_id=' . $id);
            } else {
                $errores[] = 'Error al actualizar el registro.';
            }
        }
    }

    $cliente     = (new Cliente())->obtenerPorId($ins['cliente_id']);
    $titulo      = 'Confirmar Pago de Inscripción';
    $subtitulo   = $cliente['nombres'] . ' ' . $cliente['apellidos'];
    $pagina_actual = 'inscripciones';

    ob_start(); ?>

    <div class="card">
        <div class="card-header">
            <h3>Confirmar Pago de Inscripción</h3>
            <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=listar"
               class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errores as $e): ?><p style="margin:0"><?php echo $e; ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Propietario:</strong> <?php echo e($cliente['nombres'] . ' ' . $cliente['apellidos']); ?><br>
                <strong>Lote:</strong> <?php echo e($cliente['numero_lote']); ?>
                <?php if ($cliente['manzana']): ?> &nbsp;|&nbsp; <strong>Mz:</strong> <?php echo e($cliente['manzana']); ?><?php endif; ?>
                <?php if ($cliente['etapa']): ?>   &nbsp;|&nbsp; <strong>Etapa:</strong> <?php echo e($cliente['etapa']); ?><?php endif; ?><br>
                <strong>Concepto:</strong> Inscripción / Empadronamiento<br>
                <strong>Monto:</strong> <?php echo formatearMoneda($ins['monto']); ?>
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
                              placeholder="Número de operación, referencia, etc."><?php echo $ins['observacion'] ?? ''; ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Pago
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=listar"
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

// ─────────────────────────────────────────────
function eliminarInscripcion() {
    global $modelo_pago;

    $id  = intval($_GET['id'] ?? 0);
    $ins = $modelo_pago->obtenerPorId($id);

    if (!$ins || $ins['tipo_pago'] !== 'inscripcion') {
        setFlashMessage('error', 'Inscripción no encontrada.');
        redirigir('controllers/inscripcion_controller.php?accion=listar');
    }

    if ($ins['estado'] === 'pagado') {
        setFlashMessage('error', 'No se puede eliminar una inscripción ya pagada.');
        redirigir('controllers/inscripcion_controller.php?accion=listar');
    }

    if ($modelo_pago->eliminar($id)) {
        $db = Database::getInstance()->getConnection();
        registrarAuditoria($db, 'delete', 'pagos', $id, 'Inscripción eliminada');
        setFlashMessage('success', 'Inscripción eliminada.');
    } else {
        setFlashMessage('error', 'Error al eliminar la inscripción.');
    }

    redirigir('controllers/inscripcion_controller.php?accion=listar');
}

// ─────────────────────────────────────────────
function generarMasivo() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir('controllers/inscripcion_controller.php?accion=listar');
    }
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido.');
        redirigir('controllers/inscripcion_controller.php?accion=listar');
    }

    $db      = Database::getInstance()->getConnection();
    $modelo_pago = new Pago();
    $clientes = (new Cliente())->obtenerActivos();
    $creados = 0;
    $fecha_venc = date('Y-m-t'); // último día del mes actual

    foreach ($clientes as $cli) {
        if (!$modelo_pago->existeInscripcion($cli['id'])) {
            $modelo_pago->registrarPago([
                'cliente_id'        => $cli['id'],
                'tipo_pago'         => 'inscripcion',
                'monto'             => MONTO_INSCRIPCION,
                'fecha_vencimiento' => $fecha_venc,
                'estado'            => 'pendiente',
                'registrado_por'    => $_SESSION['usuario_id'],
            ]);
            $creados++;
        }
    }

    registrarAuditoria($db, 'create', 'pagos', null,
        "Generación masiva de inscripciones: {$creados} registros creados");

    if ($creados > 0) {
        setFlashMessage('success', "Se generaron {$creados} inscripciones pendientes.");
    } else {
        setFlashMessage('info', 'Todos los clientes activos ya tienen inscripción registrada.');
    }

    redirigir('controllers/inscripcion_controller.php?accion=listar');
}
