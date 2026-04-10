<?php
/**
 * PÁGINA DE LOGIN
 */
require_once __DIR__ . '/config/autoload.php';

// Si ya está autenticado, redirigir al dashboard
if (estaAutenticado()) {
    if (esAdministrador()) {
        redirigir('index.php');
    } else {
        redirigir('controllers/cliente_panel.php?accion=inicio');
    }
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = sanear($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($correo) || empty($password)) {
        $error = 'Por favor ingrese su correo y contraseña.';
    } else {
        $modelo_usuario = new Usuario();
        $usuario = $modelo_usuario->autenticar($correo, $password);
        
        if ($usuario) {
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_correo'] = $usuario['correo'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['usuario_cliente_id'] = $usuario['cliente_id'];
            
            // Redirigir según rol
            if ($usuario['rol'] === 'administrador') {
                redirigir('index.php');
            } else {
                redirigir('controllers/cliente_panel.php?accion=inicio');
            }
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-building"></i>
                </div>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Sistema de Gestión Condominial</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <div class="form-group">
                    <label for="correo">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" id="correo" name="correo" class="form-control" 
                           placeholder="correo@ejemplo.com" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Ingrese su contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--color-texto-claro);">
                <p>¿Olvidó su contraseña? Contacte al administrador.</p>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-borde); text-align: center;">
                <p style="font-size: 0.8rem; color: var(--color-texto-claro);">
                    <strong>Credenciales de prueba:</strong><br>
                    Admin: admin@condominioterrazas.com / admin123
                </p>
            </div>
        </div>
    </div>
</body>
</html>
