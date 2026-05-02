<?php
/**
 * CONTROLADOR DE DESCARGA DE ARCHIVOS
 * Sirve archivos adjuntos de reuniones de forma segura (requiere autenticación)
 */
require_once __DIR__ . '/../config/autoload.php';
requireAuth();

$accion = $_GET['accion'] ?? 'descargar';

switch ($accion) {
    case 'descargar': descargarArchivo(); break;
    default: redirigir('index.php');
}

function descargarArchivo() {
    $id = intval($_GET['id'] ?? 0);

    if (!$id) {
        http_response_code(404);
        die('Archivo no especificado.');
    }

    $modelo = new ArchivoAdjunto();
    $archivo = $modelo->obtenerPorId($id);

    if (!$archivo) {
        http_response_code(404);
        die('Archivo no encontrado.');
    }

    // La ruta puede ser absoluta (subirArchivo) o relativa (avances)
    $ruta = $archivo['ruta_archivo'];
    if (!file_exists($ruta)) {
        $ruta = ROOT_PATH . '/' . ltrim($archivo['ruta_archivo'], '/\\');
    }
    if (!file_exists($ruta)) {
        http_response_code(404);
        die('El archivo no existe en el servidor.');
    }

    $mime_map = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    $ext  = strtolower($archivo['tipo_archivo'] ?? pathinfo($archivo['nombre_archivo'], PATHINFO_EXTENSION));
    $mime = $mime_map[$ext] ?? 'application/octet-stream';

    // PDFs e imágenes se abren en el navegador, el resto se descarga
    $disposition = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']) ? 'inline' : 'attachment';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($archivo['nombre_original']) . '"');
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: private, max-age=3600');

    readfile($ruta);
    exit;
}
