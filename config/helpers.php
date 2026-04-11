<?php
/**
 * FUNCIONES AUXILIARES Y UTILIDADES
 */

// Iniciar sesión si no está iniciada
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

// Verificar si el usuario está autenticado
function estaAutenticado() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol']);
}

// Verificar si es administrador
function esAdministrador() {
    return estaAutenticado() && $_SESSION['usuario_rol'] === 'administrador';
}

// Verificar si es cliente
function esCliente() {
    return estaAutenticado() && $_SESSION['usuario_rol'] === 'cliente';
}

// Redirigir a otra página
function redirigir($url) {
    header("Location: " . APP_URL . "/" . $url);
    exit;
}

// Sanitizar datos de entrada
function sanear($dato) {
    if (is_array($dato)) {
        return array_map('sanear', $dato);
    }
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}

// Escapar para output en HTML
function e($dato) {
    echo htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
}

// Generar token CSRF
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Formatear fecha para mostrar
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (empty($fecha)) return '';
    $date = new DateTime($fecha);
    return $date->format($formato);
}

// Formatear moneda
function formatearMoneda($monto) {
    return MONEDA . ' ' . number_format((float)($monto ?? 0), 2);
}

// Obtener nombre del mes
function nombreMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Setiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[intval($mes)] ?? '';
}

// Obtener estado del pago con clase CSS
function claseEstadoPago($estado) {
    $clases = [
        'pendiente' => 'badge-warning',
        'pagado' => 'badge-success',
        'vencido' => 'badge-danger'
    ];
    return $clases[$estado] ?? 'badge-secondary';
}

// Obtener texto del estado del pago
function textoEstadoPago($estado) {
    $textos = [
        'pendiente' => 'Pendiente',
        'pagado' => 'Pagado',
        'vencido' => 'Vencido'
    ];
    return $textos[$estado] ?? $estado;
}

// Obtener nombre del método de pago
function nombreMetodoPago($metodo) {
    $metodos = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'yape' => 'Yape',
        'plin' => 'Plin',
        'deposito' => 'Depósito'
    ];
    return $metodos[$metodo] ?? $metodo;
}

// Obtener nombre del tipo de comprobante
function nombreTipoComprobante($tipo) {
    $tipos = [
        'boleta' => 'Boleta de Venta',
        'factura' => 'Factura'
    ];
    return $tipos[$tipo] ?? $tipo;
}

// Obtener estado de la reunión
function claseEstadoReunion($estado) {
    $clases = [
        'borrador' => 'badge-secondary',
        'publicado' => 'badge-success',
        'finalizado' => 'badge-info'
    ];
    return $clases[$estado] ?? 'badge-secondary';
}

function textoEstadoReunion($estado) {
    $textos = [
        'borrador' => 'Borrador',
        'publicado' => 'Publicado',
        'finalizado' => 'Finalizado'
    ];
    return $textos[$estado] ?? $estado;
}

// Registrar actividad en auditoría
function registrarAuditoria($db, $accion, $tabla = null, $registro_id = null, $descripcion = null) {
    try {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $sql = "INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, descripcion, ip_address) 
                VALUES (:usuario_id, :accion, :tabla, :registro_id, :descripcion, :ip)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':accion' => $accion,
            ':tabla' => $tabla,
            ':registro_id' => $registro_id,
            ':descripcion' => $descripcion,
            ':ip' => $ip
        ]);
    } catch (Exception $e) {
        // No interrumpir el flujo si falla la auditoría
        error_log("Error en auditoría: " . $e->getMessage());
    }
}

// Generar mensaje flash
function setFlashMessage($tipo, $mensaje) {
    $_SESSION['flash_message'] = [
        'tipo' => $tipo, // success, error, warning, info
        'mensaje' => $mensaje
    ];
}

// Obtener y limpiar mensaje flash
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

// Validar correo electrónico
function esEmailValido($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validar DNI peruano
function esDNIVálido($dni) {
    return preg_match('/^\d{8}$/', $dni) === 1;
}

// Validar RUC peruano
function esRUCValido($ruc) {
    return preg_match('/^\d{11}$/', $ruc) === 1;
}

// Obtener valor de configuración
function getConfig($clave, $db) {
    try {
        $sql = "SELECT valor FROM configuracion WHERE clave = :clave LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':clave' => $clave]);
        $resultado = $stmt->fetch();
        return $resultado ? $resultado['valor'] : null;
    } catch (Exception $e) {
        return null;
    }
}

// Establecer valor de configuración
function setConfig($clave, $valor, $db) {
    try {
        $sql = "INSERT INTO configuracion (clave, valor) VALUES (:clave, :valor) 
                ON DUPLICATE KEY UPDATE valor = :valor2";
        $stmt = $db->prepare($sql);
        $stmt->execute([':clave' => $clave, ':valor' => $valor, ':valor2' => $valor]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Subir archivo
function subirArchivo($archivo, $directorio = UPLOAD_DIR) {
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, ALLOWED_FILE_TYPES)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }
    
    if ($archivo['size'] > UPLOAD_MAX_SIZE) {
        return ['error' => 'El archivo excede el tamaño máximo permitido (5MB)'];
    }
    
    $nombre_unico = uniqid() . '.' . $extension;
    $ruta_destino = $directorio . '/' . $nombre_unico;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return [
            'success' => true,
            'nombre_original' => $archivo['name'],
            'nombre_archivo' => $nombre_unico,
            'ruta_archivo' => $ruta_destino,
            'tipo_archivo' => $extension,
            'tamano' => $archivo['size']
        ];
    }
    
    return ['error' => 'Error al subir el archivo'];
}

// Eliminar archivo
function eliminarArchivo($ruta) {
    if (file_exists($ruta)) {
        return unlink($ruta);
    }
    return false;
}

// Debug (solo en desarrollo)
function debug($data) {
    if (APP_ENV === 'development') {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

// Función para paginación
function obtenerPaginacion($total_registros, $por_pagina, $pagina_actual) {
    $total_paginas = ceil($total_registros / $por_pagina);
    $pagina_actual = max(1, min($pagina_actual, $total_paginas));
    $offset = ($pagina_actual - 1) * $por_pagina;
    
    return [
        'total_registros' => $total_registros,
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina_actual,
        'offset' => $offset,
        'por_pagina' => $por_pagina,
        'tiene_anterior' => $pagina_actual > 1,
        'tiene_siguiente' => $pagina_actual < $total_paginas
    ];
}
