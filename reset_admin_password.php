<?php
/**
 * SCRIPT PARA RESETEAR CONTRASEÑA DE ADMINISTRADOR
 * Ejecutar una vez y luego eliminar este archivo
 */

// Configuración básica
$host = 'localhost';
$dbname = 'condominio_terrazas';
$username = 'root';
$password = '';

// Nueva contraseña deseada
$nueva_password = 'admin123';

// Generar hash
$hash = password_hash($nueva_password, PASSWORD_DEFAULT);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Actualizar contraseña del admin
    $sql = "UPDATE usuarios SET password = :password WHERE correo = 'admin@condominioterrazas.com'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':password' => $hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ ¡Contraseña actualizada exitosamente!\n\n";
        echo "Correo: admin@condominioterrazas.com\n";
        echo "Contraseña: {$nueva_password}\n\n";
        echo "Hash generado: {$hash}\n\n";
        echo "⚠️ IMPORTANTE: Elimina este archivo después de usarlo\n";
    } else {
        echo "❌ No se encontró el usuario administrador.\n";
        echo "Verifica que la base de datos esté importada correctamente.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "Verifica que MySQL esté corriendo y la base de datos exista.\n";
}
