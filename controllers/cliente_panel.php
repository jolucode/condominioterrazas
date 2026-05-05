<?php
/**
 * PANEL DEL CLIENTE
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esCliente()) {
    redirigir('login.php');
}

// Obtener el cliente vinculado a la sesión y todos sus lotes por DNI
$cliente_id_sesion = $_SESSION['usuario_cliente_id'];
$modelo_cli_base   = new Cliente();
$cliente_base      = $modelo_cli_base->obtenerPorId($cliente_id_sesion);

if (!$cliente_base) {
    redirigir('login.php');
}

// Todos los lotes del propietario (mismo DNI)
$mis_lotes  = $modelo_cli_base->obtenerPorDni($cliente_base['dni']);
$mis_ids    = array_column($mis_lotes, 'id');
if (empty($mis_ids)) $mis_ids = [$cliente_id_sesion];

$accion = $_GET['accion'] ?? 'inicio';

switch ($accion) {
    case 'inicio':
        mostrarInicio($mis_ids, $mis_lotes, $cliente_base);
        break;
    case 'mis_pagos':
        mostrarMisPagos($mis_ids, $mis_lotes, $cliente_base);
        break;
    case 'mis_comprobantes':
        mostrarMisComprobantes($mis_ids);
        break;
    case 'reuniones':
        mostrarReuniones();
        break;
    case 'mi_perfil':
        mostrarMiPerfil($mis_lotes, $cliente_base);
        break;
    default:
        mostrarInicio($mis_ids, $mis_lotes, $cliente_base);
        break;
}

function mostrarInicio(array $mis_ids, array $mis_lotes, array $cliente_base) {
    $modelo_pago    = new Pago();
    $modelo_pago->actualizarEstadosVencidos();
    $mes_actual     = date('n');
    $anio_actual    = date('Y');

    // Resumen consolidado de todos los lotes
    $resumen        = $modelo_pago->resumenPorClientes($mis_ids);
    $mant           = $resumen['mantenimiento'];
    $deuda_total    = $mant['total_deuda'] + $resumen['inscripcion']['total_deuda'] + $resumen['membresia_cuota']['total_deuda'];

    // Estado de mantenimiento de este mes por lote
    $db   = Database::getInstance()->getConnection();
    $ph   = implode(',', array_fill(0, count($mis_ids), '?'));
    $stmt = $db->prepare(
        "SELECT p.*, c.numero_lote, c.manzana, c.etapa
         FROM pagos p INNER JOIN clientes c ON p.cliente_id = c.id
         WHERE p.cliente_id IN ({$ph})
         AND p.tipo_pago = 'mantenimiento' AND p.mes = ? AND p.anio = ?
         ORDER BY c.etapa, c.manzana, c.numero_lote"
    );
    $stmt->execute(array_merge($mis_ids, [$mes_actual, $anio_actual]));
    $pagos_mes = $stmt->fetchAll();

    // Próxima reunión
    $modelo_reunion  = new Reunion();
    $proxima_reunion = $modelo_reunion->obtenerProxima();

    $titulo      = 'Mi Panel';
    $subtitulo   = 'Bienvenido, ' . $cliente_base['nombres'];
    $pagina_actual = 'inicio';

    ob_start(); ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-home"></i></div>
            <div class="stat-info">
                <div class="label">Mis Lotes</div>
                <div class="value"><?php echo count($mis_lotes); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="label">Cuotas Pagadas</div>
                <div class="value"><?php echo $mant['pagados']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="label">Cuotas Pendientes</div>
                <div class="value"><?php echo $mant['pendientes'] + $mant['vencidos']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?php echo $deuda_total > 0 ? 'red' : 'green'; ?>">
                <i class="fas fa-coins"></i>
            </div>
            <div class="stat-info">
                <div class="label">Deuda Total</div>
                <div class="value" style="font-size:1rem;"><?php echo formatearMoneda($deuda_total); ?></div>
            </div>
        </div>
    </div>

    <!-- Mis lotes y su estado este mes -->
    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fas fa-map-marker-alt"></i> Mis Lotes — <?php echo nombreMes($mes_actual) . ' ' . $anio_actual; ?></h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Etapa</th><th>Manzana</th><th>Lote</th>
                            <th>F. Compra</th><th>Manto. <?php echo nombreMes($mes_actual); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($mis_lotes as $lote):
                        $pago_lote = array_filter($pagos_mes, fn($p) => $p['numero_lote'] === $lote['numero_lote']
                            && $p['manzana'] === $lote['manzana'] && $p['etapa'] === $lote['etapa']);
                        $pago_lote = reset($pago_lote);
                        $estado_m  = $pago_lote ? $pago_lote['estado'] : null;
                    ?>
                        <tr>
                            <td><?php echo $lote['etapa']    ?: '-'; ?></td>
                            <td><?php echo $lote['manzana']  ?: '-'; ?></td>
                            <td><strong><?php echo $lote['numero_lote']; ?></strong></td>
                            <td><?php echo $lote['fecha_compra'] ? formatearFecha($lote['fecha_compra']) : '-'; ?></td>
                            <td>
                                <?php if ($estado_m): ?>
                                    <span class="badge <?php echo claseEstadoPago($estado_m); ?>"><?php echo textoEstadoPago($estado_m); ?></span>
                                <?php else: ?>
                                    <span style="color:var(--color-texto-claro);font-size:.85rem;">Sin registro</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Próxima reunión -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-alt"></i> Próxima Reunión</h3>
        </div>
        <div class="card-body">
            <?php if ($proxima_reunion): ?>
                <h4><?php echo $proxima_reunion['titulo']; ?></h4>
                <p style="color:var(--color-texto-claro);">
                    <i class="fas fa-calendar"></i> <?php echo formatearFecha($proxima_reunion['fecha_reunion']); ?>
                    <?php if ($proxima_reunion['hora_reunion']): ?>
                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($proxima_reunion['hora_reunion'])); ?>
                    <?php endif; ?>
                    <?php if ($proxima_reunion['lugar']): ?>
                        &nbsp;<i class="fas fa-map-marker-alt"></i> <?php echo $proxima_reunion['lugar']; ?>
                    <?php endif; ?>
                </p>
                <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=reuniones"
                   class="btn btn-outline btn-sm mt-2">Ver todas las reuniones</a>
            <?php else: ?>
                <p style="color:var(--color-texto-claro);">No hay reuniones programadas</p>
            <?php endif; ?>
        </div>
    </div>

    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function mostrarMisPagos(array $mis_ids, array $mis_lotes, array $cliente_base) {
    $modelo_pago = new Pago();
    $modelo_pago->actualizarEstadosVencidos();

    $anio    = intval($_GET['anio'] ?? date('Y'));
    $resumen = $modelo_pago->resumenPorClientes($mis_ids);
    $mant    = $resumen['mantenimiento'];
    $insc    = $resumen['inscripcion'];
    $memb    = $resumen['membresia_cuota'];

    // Mantenimiento: todos los lotes, año seleccionado
    $pagos_mant = $modelo_pago->obtenerPorClientes($mis_ids, $anio, 'mantenimiento');

    // Inscripciones: una por lote
    $db  = Database::getInstance()->getConnection();
    $ph  = implode(',', array_fill(0, count($mis_ids), '?'));
    $stmt = $db->prepare(
        "SELECT p.*, c.numero_lote, c.manzana, c.etapa
         FROM pagos p INNER JOIN clientes c ON p.cliente_id = c.id
         WHERE p.cliente_id IN ({$ph}) AND p.tipo_pago='inscripcion'
         ORDER BY c.etapa, c.manzana, c.numero_lote"
    );
    $stmt->execute($mis_ids);
    $inscripciones = $stmt->fetchAll();

    // Membresía: cuotas de todos los lotes
    $stmt_m = $db->prepare(
        "SELECT p.*, c.numero_lote, c.manzana, c.etapa
         FROM pagos p INNER JOIN clientes c ON p.cliente_id = c.id
         WHERE p.cliente_id IN ({$ph}) AND p.tipo_pago='membresia_cuota'
         ORDER BY c.numero_lote, p.cuota_numero ASC"
    );
    $stmt_m->execute($mis_ids);
    $cuotas_memb = $stmt_m->fetchAll();

    $deuda_total = $mant['total_deuda'] + $insc['total_deuda'] + $memb['total_deuda'];

    $titulo      = 'Mis Pagos';
    $subtitulo   = 'Estado de todos tus pagos';
    $pagina_actual = 'mis_pagos';

    ob_start(); ?>

    <!-- Resumen de deuda total -->
    <?php if ($deuda_total > 0): ?>
    <div class="alert alert-warning mb-3">
        <i class="fas fa-exclamation-triangle"></i>
        Tienes una deuda total de <strong><?php echo formatearMoneda($deuda_total); ?></strong>.
        Por favor regulariza tus pagos.
    </div>
    <?php else: ?>
    <div class="alert alert-success mb-3" style="background:var(--color-exito-claro,#e8f5e9);color:var(--color-exito,#2e7d32);border:none;border-radius:var(--radio);padding:1rem;">
        <i class="fas fa-check-circle"></i>
        <strong>¡Estás al día!</strong> Todos tus pagos están al corriente.
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-wallet"></i> Mis Pagos</h3>
        </div>
        <div class="card-body">

            <!-- Tabs -->
            <div style="display:flex;gap:4px;border-bottom:2px solid var(--color-borde);margin-bottom:1.25rem;flex-wrap:wrap;">
                <button class="tab-mis active" data-tab="cp-mant"
                        style="padding:.5rem 1.2rem;border:none;background:none;cursor:pointer;color:var(--color-texto-claro);border-bottom:2px solid transparent;margin-bottom:-2px;">
                    <i class="fas fa-money-bill-wave"></i> Mantenimiento
                    <?php if (($mant['pendientes']+$mant['vencidos']) > 0): ?>
                        <span class="badge badge-warning" style="margin-left:4px;"><?php echo $mant['pendientes']+$mant['vencidos']; ?> pendiente(s)</span>
                    <?php endif; ?>
                </button>
                <button class="tab-mis" data-tab="cp-insc"
                        style="padding:.5rem 1.2rem;border:none;background:none;cursor:pointer;color:var(--color-texto-claro);border-bottom:2px solid transparent;margin-bottom:-2px;">
                    <i class="fas fa-file-signature"></i> Inscripción
                    <?php if ($inscripcion && $inscripcion['estado'] !== 'pagado'): ?>
                        <span class="badge badge-warning" style="margin-left:4px;">Pendiente</span>
                    <?php elseif ($inscripcion): ?>
                        <span class="badge badge-success" style="margin-left:4px;">Pagada</span>
                    <?php endif; ?>
                </button>
                <button class="tab-mis" data-tab="cp-memb"
                        style="padding:.5rem 1.2rem;border:none;background:none;cursor:pointer;color:var(--color-texto-claro);border-bottom:2px solid transparent;margin-bottom:-2px;">
                    <i class="fas fa-id-card"></i> Membresía Club
                    <?php if ($memb['total']): ?>
                        <span class="badge badge-info" style="margin-left:4px;"><?php echo $memb['pagados'].'/'.$memb['total']; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Tab Mantenimiento -->
            <div id="cp-mant" class="tab-mis-content">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
                    <span style="color:var(--color-texto-claro);font-size:.9rem;">
                        Mostrando pagos del año:
                    </span>
                    <form method="GET" style="display:flex;gap:.5rem;">
                        <input type="hidden" name="accion" value="mis_pagos">
                        <select name="anio" class="form-control" onchange="this.form.submit()" style="width:auto;">
                            <?php for ($a = date('Y'); $a >= date('Y') - 2; $a--): ?>
                                <option value="<?php echo $a; ?>" <?php echo $a === $anio ? 'selected' : ''; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>
                </div>
                <?php if (!empty($pagos_mant)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr>
                            <?php if (count($mis_ids) > 1): ?><th>Lote</th><?php endif; ?>
                            <th>Mes</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($pagos_mant as $p): ?>
                            <tr>
                                <?php if (count($mis_ids) > 1): ?>
                                    <td><small style="color:var(--color-texto-claro);">Lote <?php echo $p['numero_lote']; ?><?php echo $p['manzana'] ? ' / Mz '.$p['manzana'] : ''; ?></small></td>
                                <?php endif; ?>
                                <td><?php echo nombreMes($p['mes']) . ' ' . $p['anio']; ?></td>
                                <td><?php echo formatearMoneda($p['monto']); ?></td>
                                <td><?php echo formatearFecha($p['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($p['estado']); ?>"><?php echo textoEstadoPago($p['estado']); ?></span></td>
                                <td><?php echo $p['fecha_pago'] ? formatearFecha($p['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                <td><?php echo $p['metodo_pago'] ? nombreMetodoPago($p['metodo_pago']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-money-bill-wave"></i><h3>Sin pagos para <?php echo $anio; ?></h3></div>
                <?php endif; ?>
            </div>

            <!-- Tab Inscripción -->
            <div id="cp-insc" class="tab-mis-content" style="display:none;">
                <?php if (!empty($inscripciones)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr>
                            <?php if (count($mis_ids) > 1): ?><th>Lote</th><?php endif; ?>
                            <th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($inscripciones as $ins): ?>
                            <tr>
                                <?php if (count($mis_ids) > 1): ?>
                                    <td><small style="color:var(--color-texto-claro);">Lote <?php echo $ins['numero_lote']; ?><?php echo $ins['manzana'] ? ' / Mz '.$ins['manzana'] : ''; ?></small></td>
                                <?php endif; ?>
                                <td><?php echo formatearMoneda($ins['monto']); ?></td>
                                <td><?php echo formatearFecha($ins['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($ins['estado']); ?>"><?php echo textoEstadoPago($ins['estado']); ?></span></td>
                                <td><?php echo $ins['fecha_pago'] ? formatearFecha($ins['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                <td><?php echo $ins['metodo_pago'] ? nombreMetodoPago($ins['metodo_pago']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-signature"></i>
                        <h3>Sin inscripción registrada</h3>
                        <p>Contacta a la administración para registrar tu inscripción</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Membresía -->
            <div id="cp-memb" class="tab-mis-content" style="display:none;">
                <?php if (!empty($cuotas_memb)): ?>
                <?php
                $total_cuotas_plan = array_sum(array_unique(array_column($cuotas_memb, 'total_cuotas')));
                $pagd  = count(array_filter($cuotas_memb, fn($c) => $c['estado'] === 'pagado'));
                $tot   = count($cuotas_memb);
                $pct_m = $tot > 0 ? round(($pagd / $tot) * 100) : 0;
                ?>
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:6px;">
                        <span><?php echo $pagd; ?> / <?php echo $tot; ?> cuotas pagadas</span>
                        <strong><?php echo $pct_m; ?>%</strong>
                    </div>
                    <div style="background:var(--color-borde);border-radius:6px;height:10px;overflow:hidden;">
                        <div style="width:<?php echo $pct_m; ?>%;background:<?php echo $pct_m==100?'var(--color-exito)':'var(--color-primario)'; ?>;height:100%;border-radius:6px;"></div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr>
                            <?php if (count($mis_ids) > 1): ?><th>Lote</th><?php endif; ?>
                            <th>Cuota</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($cuotas_memb as $c): ?>
                            <tr>
                                <?php if (count($mis_ids) > 1): ?>
                                    <td><small style="color:var(--color-texto-claro);">Lote <?php echo $c['numero_lote']; ?></small></td>
                                <?php endif; ?>
                                <td><span class="badge badge-info"><?php echo $c['cuota_numero']; ?>/<?php echo $c['total_cuotas']; ?></span></td>
                                <td><?php echo formatearMoneda($c['monto']); ?></td>
                                <td><?php echo formatearFecha($c['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($c['estado']); ?>"><?php echo textoEstadoPago($c['estado']); ?></span></td>
                                <td><?php echo $c['fecha_pago'] ? formatearFecha($c['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-id-card"></i>
                        <h3>Sin membresía registrada</h3>
                        <p>Contacta a la administración para registrar tu membresía</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
    (function() {
        const btns = document.querySelectorAll('.tab-mis');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => {
                    b.classList.remove('active');
                    b.style.color             = 'var(--color-texto-claro)';
                    b.style.borderBottomColor = 'transparent';
                    b.style.fontWeight        = 'normal';
                });
                document.querySelectorAll('.tab-mis-content').forEach(c => c.style.display = 'none');
                btn.classList.add('active');
                btn.style.color             = 'var(--color-primario)';
                btn.style.borderBottomColor = 'var(--color-primario)';
                btn.style.fontWeight        = '600';
                document.getElementById(btn.dataset.tab).style.display = 'block';
            });
        });
        if (btns.length) btns[0].click();
    })();
    </script>

    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function mostrarMisComprobantes(array $mis_ids) {
    $modelo_comprobante = new Comprobante();
    // Obtener comprobantes de todos los lotes
    $db   = Database::getInstance()->getConnection();
    $ph   = implode(',', array_fill(0, count($mis_ids), '?'));
    $stmt = $db->prepare(
        "SELECT c.*, p.mes, p.anio, p.tipo_pago, p.cuota_numero,
                cl.numero_lote, cl.manzana
         FROM comprobantes c
         INNER JOIN pagos p ON c.pago_id = p.id
         INNER JOIN clientes cl ON c.cliente_id = cl.id
         WHERE c.cliente_id IN ({$ph}) AND c.estado_emision = 'emitido'
         ORDER BY c.fecha_creacion DESC"
    );
    $stmt->execute($mis_ids);
    $comprobantes = $stmt->fetchAll();
    
    $titulo = 'Mis Comprobantes';
    $subtitulo = 'Descarga tus comprobantes de pago';
    $pagina_actual = 'mis_comprobantes';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Mis Comprobantes</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($comprobantes)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Serie-Número</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comprobantes as $comp): ?>
                                <tr>
                                    <td><?php echo nombreTipoComprobante($comp['tipo_comprobante']); ?></td>
                                    <td><strong><?php echo $comp['serie'] . '-' . $comp['numero']; ?></strong></td>
                                    <td><?php echo $comp['concepto']; ?></td>
                                    <td><?php echo formatearMoneda($comp['monto']); ?></td>
                                    <td><?php echo formatearFecha($comp['fecha_emision'], 'd/m/Y H:i'); ?></td>
                                    <td>
                                        <a href="<?php echo APP_URL; ?>/controllers/comprobante_controller.php?accion=imprimir&id=<?php echo $comp['id']; ?>" 
                                           class="btn btn-sm btn-outline" target="_blank">
                                            <i class="fas fa-print"></i> Imprimir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice"></i>
                    <h3>No hay comprobantes emitidos</h3>
                    <p>Los comprobantes estarán disponibles cuando se emitan</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function mostrarReuniones() {
    $modelo_reunion  = new Reunion();
    $modelo_archivo  = new ArchivoAdjunto();
    $reuniones       = $modelo_reunion->obtenerPublicadas();

    $titulo        = 'Reuniones y Acuerdos';
    $subtitulo     = 'Historial de reuniones del condominio';
    $pagina_actual = 'reuniones';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Reuniones y Acuerdos</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($reuniones)): ?>
                <div class="timeline">
                    <?php foreach ($reuniones as $reunion): ?>
                        <?php
                        $modelo_acuerdo = new Acuerdo();
                        $acuerdos       = $modelo_acuerdo->obtenerPorReunion($reunion['id']);
                        $archivos_r     = $modelo_archivo->obtenerPorReunion($reunion['id']);
                        $acta_r         = !empty($archivos_r) ? $archivos_r[0] : null;
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;margin-bottom:.5rem;">
                                    <h4 style="margin:0;"><?php echo $reunion['titulo']; ?></h4>
                                    <?php if ($acta_r): ?>
                                        <a href="<?php echo APP_URL; ?>/controllers/archivo_controller.php?accion=descargar&id=<?php echo $acta_r['id']; ?>"
                                           class="btn btn-sm btn-success" target="_blank"
                                           title="Descargar acta de la reunión">
                                            <i class="fas fa-file-pdf"></i> Descargar Acta
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="date">
                                    <i class="fas fa-calendar"></i> <?php echo formatearFecha($reunion['fecha_reunion']); ?>
                                    <?php if ($reunion['hora_reunion']): ?>
                                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($reunion['hora_reunion'])); ?>
                                    <?php endif; ?>
                                    <?php if ($reunion['lugar']): ?>
                                        <i class="fas fa-map-marker-alt"></i> <?php echo $reunion['lugar']; ?>
                                    <?php endif; ?>
                                    <span class="badge <?php echo claseEstadoReunion($reunion['estado']); ?>">
                                        <?php echo textoEstadoReunion($reunion['estado']); ?>
                                    </span>
                                </div>

                                <p style="margin-bottom: 1rem;"><?php echo nl2br($reunion['descripcion']); ?></p>

                                <?php if (!empty($acuerdos)): ?>
                                    <strong>Acuerdos:</strong>
                                    <ul style="margin: 0.5rem 0 0; padding-left: 1.5rem;">
                                        <?php foreach ($acuerdos as $acuerdo): ?>
                                            <li>
                                                <?php echo $acuerdo['descripcion']; ?>
                                                <?php if ($acuerdo['responsable']): ?>
                                                    <small style="color: var(--color-texto-claro);">
                                                        (Responsable: <?php echo $acuerdo['responsable']; ?>)
                                                    </small>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if ($reunion['proxima_fecha']): ?>
                                    <p style="margin-top: 0.75rem; padding: 0.5rem; background: var(--color-info-claro); border-radius: var(--radio); font-size: 0.85rem;">
                                        <i class="fas fa-calendar-check"></i>
                                        <strong>Próxima reunión:</strong> <?php echo formatearFecha($reunion['proxima_fecha']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-handshake"></i>
                    <h3>No hay reuniones publicadas</h3>
                    <p>Las reuniones publicadas se mostrarán aquí</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function mostrarMiPerfil(array $mis_lotes, array $cliente_base) {
    $cliente = $cliente_base;
    
    $titulo = 'Mi Perfil';
    $subtitulo = 'Mi información personal';
    $pagina_actual = 'mi_perfil';
    
    ob_start();
    ?>
    
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Información Personal</h3>
            </div>
            <div class="card-body">
                <ul class="detail-list">
                    <li>
                        <span class="label">Nombres y Apellidos</span>
                        <span class="value"><?php echo $cliente['nombres'] . ' ' . $cliente['apellidos']; ?></span>
                    </li>
                    <li>
                        <span class="label">DNI</span>
                        <span class="value"><?php echo $cliente['dni']; ?></span>
                    </li>
                    <li>
                        <span class="label">RUC</span>
                        <span class="value"><?php echo $cliente['ruc'] ?: '-'; ?></span>
                    </li>
                    <li>
                        <span class="label">Teléfono</span>
                        <span class="value"><?php echo $cliente['telefono'] ?: '-'; ?></span>
                    </li>
                    <li>
                        <span class="label">Correo</span>
                        <span class="value"><?php echo $cliente['correo'] ?: '-'; ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-home"></i> Mis Propiedades (<?php echo count($mis_lotes); ?> lote<?php echo count($mis_lotes) > 1 ? 's' : ''; ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Etapa</th>
                                <th>Manzana</th>
                                <th>Lote</th>
                                <th>F. Compra</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mis_lotes as $lote): ?>
                            <tr>
                                <td><?php echo $lote['etapa']       ?: '-'; ?></td>
                                <td><?php echo $lote['manzana']     ?: '-'; ?></td>
                                <td><strong><?php echo $lote['numero_lote']; ?></strong></td>
                                <td><?php echo $lote['fecha_compra'] ? formatearFecha($lote['fecha_compra']) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}
