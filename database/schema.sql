-- ============================================
-- SISTEMA DE GESTIÓN - CONDOMINIO TERRAZAS
-- Base de Datos MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS if0_41640060_condterrazasdb 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE if0_41640060_condterrazasdb;

-- ============================================
-- TABLA: USUARIOS
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'cliente') NOT NULL DEFAULT 'cliente',
    cliente_id INT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    token_recuperacion VARCHAR(100) NULL,
    token_expiracion DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_correo (correo),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: CLIENTES
-- ============================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    dni VARCHAR(8) NOT NULL UNIQUE,
    ruc VARCHAR(11) NULL,
    telefono VARCHAR(20) NULL,
    correo VARCHAR(100) NULL UNIQUE,
    direccion VARCHAR(200) NULL,
    numero_lote VARCHAR(20) NOT NULL,
    manzana VARCHAR(10) NULL,
    etapa VARCHAR(50) NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_estado (estado),
    INDEX idx_lote (numero_lote),
    INDEX idx_nombre (nombres, apellidos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: PAGOS
-- ============================================
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    mes INT NOT NULL COMMENT '1-12',
    anio INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL DEFAULT 70.00,
    fecha_vencimiento DATE NOT NULL,
    fecha_pago DATETIME NULL,
    estado ENUM('pendiente', 'pagado', 'vencido') NOT NULL DEFAULT 'pendiente',
    metodo_pago ENUM('efectivo', 'transferencia', 'yape', 'plin', 'deposito') NULL,
    observacion TEXT NULL,
    registrado_por INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_mes_anio (mes, anio),
    INDEX idx_fecha_pago (fecha_pago),
    UNIQUE KEY uk_cliente_mes_anio (cliente_id, mes, anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: COMPROBANTES
-- ============================================
CREATE TABLE IF NOT EXISTS comprobantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pago_id INT NOT NULL,
    tipo_comprobante ENUM('boleta', 'factura') NOT NULL,
    serie VARCHAR(10) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    cliente_id INT NOT NULL,
    dni_ruc VARCHAR(11) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_emision DATETIME NOT NULL,
    estado_emision ENUM('pendiente', 'emitido', 'anulado') NOT NULL DEFAULT 'pendiente',
    sunat_hash VARCHAR(100) NULL,
    sunat_xml VARCHAR(255) NULL,
    observacion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_pago (pago_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_tipo (tipo_comprobante),
    INDEX idx_estado (estado_emision),
    INDEX idx_serie_numero (serie, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: REUNIONES
-- ============================================
CREATE TABLE IF NOT EXISTS reuniones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_reunion DATE NOT NULL,
    hora_reunion TIME NULL,
    lugar VARCHAR(200) NULL,
    proxima_fecha DATE NULL,
    estado ENUM('borrador', 'publicado', 'finalizado') NOT NULL DEFAULT 'borrador',
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha_reunion),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: ACUERDOS
-- ============================================
CREATE TABLE IF NOT EXISTS acuerdos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    descripcion TEXT NOT NULL,
    responsable VARCHAR(150) NULL,
    estado ENUM('pendiente', 'en_proceso', 'cumplido') NOT NULL DEFAULT 'pendiente',
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id) ON DELETE CASCADE,
    INDEX idx_reunion (reunion_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: ARCHIVOS ADJUNTOS
-- ============================================
CREATE TABLE IF NOT EXISTS archivos_adjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50) NULL,
    tamano INT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    subido_por INT NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id) ON DELETE SET NULL,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_reunion (reunion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: CONFIGURACIÓN
-- ============================================
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NULL,
    descripcion VARCHAR(255) NULL,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: AUDITORÍA (LOG DE ACTIVIDADES)
-- ============================================
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50) NULL,
    registro_id INT NULL,
    descripcion TEXT NULL,
    ip_address VARCHAR(45) NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_registro),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS INICIALES
-- ============================================

-- Usuario administrador por defecto (password: admin123)
-- Hash generado con: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre_completo, correo, password, rol) VALUES 
('Administrador Principal', 'admin@condominioterrazas.com', '$2y$10$kGJfZ8M5vN2pQ4rS6tU7vO.xW8yZ0aB2cD4eF6gH8iJ0kL2mN4oP', 'administrador');

-- Configuración del condominio
INSERT INTO configuracion (clave, valor, descripcion) VALUES 
('nombre_condominio', 'Condominio Terrazas', 'Nombre del condominio'),
('cuota_mantenimiento', '70.00', 'Cuota mensual de mantenimiento en soles'),
('moneda', 'PEN', 'Moneda utilizada (PEN = Soles)'),
('direccion_condominio', '', 'Dirección del condominio'),
('telefono_condominio', '', 'Teléfono de contacto'),
('correo_condominio', 'contacto@condominioterrazas.com', 'Correo de contacto'),
('serie_boleta', 'B001', 'Serie para boletas'),
('serie_factura', 'F001', 'Serie para facturas'),
('numero_boleta', '0', 'Número actual de boleta'),
('numero_factura', '0', 'Número actual de factura'),
('razon_social', 'Condominio Terrazas', 'Razón social para facturación'),
('ruc_condominio', '', 'RUC del condominio'),
('direccion_fiscal', '', 'Dirección fiscal para facturación');

-- Lógica de actualización de pagos vencidos (Se maneja via PHP en InfinityFree por compatibilidad)
-- ============================================
-- TABLA: AVANCES DEL CONDOMINIO
-- ============================================
CREATE TABLE IF NOT EXISTS avances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    imagen_url VARCHAR(255) NULL, -- Referencia legacy
    creado_por INT NOT NULL,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: AVANCE_IMAGENES (Galeria multiple)
-- ============================================
CREATE TABLE IF NOT EXISTS avance_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    avance_id INT NOT NULL,
    ruta_imagen VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (avance_id) REFERENCES avances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EVENTO: Actualizar pagos vencidos automáticamente
-- (Requiere que el event_scheduler esté activado)
-- ============================================
-- SET GLOBAL event_scheduler = ON;
-- DELIMITER //
-- CREATE EVENT IF NOT EXISTS evt_actualizar_vencidos
-- ON SCHEDULE EVERY 1 DAY
-- DO
-- BEGIN
--     CALL actualizar_pagos_vencidos();
-- END//
-- DELIMITER ;
