<?php
/**
 * AUTOLOADER
 * Carga automática de clases y configuración
 */

// Definir ruta raíz primero
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// 1. Cargar configuración primero (define constantes básicas)
require_once ROOT_PATH . '/config/config.php';

// 2. Cargar helpers antes de usar cualquier función
require_once ROOT_PATH . '/config/helpers.php';

// 3. Iniciar sesión (ahora la función ya está disponible)
iniciarSesion();

// 4. Cargar base de datos
require_once ROOT_PATH . '/config/database.php';

// Función de carga automática de modelos
function cargarModelo($nombre) {
    $archivo_modelo = ROOT_PATH . '/models/' . $nombre . '.php';
    if (file_exists($archivo_modelo)) {
        require_once $archivo_modelo;
    }
}

// Cargar todos los modelos disponibles
require_once ROOT_PATH . '/models/ModeloBase.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/models/Cliente.php';
require_once ROOT_PATH . '/models/Pago.php';
require_once ROOT_PATH . '/models/Comprobante.php';
require_once ROOT_PATH . '/models/Reunion.php';
require_once ROOT_PATH . '/models/ArchivoAdjunto.php';

// Función para incluir vistas
function vista($nombre, $datos = []) {
    // Extraer datos para la vista
    extract($datos);
    
    $archivo_vista = ROOT_PATH . '/views/' . $nombre . '.php';
    
    if (file_exists($archivo_vista)) {
        require $archivo_vista;
    } else {
        die("Vista no encontrada: {$nombre}");
    }
}

// Función para incluir partials
function partial($nombre, $datos = []) {
    extract($datos);
    
    $archivo_partial = ROOT_PATH . '/views/partials/' . $nombre . '.php';
    
    if (file_exists($archivo_partial)) {
        require $archivo_partial;
    }
}

// Verificar autenticación
function requireAuth() {
    if (!estaAutenticado()) {
        redirigir('login.php');
    }
}

// Verificar rol de administrador
function requireAdmin() {
    if (!esAdministrador()) {
        redirigir('index.php');
    }
}

// Verificar rol de cliente
function requireCliente() {
    if (!esCliente()) {
        redirigir('index.php');
    }
}

// Procesar acción del controlador
function ejecutarAccion($controlador, $accion, $parametros = []) {
    $archivo_controlador = ROOT_PATH . '/controllers/' . $controlador . '.php';
    
    if (file_exists($archivo_controlador)) {
        require_once $archivo_controlador;
        
        // El controlador debe tener una función con el nombre de la acción
        if (function_exists($accion)) {
            call_user_func_array($accion, $parametros);
        } else {
            die("Acción no encontrada: {$accion}");
        }
    } else {
        die("Controlador no encontrado: {$controlador}");
    }
}
