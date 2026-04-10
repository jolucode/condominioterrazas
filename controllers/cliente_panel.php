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
    $modelo_pago = new Pago();
    $modelo_cliente = new Cliente();
    $cliente = $modelo_cliente->obtenerPorId($cliente_id);
    
    $anio = intval($_GET['anio'] ?? date('Y'));
    $pagos = $modelo_pago->obtenerPorCliente($cliente_id, $anio);
    
    $titulo = 'Mis Pagos';
    $subtitulo = 'Historial de pagos de mantenimiento';
    $pagina_actual = 'mis_pagos';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Mis Pagos</h3>
            <form method="GET" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="accion" value="mis_pagos">
                <select name="anio" class="form-control" onchange="this.form.submit()">
                    <?php for ($a = date('Y'); $a >= date('Y') - 2; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php echo $a === $anio ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <div class="card-body">
            <?php if (!empty($pagos)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Monto</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Fecha Pago</th>
                                <th>Método</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo nombreMes($pago['mes']) . ' ' . $pago['anio']; ?></td>
                                    <td><?php echo formatearMoneda($pago['monto']); ?></td>
                                    <td><?php echo formatearFecha($pago['fecha_vencimiento']); ?></td>
                                    <td>
                                        <span class="badge <?php echo claseEstadoPago($pago['estado']); ?>">
                                            <?php echo textoEstadoPago($pago['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $pago['fecha_pago'] ? formatearFecha($pago['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                    <td><?php echo $pago['metodo_pago'] ? nombreMetodoPago($pago['metodo_pago']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>No hay pagos registrados</h3>
                    <p>No se encontraron pagos para el año seleccionado</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
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
    $modelo_reunion = new Reunion();
    $reuniones = $modelo_reunion->obtenerPublicadas();
    
    $titulo = 'Reuniones y Acuerdos';
    $subtitulo = 'Historial de reuniones del condominio';
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
                        $acuerdos = $modelo_acuerdo->obtenerPorReunion($reunion['id']);
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4><?php echo $reunion['titulo']; ?></h4>
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
