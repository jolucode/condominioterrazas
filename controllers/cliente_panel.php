<?php
/**
 * PANEL DEL CLIENTE
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esCliente()) {
    redirigir('login.php');
}

$cliente_id = $_SESSION['usuario_cliente_id'];
$accion = $_GET['accion'] ?? 'inicio';

switch ($accion) {
    case 'inicio':
        mostrarInicio($cliente_id);
        break;
    case 'mis_pagos':
        mostrarMisPagos($cliente_id);
        break;
    case 'mis_comprobantes':
        mostrarMisComprobantes($cliente_id);
        break;
    case 'reuniones':
        mostrarReuniones($cliente_id);
        break;
    case 'mi_perfil':
        mostrarMiPerfil($cliente_id);
        break;
    default:
        mostrarInicio($cliente_id);
        break;
}

function mostrarInicio($cliente_id) {
    $modelo_cliente = new Cliente();
    $cliente = $modelo_cliente->obtenerConResumen($cliente_id);
    
    $modelo_pago = new Pago();
    $mes_actual = date('n');
    $anio_actual = date('Y');
    
    // Pago del mes actual
    $sql = "SELECT * FROM pagos WHERE cliente_id = :cliente_id AND mes = :mes AND anio = :anio LIMIT 1";
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute([':cliente_id' => $cliente_id, ':mes' => $mes_actual, ':anio' => $anio_actual]);
    $pago_mes = $stmt->fetch();
    
    // Próxima reunión
    $modelo_reunion = new Reunion();
    $proxima_reunion = $modelo_reunion->obtenerProxima();
    
    // Últimas reuniones publicadas
    $reuniones = $modelo_reunion->obtenerPublicadas();
    $reuniones = array_slice($reuniones, 0, 3);
    
    $titulo = 'Mi Panel';
    $subtitulo = 'Bienvenido, ' . $cliente['nombres'];
    $pagina_actual = 'inicio';
    
    ob_start();
    ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-home"></i></div>
            <div class="stat-info">
                <div class="label">Mi Lote</div>
                <div class="value"><?php echo $cliente['numero_lote']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon <?php echo ($pago_mes['estado'] ?? 'pendiente') === 'pagado' ? 'green' : 'red'; ?>">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <div class="label">Estado <?php echo nombreMes($mes_actual); ?></div>
                <div class="value"><?php echo textoEstadoPago($pago_mes['estado'] ?? 'pendiente'); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="label">Pagos Realizados</div>
                <div class="value"><?php echo $cliente['pagos_realizados']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="label">Pagos Pendientes</div>
                <div class="value"><?php echo $cliente['pagos_pendientes']; ?></div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-2">
        <!-- Próxima reunión -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> Próxima Reunión</h3>
            </div>
            <div class="card-body">
                <?php if ($proxima_reunion): ?>
                    <h4><?php echo $proxima_reunion['titulo']; ?></h4>
                    <p style="color: var(--color-texto-claro);">
                        <i class="fas fa-calendar"></i> <?php echo formatearFecha($proxima_reunion['fecha_reunion']); ?>
                        <?php if ($proxima_reunion['hora_reunion']): ?>
                            <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($proxima_reunion['hora_reunion'])); ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($proxima_reunion['lugar']): ?>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo $proxima_reunion['lugar']; ?></p>
                    <?php endif; ?>
                    <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=reuniones" class="btn btn-outline btn-sm mt-2">
                        Ver todas las reuniones
                    </a>
                <?php else: ?>
                    <p style="color: var(--color-texto-claro);">No hay reuniones programadas</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Últimos acuerdos -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-handshake"></i> Últimos Acuerdos</h3>
            </div>
            <div class="card-body">
                <?php
                $modelo_acuerdo = new Acuerdo();
                $acuerdos = $modelo_acuerdo->obtenerPendientes();
                ?>
                <?php if (!empty($acuerdos)): ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach (array_slice($acuerdos, 0, 5) as $acuerdo): ?>
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--color-borde);">
                                <div style="font-weight: 500;"><?php echo $acuerdo['descripcion']; ?></div>
                                <small style="color: var(--color-texto-claro);">
                                    <?php echo $acuerdo['reunion_titulo']; ?> - <?php echo formatearFecha($acuerdo['fecha_reunion']); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: var(--color-texto-claro);">No hay acuerdos pendientes</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function mostrarMisPagos($cliente_id) {
    $modelo_pago    = new Pago();
    $modelo_cliente = new Cliente();

    $anio    = intval($_GET['anio'] ?? date('Y'));
    $resumen = $modelo_pago->resumenPorCliente($cliente_id);
    $mant    = $resumen['mantenimiento'];
    $insc    = $resumen['inscripcion'];
    $memb    = $resumen['membresia_cuota'];

    $pagos_mant  = $modelo_pago->obtenerPorCliente($cliente_id, $anio, 'mantenimiento');

    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM pagos WHERE cliente_id=:cid AND tipo_pago='inscripcion' LIMIT 1");
    $stmt->execute([':cid' => $cliente_id]);
    $inscripcion = $stmt->fetch();

    $stmt_m = $db->prepare("SELECT * FROM pagos WHERE cliente_id=:cid AND tipo_pago='membresia_cuota' ORDER BY cuota_numero ASC");
    $stmt_m->execute([':cid' => $cliente_id]);
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
                        <thead><tr><th>Mes</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th></tr></thead>
                        <tbody>
                        <?php foreach ($pagos_mant as $p): ?>
                            <tr>
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
                <?php if ($inscripcion): ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Concepto</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Inscripción / Empadronamiento</td>
                                <td><?php echo formatearMoneda($inscripcion['monto']); ?></td>
                                <td><?php echo formatearFecha($inscripcion['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($inscripcion['estado']); ?>"><?php echo textoEstadoPago($inscripcion['estado']); ?></span></td>
                                <td><?php echo $inscripcion['fecha_pago'] ? formatearFecha($inscripcion['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                <td><?php echo $inscripcion['metodo_pago'] ? nombreMetodoPago($inscripcion['metodo_pago']) : '-'; ?></td>
                            </tr>
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
                $plan_t  = $cuotas_memb[0]['total_cuotas'];
                $pagd    = count(array_filter($cuotas_memb, fn($c) => $c['estado'] === 'pagado'));
                $pct_m   = $plan_t > 0 ? round(($pagd / $plan_t) * 100) : 0;
                ?>
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:6px;">
                        <span><?php echo $pagd; ?> / <?php echo $plan_t; ?> cuotas pagadas</span>
                        <strong><?php echo $pct_m; ?>%</strong>
                    </div>
                    <div style="background:var(--color-borde);border-radius:6px;height:10px;overflow:hidden;">
                        <div style="width:<?php echo $pct_m; ?>%;background:<?php echo $pct_m==100?'var(--color-exito)':'var(--color-primario)'; ?>;height:100%;border-radius:6px;"></div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Cuota</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th></tr></thead>
                        <tbody>
                        <?php foreach ($cuotas_memb as $c): ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo $c['cuota_numero']; ?>/<?php echo $c['total_cuotas']; ?></span></td>
                                <td><?php echo formatearMoneda($c['monto']); ?></td>
                                <td><?php echo formatearFecha($c['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($c['estado']); ?>"><?php echo textoEstadoPago($c['estado']); ?></span></td>
                                <td><?php echo $c['fecha_pago'] ? formatearFecha($c['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                <td><?php echo $c['metodo_pago'] ? nombreMetodoPago($c['metodo_pago']) : '-'; ?></td>
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

function mostrarMisComprobantes($cliente_id) {
    $modelo_comprobante = new Comprobante();
    $comprobantes = $modelo_comprobante->obtenerPorCliente($cliente_id);
    
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

function mostrarReuniones($cliente_id) {
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

function mostrarMiPerfil($cliente_id) {
    $modelo_cliente = new Cliente();
    $cliente = $modelo_cliente->obtenerPorId($cliente_id);
    
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
                <h3><i class="fas fa-home"></i> Mi Propiedad</h3>
            </div>
            <div class="card-body">
                <ul class="detail-list">
                    <li>
                        <span class="label">Número de Lote</span>
                        <span class="value"><?php echo $cliente['numero_lote']; ?></span>
                    </li>
                    <li>
                        <span class="label">Manzana</span>
                        <span class="value"><?php echo $cliente['manzana'] ?: '-'; ?></span>
                    </li>
                    <li>
                        <span class="label">Etapa</span>
                        <span class="value"><?php echo $cliente['etapa'] ?: '-'; ?></span>
                    </li>
                    <li>
                        <span class="label">Dirección</span>
                        <span class="value"><?php echo $cliente['direccion'] ?: '-'; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/cliente-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}
