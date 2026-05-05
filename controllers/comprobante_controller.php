<?php
/**
 * CONTROLADOR DE COMPROBANTES (FACTURACIÓN ELECTRÓNICA - SUNAT READY)
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'listar';
$modelo_comprobante = new Comprobante();

switch ($accion) {
    case 'listar':
        listarComprobantes();
        break;
    case 'emitir':
        emitirComprobante();
        break;
    case 'ver':
        verComprobante();
        break;
    case 'anular':
        anularComprobante();
        break;
    case 'imprimir':
        imprimirComprobante();
        break;
    default:
        redirigir('controllers/comprobante_controller.php?accion=listar');
        break;
}

function listarComprobantes() {
    global $modelo_comprobante;
    
    $filtros = [];
    $tipo = sanear($_GET['tipo'] ?? '');
    $estado = sanear($_GET['estado'] ?? '');
    
    if ($tipo) $filtros['tipo_comprobante'] = $tipo;
    if ($estado) $filtros['estado_emision'] = $estado;
    
    $comprobantes = $modelo_comprobante->listarConFiltros($filtros);
    $stats = $modelo_comprobante->estadisticas();
    
    $titulo = 'Comprobantes Electrónicos';
    $subtitulo = 'Gestión de facturación electrónica';
    $pagina_actual = 'comprobantes';
    
    ob_start();
    ?>
    
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-file-invoice"></i></div>
            <div class="stat-info">
                <div class="label">Total Comprobantes</div>
                <div class="value"><?php echo $stats['total']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check"></i></div>
            <div class="stat-info">
                <div class="label">Emitidos</div>
                <div class="value"><?php echo $stats['emitidos']; ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <div class="label">Pendientes</div>
                <div class="value"><?php echo $stats['pendientes']; ?></div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Listado de Comprobantes</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($comprobantes)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Serie-NNúmero</th>
                                <th>Cliente</th>
                                <th>DNI/RUC</th>
                                <th>Monto</th>
                                <th>Fecha Emisión</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comprobantes as $comp): ?>
                                <tr>
                                    <td><?php echo nombreTipoComprobante($comp['tipo_comprobante']); ?></td>
                                    <td><strong><?php echo $comp['serie'] . '-' . $comp['numero']; ?></strong></td>
                                    <td><?php echo $comp['cliente_nombre']; ?></td>
                                    <td><?php echo $comp['cliente_dni']; ?></td>
                                    <td><?php echo formatearMoneda($comp['monto']); ?></td>
                                    <td><?php echo formatearFecha($comp['fecha_emision'], 'd/m/Y H:i'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $comp['estado_emision'] === 'emitido' ? 'badge-success' : ($comp['estado_emision'] === 'anulado' ? 'badge-danger' : 'badge-warning'); ?>">
                                            <?php echo ucfirst($comp['estado_emision']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="<?php echo APP_URL; ?>/controllers/comprobante_controller.php?accion=ver&id=<?php echo $comp['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/controllers/comprobante_controller.php?accion=imprimir&id=<?php echo $comp['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Imprimir" target="_blank">
                                            <i class="fas fa-print"></i>
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
                    <h3>No hay comprobantes registrados</h3>
                    <p>Los comprobantes se generarán al marcar pagos como pagados</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function emitirComprobante() {
    global $modelo_comprobante;
    $pago_id = intval($_GET['pago_id'] ?? 0);
    
    $modelo_pago = new Pago();
    $pago = $modelo_pago->obtenerPorId($pago_id);
    
    if (!$pago) {
        setFlashMessage('error', 'Pago no encontrado');
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    // Verificar si ya tiene comprobante
    $comprobante_existente = $modelo_comprobante->obtenerPorPago($pago_id);
    if ($comprobante_existente) {
        setFlashMessage('warning', 'Este pago ya tiene un comprobante emitido');
        redirigir('controllers/comprobante_controller.php?accion=ver&id=' . $comprobante_existente['id']);
    }
    
    // Si el pago no está pagado, redirigir
    if ($pago['estado'] !== 'pagado') {
        setFlashMessage('warning', 'El pago debe estar marcado como pagado para emitir comprobante');
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/comprobante_controller.php?accion=emitir&pago_id=' . $pago_id);
        }
        $tipo_comprobante = sanear($_POST['tipo_comprobante'] ?? '');

        if (empty($tipo_comprobante)) {
            $errores[] = 'Seleccione un tipo de comprobante';
        }
        
        if (empty($errores)) {
            $modelo_cliente = new Cliente();
            $cliente        = $modelo_cliente->obtenerPorId($pago['cliente_id']);
            $dni_ruc        = $cliente['ruc'] ?: $cliente['dni'];

            // Concepto según tipo de pago
            if ($pago['tipo_pago'] === 'inscripcion') {
                $concepto = 'Inscripción / Empadronamiento';
            } elseif ($pago['tipo_pago'] === 'membresia_cuota') {
                $concepto = 'Membresía Perpetua Club — Cuota ' . $pago['cuota_numero'] . ' de ' . $pago['total_cuotas'];
            } else {
                $concepto = 'Mantenimiento mensual - ' . nombreMes($pago['mes']) . ' ' . $pago['anio'];
            }

            // Datos del comprobante — la numeración se reserva atómicamente dentro de crearComprobante()
            $datos_comprobante = [
                'pago_id'          => $pago_id,
                'tipo_comprobante' => $tipo_comprobante,
                'cliente_id'       => $pago['cliente_id'],
                'dni_ruc'          => $dni_ruc,
                'concepto'         => $concepto,
                'monto'            => $pago['monto'],
                'fecha_emision'    => date('Y-m-d H:i:s'),
                'estado_emision'   => 'emitido',
                'sunat_hash'       => bin2hex(random_bytes(16)),
            ];

            $comprobante_id = $modelo_comprobante->crearComprobante($datos_comprobante);

            if ($comprobante_id) {
                $db         = Database::getInstance()->getConnection();
                $comprobante_nuevo = $modelo_comprobante->obtenerPorId($comprobante_id);
                $num_ref    = ($comprobante_nuevo['serie'] ?? '') . '-' . ($comprobante_nuevo['numero'] ?? '');
                registrarAuditoria($db, 'create', 'comprobantes', $comprobante_id,
                    "Comprobante emitido: {$tipo_comprobante} {$num_ref}");
                setFlashMessage('success', 'Comprobante emitido exitosamente');
                redirigir('controllers/comprobante_controller.php?accion=ver&id=' . $comprobante_id);
            } else {
                $errores[] = 'Error al emitir el comprobante';
            }
        }
    }
    
    $modelo_cliente = new Cliente();
    $cliente = $modelo_cliente->obtenerPorId($pago['cliente_id']);
    
    $titulo = 'Emitir Comprobante';
    $subtitulo = 'Generar boleta o factura electrónica';
    $pagina_actual = 'comprobantes';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Emitir Comprobante Electrónico</h3>
            <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i>
                <strong>Pago de:</strong> <?php echo $cliente['nombres'] . ' ' . $cliente['apellidos']; ?><br>
                <strong>Concepto:</strong> Mantenimiento <?php echo nombreMes($pago['mes']) . ' ' . $pago['anio']; ?><br>
                <strong>Monto:</strong> <?php echo formatearMoneda($pago['monto']); ?>
            </div>
            
            <form method="POST" data-validate>
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
                <div class="form-group">
                    <label>Tipo de Comprobante <span class="required">*</span></label>
                    <select name="tipo_comprobante" class="form-control" required>
                        <option value="">Seleccione tipo</option>
                        <option value="boleta">Boleta de Venta</option>
                        <option value="factura">Factura</option>
                    </select>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Nota:</strong> Este comprobante será registrado y preparado para envío a SUNAT.
                    <?php if (empty(SUNAT_API_KEY)): ?>
                        <br>Actualmente en modo simulación. Configure las credenciales de SUNAT en <code>config/config.php</code> para habilitar la facturación real.
                    <?php endif; ?>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-invoice"></i> Emitir Comprobante
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function verComprobante() {
    global $modelo_comprobante;
    $id = intval($_GET['id'] ?? 0);
    $comprobante = $modelo_comprobante->obtenerParaImpresion($id);
    
    if (!$comprobante) {
        setFlashMessage('error', 'Comprobante no encontrado');
        redirigir('controllers/comprobante_controller.php?accion=listar');
    }
    
    $titulo = 'Comprobante ' . $comprobante['serie'] . '-' . $comprobante['numero'];
    $subtitulo = nombreTipoComprobante($comprobante['tipo_comprobante']);
    $pagina_actual = 'comprobantes';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3><?php echo nombreTipoComprobante($comprobante['tipo_comprobante']); ?></h3>
            <div class="d-flex gap-1">
                <a href="<?php echo APP_URL; ?>/controllers/comprobante_controller.php?accion=imprimir&id=<?php echo $comprobante['id']; ?>" 
                   class="btn btn-outline btn-sm" target="_blank">
                    <i class="fas fa-print"></i> Imprimir
                </a>
                <?php if ($comprobante['estado_emision'] === 'emitido'): ?>
                    <a href="<?php echo APP_URL; ?>/controllers/comprobante_controller.php?accion=anular&id=<?php echo $comprobante['id']; ?>" 
                       class="btn btn-danger btn-sm"
                       data-confirm-delete="¿Está seguro de anular este comprobante?">
                        <i class="fas fa-ban"></i> Anular
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div style="border: 2px solid var(--color-borde); padding: 2rem; border-radius: var(--radio); max-width: 600px; margin: 0 auto;">
                <!-- Encabezado -->
                <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px dashed var(--color-borde);">
                    <h2 style="margin: 0;"><?php echo getConfig('razon_social', Database::getInstance()->getConnection()) ?: APP_NAME; ?></h2>
                    <?php $ruc_condo = getConfig('ruc_condominio', Database::getInstance()->getConnection()); ?>
                    <?php if ($ruc_condo): ?>
                        <p style="margin: 0.5rem 0;">RUC: <?php echo $ruc_condo; ?></p>
                    <?php endif; ?>
                    <h3 style="margin: 1rem 0 0.5rem; color: var(--color-primario);">
                        <?php echo strtoupper(nombreTipoComprobante($comprobante['tipo_comprobante'])); ?>
                    </h3>
                    <p style="font-size: 1.25rem; font-weight: bold; margin: 0;">
                        <?php echo $comprobante['serie'] . '-' . $comprobante['numero']; ?>
                    </p>
                </div>
                
                <!-- Datos del cliente -->
                <div style="margin-bottom: 1.5rem;">
                    <strong>Datos del Cliente:</strong><br>
                    Nombre: <?php echo $comprobante['cliente_nombre']; ?><br>
                    <?php echo strlen($comprobante['dni_ruc']) == 11 ? 'RUC' : 'DNI'; ?>: <?php echo $comprobante['dni_ruc']; ?>
                </div>
                
                <!-- Detalle -->
                <div style="margin-bottom: 1.5rem;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--color-fondo);">
                                <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid var(--color-borde);">Concepto</th>
                                <th style="padding: 0.5rem; text-align: right; border-bottom: 1px solid var(--color-borde);">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 0.5rem; border-bottom: 1px solid var(--color-borde);">
                                    <?php echo $comprobante['concepto']; ?>
                                </td>
                                <td style="padding: 0.5rem; text-align: right; border-bottom: 1px solid var(--color-borde);">
                                    <?php echo formatearMoneda($comprobante['monto']); ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0.5rem; text-align: right;"><strong>Total:</strong></td>
                                <td style="padding: 0.5rem; text-align: right;"><strong><?php echo formatearMoneda($comprobante['monto']); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Fecha y estado -->
                <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px dashed var(--color-borde); font-size: 0.85rem; color: var(--color-texto-claro);">
                    Fecha de Emisión: <?php echo formatearFecha($comprobante['fecha_emision'], 'd/m/Y H:i:s'); ?><br>
                    Estado: <span class="badge <?php echo $comprobante['estado_emision'] === 'emitido' ? 'badge-success' : 'badge-danger'; ?>">
                        <?php echo ucfirst($comprobante['estado_emision']); ?>
                    </span>
                    <?php if ($comprobante['sunat_hash']): ?>
                        <br>Hash SUNAT: <code style="font-size: 0.75rem;"><?php echo $comprobante['sunat_hash']; ?></code>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function anularComprobante() {
    global $modelo_comprobante;
    $id = intval($_GET['id'] ?? 0);
    
    if ($modelo_comprobante->anularComprobante($id)) {
        setFlashMessage('success', 'Comprobante anulado exitosamente');
        
        // ============================================
        // AQUÍ SE CONECTARÍA CON SUNAT PARA ANULAR
        // ============================================
        // $sunat = new SunatAPI();
        // $sunat->anularComprobante($id);
        
    } else {
        setFlashMessage('error', 'Error al anular el comprobante');
    }
    
    redirigir('controllers/comprobante_controller.php?accion=listar');
}

function imprimirComprobante() {
    global $modelo_comprobante;
    $id = intval($_GET['id'] ?? 0);
    $comprobante = $modelo_comprobante->obtenerParaImpresion($id);
    
    if (!$comprobante) {
        die('Comprobante no encontrado');
    }
    
    // Vista de impresión
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Comprobante <?php echo $comprobante['serie'] . '-' . $comprobante['numero']; ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
            .comprobante { max-width: 600px; margin: 0 auto; border: 2px solid #333; padding: 20px; }
            .header { text-align: center; border-bottom: 1px dashed #999; padding-bottom: 15px; margin-bottom: 15px; }
            .header h2 { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background: #f5f5f5; }
            .total { text-align: right; font-size: 14px; font-weight: bold; }
            .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; border-top: 1px dashed #999; padding-top: 10px; }
            @media print { body { margin: 0; } }
        </style>
    </head>
    <body onload="window.print()">
        <div class="comprobante">
            <div class="header">
                <h2><?php echo getConfig('razon_social', Database::getInstance()->getConnection()) ?: APP_NAME; ?></h2>
                <?php $ruc_condo = getConfig('ruc_condominio', Database::getInstance()->getConnection()); ?>
                <?php if ($ruc_condo): ?><p>RUC: <?php echo $ruc_condo; ?></p><?php endif; ?>
                <h3><?php echo strtoupper(nombreTipoComprobante($comprobante['tipo_comprobante'])); ?></h3>
                <p style="font-size: 16px; font-weight: bold;"><?php echo $comprobante['serie'] . '-' . $comprobante['numero']; ?></p>
            </div>
            
            <p><strong>Cliente:</strong> <?php echo $comprobante['cliente_nombre']; ?></p>
            <p><strong><?php echo strlen($comprobante['dni_ruc']) == 11 ? 'RUC' : 'DNI'; ?>:</strong> <?php echo $comprobante['dni_ruc']; ?></p>
            
            <table>
                <tr><th>Concepto</th><th style="text-align: right;">Monto</th></tr>
                <tr>
                    <td><?php echo $comprobante['concepto']; ?></td>
                    <td style="text-align: right;"><?php echo formatearMoneda($comprobante['monto']); ?></td>
                </tr>
            </table>
            
            <p class="total">TOTAL: <?php echo formatearMoneda($comprobante['monto']); ?></p>
            
            <div class="footer">
                Fecha de Emisión: <?php echo formatearFecha($comprobante['fecha_emision'], 'd/m/Y H:i:s'); ?><br>
                Estado: <?php echo strtoupper($comprobante['estado_emision']); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
