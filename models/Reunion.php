<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE REUNIONES
 */

class Reunion extends ModeloBase {
    protected $tabla = 'reuniones';
    
    /**
     * Obtener reuniones publicadas
     */
    public function obtenerPublicadas() {
        $sql = "SELECT * FROM {$this->tabla} 
                WHERE estado IN ('publicado', 'finalizado')
                ORDER BY fecha_reunion DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener próxima reunión
     */
    public function obtenerProxima() {
        $sql = "SELECT * FROM {$this->tabla} 
                WHERE estado IN ('borrador', 'publicado')
                AND fecha_reunion >= CURDATE()
                ORDER BY fecha_reunion ASC
                LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    /**
     * Obtener reunión con acuerdos
     */
    public function obtenerConAcuerdos($id) {
        $sql = "SELECT r.*, u.nombre_completo as creador_nombre
                FROM {$this->tabla} r
                LEFT JOIN usuarios u ON r.creado_por = u.id
                WHERE r.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $reunion = $stmt->fetch();
        
        if ($reunion) {
            $modelo_acuerdo = new Acuerdo();
            $reunion['acuerdos'] = $modelo_acuerdo->obtenerPorReunion($id);
        }
        
        return $reunion;
    }
    
    /**
     * Crear reunión con acuerdos
     */
    public function crearConAcuerdos($datos_reunion, $acuerdos) {
        $db = $this->db;
        
        try {
            $db->beginTransaction();
            
            // Crear reunión
            $reunion_id = $this->insertar($datos_reunion);
            
            // Crear acuerdos
            if (!empty($acuerdos) && $reunion_id) {
                $modelo_acuerdo = new Acuerdo();
                $orden = 1;
                foreach ($acuerdos as $acuerdo) {
                    $acuerdo['reunion_id'] = $reunion_id;
                    $acuerdo['orden'] = $orden;
                    $modelo_acuerdo->insertar($acuerdo);
                    $orden++;
                }
            }
            
            $db->commit();
            return $reunion_id;
            
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
    
    /**
     * Actualizar reunión con acuerdos
     */
    public function actualizarConAcuerdos($id, $datos_reunion, $acuerdos) {
        $db = $this->db;
        
        try {
            $db->beginTransaction();
            
            // Actualizar reunión
            $this->actualizar($id, $datos_reunion);
            
            // Eliminar acuerdos anteriores
            $modelo_acuerdo = new Acuerdo();
            $modelo_acuerdo->eliminarPorReunion($id);
            
            // Crear nuevos acuerdos
            if (!empty($acuerdos)) {
                $orden = 1;
                foreach ($acuerdos as $acuerdo) {
                    $acuerdo['reunion_id'] = $id;
                    $acuerdo['orden'] = $orden;
                    $modelo_acuerdo->insertar($acuerdo);
                    $orden++;
                }
            }
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
    
    /**
     * Listar con paginación
     */
    public function listarPaginado($pagina = 1, $por_pagina = 10, $estado = null) {
        $sql = "SELECT r.*, u.nombre_completo as creador_nombre,
                       (SELECT COUNT(*) FROM acuerdos WHERE reunion_id = r.id) as total_acuerdos
                FROM {$this->tabla} r
                LEFT JOIN usuarios u ON r.creado_por = u.id
                WHERE 1=1";
        $params = [];
        
        if ($estado) {
            $sql .= " AND r.estado = :estado";
            $params[':estado'] = $estado;
        }
        
        // Obtener total
        $sql_count = "SELECT COUNT(*) as total FROM {$this->tabla}";
        if ($estado) {
            $sql_count .= " WHERE estado = :estado";
        }
        $stmt_count = $this->db->prepare($sql_count);
        $stmt_count->execute($params);
        $total = $stmt_count->fetch()['total'];
        
        // Obtener registros
        $sql .= " ORDER BY r.fecha_reunion DESC";
        $offset = intval(($pagina - 1) * $por_pagina);
        $por_pagina = intval($por_pagina);
        $sql .= " LIMIT {$por_pagina} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return [
            'datos' => $stmt->fetchAll(),
            'paginacion' => obtenerPaginacion($total, $por_pagina, $pagina)
        ];
    }
}

/**
 * MODELO DE ACUERDOS
 */

class Acuerdo extends ModeloBase {
    protected $tabla = 'acuerdos';
    
    /**
     * Obtener acuerdos por reunión
     */
    public function obtenerPorReunion($reunion_id) {
        $sql = "SELECT * FROM {$this->tabla} 
                WHERE reunion_id = :reunion_id 
                ORDER BY orden ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reunion_id' => $reunion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Eliminar acuerdos por reunión
     */
    public function eliminarPorReunion($reunion_id) {
        $sql = "DELETE FROM {$this->tabla} WHERE reunion_id = :reunion_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':reunion_id' => $reunion_id]);
    }
    
    /**
     * Actualizar estado de acuerdo
     */
    public function actualizarEstado($id, $estado) {
        return $this->actualizar($id, ['estado' => $estado]);
    }
    
    /**
     * Obtener acuerdos pendientes
     */
    public function obtenerPendientes() {
        $sql = "SELECT a.*, r.titulo as reunion_titulo, r.fecha_reunion
                FROM {$this->tabla} a
                INNER JOIN reuniones r ON a.reunion_id = r.id
                WHERE a.estado = 'pendiente'
                ORDER BY r.fecha_reunion DESC
                LIMIT 10";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
