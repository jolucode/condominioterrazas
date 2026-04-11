<?php
require_once __DIR__ . '/config/config.php';
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT nombre_completo, correo, rol FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "USUARIOS ENCONTRADOS:\n";
    foreach ($usuarios as $u) {
        echo "- " . $u['nombre_completo'] . " (" . $u['correo'] . ") - Rol: " . $u['rol'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
