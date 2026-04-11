<?php
require_once __DIR__ . '/config/config.php';
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT u.correo, u.rol, c.dni FROM usuarios u JOIN clientes c ON u.cliente_id = c.id");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "CREDENCIALES ENCONTRADAS:\n";
    foreach ($registros as $r) {
        echo "- Usuario: " . $r['correo'] . " | Rol: " . $r['rol'] . " | DNI (Posible clave): " . $r['dni'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
