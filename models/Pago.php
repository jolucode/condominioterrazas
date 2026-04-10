<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE PAGOS
 */

class Pago extends ModeloBase {
    protected $tabla = 'pagos';
    
    /**
     * Obtener pagos de un cliente
     */
    public function obtenerPorCliente($cliente_id, $anio = null) {
        $sql = "SELECT p.*, CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre, c.numero_lote
                FROM {$this->tabla} p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE p.cliente_id = :cliente_id";
        
        $params = [':cliente_id' => $cliente_id];
        
        if ($anio) {
            $sql .= " AND p.anio = :anio";
            $params[':anio'] = $anio;
        }
        
        $sql .= " ORDER BY p.anio DESC, p.mes DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener todos los pagos con filtros
     */
    public function obtenerConFiltros($filtros = [], $pagina = 1, $por_pagina = 15) {
        $sql = "SELECT p.*, CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre, 
                       c.dni as cliente_dni, c.numero_lote
                FROM {$this->tabla} p
                INNER JOIN clientes c ON p.cliente_id = c.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['cliente_id'])) {
            $sql .= " AND p.cliente_id = :cliente_id";
            $params[':cliente_id'] = $filtros['cliente_id'];
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND p.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        
        if (!empty($filtros['mes'])) {
            $sql .= " AND p.mes = :mes";
            $params[':mes'] = $filtros['mes'];
        }
        
        if (!empty($filtros['anio'])) {
            $sql .= " AND p.anio = :anio";
            $params[':anio'] = $filtros['anio'];
        }
        
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (c.nombres LIKE :busqueda OR c.apellidos LIKE :busqueda OR c.dni LIKE :busqueda)";
            $params[':busqueda'] = "%{$filtros['busqueda']}%";
        }
        
        // Obtener total
        $sql_count = str_replace("SELECT p.*, CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre, 
                       c.dni as cliente_dni, c.numero_lote", "SELECT COUNT(*) as total", $sql);
        $stmt_count = $this->db->prepare($sql_count);
        $stmt_count->execute($params);
        $total = $stmt_count->fetch()['total'];
        
        // Ordenar y paginar
        $sql .= " ORDER BY p.id DESC";
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
    
    /**
     * Registrar pago
     */
    public function registrarPago($datos) {
        return $this->insertar($datos);
    }
    
    /**
     * Marcar pago como pagado
     */
    public function marcarComoPagado($id, $metodo_pago, $observacion = '', $usuario_id = null) {
        $datos = [
            'estado' => 'pagado',
            'fecha_pago' => date('Y-m-d H:i:s'),
            'metodo_pago' => $metodo_pago,
            'registrado_por' => $usuario_id
        ];
        
        if ($observacion) {
            $datos['observacion'] = $observacion;
        }
        
        return $this->actualizar($id, $datos);
    }
    
    /**
     * Actualizar estados vencidos
     */
    public function actualizarVencidos() {
        $sql = "UPDATE {$this->tabla} 
                SET estado = 'vencido' 
                WHERE estado = 'pendiente' 
                AND fecha_vencimiento < CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * Estadísticas de pagos
     */
    public function estadisticas($mes = null, $anio = null) {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as pagados,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos,
                    SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) as total_recaudado,
                    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as total_pendiente,
                    SUM(CASE WHEN estado = 'vencido' THEN monto ELSE 0 END) as total_vencido
                FROM {$this->tabla}
                WHERE 1=1";
        
        $params = [];
        
        if ($mes) {
            $sql .= " AND mes = :mes";
            $params[':mes'] = $mes;
        }
        
        if ($anio) {
            $sql .= " AND anio = :anio";
            $params[':anio'] = $anio;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Pagos por mes (para gráficos)
     */
    public function pagosPorMes($anio) {
        $sql = "SELECT 
                    mes,
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as pagados,
                    SUM(CASE WHEN estado = 'pendiente' OR estado = 'vencido' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) as total_recaudado
                FROM {$this->tabla}
                WHERE anio = :anio
                GROUP BY mes
                ORDER BY mes";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':anio' => $anio]);
        return $stmt->fetchAll();
    }
    
    /**
     * Últimos pagos registrados
     */
    public function ultimosPagos($limite = 10) {
        $sql = "SELECT p.*, CONCAT(c.nombres, ' ', c.apellidos) as cliente_nombre, c.numero_lote
                FROM {$this->tabla} p
                INNER JOIN clientes c ON p.cliente_id = c.id
                ORDER BY p.fecha_creacion DESC
                LIMIT " . intval($limite);
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar si ya existe pago para cliente/mes/año
     */
    public function existePago($cliente_id, $mes, $anio, $excluir_id = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla} 
                WHERE cliente_id = :cliente_id AND mes = :mes AND anio = :anio";
        $params = [
            ':cliente_id' => $cliente_id,
            ':mes' => $mes,
            ':anio' => $anio
        ];
        
        if ($excluir_id) {
            $sql .= " AND id != :id";
            $params[':id'] = $excluir_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }
}
