<?php
/**
 * CONTROLADOR DE REPORTES
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'index';

switch ($accion) {
    case 'index':
        mostrarReportes();
        break;
    case 'clientes':
        reporteClientes();
        break;
    case 'pagos_mes':
        reportePagosPorMes();
        break;
    case 'pagos_pendientes':
        reportePagosPendientes();
        break;
    case 'deuda_consolidada':
        reporteDeudaConsolidada();
        break;
    case 'pagos_cliente':
        reportePagosPorCliente();
        break;
    case 'reuniones':
        reporteReuniones();
        break;
    default:
        mostrarReportes();
        break;
}

function mostrarReportes() {
    $titulo = 'Reportes';
    $subtitulo = 'Genera y exporta reportes del condominio';
    $pagina_actual = 'reportes';
    
    ob_start();
    ?>
    
    <div class="grid grid-3">
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-users" style="font-size: 2.5rem; color: var(--color-primario); margin-bottom: 1rem;"></i>
                <h4>Clientes Registrados</h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem;">Lista completa de propietarios</p>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=clientes&formato=excel" class="btn btn-success btn-sm mt-2">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=clientes&formato=pdf" class="btn btn-danger btn-sm mt-2">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-chart-bar" style="font-size: 2.5rem; color: var(--color-exito); margin-bottom: 1rem;"></i>
                <h4>Pagos por Mes</h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem;">Resumen mensual de pagos</p>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=pagos_mes&formato=excel" class="btn btn-success btn-sm mt-2">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-clock" style="font-size: 2.5rem; color: var(--color-advertencia); margin-bottom: 1rem;"></i>
                <h4>Pagos Pendientes</h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem;">Clientes con pagos pendientes</p>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=pagos_pendientes&formato=excel" class="btn btn-success btn-sm mt-2">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-user-check" style="font-size: 2.5rem; color: var(--color-info); margin-bottom: 1rem;"></i>
                <h4>Pagos por Cliente</h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem;">Historial individual</p>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=pagos_cliente" class="btn btn-primary btn-sm mt-2">
                    <i class="fas fa-search"></i> Ver Reporte
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-handshake" style="font-size: 2.5rem; color: var(--color-secundario); margin-bottom: 1rem;"></i>
                <h4>Historial de Reuniones</h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem;">Todas las reuniones</p>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=reuniones&formato=excel" class="btn btn-success btn-sm mt-2">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>

        <div class="card" style="border-left: 4px solid var(--color-peligro);">
            <div class="card-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-exclamation-circle" style="font-size: 2.5rem; color: var(--color-peligro); margin-bottom: 1rem;"></i>
                <h4>Deuda Consolidada</h4>
                <p style="color: var(--color-texto-claro); font-size: 0.9rem;">Mantenimiento + Inscripción + Membresía por propietario</p>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=deuda_consolidada" class="btn btn-primary btn-sm mt-2">
                    <i class="fas fa-table"></i> Ver Reporte
                </a>
                <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=deuda_consolidada&formato=excel" class="btn btn-success btn-sm mt-2">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function reporteClientes() {
    $modelo_cliente = new Cliente();
    $clientes = $modelo_cliente->obtenerTodos('apellidos, nombres');
    
    $formato = $_GET['formato'] ?? 'excel';
    
    if ($formato === 'excel') {
        // Exportar a CSV (compatible con Excel)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_clientes_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Nombres', 'Apellidos', 'DNI', 'RUC', 'Teléfono', 'Correo', 'Lote', 'Manzana', 'Etapa', 'Estado', 'Fecha Registro'], ';');
        
        foreach ($clientes as $cliente) {
            fputcsv($output, [
                $cliente['id'],
                $cliente['nombres'],
                $cliente['apellidos'],
                $cliente['dni'],
                $cliente['ruc'] ?: '',
                $cliente['telefono'] ?: '',
                $cliente['correo'] ?: '',
                $cliente['numero_lote'],
                $cliente['manzana'] ?: '',
                $cliente['etapa'] ?: '',
                $cliente['estado'],
                formatearFecha($cliente['fecha_registro'], 'd/m/Y')
            ], ';');
        }
        fclose($output);
        exit;
    }
    
    // PDF - Mostrar vista para imprimir
    $titulo = 'Reporte de Clientes';
    $subtitulo = 'Lista de propietarios';
    $pagina_actual = 'reportes';
    
    ob_start();
    ?>
    <div class="card">
        <div class="card-header">
            <h3>Reporte de Clientes</h3>
            <button onclick="window.print()" class="btn btn-outline btn-sm">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="tabla-clientes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombres y Apellidos</th>
                            <th>DNI</th>
                            <th>Teléfono</th>
                            <th>Lote</th>
                            <th>Correo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo $cliente['id']; ?></td>
                                <td><?php echo $cliente['nombres'] . ' ' . $cliente['apellidos']; ?></td>
                                <td><?php echo $cliente['dni']; ?></td>
                                <td><?php echo $cliente['telefono'] ?: '-'; ?></td>
                                <td><?php echo $cliente['numero_lote']; ?></td>
                                <td><?php echo $cliente['correo'] ?: '-'; ?></td>
                                <td><?php echo ucfirst($cliente['estado']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function reportePagosPorMes() {
    $anio  = intval($_GET['anio']  ?? date('Y'));
    $etapa = sanear($_GET['etapa'] ?? '');

    $db     = Database::getInstance()->getConnection();
    $where  = "WHERE p.anio = :anio AND p.tipo_pago = 'mantenimiento'";
    $params = [':anio' => $anio];

    if ($etapa) {
        $where .= " AND c.etapa = :etapa";
        $params[':etapa'] = $etapa;
    }

    $sql = "SELECT
                p.mes, p.anio,
                COUNT(*) as total,
                SUM(CASE WHEN p.estado = 'pagado'    THEN 1 ELSE 0 END) as pagados,
                SUM(CASE WHEN p.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN p.estado = 'vencido'   THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN p.estado = 'pagado'    THEN p.monto ELSE 0 END) as total_recaudado
            FROM pagos p
            INNER JOIN clientes c ON p.cliente_id = c.id
            {$where}
            GROUP BY p.mes, p.anio
            ORDER BY p.mes";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();

    // Etapas para el select
    $etapas = (new Cliente())->obtenerEtapas();

    $formato = $_GET['formato'] ?? 'excel';

    if ($formato === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_pagos_mes_' . $anio . ($etapa ? '_' . $etapa : '') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Mes', 'Año', 'Etapa', 'Total', 'Pagados', 'Pendientes', 'Vencidos', 'Total Recaudado'], ';');

        foreach ($datos as $row) {
            fputcsv($output, [
                nombreMes($row['mes']),
                $row['anio'],
                $etapa ?: 'Todas',
                $row['total'],
                $row['pagados'],
                $row['pendientes'],
                $row['vencidos'],
                $row['total_recaudado']
            ], ';');
        }
        fclose($output);
        exit;
    }

    $titulo      = 'Reporte de Pagos por Mes';
    $subtitulo   = 'Año ' . $anio . ($etapa ? ' — ' . $etapa : '');
    $pagina_actual = 'reportes';

    ob_start();
    ?>
    <div class="card">
        <div class="card-header">
            <h3>Pagos por Mes - <?php echo $anio; ?><?php echo $etapa ? ' — ' . htmlspecialchars($etapa) : ''; ?></h3>
            <div class="d-flex gap-1">
                <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
                    <input type="hidden" name="accion" value="pagos_mes">
                    <select name="anio" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <?php for ($a = date('Y'); $a >= date('Y') - 3; $a--): ?>
                            <option value="<?php echo $a; ?>" <?php echo $a === $anio ? 'selected' : ''; ?>><?php echo $a; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="etapa" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <option value="">Todas las etapas</option>
                        <?php foreach ($etapas as $et): ?>
                            <option value="<?php echo htmlspecialchars($et); ?>" <?php echo $etapa === $et ? 'selected' : ''; ?>><?php echo htmlspecialchars($et); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a href="?accion=pagos_mes&anio=<?php echo $anio; ?>&etapa=<?php echo urlencode($etapa); ?>&formato=excel" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <button onclick="window.print()" class="btn btn-outline btn-sm">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Total</th>
                            <th>Pagados</th>
                            <th>Pendientes</th>
                            <th>Vencidos</th>
                            <th>Total Recaudado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $row): ?>
                            <tr>
                                <td><?php echo nombreMes($row['mes']); ?></td>
                                <td><?php echo $row['total']; ?></td>
                                <td><span class="badge badge-success"><?php echo $row['pagados']; ?></span></td>
                                <td><span class="badge badge-warning"><?php echo $row['pendientes']; ?></span></td>
                                <td><span class="badge badge-danger"><?php echo $row['vencidos']; ?></span></td>
                                <td><?php echo formatearMoneda($row['total_recaudado']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function reportePagosPendientes() {
    $etapa  = sanear($_GET['etapa'] ?? '');
    $tipo   = sanear($_GET['tipo_pago'] ?? 'mantenimiento');
    $etapas = (new Cliente())->obtenerEtapas();

    $db     = Database::getInstance()->getConnection();
    $where  = "WHERE p.estado IN ('pendiente', 'vencido') AND p.tipo_pago = :tipo_pago";
    $params = [':tipo_pago' => $tipo];

    if ($etapa) {
        $where .= " AND c.etapa = :etapa";
        $params[':etapa'] = $etapa;
    }

    $sql = "SELECT p.*, CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre,
                   c.dni, c.numero_lote, c.manzana, c.etapa as cliente_etapa, c.telefono
            FROM pagos p
            INNER JOIN clientes c ON p.cliente_id = c.id
            {$where}
            ORDER BY c.etapa ASC, p.fecha_vencimiento ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $pagos = $stmt->fetchAll();

    $formato = $_GET['formato'] ?? 'excel';

    if ($formato === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        $fname = 'reporte_pendientes_' . $tipo . ($etapa ? '_' . $etapa : '') . '_' . date('Y-m-d') . '.csv';
        header('Content-Disposition: attachment; filename="' . $fname . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Etapa', 'Cliente', 'DNI', 'Lote', 'Manzana', 'Tipo Pago', 'Concepto', 'Monto', 'Vencimiento', 'Estado'], ';');

        foreach ($pagos as $pago) {
            $concepto = $pago['tipo_pago'] === 'mantenimiento'
                ? nombreMes($pago['mes']) . ' ' . $pago['anio']
                : ($pago['tipo_pago'] === 'inscripcion' ? 'Inscripción' : 'Membresía cuota ' . $pago['cuota_numero']);

            fputcsv($output, [
                $pago['cliente_etapa'] ?: '-',
                $pago['cliente_nombre'],
                $pago['dni'],
                $pago['numero_lote'],
                $pago['manzana'] ?: '-',
                ucfirst(str_replace('_', ' ', $pago['tipo_pago'])),
                $concepto,
                $pago['monto'],
                formatearFecha($pago['fecha_vencimiento'], 'd/m/Y'),
                textoEstadoPago($pago['estado'])
            ], ';');
        }
        fclose($output);
        exit;
    }

    $titulo      = 'Reporte de Pagos Pendientes';
    $subtitulo   = count($pagos) . ' registros' . ($etapa ? ' — ' . $etapa : '');
    $pagina_actual = 'reportes';

    ob_start();
    ?>
    <div class="card">
        <div class="card-header">
            <h3>Pagos Pendientes</h3>
            <div class="d-flex gap-1" style="flex-wrap:wrap;gap:.5rem;">
                <form method="GET" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="accion" value="pagos_pendientes">
                    <select name="tipo_pago" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <option value="mantenimiento" <?php echo $tipo==='mantenimiento'?'selected':''; ?>>Mantenimiento</option>
                        <option value="inscripcion"   <?php echo $tipo==='inscripcion'?'selected':''; ?>>Inscripción</option>
                        <option value="membresia_cuota" <?php echo $tipo==='membresia_cuota'?'selected':''; ?>>Membresía</option>
                    </select>
                    <select name="etapa" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <option value="">Todas las etapas</option>
                        <?php foreach ($etapas as $et): ?>
                            <option value="<?php echo htmlspecialchars($et); ?>" <?php echo $etapa===$et?'selected':''; ?>>
                                <?php echo htmlspecialchars($et); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a href="?accion=pagos_pendientes&etapa=<?php echo urlencode($etapa); ?>&tipo_pago=<?php echo urlencode($tipo); ?>&formato=excel"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <button onclick="window.print()" class="btn btn-outline btn-sm">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($pagos)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Etapa</th>
                                <th>Cliente</th>
                                <th>DNI</th>
                                <th>Lote / Mz</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago):
                                $concepto = $pago['tipo_pago'] === 'mantenimiento'
                                    ? nombreMes($pago['mes']) . ' ' . $pago['anio']
                                    : ($pago['tipo_pago'] === 'inscripcion' ? 'Inscripción' : 'Membresía cuota ' . $pago['cuota_numero']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pago['cliente_etapa'] ?: '-'); ?></td>
                                    <td><?php echo $pago['cliente_nombre']; ?></td>
                                    <td><?php echo $pago['dni']; ?></td>
                                    <td><?php echo $pago['numero_lote']; ?><?php echo $pago['manzana'] ? ' / Mz '.$pago['manzana'] : ''; ?></td>
                                    <td><?php echo $concepto; ?></td>
                                    <td><?php echo formatearMoneda($pago['monto']); ?></td>
                                    <td><?php echo formatearFecha($pago['fecha_vencimiento']); ?></td>
                                    <td><span class="badge <?php echo claseEstadoPago($pago['estado']); ?>"><?php echo textoEstadoPago($pago['estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>¡Sin pendientes!</h3>
                    <p>No hay pagos pendientes con los filtros seleccionados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function reportePagosPorCliente() {
    $modelo_cliente = new Cliente();
    $clientes = $modelo_cliente->obtenerActivos();
    
    $cliente_id = intval($_GET['cliente_id'] ?? 0);
    $pagos = [];
    $cliente = null;
    
    if ($cliente_id) {
        $cliente = $modelo_cliente->obtenerPorId($cliente_id);
        $modelo_pago = new Pago();
        $pagos = $modelo_pago->obtenerPorCliente($cliente_id);
    }
    
    $titulo = 'Pagos por Cliente';
    $subtitulo = 'Consulta individual';
    $pagina_actual = 'reportes';
    
    ob_start();
    ?>
    
    <div class="card mb-3">
        <div class="card-header">
            <h3>Buscar Cliente</h3>
        </div>
        <div class="card-body">
            <form method="GET">
                <input type="hidden" name="accion" value="pagos_cliente">
                <div class="form-group">
                    <label>Seleccione un cliente</label>
                    <select name="cliente_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?php echo $cli['id']; ?>" <?php echo $cli['id'] === $cliente_id ? 'selected' : ''; ?>>
                                <?php echo $cli['nombres'] . ' ' . $cli['apellidos'] . ' - Lote ' . $cli['numero_lote']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($cliente): ?>
    <div class="card">
        <div class="card-header">
            <h3>Pagos de: <?php echo $cliente['nombres'] . ' ' . $cliente['apellidos']; ?></h3>
            <?php if (!empty($pagos)): ?>
                <button onclick="window.print()" class="btn btn-outline btn-sm">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($pagos)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mes/Año</th>
                                <th>Monto</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Fecha Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo nombreMes($pago['mes']) . ' ' . $pago['anio']; ?></td>
                                    <td><?php echo formatearMoneda($pago['monto']); ?></td>
                                    <td><?php echo formatearFecha($pago['fecha_vencimiento']); ?></td>
                                    <td><span class="badge <?php echo claseEstadoPago($pago['estado']); ?>"><?php echo textoEstadoPago($pago['estado']); ?></span></td>
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
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function reporteReuniones() {
    $modelo_reunion = new Reunion();
    $reuniones = $modelo_reunion->obtenerTodos('fecha_reunion DESC');
    
    $formato = $_GET['formato'] ?? 'excel';
    
    if ($formato === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_reuniones_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Título', 'Fecha', 'Hora', 'Lugar', 'Estado', 'Próxima Fecha'], ';');
        
        foreach ($reuniones as $reunion) {
            fputcsv($output, [
                $reunion['titulo'],
                formatearFecha($reunion['fecha_reunion'], 'd/m/Y'),
                $reunion['hora_reunion'] ? date('H:i', strtotime($reunion['hora_reunion'])) : '',
                $reunion['lugar'] ?: '',
                textoEstadoReunion($reunion['estado']),
                $reunion['proxima_fecha'] ? formatearFecha($reunion['proxima_fecha'], 'd/m/Y') : ''
            ], ';');
        }
        fclose($output);
        exit;
    }
    
    redirigir('controllers/reunion_controller.php?accion=listar');
}

// ─────────────────────────────────────────────────────
function reporteDeudaConsolidada() {
    $etapa   = sanear($_GET['etapa']   ?? '');
    $solo_deuda = isset($_GET['solo_deuda']) ? (bool)$_GET['solo_deuda'] : true;
    $etapas  = (new Cliente())->obtenerEtapas();

    $db      = Database::getInstance()->getConnection();
    $where_c = "WHERE c.estado = 'activo'";
    $params  = [];
    if ($etapa) {
        $where_c .= " AND c.etapa = :etapa";
        $params[':etapa'] = $etapa;
    }

    $sql = "SELECT
                c.id, c.nombres, c.apellidos, c.dni,
                c.numero_lote, c.manzana, c.etapa as cliente_etapa, c.telefono,
                -- Mantenimiento
                COALESCE(SUM(CASE WHEN p.tipo_pago='mantenimiento' AND p.estado='pagado'   THEN p.monto ELSE 0 END),0) as mant_pagado,
                COALESCE(SUM(CASE WHEN p.tipo_pago='mantenimiento' AND p.estado!='pagado'  THEN p.monto ELSE 0 END),0) as mant_deuda,
                COALESCE(COUNT(CASE WHEN p.tipo_pago='mantenimiento' AND p.estado!='pagado' THEN 1 END),0) as mant_pendientes,
                -- Inscripción
                COALESCE(SUM(CASE WHEN p.tipo_pago='inscripcion'    AND p.estado='pagado'   THEN p.monto ELSE 0 END),0) as insc_pagado,
                COALESCE(SUM(CASE WHEN p.tipo_pago='inscripcion'    AND p.estado!='pagado'  THEN p.monto ELSE 0 END),0) as insc_deuda,
                MAX(CASE WHEN p.tipo_pago='inscripcion' THEN p.estado END) as insc_estado,
                -- Membresía
                COALESCE(SUM(CASE WHEN p.tipo_pago='membresia_cuota' AND p.estado='pagado'  THEN p.monto ELSE 0 END),0) as memb_pagado,
                COALESCE(SUM(CASE WHEN p.tipo_pago='membresia_cuota' AND p.estado!='pagado' THEN p.monto ELSE 0 END),0) as memb_deuda,
                COALESCE(COUNT(CASE WHEN p.tipo_pago='membresia_cuota' AND p.estado!='pagado' THEN 1 END),0) as memb_pendientes
            FROM clientes c
            LEFT JOIN pagos p ON c.id = p.cliente_id
            {$where_c}
            GROUP BY c.id
            ORDER BY c.etapa ASC, c.apellidos ASC, c.nombres ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    // Calcular total deuda por fila
    foreach ($filas as &$f) {
        $f['deuda_total'] = $f['mant_deuda'] + $f['insc_deuda'] + $f['memb_deuda'];
    }
    unset($f);

    // Filtrar solo con deuda si se pide
    if ($solo_deuda) {
        $filas = array_filter($filas, fn($f) => $f['deuda_total'] > 0);
    }

    $formato = $_GET['formato'] ?? 'html';

    if ($formato === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        $fname = 'reporte_deuda_consolidada' . ($etapa ? '_' . $etapa : '') . '_' . date('Y-m-d') . '.csv';
        header('Content-Disposition: attachment; filename="' . $fname . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Etapa','Propietario','DNI','Lote','Manzana','Teléfono',
            'Deuda Mant.','Cuotas Pend. Mant.',
            'Estado Inscripción','Deuda Inscripción',
            'Cuotas Pend. Membresía','Deuda Membresía',
            'DEUDA TOTAL'
        ], ';');

        foreach ($filas as $f) {
            fputcsv($output, [
                $f['cliente_etapa'] ?: '-',
                $f['nombres'] . ' ' . $f['apellidos'],
                $f['dni'],
                $f['numero_lote'],
                $f['manzana'] ?: '-',
                $f['telefono'] ?: '-',
                $f['mant_deuda'],
                $f['mant_pendientes'],
                $f['insc_estado'] ? ucfirst($f['insc_estado']) : 'Sin registro',
                $f['insc_deuda'],
                $f['memb_pendientes'],
                $f['memb_deuda'],
                $f['deuda_total'],
            ], ';');
        }
        fclose($output);
        exit;
    }

    // Totales para footer
    $total_mant = array_sum(array_column($filas, 'mant_deuda'));
    $total_insc = array_sum(array_column($filas, 'insc_deuda'));
    $total_memb = array_sum(array_column($filas, 'memb_deuda'));
    $gran_total = $total_mant + $total_insc + $total_memb;

    $titulo      = 'Deuda Consolidada';
    $subtitulo   = count($filas) . ' propietarios con deuda' . ($etapa ? ' — ' . $etapa : '');
    $pagina_actual = 'reportes';

    ob_start(); ?>

    <!-- Resumen de totales -->
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-info">
                <div class="label">Deuda Mantenimiento</div>
                <div class="value" style="font-size:1.1rem;"><?php echo formatearMoneda($total_mant); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-file-signature"></i></div>
            <div class="stat-info">
                <div class="label">Deuda Inscripción</div>
                <div class="value" style="font-size:1.1rem;"><?php echo formatearMoneda($total_insc); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-id-card"></i></div>
            <div class="stat-info">
                <div class="label">Deuda Membresía</div>
                <div class="value" style="font-size:1.1rem;"><?php echo formatearMoneda($total_memb); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-info">
                <div class="label">DEUDA TOTAL</div>
                <div class="value" style="font-size:1.2rem;color:var(--color-peligro);"><?php echo formatearMoneda($gran_total); ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-circle"></i> Deuda Consolidada por Propietario</h3>
            <div class="d-flex gap-1" style="flex-wrap:wrap;gap:.5rem;">
                <form method="GET" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="accion" value="deuda_consolidada">
                    <select name="etapa" class="form-control" style="width:auto;" onchange="this.form.submit()">
                        <option value="">Todas las etapas</option>
                        <?php foreach ($etapas as $et): ?>
                            <option value="<?php echo htmlspecialchars($et); ?>" <?php echo $etapa===$et?'selected':''; ?>>
                                <?php echo htmlspecialchars($et); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label style="display:flex;align-items:center;gap:.3rem;font-size:.9rem;cursor:pointer;">
                        <input type="checkbox" name="solo_deuda" value="0" <?php echo !$solo_deuda?'checked':''; ?> onchange="this.form.submit()">
                        Ver todos (incluir sin deuda)
                    </label>
                </form>
                <a href="?accion=deuda_consolidada&etapa=<?php echo urlencode($etapa); ?>&solo_deuda=<?php echo $solo_deuda?1:0; ?>&formato=excel"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
                <button onclick="window.print()" class="btn btn-outline btn-sm">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($filas)): ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Etapa</th>
                            <th>Propietario</th>
                            <th>Lote / Mz</th>
                            <th style="text-align:center;">Mantenimiento</th>
                            <th style="text-align:center;">Inscripción</th>
                            <th style="text-align:center;">Membresía</th>
                            <th style="text-align:right;">Deuda Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filas as $f): ?>
                        <tr <?php echo $f['deuda_total'] > 0 ? '' : 'style="opacity:.6"'; ?>>
                            <td><?php echo htmlspecialchars($f['cliente_etapa'] ?: '-'); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($f['nombres'] . ' ' . $f['apellidos']); ?></strong><br>
                                <small style="color:var(--color-texto-claro)"><?php echo $f['dni']; ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($f['numero_lote']); ?>
                                <?php echo $f['manzana'] ? ' / Mz ' . htmlspecialchars($f['manzana']) : ''; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($f['mant_deuda'] > 0): ?>
                                    <span style="color:var(--color-peligro);font-weight:600;"><?php echo formatearMoneda($f['mant_deuda']); ?></span><br>
                                    <small style="color:var(--color-texto-claro);"><?php echo $f['mant_pendientes']; ?> cuota(s)</small>
                                <?php else: ?>
                                    <span style="color:var(--color-exito)"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($f['insc_deuda'] > 0): ?>
                                    <span style="color:var(--color-peligro);font-weight:600;"><?php echo formatearMoneda($f['insc_deuda']); ?></span><br>
                                    <span class="badge badge-warning" style="font-size:.75rem;">Pendiente</span>
                                <?php elseif ($f['insc_estado'] === 'pagado'): ?>
                                    <span style="color:var(--color-exito)"><i class="fas fa-check"></i></span>
                                <?php else: ?>
                                    <span style="color:var(--color-texto-claro);font-size:.8rem;">Sin registro</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($f['memb_deuda'] > 0): ?>
                                    <span style="color:var(--color-peligro);font-weight:600;"><?php echo formatearMoneda($f['memb_deuda']); ?></span><br>
                                    <small style="color:var(--color-texto-claro);"><?php echo $f['memb_pendientes']; ?> cuota(s)</small>
                                <?php elseif ($f['memb_pagado'] > 0): ?>
                                    <span style="color:var(--color-exito)"><i class="fas fa-check"></i></span>
                                <?php else: ?>
                                    <span style="color:var(--color-texto-claro);font-size:.8rem;">Sin registro</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;font-weight:700;font-size:1rem;color:<?php echo $f['deuda_total']>0?'var(--color-peligro)':'var(--color-exito)'; ?>">
                                <?php echo formatearMoneda($f['deuda_total']); ?>
                            </td>
                            <td>
                                <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=ver&id=<?php echo $f['id']; ?>"
                                   class="btn btn-sm btn-outline" title="Ver perfil">
                                    <i class="fas fa-user"></i>
                                </a>
                                <?php if (!empty($f['telefono'])): ?>
                                <?php
                                $msg = "Hola *{$f['nombres']} {$f['apellidos']}*, te saludamos de Condominio Terrazas. "
                                     . "Tienes una deuda pendiente de *" . formatearMoneda($f['deuda_total']) . "* "
                                     . "entre mantenimiento, inscripción y/o membresía. "
                                     . "Por favor regulariza tu situación. ¡Gracias!";
                                $url_wa = "https://api.whatsapp.com/send?phone=51"
                                        . preg_replace('/[^0-9]/', '', $f['telefono'])
                                        . "&text=" . urlencode($msg);
                                ?>
                                <a href="<?php echo $url_wa; ?>" class="btn btn-sm btn-whatsapp"
                                   target="_blank" title="WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:700;background:var(--color-fondo);">
                            <td colspan="3">TOTALES</td>
                            <td style="text-align:center;color:var(--color-peligro);"><?php echo formatearMoneda($total_mant); ?></td>
                            <td style="text-align:center;color:var(--color-peligro);"><?php echo formatearMoneda($total_insc); ?></td>
                            <td style="text-align:center;color:var(--color-peligro);"><?php echo formatearMoneda($total_memb); ?></td>
                            <td style="text-align:right;color:var(--color-peligro);font-size:1.05rem;"><?php echo formatearMoneda($gran_total); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>¡Sin deudas!</h3>
                    <p>Todos los propietarios están al día<?php echo $etapa ? ' en ' . htmlspecialchars($etapa) : ''; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'));
}
