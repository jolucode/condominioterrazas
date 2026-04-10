<?php
/**
 * CONTROLADOR DE USUARIOS
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'listar';
$modelo_usuario = new Usuario();

switch ($accion) {
    case 'listar':
        listarUsuarios();
        break;
    case 'crear':
        crearUsuario();
        break;
    case 'editar':
        editarUsuario();
        break;
    case 'eliminar':
        eliminarUsuario();
        break;
    case 'cambiar_password':
        cambiarPassword();
        break;
    default:
        redirigir('controllers/usuario_controller.php?accion=listar');
        break;
}

function listarUsuarios() {
    global $modelo_usuario;
    
    $usuarios = $modelo_usuario->obtenerTodos('id DESC');
    
    $titulo = 'Gestión de Usuarios';
    $subtitulo = 'Administra los usuarios del sistema';
    $pagina_actual = 'usuarios';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Usuarios del Sistema</h3>
            <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=crear" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($usuarios)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo $usuario['nombre_completo']; ?></td>
                                    <td><?php echo $usuario['correo']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $usuario['rol'] === 'administrador' ? 'badge-info' : 'badge-secondary'; ?>">
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['estado'] === 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($usuario['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatearFecha($usuario['fecha_creacion'], 'd/m/Y H:i'); ?></td>
                                    <td class="actions">
                                        <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=editar&id=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=eliminar&id=<?php echo $usuario['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Eliminar"
                                               data-confirm-delete="¿Está seguro de eliminar este usuario?">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-shield"></i>
                    <h3>No hay usuarios registrados</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function crearUsuario() {
    global $modelo_usuario;
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre_completo = sanear($_POST['nombre_completo'] ?? '');
        $correo = sanear($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = sanear($_POST['rol'] ?? 'cliente');
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $estado = sanear($_POST['estado'] ?? 'activo');
        
        if (empty($nombre_completo)) $errores[] = 'El nombre es requerido';
        if (empty($correo)) $errores[] = 'El correo es requerido';
        elseif (!esEmailValido($correo)) $errores[] = 'El correo no es válido';
        elseif ($modelo_usuario->correoExiste($correo)) $errores[] = 'El correo ya está registrado';
        if (empty($password)) $errores[] = 'La contraseña es requerida';
        elseif (strlen($password) < PASSWORD_MIN_LENGTH) $errores[] = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
        
        if (empty($errores)) {
            $usuario_id = $modelo_usuario->crearUsuario([
                'nombre_completo' => $nombre_completo,
                'correo' => $correo,
                'password' => $password,
                'rol' => $rol,
                'cliente_id' => $cliente_id > 0 ? $cliente_id : null,
                'estado' => $estado
            ]);
            
            if ($usuario_id) {
                $db = Database::getInstance()->getConnection();
                registrarAuditoria($db, 'create', 'usuarios', $usuario_id, "Usuario creado: {$nombre_completo}");
                setFlashMessage('success', 'Usuario creado exitosamente');
                redirigir('controllers/usuario_controller.php?accion=listar');
            } else {
                $errores[] = 'Error al crear el usuario';
            }
        }
    }
    
    $titulo = 'Nuevo Usuario';
    $subtitulo = 'Crear usuario del sistema';
    $pagina_actual = 'usuarios';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Nuevo Usuario</h3>
            <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=listar" class="btn btn-outline btn-sm">
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
                    <label>Nombre Completo <span class="required">*</span></label>
                    <input type="text" name="nombre_completo" class="form-control" required
                           value="<?php echo isset($_POST['nombre_completo']) ? $_POST['nombre_completo'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico <span class="required">*</span></label>
                    <input type="email" name="correo" class="form-control" required
                           value="<?php echo isset($_POST['correo']) ? $_POST['correo'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                    <small style="color: var(--color-texto-claro);">Mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="rol" class="form-control">
                            <option value="cliente" <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'cliente') ? 'selected' : ''; ?>>Cliente</option>
                            <option value="administrador" <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="activo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=listar" class="btn btn-secondary">
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

function editarUsuario() {
    global $modelo_usuario;
    
    $id = intval($_GET['id'] ?? 0);
    $usuario = $modelo_usuario->obtenerPorId($id);
    
    if (!$usuario) {
        setFlashMessage('error', 'Usuario no encontrado');
        redirigir('controllers/usuario_controller.php?accion=listar');
    }
    
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre_completo = sanear($_POST['nombre_completo'] ?? '');
        $correo = sanear($_POST['correo'] ?? '');
        $rol = sanear($_POST['rol'] ?? 'cliente');
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $estado = sanear($_POST['estado'] ?? 'activo');
        
        if (empty($nombre_completo)) $errores[] = 'El nombre es requerido';
        if (empty($correo)) $errores[] = 'El correo es requerido';
        elseif (!esEmailValido($correo)) $errores[] = 'El correo no es válido';
        elseif ($modelo_usuario->correoExiste($correo, $id)) $errores[] = 'El correo ya está registrado';
        
        if (empty($errores)) {
            if ($modelo_usuario->actualizar($id, [
                'nombre_completo' => $nombre_completo,
                'correo' => $correo,
                'rol' => $rol,
                'cliente_id' => $cliente_id > 0 ? $cliente_id : null,
                'estado' => $estado
            ])) {
                setFlashMessage('success', 'Usuario actualizado exitosamente');
                redirigir('controllers/usuario_controller.php?accion=listar');
            } else {
                $errores[] = 'Error al actualizar el usuario';
            }
        }
    }
    
    $titulo = 'Editar Usuario';
    $subtitulo = 'Actualizar información del usuario';
    $pagina_actual = 'usuarios';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Usuario</h3>
            <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=listar" class="btn btn-outline btn-sm">
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
                    <label>Nombre Completo <span class="required">*</span></label>
                    <input type="text" name="nombre_completo" class="form-control" required
                           value="<?php echo $usuario['nombre_completo']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico <span class="required">*</span></label>
                    <input type="email" name="correo" class="form-control" required
                           value="<?php echo $usuario['correo']; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="rol" class="form-control">
                            <option value="cliente" <?php echo $usuario['rol'] === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                            <option value="administrador" <?php echo $usuario['rol'] === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="activo" <?php echo $usuario['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo $usuario['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Usuario
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=listar" class="btn btn-secondary">
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

function eliminarUsuario() {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id == $_SESSION['usuario_id']) {
        setFlashMessage('error', 'No puede eliminar su propio usuario');
        redirigir('controllers/usuario_controller.php?accion=listar');
    }
    
    if ($modelo_usuario->eliminar($id)) {
        setFlashMessage('success', 'Usuario eliminado exitosamente');
    } else {
        setFlashMessage('error', 'Error al eliminar el usuario');
    }
    
    redirigir('controllers/usuario_controller.php?accion=listar');
}

function cambiarPassword() {
    global $modelo_usuario;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password_nuevo = $_POST['password_nuevo'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($password_nuevo)) {
            setFlashMessage('error', 'La contraseña es requerida');
        } elseif (strlen($password_nuevo) < PASSWORD_MIN_LENGTH) {
            setFlashMessage('error', 'La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
        } elseif ($password_nuevo !== $password_confirm) {
            setFlashMessage('error', 'Las contraseñas no coinciden');
        } else {
            if ($modelo_usuario->cambiarPassword($id, $password_nuevo)) {
                setFlashMessage('success', 'Contraseña actualizada exitosamente');
            } else {
                setFlashMessage('error', 'Error al cambiar la contraseña');
            }
        }
    }
    
    redirigir('controllers/usuario_controller.php?accion=editar&id=' . $id);
}
