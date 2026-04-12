<?php
/**
 * CONFIGURACIÓN PRINCIPAL DEL SISTEMA
 * Condominio Terrazas - Sistema de Gestión
 */

// Evitar acceso directo
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Condominio Terrazas');
}

// Definir ROOT_PATH si no está definido (para acceso directo a este archivo)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// ============================================
// CONFIGURACIÓN HÍBRIDA (LOCAL / PRODUCCIÓN)
// ============================================
// Detectar automáticamente si estamos en Local o Nube
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false);

if ($is_local) {
    // LOCAL (LARAGON)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'condominio_terrazas');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('APP_URL', 'http://localhost/condominioterrazas');
    define('APP_ENV', 'development');
} else {
    // PRODUCCIÓN (INFINITYFREE / OTROS)
    define('DB_HOST', 'sql211.infinityfree.com');
    define('DB_NAME', 'if0_41640060_condterrazasdb'); 
    define('DB_USER', 'if0_41640060');
    define('DB_PASS', 'Fijoww1212');
    
    // Auto-detectar URL en producción para evitar errores de escritura
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    define('APP_URL', $protocol . $_SERVER['HTTP_HOST']); 
    define('APP_ENV', 'production');
}

define('DB_CHARSET', 'utf8mb4');
define('APP_VERSION', '1.0.1');

// ============================================
// RUTAS DEL SISTEMA
// ============================================
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('MODELS_PATH', ROOT_PATH . '/models');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// ============================================
// CONFIGURACIÓN DE SESIÓN
// ============================================
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'condominio_session');

// ============================================
// CONFIGURACIÓN DE PAGOS
// ============================================
define('CUOTA_MANTENIMIENTO', 70.00);
define('MONEDA', 'S/.');
define('MONEDA_ISO', 'PEN');

// ============================================
// CONFIGURACIÓN DE ARCHIVOS
// ============================================
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_DIR', UPLOADS_PATH . '/reuniones');
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx']);

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// ============================================
// CONFIGURACIÓN DE FECHA
// ============================================
date_default_timezone_set('America/Lima');
define('DEFAULT_LOCALE', 'es_PE');

// ============================================
// CONFIGURACIÓN DE ERRORES
// ============================================
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // En producción, silenciamos errores pero los registramos si es posible
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// CONFIGURACIÓN DE SUNAT (FACTURACIÓN ELECTRÓNICA)
// ============================================
define('SUNAT_API_URL', 'https://api.facturador.pe/v1'); // URL de ejemplo
define('SUNAT_API_KEY', ''); // Clave API del proveedor
define('SUNAT_RUC', ''); // RUC del condominio
define('SUNAT_USUARIO_SOL', ''); // Usuario SOL
define('SUNAT_CLAVE_SOL', ''); // Clave SOL
define('SUNAT_AMBIENTE', 'beta'); // beta | produccion
