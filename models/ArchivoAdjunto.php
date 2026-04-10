<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE ARCHIVOS ADJUNTOS
 */

class ArchivoAdjunto extends ModeloBase {
    protected $tabla = 'archivos_adjuntos';
    
    /**
     * Obtener archivos de una reunión
     */
    public function obtenerPorReunion($reunion_id) {
        $sql = "SELECT * FROM {$this->tabla} 
                WHERE reunion_id = :reunion_id 
                ORDER BY fecha_subida DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reunion_id' => $reunion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Guardar archivo
     */
    public function guardarArchivo($datos) {
        return $this->insertar($datos);
    }
    
    /**
     * Eliminar archivo físico y registro
     */
    public function eliminarConArchivo($id) {
        $archivo = $this->obtenerPorId($id);
        
        if ($archivo) {
            // Eliminar archivo físico
            eliminarArchivo($archivo['ruta_archivo']);
            
            // Eliminar registro
            return $this->eliminar($id);
        }
        
        return false;
    }
}
