<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE AVANCES DEL CONDOMINIO
 */
class Avance extends ModeloBase {
    protected $tabla = 'avances';
    
    /**
     * Obtener todos los avances con sus imágenes y el nombre del administrador
     */
    public function obtenerTodos($orden = 'a.fecha_publicacion DESC') {
        // Primero obtenemos los avances
        $sql = "SELECT a.*, u.nombre_completo as administrador 
                FROM {$this->tabla} a
                INNER JOIN usuarios u ON a.creado_por = u.id
                ORDER BY {$orden}";
        $stmt = $this->db->query($sql);
        $avances = $stmt->fetchAll();

        // Luego, para cada avance, buscamos sus imágenes
        foreach ($avances as &$avance) {
            $sql_img = "SELECT * FROM avance_imagenes WHERE avance_id = :avance_id ORDER BY orden ASC";
            $stmt_img = $this->db->prepare($sql_img);
            $stmt_img->execute([':avance_id' => $avance['id']]);
            $avance['imagenes'] = $stmt_img->fetchAll();
        }

        return $avances;
    }

    /**
     * Guardar una imagen vinculada a un avance
     */
    public function guardarImagen($avance_id, $ruta, $orden = 0) {
        $sql = "INSERT INTO avance_imagenes (avance_id, ruta_imagen, orden) VALUES (:avance_id, :ruta, :orden)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':avance_id' => $avance_id,
            ':ruta' => $ruta,
            ':orden' => $orden
        ]);
    }
}
