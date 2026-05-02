<?php
/**
 * CONTROLADOR DE AVANCES DEL CONDOMINIO
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'listar';
$modelo_avance = new Avance();

switch ($accion) {
    case 'listar':
        listarAvances();
        break;
    case 'crear':
        if (!esAdministrador()) redirigir('controllers/avance_controller.php?accion=listar');
        crearAvance();
        break;
    case 'eliminar':
        if (!esAdministrador()) redirigir('controllers/avance_controller.php?accion=listar');
        eliminarAvance();
        break;
    default:
        redirigir('controllers/avance_controller.php?accion=listar');
        break;
}

/**
 * Listar todos los avances (Vista Galería)
 */
function listarAvances() {
    global $modelo_avance;
    $avances = $modelo_avance->obtenerTodos();
    
    $titulo = "Avances del Condominio";
    $pagina_actual = 'avances';
    
    ob_start();
    include __DIR__ . '/../views/avances/index.php';
    $contenido = ob_get_clean();
    
    if (esAdministrador()) {
        include __DIR__ . '/../views/partials/admin-layout.php';
    } else {
        include __DIR__ . '/../views/partials/cliente-layout.php';
    }
}

/**
 * Crear un nuevo avance (Solo Admin)
 */
function crearAvance() {
    global $modelo_avance;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Token de seguridad inválido. Intente nuevamente.');
            redirigir('controllers/avance_controller.php?accion=crear');
        }
        $titulo_post = sanear($_POST['titulo']);
        $descripcion = sanear($_POST['descripcion']);
        $creado_por = $_SESSION['usuario_id'];
        
        if (!empty($titulo_post) && !empty($descripcion) && isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
            $avance_id = $modelo_avance->insertar([
                'titulo' => $titulo_post,
                'descripcion' => $descripcion,
                'creado_por' => $creado_por
            ]);

            if ($avance_id) {
                $files = $_FILES['imagenes'];
                $dir_subida = __DIR__ . '/../uploads/avances/';
                
                if (!is_dir($dir_subida)) {
                    mkdir($dir_subida, 0777, true);
                }

                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === 0) {
                        $extension = pathinfo($name, PATHINFO_EXTENSION);
                        $nombre_nuevo = uniqid('avance_') . '_' . $key . '.' . $extension;
                        $ruta_destino = $dir_subida . $nombre_nuevo;
                        
                        if (move_uploaded_file($files['tmp_name'][$key], $ruta_destino)) {
                            $modelo_avance->guardarImagen($avance_id, 'uploads/avances/' . $nombre_nuevo, $key);
                        }
                    }
                }
                
                setFlashMessage('success', 'Avance publicado correctamente con múltiples imágenes.');
                redirigir('controllers/avance_controller.php?accion=listar');
            } else {
                setFlashMessage('error', 'Error al crear el registro inicial.');
            }
        } else {
            setFlashMessage('error', 'Por favor complete todos los campos y suba al menos una imagen.');
        }
    }
    
    $titulo = "Publicar Nuevo Avance";
    $pagina_actual = 'avances';
    
    ob_start();
    include __DIR__ . '/../views/avances/crear.php';
    $contenido = ob_get_clean();
    
    include __DIR__ . '/../views/partials/admin-layout.php';
}

/**
 * Eliminar un avance (Solo Admin)
 */
function eliminarAvance() {
    global $modelo_avance;
    $id = intval($_GET['id'] ?? 0);

    if (!$id || !$modelo_avance->obtenerPorId($id)) {
        setFlashMessage('error', 'Avance no encontrado.');
        redirigir('controllers/avance_controller.php?accion=listar');
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM avance_imagenes WHERE avance_id = :id");
    $stmt->execute([':id' => $id]);
    $imagenes = $stmt->fetchAll();

    foreach ($imagenes as $img) {
        $archivo = __DIR__ . '/../' . $img['ruta_imagen'];
        if (file_exists($archivo)) {
            @unlink($archivo);
        }
    }

    $modelo_avance->eliminar($id);
    setFlashMessage('success', 'Avance eliminado correctamente.');
    redirigir('controllers/avance_controller.php?accion=listar');
}
