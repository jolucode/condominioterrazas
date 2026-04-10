<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE COMPROBANTES
 */

class Comprobante extends ModeloBase {
    protected $tabla = 'comprobantes';
    
    /**
     * Generar siguiente número de comprobante
     */
    public function generarNumero($tipo_comprobante) {
        $campo_numero = $tipo_comprobante === 'boleta' ? 'numero_boleta' : 'numero_factura';
        $campo_serie = $tipo_comprobante === 'boleta' ? 'serie_boleta' : 'serie_factura';
        
        $serie = getConfig($campo_serie, $this->db);
        $numero_actual = intval(getConfig($campo_numero, $this->db));
        $nuevo_numero = $numero_actual + 1;
        
        // Formatear con ceros a la izquierda
        $numero_formateado = str_pad($nuevo_numero, 8, '0', STR_PAD_LEFT);
        
        return [
            'serie' => $serie,
            'numero' => $numero_formateado,
            'numero_actual' => $nuevo_numero
        ];
    }
    
    /**
     * Crear comprobante
     */
    public function crearComprobante($datos) {
        $db = $this->db;
        
        try {
            $db->beginTransaction();
            
            // Insertar comprobante
            $comprobante_id = $this->insertar($datos);
            
            // Actualizar contador
            $campo_numero = $datos['tipo_comprobante'] === 'boleta' ? 'numero_boleta' : 'numero_factura';
            setConfig($campo_numero, $datos['numero_actual'], $db);
            
            $db->commit();
            return $comprobante_id;
            
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
    
    /**
     * Obtener comprobante por pago
     */
    public function obtenerPorPago($pago_id) {
        $sql = "SELECT c.*, CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
                       cl.dni as cliente_dni, cl.ruc as cliente_ruc
                FROM {$this->tabla} c
                INNER JOIN clientes cl ON c.cliente_id = cl.id
                WHERE c.pago_id = :pago_id
                ORDER BY c.id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pago_id' => $pago_id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener comprobantes de un cliente
     */
    public function obtenerPorCliente($cliente_id) {
        $sql = "SELECT c.*, p.mes, p.anio
                FROM {$this->tabla} c
                INNER JOIN pagos p ON c.pago_id = p.id
                WHERE c.cliente_id = :cliente_id
                AND c.estado_emision = 'emitido'
                ORDER BY c.fecha_creacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cliente_id' => $cliente_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Listar comprobantes con filtros
     */
    public function listarConFiltros($filtros = []) {
        $sql = "SELECT c.*, CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
                       cl.dni as cliente_dni
                FROM {$this->tabla} c
                INNER JOIN clientes cl ON c.cliente_id = cl.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filtros['tipo_comprobante'])) {
            $sql .= " AND c.tipo_comprobante = :tipo";
            $params[':tipo'] = $filtros['tipo_comprobante'];
        }
        
        if (!empty($filtros['estado_emision'])) {
            $sql .= " AND c.estado_emision = :estado";
            $params[':estado'] = $filtros['estado_emision'];
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND c.fecha_emision >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND c.fecha_emision <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        
        $sql .= " ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Anular comprobante
     */
    public function anularComprobante($id) {
        return $this->actualizar($id, ['estado_emision' => 'anulado']);
    }
    
    /**
     * Obtener comprobante con datos completos para impresión
     */
    public function obtenerParaImpresion($id) {
        $sql = "SELECT c.*, 
                       CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
                       cl.dni as cliente_dni,
                       cl.ruc as cliente_ruc,
                       cl.direccion as cliente_direccion,
                       p.mes,
                       p.anio,
                       p.metodo_pago
                FROM {$this->tabla} c
                INNER JOIN clientes cl ON c.cliente_id = cl.id
                INNER JOIN pagos p ON c.pago_id = p.id
                WHERE c.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Estadísticas de comprobantes
     */
    public function estadisticas() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_emision = 'emitido' THEN 1 ELSE 0 END) as emitidos,
                    SUM(CASE WHEN estado_emision = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado_emision = 'anulado' THEN 1 ELSE 0 END) as anulados,
                    SUM(CASE WHEN tipo_comprobante = 'boleta' THEN 1 ELSE 0 END) as boletas,
                    SUM(CASE WHEN tipo_comprobante = 'factura' THEN 1 ELSE 0 END) as facturas
                FROM {$this->tabla}";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
