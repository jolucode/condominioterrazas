<?php
require_once ROOT_PATH . '/models/ModeloBase.php';

/**
 * MODELO DE USUARIOS
 */

class Usuario extends ModeloBase {
    protected $tabla = 'usuarios';
    
    /**
     * Autenticar usuario
     */
    public function autenticar($correo, $password) {
        $sql = "SELECT * FROM {$this->tabla} WHERE correo = :correo AND estado = 'activo' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Actualizar último acceso
            $this->actualizarUltimoAcceso($usuario['id']);
            return $usuario;
        }
        
        return false;
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crearUsuario($datos) {
        // Hashear contraseña
        $datos['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        return $this->insertar($datos);
    }
    
    /**
     * Cambiar contraseña
     */
    public function cambiarPassword($id, $password_nuevo) {
        $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
        return $this->actualizar($id, ['password' => $password_hash]);
    }
    
    /**
     * Generar token de recuperación
     */
    public function generarTokenRecuperacion($correo) {
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE {$this->tabla} SET token_recuperacion = :token, token_expiracion = :expiracion 
                WHERE correo = :correo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':expiracion' => $expiracion,
            ':correo' => $correo
        ]);
        
        return $token;
    }
    
    /**
     * Validar token de recuperación
     */
    public function validarTokenRecuperacion($token) {
        $sql = "SELECT * FROM {$this->tabla} 
                WHERE token_recuperacion = :token 
                AND token_expiracion > NOW() 
                AND estado = 'activo' 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener usuario por cliente_id
     */
    public function obtenerPorClienteId($cliente_id) {
        $sql = "SELECT * FROM {$this->tabla} WHERE cliente_id = :cliente_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cliente_id' => $cliente_id]);
        return $stmt->fetch();
    }
    
    /**
     * Actualizar último acceso
     */
    private function actualizarUltimoAcceso($id) {
        // Se podría agregar un campo ultimo_acceso a la tabla
        // Por ahora solo actualizamos el timestamp automático
    }
    
    /**
     * Verificar si el correo ya existe
     */
    public function correoExiste($correo, $excluir_id = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE correo = :correo";
        $params = [':correo' => $correo];
        
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
     * Listar usuarios con filtro
     */
    public function listarConFiltro($filtro = '', $params = [], $orden = 'id DESC') {
        $sql = "SELECT * FROM {$this->tabla}";
        
        if ($filtro) {
            $sql .= " WHERE {$filtro}";
        }
        
        $sql .= " ORDER BY {$orden}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
