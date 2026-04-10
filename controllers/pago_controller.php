<?php
/**
 * CONTROLADOR DE PAGOS
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'listar';
$modelo_pago = new Pago();

switch ($accion) {
    case 'listar':
        listarPagos();
        break;
    case 'registrar':
        registrarPago();
        break;
    case 'marcar_pagado':
        marcarPagado();
        break;
    case 'editar':
        editarPago();
        break;
    case 'eliminar':
        eliminarPago();
        break;
    case 'generar_pagos':
        generarPagosMensuales();
        break;
    default:
        redirigir('controllers/pago_controller.php?accion=listar');
        break;
}

function listarPagos() {
    global $modelo_pago;
    
    $pagina = intval($_GET['pagina'] ?? 1);
    $cliente_id = intval($_GET['cliente_id'] ?? 0);
    $estado = sanear($_GET['estado'] ?? '');
    $mes = intval($_GET['mes'] ?? 0);
    $anio = intval($_GET['anio'] ?? date('Y'));
    $busqueda = sanear($_GET['busqueda'] ?? '');
    
    $filtros = [];
    if ($cliente_id) $filtros['cliente_id'] = $cliente_id;
    if ($estado) $filtros['estado'] = $estado;
    if ($mes) $filtros['mes'] = $mes;
    if ($anio) $filtros['anio'] = $anio;
    if ($busqueda) $filtros['busqueda'] = $busqueda;
    
    // Actualizar pagos vencidos
    $modelo_pago->actualizarVencidos();
    
    $resultado = $modelo_pago->obtenerConFiltros($filtros, $pagina, 15);
    $pagos = $resultado['datos'];
    $paginacion = $resultado['paginacion'];
    
    // Obtener clientes para filtro
    $modelo_cliente = new Cliente();
    $clientes = $modelo_cliente->obtenerActivos();
    
    $titulo = 'Gestión de Pagos';
    $subtitulo = 'Administra los pagos de mantenimiento';
    $pagina_actual = 'pagos';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <h3>Listado de Pagos</h3>
                <span class="badge badge-info"><?php echo $paginacion['total_registros']; ?> registros</span>
            </div>
            <div class="d-flex gap-1">
                <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=registrar" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Pago
                </a>
                <button class="btn btn-outline btn-sm" data-modal="modal-generar-pagos">
                    <i class="fas fa-calendar-plus"></i> Generar Pagos Mensuales
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" class="filters-bar mb-3">
                <input type="hidden" name="accion" value="listar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o DNI..." 
                           value="<?php echo $busqueda; ?>">
                </div>
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                    <option value="pagado" <?php echo $estado === 'pagado' ? 'selected' : ''; ?>>Pagados</option>
                    <option value="vencido" <?php echo $estado === 'vencido' ? 'selected' : ''; ?>>Vencidos</option>
                </select>
                <select name="mes" class="form-control">
                    <option value="">Todos los meses</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $mes === $m ? 'selected' : ''; ?>><?php echo nombreMes($m); ?></option>
                    <?php endfor; ?>
                </select>
                <select name="anio" class="form-control">
                    <?php for ($a = date('Y'); $a >= date('Y') - 2; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php echo $anio === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-outline btn-sm">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
            
            <!-- Tabla -->
            <?php if (!empty($pagos)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Lote</th>
                                <th>Mes/Año</th>
                                <th>Monto</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Fecha Pago</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo $pago['id']; ?></td>
                                    <td><?php echo $pago['cliente_nombre']; ?></td>
                                    <td><?php echo $pago['numero_lote']; ?></td>
                                    <td><?php echo nombreMes($pago['mes']) . ' ' . $pago['anio']; ?></td>
                                    <td><?php echo formatearMoneda($pago['monto']); ?></td>
                                    <td><?php echo formatearFecha($pago['fecha_vencimiento']); ?></td>
                                    <td>
                                        <span class="badge <?php echo claseEstadoPago($pago['estado']); ?>">
                                            <?php echo textoEstadoPago($pago['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $pago['fecha_pago'] ? formatearFecha($pago['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                    <td class="actions">
                                        <?php if ($pago['estado'] !== 'pagado'): ?>
                                            <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=marcar_pagado&id=<?php echo $pago['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Marcar como pagado">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=editar&id=<?php echo $pago['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <?php if ($paginacion['total_paginas'] > 1): ?>
                    <div class="pagination">
                        <?php if ($paginacion['tiene_anterior']): ?>
                            <a href="?accion=listar&pagina=<?php echo $paginacion['pagina_actual'] - 1; ?>&estado=<?php echo urlencode($estado); ?>&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        <span class="active"><?php echo $paginacion['pagina_actual']; ?></span>
                        <?php if ($paginacion['tiene_siguiente']): ?>
                            <a href="?accion=listar&pagina=<?php echo $paginacion['pagina_actual'] + 1; ?>&estado=<?php echo urlencode($estado); ?>&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>&busqueda=<?php echo urlencode($busqueda); ?>">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>No se encontraron pagos</h3>
                    <p>Intenta con otros filtros o registra un nuevo pago</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal Generar Pagos -->
    <div id="modal-generar-pagos" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Generar Pagos Mensuales</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST" action="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=generar_pagos" data-confirm="¿Está seguro de generar los pagos para todos los clientes activos?">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mes <span class="required">*</span></label>
                            <select name="mes" class="form-control" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m === date('n') ? 'selected' : ''; ?>>
                                        <?php echo nombreMes($m); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Año <span class="required">*</span></label>
                            <select name="anio" class="form-control" required>
                                <?php for ($a = date('Y'); $a <= date('Y') + 1; $a++): ?>
                                    <option value="<?php echo $a; ?>" <?php echo $a === date('Y') ? 'selected' : ''; ?>><?php echo $a; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Monto (S/.)</label>
                        <input type="number" name="monto" class="form-control" step="0.01" value="<?php echo CUOTA_MANTENIMIENTO; ?>" required>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Se generarán pagos pendientes para todos los clientes activos que no tengan un pago registrado para este mes/año.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Generar Pagos
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function registrarPago() {
    global $modelo_pago;
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $mes = intval($_POST['mes'] ?? 0);
        $anio = intval($_POST['anio'] ?? date('Y'));
        $monto = floatval($_POST['monto'] ?? CUOTA_MANTENIMIENTO);
        $metodo_pago = sanear($_POST['metodo_pago'] ?? '');
        $observacion = sanear($_POST['observacion'] ?? '');
        $fecha_vencimiento = sanear($_POST['fecha_vencimiento'] ?? '');
        
        // Validaciones
        if (!$cliente_id) $errores[] = 'Seleccione un cliente';
        if (!$mes || $mes < 1 || $mes > 12) $errores[] = 'Seleccione un mes válido';
        if (!$anio) $errores[] = 'Seleccione un año válido';
        if ($monto <= 0) $errores[] = 'El monto debe ser mayor a 0';
        
        // Verificar si ya existe pago
        if ($cliente_id && $mes && $anio) {
            if ($modelo_pago->existePago($cliente_id, $mes, $anio)) {
                $errores[] = 'Ya existe un pago registrado para este cliente en ' . nombreMes($mes) . ' ' . $anio;
            }
        }
        
        if (empty($errores)) {
            $fecha_vencimiento = $fecha_vencimiento ?: date('Y-' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '-10');
            $estado = 'pendiente';
            
            $datos = [
                'cliente_id' => $cliente_id,
                'mes' => $mes,
                'anio' => $anio,
                'monto' => $monto,
                'fecha_vencimiento' => $fecha_vencimiento,
                'estado' => $estado,
                'metodo_pago' => $metodo_pago,
                'observacion' => $observacion,
                'registrado_por' => $_SESSION['usuario_id']
            ];
            
            $pago_id = $modelo_pago->registrarPago($datos);
            
            if ($pago_id) {
                $db = Database::getInstance()->getConnection();
                $cliente_model = new Cliente();
                $cliente = $cliente_model->obtenerPorId($cliente_id);
                registrarAuditoria($db, 'create', 'pagos', $pago_id, 
                    "Pago registrado: {$cliente['nombres']} {$cliente['apellidos']} - " . nombreMes($mes) . " {$anio}");
                setFlashMessage('success', 'Pago registrado exitosamente');
                redirigir('controllers/pago_controller.php?accion=listar');
            } else {
                $errores[] = 'Error al registrar el pago';
            }
        }
    }
    
    // Obtener clientes
    $modelo_cliente = new Cliente();
    $clientes = $modelo_cliente->obtenerActivos();
    
    $titulo = 'Registrar Pago';
    $subtitulo = 'Registrar nuevo pago de mantenimiento';
    $pagina_actual = 'pagos';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Registrar Pago</h3>
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
            
            <form method="POST" data-validate>
                <div class="form-group">
                    <label>Cliente <span class="required">*</span></label>
                    <select name="cliente_id" class="form-control" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?php echo $cli['id']; ?>" <?php echo (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cli['id']) ? 'selected' : ''; ?>>
                                <?php echo $cli['nombres'] . ' ' . $cli['apellidos'] . ' - Lote ' . $cli['numero_lote']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Mes <span class="required">*</span></label>
                        <select name="mes" class="form-control" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo (isset($_POST['mes']) && $_POST['mes'] == $m) ? 'selected' : ($m === date('n') ? 'selected' : ''); ?>>
                                    <?php echo nombreMes($m); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Año <span class="required">*</span></label>
                        <select name="anio" class="form-control" required>
                            <?php for ($a = date('Y'); $a >= date('Y') - 2; $a--): ?>
                                <option value="<?php echo $a; ?>" <?php echo (isset($_POST['anio']) && $_POST['anio'] == $a) ? 'selected' : ($a === date('Y') ? 'selected' : ''); ?>>
                                    <?php echo $a; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Monto (S/.)</label>
                        <input type="number" name="monto" class="form-control" step="0.01" min="0" 
                               value="<?php echo isset($_POST['monto']) ? $_POST['monto'] : CUOTA_MANTENIMIENTO; ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" class="form-control" 
                               value="<?php echo isset($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Método de Pago</label>
                    <select name="metodo_pago" class="form-control">
                        <option value="">Seleccione método</option>
                        <option value="efectivo" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="transferencia" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                        <option value="yape" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'yape') ? 'selected' : ''; ?>>Yape</option>
                        <option value="plin" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'plin') ? 'selected' : ''; ?>>Plin</option>
                        <option value="deposito" <?php echo (isset($_POST['metodo_pago']) && $_POST['metodo_pago'] === 'deposito') ? 'selected' : ''; ?>>Depósito</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="3"><?php echo isset($_POST['observacion']) ? $_POST['observacion'] : ''; ?></textarea>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Pago
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

function marcarPagado() {
    global $modelo_pago;
    
    $id = intval($_GET['id'] ?? 0);
    $pago = $modelo_pago->obtenerPorId($id);
    
    if (!$pago) {
        setFlashMessage('error', 'Pago no encontrado');
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    if ($pago['estado'] === 'pagado') {
        setFlashMessage('warning', 'El pago ya está marcado como pagado');
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    // Si es POST, procesar
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $metodo_pago = sanear($_POST['metodo_pago'] ?? '');
        $observacion = sanear($_POST['observacion'] ?? '');
        
        if (empty($metodo_pago)) {
            setFlashMessage('error', 'Seleccione un método de pago');
        } else {
            if ($modelo_pago->marcarComoPagado($id, $metodo_pago, $observacion, $_SESSION['usuario_id'])) {
                $db = Database::getInstance()->getConnection();
                $cliente_model = new Cliente();
                $cliente = $cliente_model->obtenerPorId($pago['cliente_id']);
                registrarAuditoria($db, 'update', 'pagos', $id, 
                    "Pago marcado como pagado: {$cliente['nombres']} {$cliente['apellidos']} - " . nombreMes($pago['mes']) . " {$pago['anio']}");
                setFlashMessage('success', 'Pago marcado como pagado exitosamente');
                
                // Preguntar si desea emitir comprobante
                redirigir('controllers/comprobante_controller.php?accion=emitir&pago_id=' . $id);
            } else {
                setFlashMessage('error', 'Error al marcar el pago como pagado');
            }
        }
    }
    
    $titulo = 'Marcar Pago como Pagado';
    $subtitulo = 'Confirmar recepción del pago';
    $pagina_actual = 'pagos';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Marcar Pago como Pagado</h3>
            <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Cliente:</strong> <?php 
                $cliente_model = new Cliente();
                $cliente = $cliente_model->obtenerPorId($pago['cliente_id']);
                echo $cliente['nombres'] . ' ' . $cliente['apellidos']; ?><br>
                <strong>Concepto:</strong> Mantenimiento <?php echo nombreMes($pago['mes']) . ' ' . $pago['anio']; ?><br>
                <strong>Monto:</strong> <?php echo formatearMoneda($pago['monto']); ?>
            </div>
            
            <form method="POST" data-validate>
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
                    <textarea name="observacion" class="form-control" rows="3" placeholder="Número de operación, referencia, etc."></textarea>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmar Pago
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

function editarPago() {
    global $modelo_pago;
    
    $id = intval($_GET['id'] ?? 0);
    $pago = $modelo_pago->obtenerPorId($id);
    
    if (!$pago) {
        setFlashMessage('error', 'Pago no encontrado');
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $monto = floatval($_POST['monto'] ?? 0);
        $metodo_pago = sanear($_POST['metodo_pago'] ?? '');
        $observacion = sanear($_POST['observacion'] ?? '');
        $fecha_vencimiento = sanear($_POST['fecha_vencimiento'] ?? '');
        $estado = sanear($_POST['estado'] ?? 'pendiente');
        
        if ($monto <= 0) {
            setFlashMessage('error', 'El monto debe ser mayor a 0');
        } else {
            $datos = [
                'monto' => $monto,
                'metodo_pago' => $metodo_pago,
                'observacion' => $observacion,
                'fecha_vencimiento' => $fecha_vencimiento,
                'estado' => $estado
            ];
            
            if ($modelo_pago->actualizar($id, $datos)) {
                setFlashMessage('success', 'Pago actualizado exitosamente');
                redirigir('controllers/pago_controller.php?accion=listar');
            } else {
                setFlashMessage('error', 'Error al actualizar el pago');
            }
        }
    }
    
    $titulo = 'Editar Pago';
    $subtitulo = 'Actualizar información del pago';
    $pagina_actual = 'pagos';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Pago</h3>
            <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label>Monto (S/.)</label>
                        <input type="number" name="monto" class="form-control" step="0.01" min="0" 
                               value="<?php echo $pago['monto']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Vencimiento</label>
                        <input type="date" name="fecha_vencimiento" class="form-control" 
                               value="<?php echo $pago['fecha_vencimiento']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="pendiente" <?php echo $pago['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="pagado" <?php echo $pago['estado'] === 'pagado' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="vencido" <?php echo $pago['estado'] === 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Método de Pago</label>
                    <select name="metodo_pago" class="form-control">
                        <option value="">Seleccione método</option>
                        <option value="efectivo" <?php echo $pago['metodo_pago'] === 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                        <option value="transferencia" <?php echo $pago['metodo_pago'] === 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                        <option value="yape" <?php echo $pago['metodo_pago'] === 'yape' ? 'selected' : ''; ?>>Yape</option>
                        <option value="plin" <?php echo $pago['metodo_pago'] === 'plin' ? 'selected' : ''; ?>>Plin</option>
                        <option value="deposito" <?php echo $pago['metodo_pago'] === 'deposito' ? 'selected' : ''; ?>>Depósito</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="3"><?php echo $pago['observacion'] ?: ''; ?></textarea>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Pago
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

function eliminarPago() {
    $id = intval($_GET['id'] ?? 0);
    
    if ($modelo_pago->eliminar($id)) {
        $db = Database::getInstance()->getConnection();
        registrarAuditoria($db, 'delete', 'pagos', $id, "Pago eliminado");
        setFlashMessage('success', 'Pago eliminado exitosamente');
    } else {
        setFlashMessage('error', 'Error al eliminar el pago');
    }
    
    redirigir('controllers/pago_controller.php?accion=listar');
}

function generarPagosMensuales() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    $mes = intval($_POST['mes'] ?? 0);
    $anio = intval($_POST['anio'] ?? date('Y'));
    $monto = floatval($_POST['monto'] ?? CUOTA_MANTENIMIENTO);
    
    if ($mes < 1 || $mes > 12) {
        setFlashMessage('error', 'Mes inválido');
        redirigir('controllers/pago_controller.php?accion=listar');
    }
    
    $modelo_cliente = new Cliente();
    $creados = $modelo_cliente->generarPagosMensuales($mes, $anio, $monto);
    
    if ($creados > 0) {
        setFlashMessage('success', "Se generaron {$creados} pagos para " . nombreMes($mes) . " {$anio}");
    } else {
        setFlashMessage('info', 'No se generaron pagos. Es posible que ya existan para este mes.');
    }
    
    redirigir('controllers/pago_controller.php?accion=listar');
}
