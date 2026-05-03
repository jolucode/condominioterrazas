<?php
/**
 * DASHBOARD PRINCIPAL (ADMIN)
 */
require_once __DIR__ . '/config/autoload.php';

// Verificar autenticación y rol
if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$db = Database::getInstance()->getConnection();

// Obtener estadísticas
$modelo_pago = new Pago();
$modelo_cliente = new Cliente();
$modelo_reunion = new Reunion();

// Estadísticas generales
$stats_clientes = $modelo_cliente->estadisticas();

// Mes y año actual
$mes_actual  = date('n');
$anio_actual = date('Y');

// Estadísticas de mantenimiento del mes
$stats_pagos = $modelo_pago->estadisticas($mes_actual, $anio_actual);

// Stats de inscripciones y membresía (globales)
$stats_insc = $db->query(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado='pagado'  THEN 1 ELSE 0 END) as pagadas,
        SUM(CASE WHEN estado!='pagado' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado='pagado'  THEN monto ELSE 0 END) as recaudado,
        SUM(CASE WHEN estado!='pagado' THEN monto ELSE 0 END) as por_cobrar
     FROM pagos WHERE tipo_pago='inscripcion'"
)->fetch();

$stats_memb = $db->query(
    "SELECT
        COUNT(DISTINCT cliente_id) as clientes,
        SUM(CASE WHEN estado='pagado'  THEN monto ELSE 0 END) as recaudado,
        SUM(CASE WHEN estado!='pagado' THEN monto ELSE 0 END) as por_cobrar
     FROM pagos WHERE tipo_pago='membresia_cuota'"
)->fetch();

// Deuda global consolidada
$deuda_global = $db->query(
    "SELECT SUM(CASE WHEN estado!='pagado' THEN monto ELSE 0 END) as total_deuda
     FROM pagos WHERE tipo_pago IN ('mantenimiento','inscripcion','membresia_cuota')"
)->fetch()['total_deuda'];

// Próxima reunión
$proxima_reunion = $modelo_reunion->obtenerProxima();

// Últimos pagos (todos los tipos)
$ultimos_pagos = $modelo_pago->ultimosPagos(8);

// Acuerdos pendientes
$modelo_acuerdo      = new Acuerdo();
$acuerdos_pendientes = $modelo_acuerdo->obtenerPendientes();

// Datos para gráfico de pagos del año (mantenimiento)
$pagos_por_mes = $modelo_pago->pagosPorMes($anio_actual);

// Preparar datos para el gráfico
$datos_grafico_meses = [];
for ($i = 1; $i <= 12; $i++) {
    $datos_grafico_meses[$i] = [
        'mes' => nombreMes($i),
        'pagados' => 0,
        'pendientes' => 0,
        'vencidos' => 0,
        'recaudado' => 0
    ];
}

foreach ($pagos_por_mes as $pago_mes) {
    $datos_grafico_meses[$pago_mes['mes']] = [
        'mes' => nombreMes($pago_mes['mes']),
        'pagados' => intval($pago_mes['pagados']),
        'pendientes' => intval($pago_mes['pendientes']),
        'vencidos' => intval($pago_mes['vencidos']),
        'recaudado' => floatval($pago_mes['total_recaudado'])
    ];
}

$titulo = 'Dashboard';
$subtitulo = 'Resumen general del condominio';
$pagina_actual = 'dashboard';

// Capturar contenido
ob_start();
?>

<!-- Estadísticas principales -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="label">Propietarios Activos</div>
            <div class="value"><?php echo $stats_clientes['activos']; ?></div>
            <div class="change positive"><i class="fas fa-check-circle"></i> Activos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="label">Mant. Pagado (<?php echo nombreMes($mes_actual); ?>)</div>
            <div class="value"><?php echo $stats_pagos['pagados']; ?></div>
            <div class="change positive"><?php echo formatearMoneda($stats_pagos['total_recaudado']); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="label">Mant. Pendiente (<?php echo nombreMes($mes_actual); ?>)</div>
            <div class="value"><?php echo $stats_pagos['pendientes'] + $stats_pagos['vencidos']; ?></div>
            <div class="change negative"><?php echo formatearMoneda($stats_pagos['total_pendiente'] + $stats_pagos['total_vencido']); ?> por cobrar</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
        <div class="stat-info">
            <div class="label">Deuda Global Total</div>
            <div class="value" style="font-size:1.1rem;"><?php echo formatearMoneda($deuda_global); ?></div>
            <div class="change negative">3 tipos de pago</div>
        </div>
    </div>
</div>

<!-- Resumen de recaudación por tipo -->
<div class="card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-chart-pie"></i> Recaudación por Tipo de Pago</h3>
        <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=deuda_consolidada"
           class="btn btn-outline btn-sm">
            <i class="fas fa-exclamation-circle"></i> Ver deudas
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo de Pago</th>
                        <th style="text-align:right">Recaudado</th>
                        <th style="text-align:right">Por Cobrar</th>
                        <th style="text-align:right">% Cobrado</th>
                        <th>Acceso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $mant_total = $stats_pagos['total_recaudado'] + $stats_pagos['total_pendiente'] + $stats_pagos['total_vencido'];
                    $mant_pct   = $mant_total > 0 ? round(($stats_pagos['total_recaudado'] / $mant_total) * 100) : 0;
                    $insc_total = $stats_insc['recaudado'] + $stats_insc['por_cobrar'];
                    $insc_pct   = $insc_total > 0 ? round(($stats_insc['recaudado'] / $insc_total) * 100) : 0;
                    $memb_total = $stats_memb['recaudado'] + $stats_memb['por_cobrar'];
                    $memb_pct   = $memb_total > 0 ? round(($stats_memb['recaudado'] / $memb_total) * 100) : 0;
                    ?>
                    <tr>
                        <td><i class="fas fa-money-bill-wave" style="color:var(--color-primario)"></i> <strong>Mantenimiento</strong></td>
                        <td style="text-align:right;color:var(--color-exito);font-weight:600;"><?php echo formatearMoneda($stats_pagos['total_recaudado']); ?></td>
                        <td style="text-align:right;color:var(--color-peligro);"><?php echo formatearMoneda($stats_pagos['total_pendiente'] + $stats_pagos['total_vencido']); ?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;align-items:center;gap:.5rem;justify-content:flex-end;">
                                <div style="width:60px;background:var(--color-borde);border-radius:4px;height:6px;overflow:hidden;">
                                    <div style="width:<?php echo $mant_pct; ?>%;background:var(--color-exito);height:100%;"></div>
                                </div>
                                <span><?php echo $mant_pct; ?>%</span>
                            </div>
                        </td>
                        <td><a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="btn btn-sm btn-outline">Ver</a></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-signature" style="color:var(--color-info)"></i> <strong>Inscripción</strong>
                            <small style="color:var(--color-texto-claro)"> (<?php echo $stats_insc['pagadas']; ?>/<?php echo $stats_insc['total']; ?> propietarios)</small>
                        </td>
                        <td style="text-align:right;color:var(--color-exito);font-weight:600;"><?php echo formatearMoneda($stats_insc['recaudado']); ?></td>
                        <td style="text-align:right;color:var(--color-peligro);"><?php echo formatearMoneda($stats_insc['por_cobrar']); ?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;align-items:center;gap:.5rem;justify-content:flex-end;">
                                <div style="width:60px;background:var(--color-borde);border-radius:4px;height:6px;overflow:hidden;">
                                    <div style="width:<?php echo $insc_pct; ?>%;background:var(--color-exito);height:100%;"></div>
                                </div>
                                <span><?php echo $insc_pct; ?>%</span>
                            </div>
                        </td>
                        <td><a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=listar" class="btn btn-sm btn-outline">Ver</a></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-id-card" style="color:var(--color-advertencia)"></i> <strong>Membresía Club</strong>
                            <small style="color:var(--color-texto-claro)"> (<?php echo $stats_memb['clientes']; ?> enrolados)</small>
                        </td>
                        <td style="text-align:right;color:var(--color-exito);font-weight:600;"><?php echo formatearMoneda($stats_memb['recaudado']); ?></td>
                        <td style="text-align:right;color:var(--color-peligro);"><?php echo formatearMoneda($stats_memb['por_cobrar']); ?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;align-items:center;gap:.5rem;justify-content:flex-end;">
                                <div style="width:60px;background:var(--color-borde);border-radius:4px;height:6px;overflow:hidden;">
                                    <div style="width:<?php echo $memb_pct; ?>%;background:var(--color-exito);height:100%;"></div>
                                </div>
                                <span><?php echo $memb_pct; ?>%</span>
                            </div>
                        </td>
                        <td><a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=listar" class="btn btn-sm btn-outline">Ver</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Accesos rápidos -->
<div class="card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Accesos Rápidos</h3>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=crear" class="quick-action">
                <i class="fas fa-user-plus"></i>
                <span>Nuevo Cliente</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=registrar" class="quick-action">
                <i class="fas fa-cash-register"></i>
                <span>Registrar Pago</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=crear" class="quick-action">
                <i class="fas fa-calendar-plus"></i>
                <span>Nueva Reunión</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=index" class="quick-action">
                <i class="fas fa-chart-pie"></i>
                <span>Ver Reportes</span>
            </a>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Gráfico de pagos por mes -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar"></i> Pagos por Mes - <?php echo $anio_actual; ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($datos_grafico_meses)): ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($datos_grafico_meses as $datos): ?>
                        <?php if ($datos['pagados'] > 0 || $datos['pendientes'] > 0 || $datos['vencidos'] > 0): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="min-width: 80px; font-size: 0.8rem; color: var(--color-texto-claro);"><?php echo $datos['mes']; ?></div>
                                <div style="flex: 1; background: var(--color-fondo); border-radius: 0.25rem; height: 24px; overflow: hidden; display: flex;">
                                    <?php 
                                    $total = $datos['pagados'] + $datos['pendientes'] + $datos['vencidos'];
                                    $porcentaje_pagados = $total > 0 ? ($datos['pagados'] / $total) * 100 : 0;
                                    $porcentaje_pendientes = $total > 0 ? ($datos['pendientes'] / $total) * 100 : 0;
                                    $porcentaje_vencidos = $total > 0 ? ($datos['vencidos'] / $total) * 100 : 0;
                                    ?>
                                    <div style="width: <?php echo $porcentaje_pagados; ?>%; background: var(--color-exito); height: 100%;" 
                                         title="Pagados: <?php echo $datos['pagados']; ?>"></div>
                                    <div style="width: <?php echo $porcentaje_pendientes; ?>%; background: var(--color-advertencia); height: 100%;"
                                         title="Pendientes: <?php echo $datos['pendientes']; ?>"></div>
                                    <div style="width: <?php echo $porcentaje_vencidos; ?>%; background: var(--color-peligro); height: 100%;"
                                         title="Vencidos: <?php echo $datos['vencidos']; ?>"></div>
                                </div>
                                <div style="min-width: 40px; font-size: 0.75rem; text-align: right;">
                                    <span style="color: var(--color-exito);"><?php echo $datos['pagados']; ?></span>/<?php echo $total; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.8rem;">
                    <span><span style="display: inline-block; width: 12px; height: 12px; background: var(--color-exito); border-radius: 2px; margin-right: 0.25rem;"></span> Pagados</span>
                    <span><span style="display: inline-block; width: 12px; height: 12px; background: var(--color-advertencia); border-radius: 2px; margin-right: 0.25rem;"></span> Pendientes</span>
                    <span><span style="display: inline-block; width: 12px; height: 12px; background: var(--color-peligro); border-radius: 2px; margin-right: 0.25rem;"></span> Vencidos</span>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Sin datos aún</h3>
                    <p>Los pagos se mostrarán aquí conforme se registren</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Próxima reunión -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-alt"></i> Próxima Reunión</h3>
        </div>
        <div class="card-body">
            <?php if ($proxima_reunion): ?>
                <h4 style="margin-bottom: 0.5rem;"><?php echo $proxima_reunion['titulo']; ?></h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem; margin-bottom: 1rem;">
                    <i class="fas fa-calendar"></i> <?php echo formatearFecha($proxima_reunion['fecha_reunion']); ?>
                    <?php if ($proxima_reunion['hora_reunion']): ?>
                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($proxima_reunion['hora_reunion'])); ?>
                    <?php endif; ?>
                </p>
                
                <?php if ($proxima_reunion['lugar']): ?>
                    <p style="font-size: 0.85rem; margin-bottom: 1rem;">
                        <i class="fas fa-map-marker-alt"></i> <?php echo $proxima_reunion['lugar']; ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($proxima_reunion['proxima_fecha']): ?>
                    <p style="font-size: 0.85rem; padding: 0.75rem; background: var(--color-info-claro); border-radius: var(--radio);">
                        <i class="fas fa-calendar-check"></i> 
                        <strong>Próxima fecha:</strong> <?php echo formatearFecha($proxima_reunion['proxima_fecha']); ?>
                    </p>
                <?php endif; ?>
                
                <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=ver&id=<?php echo $proxima_reunion['id']; ?>" 
                   class="btn btn-outline btn-sm mt-2">
                    <i class="fas fa-eye"></i> Ver detalles
                </a>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No hay reuniones programadas</h3>
                    <p>Cree una nueva reunión desde el botón inferior</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Últimos pagos -->
<div class="card mt-3">
    <div class="card-header">
        <h3><i class="fas fa-receipt"></i> Últimos Pagos Registrados</h3>
        <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="btn btn-outline btn-sm">
            Ver todos
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($ultimos_pagos)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Lote</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pagos as $pago):
                            // Determinar icono y concepto según tipo
                            $iconos = [
                                'mantenimiento'   => '<i class="fas fa-money-bill-wave" style="color:var(--color-primario)"></i>',
                                'inscripcion'     => '<i class="fas fa-file-signature" style="color:var(--color-info)"></i>',
                                'membresia_cuota' => '<i class="fas fa-id-card" style="color:var(--color-advertencia)"></i>',
                            ];
                            $tipo_icon = $iconos[$pago['tipo_pago']] ?? '';
                            if ($pago['tipo_pago'] === 'mantenimiento') {
                                $concepto = nombreMes($pago['mes']) . ' ' . $pago['anio'];
                            } elseif ($pago['tipo_pago'] === 'inscripcion') {
                                $concepto = 'Inscripción';
                            } else {
                                $concepto = 'Membresía cuota ' . ($pago['cuota_numero'] ?? '?');
                            }
                        ?>
                            <tr>
                                <td><?php echo $pago['cliente_nombre']; ?></td>
                                <td><?php echo $pago['numero_lote']; ?></td>
                                <td><?php echo $tipo_icon; ?></td>
                                <td><?php echo $concepto; ?></td>
                                <td><?php echo formatearMoneda($pago['monto']); ?></td>
                                <td>
                                    <span class="badge <?php echo claseEstadoPago($pago['estado']); ?>">
                                        <?php echo textoEstadoPago($pago['estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatearFecha($pago['fecha_creacion'], 'd/m/Y H:i'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Sin pagos registrados</h3>
                <p>Los pagos se mostrarán aquí conforme se registren</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$contenido = ob_get_clean();

// Incluir layout
vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
