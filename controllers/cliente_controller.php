<?php
/**
 * CONTROLADOR DE CLIENTES
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'listar';
$modelo_cliente = new Cliente();

switch ($accion) {
    case 'listar':
        listarClientes();
        break;
    case 'crear':
        crearCliente();
        break;
    case 'editar':
        editarCliente();
        break;
    case 'eliminar':
        eliminarCliente();
        break;
    case 'ver':
        verCliente();
        break;
    default:
        redirigir('controllers/cliente_controller.php?accion=listar');
        break;
}

function listarClientes() {
    global $modelo_cliente;
    
    $pagina = intval($_GET['pagina'] ?? 1);
    $busqueda = sanear($_GET['busqueda'] ?? '');
    $estado = sanear($_GET['estado'] ?? '');
    
    $filtro = '';
    $params = [];
    
    if ($busqueda) {
        $filtro = "(nombres LIKE :busqueda OR apellidos LIKE :busqueda OR dni LIKE :busqueda OR numero_lote LIKE :busqueda)";
        $params[':busqueda'] = "%{$busqueda}%";
    }
    
    if ($estado) {
        if ($filtro) {
            $filtro .= " AND estado = :estado";
        } else {
            $filtro = "estado = :estado";
        }
        $params[':estado'] = $estado;
    }
    
    $resultado = $modelo_cliente->listarPaginado($pagina, 15, $filtro, $params);
    $clientes = $resultado['datos'];
    $paginacion = $resultado['paginacion'];
    
    $titulo = 'Gestión de Clientes';
    $subtitulo = 'Administra los propietarios del condominio';
    $pagina_actual = 'clientes';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <h3>Listado de Clientes</h3>
                <span class="badge badge-info"><?php echo $paginacion['total_registros']; ?> registros</span>
            </div>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=crear" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuevo Cliente
            </a>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" class="filters-bar mb-3">
                <input type="hidden" name="accion" value="listar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, DNI o lote..." 
                           value="<?php echo isset($_GET['busqueda']) ? $_GET['busqueda'] : ''; ?>">
                </div>
                <select name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'activo') ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivos</option>
                </select>
                <button type="submit" class="btn btn-outline btn-sm">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </form>
            
            <!-- Tabla -->
            <?php if (!empty($clientes)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombres y Apellidos</th>
                                <th>DNI</th>
                                <th>Teléfono</th>
                                <th>Lote</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
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
                                    <td>
                                        <span class="badge <?php echo $cliente['estado'] === 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($cliente['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=ver&id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-outline btn-sm" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=editar&id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-outline btn-sm" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=eliminar&id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           data-confirm-delete="¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.">
                                            <i class="fas fa-trash"></i>
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
                            <a href="?accion=listar&pagina=<?php echo $paginacion['pagina_actual'] - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo urlencode($estado); ?>">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <span class="active"><?php echo $paginacion['pagina_actual']; ?></span>
                        
                        <?php if ($paginacion['tiene_siguiente']): ?>
                            <a href="?accion=listar&pagina=<?php echo $paginacion['pagina_actual'] + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo urlencode($estado); ?>">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No se encontraron clientes</h3>
                    <p>Intenta con otros filtros o registra un nuevo cliente</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function crearCliente() {
    global $modelo_cliente;
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombres = sanear($_POST['nombres'] ?? '');
        $apellidos = sanear($_POST['apellidos'] ?? '');
        $dni = sanear($_POST['dni'] ?? '');
        $ruc = sanear($_POST['ruc'] ?? '');
        $telefono = sanear($_POST['telefono'] ?? '');
        $correo = sanear($_POST['correo'] ?? '');
        $direccion = sanear($_POST['direccion'] ?? '');
        $numero_lote = sanear($_POST['numero_lote'] ?? '');
        $manzana = sanear($_POST['manzana'] ?? '');
        $etapa = sanear($_POST['etapa'] ?? '');
        $estado = sanear($_POST['estado'] ?? 'activo');
        $crear_usuario = isset($_POST['crear_usuario']);
        $password_usuario = $_POST['password_usuario'] ?? '';
        
        // Validaciones
        if (empty($nombres)) $errores[] = 'El nombre es requerido';
        if (empty($apellidos)) $errores[] = 'El apellido es requerido';
        if (empty($dni)) $errores[] = 'El DNI es requerido';
        elseif (!esDNIVálido($dni)) $errores[] = 'El DNI debe tener 8 dígitos';
        elseif ($modelo_cliente->dniExiste($dni)) $errores[] = 'El DNI ya está registrado';
        if (empty($numero_lote)) $errores[] = 'El número de lote es requerido';
        if ($correo && !esEmailValido($correo)) $errores[] = 'El correo no es válido';
        
        if ($crear_usuario) {
            if (empty($correo)) $errores[] = 'El correo es requerido para crear usuario';
            if (empty($password_usuario)) $errores[] = 'La contraseña es requerida para crear usuario';
            elseif (strlen($password_usuario) < PASSWORD_MIN_LENGTH) $errores[] = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
        }
        
        if (empty($errores)) {
            $db = Database::getInstance()->getConnection();
            
            try {
                $db->beginTransaction();
                
                // Insertar cliente
                $datos_cliente = [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'dni' => $dni,
                    'ruc' => $ruc ?: null,
                    'telefono' => $telefono,
                    'correo' => $correo ?: null,
                    'direccion' => $direccion,
                    'numero_lote' => $numero_lote,
                    'manzana' => $manzana,
                    'etapa' => $etapa,
                    'estado' => $estado
                ];
                
                $cliente_id = $modelo_cliente->insertar($datos_cliente);
                
                // Crear usuario si se solicita
                if ($crear_usuario && $correo) {
                    $modelo_usuario = new Usuario();
                    $modelo_usuario->crearUsuario([
                        'nombre_completo' => $nombres . ' ' . $apellidos,
                        'correo' => $correo,
                        'password' => $password_usuario,
                        'rol' => 'cliente',
                        'cliente_id' => $cliente_id
                    ]);
                }
                
                $db->commit();
                
                registrarAuditoria($db, 'create', 'clientes', $cliente_id, "Cliente {$nombres} {$apellidos} creado");
                setFlashMessage('success', 'Cliente registrado exitosamente');
                redirigir('controllers/cliente_controller.php?accion=listar');
                
            } catch (Exception $e) {
                $db->rollBack();
                $errores[] = 'Error al registrar el cliente: ' . $e->getMessage();
            }
        }
    }
    
    $titulo = 'Nuevo Cliente';
    $subtitulo = 'Registrar un nuevo propietario';
    $pagina_actual = 'clientes';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Registro de Cliente</h3>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="btn btn-outline btn-sm">
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
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombres <span class="required">*</span></label>
                        <input type="text" name="nombres" class="form-control" required 
                               value="<?php echo isset($_POST['nombres']) ? $_POST['nombres'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Apellidos <span class="required">*</span></label>
                        <input type="text" name="apellidos" class="form-control" required
                               value="<?php echo isset($_POST['apellidos']) ? $_POST['apellidos'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>DNI <span class="required">*</span></label>
                        <input type="text" name="dni" class="form-control" required maxlength="8" data-type="dni"
                               value="<?php echo isset($_POST['dni']) ? $_POST['dni'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>RUC</label>
                        <input type="text" name="ruc" class="form-control" maxlength="11" data-type="ruc"
                               value="<?php echo isset($_POST['ruc']) ? $_POST['ruc'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="<?php echo isset($_POST['telefono']) ? $_POST['telefono'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" class="form-control"
                               value="<?php echo isset($_POST['correo']) ? $_POST['correo'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Número de Lote <span class="required">*</span></label>
                        <input type="text" name="numero_lote" class="form-control" required
                               value="<?php echo isset($_POST['numero_lote']) ? $_POST['numero_lote'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Manzana</label>
                        <input type="text" name="manzana" class="form-control"
                               value="<?php echo isset($_POST['manzana']) ? $_POST['manzana'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Etapa</label>
                        <input type="text" name="etapa" class="form-control"
                               value="<?php echo isset($_POST['etapa']) ? $_POST['etapa'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="<?php echo isset($_POST['direccion']) ? $_POST['direccion'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="activo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                
                <div class="form-group" style="padding: 1rem; background: var(--color-fondo); border-radius: var(--radio);">
                    <div class="form-check">
                        <input type="checkbox" name="crear_usuario" id="crear_usuario" 
                               <?php echo isset($_POST['crear_usuario']) ? 'checked' : ''; ?>>
                        <label for="crear_usuario" style="margin: 0; font-weight: 500;">
                            Crear usuario para acceso al sistema
                        </label>
                    </div>
                </div>
                
                <div id="usuario-fields" style="<?php echo !isset($_POST['crear_usuario']) ? 'display: none;' : ''; ?>">
                    <div class="form-group">
                        <label>Contraseña para el usuario</label>
                        <input type="password" name="password_usuario" class="form-control" minlength="6"
                               placeholder="Mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres">
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cliente
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('crear_usuario').addEventListener('change', function() {
            document.getElementById('usuario-fields').style.display = this.checked ? 'block' : 'none';
        });
    </script>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function editarCliente() {
    global $modelo_cliente;
    
    $id = intval($_GET['id'] ?? 0);
    $cliente = $modelo_cliente->obtenerPorId($id);
    
    if (!$cliente) {
        setFlashMessage('error', 'Cliente no encontrado');
        redirigir('controllers/cliente_controller.php?accion=listar');
    }
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombres = sanear($_POST['nombres'] ?? '');
        $apellidos = sanear($_POST['apellidos'] ?? '');
        $dni = sanear($_POST['dni'] ?? '');
        $ruc = sanear($_POST['ruc'] ?? '');
        $telefono = sanear($_POST['telefono'] ?? '');
        $correo = sanear($_POST['correo'] ?? '');
        $direccion = sanear($_POST['direccion'] ?? '');
        $numero_lote = sanear($_POST['numero_lote'] ?? '');
        $manzana = sanear($_POST['manzana'] ?? '');
        $etapa = sanear($_POST['etapa'] ?? '');
        $estado = sanear($_POST['estado'] ?? 'activo');
        
        // Validaciones
        if (empty($nombres)) $errores[] = 'El nombre es requerido';
        if (empty($apellidos)) $errores[] = 'El apellido es requerido';
        if (empty($dni)) $errores[] = 'El DNI es requerido';
        elseif (!esDNIVálido($dni)) $errores[] = 'El DNI debe tener 8 dígitos';
        elseif ($modelo_cliente->dniExiste($dni, $id)) $errores[] = 'El DNI ya está registrado';
        if (empty($numero_lote)) $errores[] = 'El número de lote es requerido';
        if ($correo && !esEmailValido($correo)) $errores[] = 'El correo no es válido';
        
        if (empty($errores)) {
            $datos = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'dni' => $dni,
                'ruc' => $ruc ?: null,
                'telefono' => $telefono,
                'correo' => $correo ?: null,
                'direccion' => $direccion,
                'numero_lote' => $numero_lote,
                'manzana' => $manzana,
                'etapa' => $etapa,
                'estado' => $estado
            ];
            
            if ($modelo_cliente->actualizar($id, $datos)) {
                $db = Database::getInstance()->getConnection();
                registrarAuditoria($db, 'update', 'clientes', $id, "Cliente {$nombres} {$apellidos} actualizado");
                setFlashMessage('success', 'Cliente actualizado exitosamente');
                redirigir('controllers/cliente_controller.php?accion=listar');
            } else {
                $errores[] = 'Error al actualizar el cliente';
            }
        }
    }
    
    $titulo = 'Editar Cliente';
    $subtitulo = 'Actualizar información del propietario';
    $pagina_actual = 'clientes';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Cliente</h3>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="btn btn-outline btn-sm">
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
                <div class="form-row">
                    <div class="form-group">
                        <label>Nombres <span class="required">*</span></label>
                        <input type="text" name="nombres" class="form-control" required 
                               value="<?php echo $cliente['nombres']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Apellidos <span class="required">*</span></label>
                        <input type="text" name="apellidos" class="form-control" required
                               value="<?php echo $cliente['apellidos']; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>DNI <span class="required">*</span></label>
                        <input type="text" name="dni" class="form-control" required maxlength="8" data-type="dni"
                               value="<?php echo $cliente['dni']; ?>">
                    </div>
                    <div class="form-group">
                        <label>RUC</label>
                        <input type="text" name="ruc" class="form-control" maxlength="11" data-type="ruc"
                               value="<?php echo $cliente['ruc'] ?: ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="<?php echo $cliente['telefono'] ?: ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" class="form-control"
                               value="<?php echo $cliente['correo'] ?: ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Número de Lote <span class="required">*</span></label>
                        <input type="text" name="numero_lote" class="form-control" required
                               value="<?php echo $cliente['numero_lote']; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Manzana</label>
                        <input type="text" name="manzana" class="form-control"
                               value="<?php echo $cliente['manzana'] ?: ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Etapa</label>
                        <input type="text" name="etapa" class="form-control"
                               value="<?php echo $cliente['etapa'] ?: ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="<?php echo $cliente['direccion'] ?: ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="activo" <?php echo $cliente['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $cliente['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Cliente
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="btn btn-secondary">
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

function verCliente() {
    global $modelo_cliente;
    
    $id = intval($_GET['id'] ?? 0);
    $cliente = $modelo_cliente->obtenerConResumen($id);
    
    if (!$cliente) {
        setFlashMessage('error', 'Cliente no encontrado');
        redirigir('controllers/cliente_controller.php?accion=listar');
    }
    
    // Obtener pagos del cliente
    $modelo_pago = new Pago();
    $pagos = $modelo_pago->obtenerPorCliente($id);
    
    $titulo = 'Detalle del Cliente';
    $subtitulo = $cliente['nombres'] . ' ' . $cliente['apellidos'];
    $pagina_actual = 'clientes';
    
    ob_start();
    ?>
    
    <div class="grid grid-2">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Información Personal</h3>
                <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=editar&id=<?php echo $cliente['id']; ?>" 
                   class="btn btn-outline btn-sm">
                    <i class="fas fa-edit"></i> Editar
                </a>
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
                    <li>
                        <span class="label">Dirección</span>
                        <span class="value"><?php echo $cliente['direccion'] ?: '-'; ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-home"></i> Propiedad</h3>
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
                        <span class="label">Estado</span>
                        <span class="value">
                            <span class="badge <?php echo $cliente['estado'] === 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($cliente['estado']); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Resumen de Pagos</h3>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Total Registros</div>
                        <div class="value"><?php echo $cliente['total_pagos']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pagados</div>
                        <div class="value"><?php echo $cliente['pagos_realizados']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Pendientes</div>
                        <div class="value"><?php echo $cliente['pagos_pendientes']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="stat-info">
                        <div class="label">Vencidos</div>
                        <div class="value"><?php echo $cliente['pagos_vencidos']; ?></div>
                    </div>
                </div>
            </div>
            
            <p style="text-align: center; font-size: 1.1rem; margin-top: 1rem;">
                <strong>Total Pagado:</strong> <?php echo formatearMoneda($cliente['total_pagado']); ?>
            </p>
        </div>
    </div>
    
    <?php if (!empty($pagos)): ?>
    <div class="card mt-3">
        <div class="card-header">
            <h3><i class="fas fa-receipt"></i> Historial de Pagos</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mes/Año</th>
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
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mt-3">
        <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function eliminarCliente() {
    global $modelo_cliente;
    
    $id = intval($_GET['id'] ?? 0);
    $cliente = $modelo_cliente->obtenerPorId($id);
    
    if (!$cliente) {
        setFlashMessage('error', 'Cliente no encontrado');
        redirigir('controllers/cliente_controller.php?accion=listar');
    }
    
    if ($modelo_cliente->eliminar($id)) {
        $db = Database::getInstance()->getConnection();
        registrarAuditoria($db, 'delete', 'clientes', $id, "Cliente {$cliente['nombres']} {$cliente['apellidos']} eliminado");
        setFlashMessage('success', 'Cliente eliminado exitosamente');
    } else {
        setFlashMessage('error', 'Error al eliminar el cliente');
    }
    
    redirigir('controllers/cliente_controller.php?accion=listar');
}
