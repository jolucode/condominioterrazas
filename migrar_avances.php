<?php
require_once __DIR__ . '/config/autoload.php';
$db = Database::getInstance()->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS avances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    imagen_url VARCHAR(255) NOT NULL,
    creado_por INT NOT NULL,
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha_publicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $db->exec($sql);
    echo "Tabla 'avances' creada correctamente.\n";
    
    // Create upload directory
    $dir = __DIR__ . '/uploads/avances';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Directorio 'uploads/avances' creado.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
