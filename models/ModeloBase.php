<?php
/**
 * MODELO BASE
 * Clase base para todos los modelos
 */

class ModeloBase {
    protected $db;
    protected $tabla;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener registro por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener todos los registros
     */
    public function obtenerTodos($orden = 'id DESC') {
        $sql = "SELECT * FROM {$this->tabla} ORDER BY {$orden}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Insertar registro
     */
    public function insertar($datos) {
        $campos = implode(', ', array_keys($datos));
        $placeholders = ':' . implode(', :', array_keys($datos));
        
        $sql = "INSERT INTO {$this->tabla} ({$campos}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($datos)) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Actualizar registro
     */
    public function actualizar($id, $datos) {
        $campos = [];
        foreach (array_keys($datos) as $campo) {
            $campos[] = "{$campo} = :{$campo}";
        }
        $campos_str = implode(', ', $campos);
        
        $sql = "UPDATE {$this->tabla} SET {$campos_str} WHERE {$this->primaryKey} = :id";
        $datos['id'] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($datos);
    }
    
    /**
     * Eliminar registro
     */
    public function eliminar($id) {
        $sql = "DELETE FROM {$this->tabla} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Contar registros
     */
    public function contar($condicion = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla}";
        if ($condicion) {
            $sql .= " WHERE {$condicion}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        return $resultado['total'];
    }
}
