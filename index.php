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
$mes_actual = date('n');
$anio_actual = date('Y');

// Estadísticas de pagos del mes
$stats_pagos = $modelo_pago->estadisticas($mes_actual, $anio_actual);

// Próxima reunión
$proxima_reunion = $modelo_reunion->obtenerProxima();

// Últimos pagos
$ultimos_pagos = $modelo_pago->ultimosPagos(10);

// Acuerdos pendientes
$modelo_acuerdo = new Acuerdo();
$acuerdos_pendientes = $modelo_acuerdo->obtenerPendientes();

// Datos para gráfico de pagos del año
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
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <div class="label">Total Clientes</div>
            <div class="value"><?php echo $stats_clientes['activos']; ?></div>
            <div class="change positive">
                <i class="fas fa-check-circle"></i> Activos
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <div class="label">Pagados este Mes</div>
            <div class="value"><?php echo $stats_pagos['pagados']; ?></div>
            <div class="change positive">
                <?php echo formatearMoneda($stats_pagos['total_recaudado']); ?> recaudado
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon yellow">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <div class="label">Pendientes</div>
            <div class="value"><?php echo $stats_pagos['pendientes']; ?></div>
            <div class="change">
                Por cobrar este mes
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-info">
            <div class="label">Vencidos</div>
            <div class="value"><?php echo $stats_pagos['vencidos']; ?></div>
            <div class="change negative">
                <?php echo formatearMoneda($stats_pagos['total_vencido']); ?> vencido
            </div>
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
                            <th>Mes/Año</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pagos as $pago): ?>
                            <tr>
                                <td><?php echo $pago['cliente_nombre']; ?></td>
                                <td><?php echo $pago['numero_lote']; ?></td>
                                <td><?php echo nombreMes($pago['mes']) . ' ' . $pago['anio']; ?></td>
                                <td><?php echo formatearMoneda($pago['monto']); ?></td>
                                <td>
                                    <span class="badge <?php echo claseEstadoPago($pago['estado']); ?>">
                                        <?php echo textoEstadoPago($pago['estado']); ?>
                                    </span>
                                </td>
                                <td><?php echo $pago['fecha_pago'] ? formatearFecha($pago['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
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
