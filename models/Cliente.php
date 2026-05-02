<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE CLIENTES
 */

class Cliente extends ModeloBase {
    protected $tabla = 'clientes';
    
    /**
     * Obtener todos los clientes activos
     */
    public function obtenerActivos() {
        $sql = "SELECT * FROM {$this->tabla} WHERE estado = 'activo' ORDER BY apellidos, nombres";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar clientes
     */
    public function buscar($termino) {
        $sql = "SELECT * FROM {$this->tabla} 
                WHERE nombres LIKE :termino1 
                OR apellidos LIKE :termino2 
                OR dni LIKE :termino3 
                OR numero_lote LIKE :termino4 
                OR correo LIKE :termino5
                ORDER BY apellidos, nombres";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':termino1' => "%{$termino}%",
            ':termino2' => "%{$termino}%",
            ':termino3' => "%{$termino}%",
            ':termino4' => "%{$termino}%",
            ':termino5' => "%{$termino}%"
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener cliente con resumen de pagos
     */
    public function obtenerConResumen($id) {
        $sql = "SELECT c.*, 
                       COUNT(p.id) as total_pagos,
                       SUM(CASE WHEN p.estado = 'pagado' THEN 1 ELSE 0 END) as pagos_realizados,
                       SUM(CASE WHEN p.estado = 'pendiente' THEN 1 ELSE 0 END) as pagos_pendientes,
                       SUM(CASE WHEN p.estado = 'vencido' THEN 1 ELSE 0 END) as pagos_vencidos,
                       SUM(CASE WHEN p.estado = 'pagado' THEN p.monto ELSE 0 END) as total_pagado
                FROM {$this->tabla} c
                LEFT JOIN pagos p ON c.id = p.cliente_id
                WHERE c.id = :id
                GROUP BY c.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Listar con paginación y filtros
     */
    public function listarPaginado($pagina = 1, $por_pagina = 10, $filtro = '', $params = []) {
        $sql = "SELECT * FROM {$this->tabla}";

        if ($filtro) {
            $sql .= " WHERE {$filtro}";
        }

        // Obtener total
        $sql_count = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
        $stmt_count = $this->db->prepare($sql_count);
        if (!empty($params)) {
            $stmt_count->execute($params);
        } else {
            $stmt_count->execute();
        }
        $total = $stmt_count->fetch()['total'];

        // Obtener registros
        $offset = intval(($pagina - 1) * $por_pagina);
        $por_pagina = intval($por_pagina);
        $sql .= " ORDER BY id DESC LIMIT {$por_pagina} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        return [
            'datos' => $stmt->fetchAll(),
            'paginacion' => obtenerPaginacion($total, $por_pagina, $pagina)
        ];
    }
    
    /**
     * Verificar si DNI ya existe
     */
    public function dniExiste($dni, $excluir_id = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE dni = :dni";
        $params = [':dni' => $dni];
        
        if ($excluir_id) {
            $sql .= " AND id != :id";
            $params[':id'] = $excluir_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }
    
    /**
     * Generar pagos mensuales para todos los clientes
     */
    public function generarPagosMensuales($mes, $anio, $monto) {
        $clientes = $this->obtenerActivos();
        $fecha_vencimiento = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anio));
        $creados = 0;
        
        foreach ($clientes as $cliente) {
            // Verificar si ya existe el pago para este mes/año
            $sql = "SELECT COUNT(*) as total FROM pagos 
                    WHERE cliente_id = :cliente_id AND mes = :mes AND anio = :anio";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':cliente_id' => $cliente['id'],
                ':mes' => $mes,
                ':anio' => $anio
            ]);
            $existe = $stmt->fetch()['total'];
            
            if ($existe == 0) {
                $sql_insert = "INSERT INTO pagos (cliente_id, tipo_pago, mes, anio, monto, fecha_vencimiento, estado)
                              VALUES (:cliente_id, 'mantenimiento', :mes, :anio, :monto, :fecha_vencimiento, 'pendiente')";
                $stmt_insert = $this->db->prepare($sql_insert);
                $stmt_insert->execute([
                    ':cliente_id'       => $cliente['id'],
                    ':mes'              => $mes,
                    ':anio'             => $anio,
                    ':monto'            => $monto,
                    ':fecha_vencimiento' => $fecha_vencimiento,
                ]);
                $creados++;
            }
        }
        
        return $creados;
    }
    
    /**
     * Obtener etapas distintas registradas (para filtros)
     */
    public function obtenerEtapas() {
        $sql = "SELECT DISTINCT etapa FROM {$this->tabla}
                WHERE etapa IS NOT NULL AND etapa != ''
                ORDER BY etapa ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Estadísticas generales de clientes
     */
    public function estadisticas() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                FROM {$this->tabla}";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
