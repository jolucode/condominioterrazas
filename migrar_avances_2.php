<?php
require_once __DIR__ . '/config/autoload.php';
$db = Database::getInstance()->getConnection();

try {
    // 1. Crear tabla para múltiples imágenes
    $sql_imagenes = "CREATE TABLE IF NOT EXISTS avance_imagenes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        avance_id INT NOT NULL,
        ruta_imagen VARCHAR(255) NOT NULL,
        orden INT DEFAULT 0,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (avance_id) REFERENCES avances(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql_imagenes);
    echo "Tabla 'avance_imagenes' creada correctamente.\n";

    // 2. Modificar tabla avances (opcional: quitar imagen_url original si ya existe datos)
    // No la quitaremos por ahora para no romper si ya subieron algo, pero permitiremos que sea NULL
    $sql_alter = "ALTER TABLE avances MODIFY imagen_url VARCHAR(255) NULL;";
    $db->exec($sql_alter);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
