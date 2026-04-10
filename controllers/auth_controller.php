<?php
/**
 * CONTROLADOR DE AUTENTICACIÓN
 */
require_once __DIR__ . '/../config/autoload.php';

$accion = $_GET['accion'] ?? 'login';

switch ($accion) {
    case 'cerrar':
        cerrarSesion();
        break;
    
    default:
        redirigir('login.php');
        break;
}

function cerrarSesion() {
    // Registrar auditoría
    if (estaAutenticado()) {
        $db = Database::getInstance()->getConnection();
        registrarAuditoria($db, 'logout', 'usuarios', $_SESSION['usuario_id'], 'Cierre de sesión');
    }
    
    // Destruir sesión
    session_unset();
    session_destroy();
    
    redirigir('login.php');
}
