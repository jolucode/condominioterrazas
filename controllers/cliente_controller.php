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
    case 'listar_ajax':
        listarClientesAjax();
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
        $filtro = "(nombres LIKE :busq1 OR apellidos LIKE :busq2 OR dni LIKE :busq3 OR numero_lote LIKE :busq4)";
        $params[':busq1'] = "%{$busqueda}%";
        $params[':busq2'] = "%{$busqueda}%";
        $params[':busq3'] = "%{$busqueda}%";
        $params[':busq4'] = "%{$busqueda}%";
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
            <div class="filters-bar mb-3">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="busqueda-input" class="form-control" placeholder="Buscar por nombre, DNI o lote... (búsqueda en vivo)"
                           value="<?php echo isset($_GET['busqueda']) ? $_GET['busqueda'] : ''; ?>">
                </div>
                <select id="estado-select" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="activo" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'activo') ? 'selected' : ''; ?>>Activos</option>
                    <option value="inactivo" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivos</option>
                </select>
            </div>
            
            <input type="hidden" id="current-page" value="1">
            <input type="hidden" id="base-url" value="<?php echo APP_URL; ?>">

            <!-- Tabla -->
            <div id="clientes-table-container">
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
    </div>
    
    <script>
    (function() {
        const busquedaInput = document.getElementById('busqueda-input');
        const estadoSelect = document.getElementById('estado-select');
        const currentPageInput = document.getElementById('current-page');
        const container = document.getElementById('clientes-table-container');
        const baseUrl = document.getElementById('base-url').value;
        let searchTimeout;
        
        function cargarClientes(pagina = 1) {
            const busqueda = encodeURIComponent(busquedaInput.value);
            const estado = encodeURIComponent(estadoSelect.value);
            
            const url = `${baseUrl}/controllers/cliente_controller.php?accion=listar_ajax&pagina=${pagina}&busqueda=${busqueda}&estado=${estado}`;
            
            container.innerHTML = '<div style="text-align:center;padding:2rem;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    currentPageInput.value = pagina;
                })
                .catch(error => {
                    container.innerHTML = '<div class="alert alert-danger">Error al cargar los datos</div>';
                });
        }
        
        // Live search con debounce
        busquedaInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                cargarClientes(1);
            }, 400);
        });
        
        // Filtrar por estado
        estadoSelect.addEventListener('change', function() {
            cargarClientes(1);
        });
        
        // Paginación AJAX
        container.addEventListener('click', function(e) {
            const paginationLink = e.target.closest('.pagination a');
            if (paginationLink) {
                e.preventDefault();
                const url = new URL(paginationLink.href);
                const pagina = url.searchParams.get('pagina');
                if (pagina) {
                    cargarClientes(parseInt(pagina));
                }
            }
        });
    })();
    </script>

    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function listarClientesAjax() {
    global $modelo_cliente;
    
    $pagina = intval($_GET['pagina'] ?? 1);
    $busqueda = sanear($_GET['busqueda'] ?? '');
    $estado = sanear($_GET['estado'] ?? '');
    
    $filtro = '';
    $params = [];
    
    if ($busqueda) {
        $filtro = "(nombres LIKE :busq1 OR apellidos LIKE :busq2 OR dni LIKE :busq3 OR numero_lote LIKE :busq4)";
        $params[':busq1'] = "%{$busqueda}%";
        $params[':busq2'] = "%{$busqueda}%";
        $params[':busq3'] = "%{$busqueda}%";
        $params[':busq4'] = "%{$busqueda}%";
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
    
    ob_start();
    
    if (!empty($clientes)): ?>
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
                                   data-confirm-delete="¿Está seguro de eliminar este cliente?">
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
                    <a href="?pagina=<?php echo $paginacion['pagina_actual'] - 1; ?>">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>
                
                <span class="active"><?php echo $paginacion['pagina_actual']; ?></span>
                
                <?php if ($paginacion['tiene_siguiente']): ?>
                    <a href="?pagina=<?php echo $paginacion['pagina_actual'] + 1; ?>">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <h3>No se encontraron clientes</h3>
            <p>Intenta con otros filtros</p>
        </div>
    <?php endif;
    
    echo ob_get_clean();
    exit;
}

function crearCliente() {
    global $modelo_cliente;
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/cliente_controller.php?accion=crear');
        }
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
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
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
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/cliente_controller.php?accion=editar&id=' . $id);
        }
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
                <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">
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

    $id      = intval($_GET['id'] ?? 0);
    $cliente = $modelo_cliente->obtenerConResumen($id);

    if (!$cliente) {
        setFlashMessage('error', 'Cliente no encontrado');
        redirigir('controllers/cliente_controller.php?accion=listar');
    }

    $modelo_pago = new Pago();

    // Datos de los 3 tipos
    $pagos_mant  = $modelo_pago->obtenerPorCliente($id, null, 'mantenimiento');
    $resumen     = $modelo_pago->resumenPorCliente($id);
    $mant        = $resumen['mantenimiento'];
    $insc        = $resumen['inscripcion'];
    $memb        = $resumen['membresia_cuota'];

    // Inscripción: buscar el registro único
    $db          = Database::getInstance()->getConnection();
    $stmt        = $db->prepare("SELECT * FROM pagos WHERE cliente_id=:cid AND tipo_pago='inscripcion' LIMIT 1");
    $stmt->execute([':cid' => $id]);
    $inscripcion = $stmt->fetch();

    // Membresía: cuotas
    $stmt_m = $db->prepare("SELECT * FROM pagos WHERE cliente_id=:cid AND tipo_pago='membresia_cuota' ORDER BY cuota_numero ASC");
    $stmt_m->execute([':cid' => $id]);
    $cuotas_memb = $stmt_m->fetchAll();

    $deuda_total = $mant['total_deuda'] + $insc['total_deuda'] + $memb['total_deuda'];

    $titulo        = 'Detalle del Cliente';
    $subtitulo     = $cliente['nombres'] . ' ' . $cliente['apellidos'];
    $pagina_actual = 'clientes';

    ob_start(); ?>

    <!-- Fila 1: Info personal + Propiedad -->
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
                    <li><span class="label">Nombres y Apellidos</span>
                        <span class="value"><?php echo $cliente['nombres'] . ' ' . $cliente['apellidos']; ?></span></li>
                    <li><span class="label">DNI</span>
                        <span class="value"><?php echo $cliente['dni']; ?></span></li>
                    <li><span class="label">RUC</span>
                        <span class="value"><?php echo $cliente['ruc'] ?: '-'; ?></span></li>
                    <li><span class="label">Teléfono</span>
                        <span class="value"><?php echo $cliente['telefono'] ?: '-'; ?></span></li>
                    <li><span class="label">Correo</span>
                        <span class="value"><?php echo $cliente['correo'] ?: '-'; ?></span></li>
                    <li><span class="label">Dirección</span>
                        <span class="value"><?php echo $cliente['direccion'] ?: '-'; ?></span></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-home"></i> Propiedad</h3>
            </div>
            <div class="card-body">
                <ul class="detail-list">
                    <li><span class="label">Número de Lote</span>
                        <span class="value"><?php echo $cliente['numero_lote']; ?></span></li>
                    <li><span class="label">Manzana</span>
                        <span class="value"><?php echo $cliente['manzana'] ?: '-'; ?></span></li>
                    <li><span class="label">Etapa</span>
                        <span class="value"><?php echo $cliente['etapa'] ?: '-'; ?></span></li>
                    <li><span class="label">Estado</span>
                        <span class="value">
                            <span class="badge <?php echo $cliente['estado'] === 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo ucfirst($cliente['estado']); ?>
                            </span>
                        </span></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Fila 2: Resumen consolidado de los 3 tipos -->
    <div class="card mt-3">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Resumen Financiero</h3>
            <?php if ($deuda_total > 0): ?>
                <span class="badge badge-danger" style="font-size:.9rem; padding:.4rem .8rem;">
                    Deuda total: <?php echo formatearMoneda($deuda_total); ?>
                </span>
            <?php else: ?>
                <span class="badge badge-success" style="font-size:.9rem; padding:.4rem .8rem;">
                    Al día
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Registros</th>
                            <th>Pagados</th>
                            <th>Pendientes</th>
                            <th>Total Pagado</th>
                            <th>Deuda</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Mantenimiento -->
                        <tr>
                            <td><i class="fas fa-money-bill-wave" style="color:var(--color-primario)"></i> <strong>Mantenimiento Mensual</strong></td>
                            <td><?php echo $mant['total']; ?></td>
                            <td><span class="badge badge-success"><?php echo $mant['pagados']; ?></span></td>
                            <td><span class="badge <?php echo ($mant['pendientes']+$mant['vencidos'])>0 ? 'badge-warning' : 'badge-secondary'; ?>"><?php echo $mant['pendientes'] + $mant['vencidos']; ?></span></td>
                            <td><?php echo formatearMoneda($mant['total_pagado']); ?></td>
                            <td style="color:<?php echo $mant['total_deuda']>0 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>; font-weight:600;">
                                <?php echo formatearMoneda($mant['total_deuda']); ?>
                            </td>
                            <td>
                                <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar&cliente_id=<?php echo $id; ?>" class="btn btn-sm btn-outline">Ver</a>
                            </td>
                        </tr>
                        <!-- Inscripción -->
                        <tr>
                            <td><i class="fas fa-file-signature" style="color:var(--color-info)"></i> <strong>Inscripción</strong></td>
                            <td><?php echo $insc['total'] ?: '—'; ?></td>
                            <td><?php echo $insc['total'] ? '<span class="badge badge-' . ($insc['pagados']>0 ? 'success' : 'secondary') . '">' . $insc['pagados'] . '</span>' : '—'; ?></td>
                            <td><?php echo $insc['total'] ? '<span class="badge badge-' . (($insc['pendientes']+$insc['vencidos'])>0 ? 'warning' : 'secondary') . '">' . ($insc['pendientes']+$insc['vencidos']) . '</span>' : '—'; ?></td>
                            <td><?php echo formatearMoneda($insc['total_pagado']); ?></td>
                            <td style="color:<?php echo $insc['total_deuda']>0 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>; font-weight:600;">
                                <?php echo $insc['total'] ? formatearMoneda($insc['total_deuda']) : '—'; ?>
                            </td>
                            <td>
                                <?php if ($insc['total']): ?>
                                    <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=listar" class="btn btn-sm btn-outline">Ver</a>
                                <?php else: ?>
                                    <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=registrar" class="btn btn-sm btn-primary">Registrar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Membresía -->
                        <tr>
                            <td><i class="fas fa-id-card" style="color:var(--color-advertencia)"></i> <strong>Membresía Club</strong></td>
                            <td><?php echo $memb['total'] ? $memb['pagados'].'/'.$memb['total'].' cuotas' : '—'; ?></td>
                            <td><?php echo $memb['total'] ? '<span class="badge badge-success">'.$memb['pagados'].'</span>' : '—'; ?></td>
                            <td><?php echo $memb['total'] ? '<span class="badge badge-'.( ($memb['pendientes']+$memb['vencidos'])>0 ? 'warning' : 'secondary').'">' . ($memb['pendientes']+$memb['vencidos']) . '</span>' : '—'; ?></td>
                            <td><?php echo formatearMoneda($memb['total_pagado']); ?></td>
                            <td style="color:<?php echo $memb['total_deuda']>0 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>; font-weight:600;">
                                <?php echo $memb['total'] ? formatearMoneda($memb['total_deuda']) : '—'; ?>
                            </td>
                            <td>
                                <?php if ($memb['total']): ?>
                                    <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=ver&cliente_id=<?php echo $id; ?>" class="btn btn-sm btn-outline">Ver cuotas</a>
                                <?php else: ?>
                                    <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=registrar" class="btn btn-sm btn-primary">Registrar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fila 3: Historial con tabs -->
    <div class="card mt-3">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Historial de Pagos</h3>
        </div>
        <div class="card-body">

            <!-- Tabs -->
            <div style="display:flex;gap:4px;border-bottom:2px solid var(--color-borde);margin-bottom:1.25rem;">
                <button class="tab-btn-cli active" data-tab="tab-mant"
                        style="padding:.5rem 1.2rem;border:none;background:none;cursor:pointer;color:var(--color-texto-claro);border-bottom:2px solid transparent;margin-bottom:-2px;font-size:.95rem;">
                    <i class="fas fa-money-bill-wave"></i> Mantenimiento
                    <span class="badge badge-info" style="margin-left:4px;"><?php echo count($pagos_mant); ?></span>
                </button>
                <button class="tab-btn-cli" data-tab="tab-insc"
                        style="padding:.5rem 1.2rem;border:none;background:none;cursor:pointer;color:var(--color-texto-claro);border-bottom:2px solid transparent;margin-bottom:-2px;font-size:.95rem;">
                    <i class="fas fa-file-signature"></i> Inscripción
                    <?php if ($inscripcion): ?>
                        <span class="badge <?php echo claseEstadoPago($inscripcion['estado']); ?>" style="margin-left:4px;"><?php echo textoEstadoPago($inscripcion['estado']); ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn-cli" data-tab="tab-memb"
                        style="padding:.5rem 1.2rem;border:none;background:none;cursor:pointer;color:var(--color-texto-claro);border-bottom:2px solid transparent;margin-bottom:-2px;font-size:.95rem;">
                    <i class="fas fa-id-card"></i> Membresía
                    <?php if ($memb['total']): ?>
                        <span class="badge badge-info" style="margin-left:4px;"><?php echo $memb['pagados'].'/'.$memb['total']; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Tab Mantenimiento -->
            <div id="tab-mant" class="tab-content-cli">
                <?php if (!empty($pagos_mant)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Mes/Año</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th></tr></thead>
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
                    <div class="empty-state"><i class="fas fa-money-bill-wave"></i><h3>Sin pagos de mantenimiento</h3></div>
                <?php endif; ?>
            </div>

            <!-- Tab Inscripción -->
            <div id="tab-insc" class="tab-content-cli" style="display:none;">
                <?php if ($inscripcion): ?>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Concepto</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th><th>Acciones</th></tr></thead>
                        <tbody>
                            <tr>
                                <td>Inscripción / Empadronamiento</td>
                                <td><?php echo formatearMoneda($inscripcion['monto']); ?></td>
                                <td><?php echo formatearFecha($inscripcion['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($inscripcion['estado']); ?>"><?php echo textoEstadoPago($inscripcion['estado']); ?></span></td>
                                <td><?php echo $inscripcion['fecha_pago'] ? formatearFecha($inscripcion['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                <td><?php echo $inscripcion['metodo_pago'] ? nombreMetodoPago($inscripcion['metodo_pago']) : '-'; ?></td>
                                <td>
                                    <?php if ($inscripcion['estado'] !== 'pagado'): ?>
                                        <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=marcar_pagado&id=<?php echo $inscripcion['id']; ?>"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Pagar
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--color-exito)"><i class="fas fa-check-circle"></i> Pagada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-signature"></i>
                        <h3>Sin inscripción registrada</h3>
                        <a href="<?php echo APP_URL; ?>/controllers/inscripcion_controller.php?accion=registrar" class="btn btn-primary mt-2">
                            <i class="fas fa-plus"></i> Registrar Inscripción
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Membresía -->
            <div id="tab-memb" class="tab-content-cli" style="display:none;">
                <?php if (!empty($cuotas_memb)): ?>
                <?php
                $plan_total   = $cuotas_memb[0]['total_cuotas'];
                $c_pagadas    = count(array_filter($cuotas_memb, fn($c) => $c['estado'] === 'pagado'));
                $pct_memb     = $plan_total > 0 ? round(($c_pagadas / $plan_total) * 100) : 0;
                ?>
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:6px;">
                        <span><?php echo $c_pagadas; ?> / <?php echo $plan_total; ?> cuotas pagadas</span>
                        <strong><?php echo $pct_memb; ?>%</strong>
                    </div>
                    <div style="background:var(--color-borde);border-radius:6px;height:10px;overflow:hidden;">
                        <div style="width:<?php echo $pct_memb; ?>%;background:var(--color-primario);height:100%;border-radius:6px;"></div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Cuota</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th>Fecha Pago</th><th>Método</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($cuotas_memb as $c): ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo $c['cuota_numero']; ?>/<?php echo $c['total_cuotas']; ?></span></td>
                                <td><?php echo formatearMoneda($c['monto']); ?></td>
                                <td><?php echo formatearFecha($c['fecha_vencimiento']); ?></td>
                                <td><span class="badge <?php echo claseEstadoPago($c['estado']); ?>"><?php echo textoEstadoPago($c['estado']); ?></span></td>
                                <td><?php echo $c['fecha_pago'] ? formatearFecha($c['fecha_pago'], 'd/m/Y H:i') : '-'; ?></td>
                                <td><?php echo $c['metodo_pago'] ? nombreMetodoPago($c['metodo_pago']) : '-'; ?></td>
                                <td>
                                    <?php if ($c['estado'] !== 'pagado'): ?>
                                        <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=marcar_cuota&id=<?php echo $c['id']; ?>"
                                           class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i> Pagar
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--color-exito)"><i class="fas fa-check-circle"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-id-card"></i>
                        <h3>Sin membresía registrada</h3>
                        <a href="<?php echo APP_URL; ?>/controllers/membresia_controller.php?accion=registrar" class="btn btn-primary mt-2">
                            <i class="fas fa-plus"></i> Registrar Membresía
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="mt-3">
        <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
    </div>

    <script>
    (function() {
        const btns = document.querySelectorAll('.tab-btn-cli');
        btns.forEach(btn => {
            btn.addEventListener('click', () => {
                btns.forEach(b => {
                    b.classList.remove('active');
                    b.style.color            = 'var(--color-texto-claro)';
                    b.style.borderBottomColor = 'transparent';
                    b.style.fontWeight       = 'normal';
                });
                document.querySelectorAll('.tab-content-cli').forEach(c => c.style.display = 'none');
                btn.classList.add('active');
                btn.style.color            = 'var(--color-primario)';
                btn.style.borderBottomColor = 'var(--color-primario)';
                btn.style.fontWeight       = '600';
                document.getElementById(btn.dataset.tab).style.display = 'block';
            });
        });
        // Activar el primero al cargar
        if (btns.length) btns[0].click();
    })();
    </script>

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
