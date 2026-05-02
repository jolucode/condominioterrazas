<?php
/**
 * MIGRACIÓN: Agregar tipo_pago, cuota_numero, total_cuotas a tabla pagos
 * Ejecutar una sola vez desde el navegador: /condominioterrazas/migrar_tipo_pago.php
 */
require_once __DIR__ . '/config/autoload.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$mensajes = [];
$errores  = [];

function ejecutar($db, $sql, &$mensajes, &$errores, $ok_msg) {
    try {
        $db->exec($sql);
        $mensajes[] = $ok_msg;
    } catch (PDOException $e) {
        $errores[] = 'ERROR — ' . $e->getMessage();
    }
}

// 1. Añadir tipo_pago
$col = $db->query("SHOW COLUMNS FROM pagos LIKE 'tipo_pago'")->rowCount();
if ($col === 0) {
    ejecutar($db,
        "ALTER TABLE pagos ADD COLUMN tipo_pago ENUM('mantenimiento','inscripcion','membresia_cuota') NOT NULL DEFAULT 'mantenimiento' AFTER anio",
        $mensajes, $errores, 'OK: Columna tipo_pago añadida'
    );
} else {
    $mensajes[] = 'SKIP: tipo_pago ya existe';
}

// 2. Añadir cuota_numero
$col = $db->query("SHOW COLUMNS FROM pagos LIKE 'cuota_numero'")->rowCount();
if ($col === 0) {
    ejecutar($db,
        "ALTER TABLE pagos ADD COLUMN cuota_numero TINYINT UNSIGNED NULL AFTER tipo_pago",
        $mensajes, $errores, 'OK: Columna cuota_numero añadida'
    );
} else {
    $mensajes[] = 'SKIP: cuota_numero ya existe';
}

// 3. Añadir total_cuotas
$col = $db->query("SHOW COLUMNS FROM pagos LIKE 'total_cuotas'")->rowCount();
if ($col === 0) {
    ejecutar($db,
        "ALTER TABLE pagos ADD COLUMN total_cuotas TINYINT UNSIGNED NULL AFTER cuota_numero",
        $mensajes, $errores, 'OK: Columna total_cuotas añadida'
    );
} else {
    $mensajes[] = 'SKIP: total_cuotas ya existe';
}

// 4. Hacer mes y anio nullable
ejecutar($db,
    "ALTER TABLE pagos MODIFY mes INT NULL, MODIFY anio INT NULL",
    $mensajes, $errores, 'OK: mes y anio ahora son NULL-able'
);

// 5. Eliminar índice único antiguo
$idx = $db->query("SHOW INDEX FROM pagos WHERE Key_name = 'uk_cliente_mes_anio'")->rowCount();
if ($idx > 0) {
    ejecutar($db,
        "ALTER TABLE pagos DROP INDEX uk_cliente_mes_anio",
        $mensajes, $errores, 'OK: Índice uk_cliente_mes_anio eliminado'
    );
} else {
    $mensajes[] = 'SKIP: uk_cliente_mes_anio no existe';
}

// 6. Crear nuevo índice único (cliente + tipo + mes + anio)
$idx = $db->query("SHOW INDEX FROM pagos WHERE Key_name = 'uk_cliente_tipo_mes_anio'")->rowCount();
if ($idx === 0) {
    ejecutar($db,
        "ALTER TABLE pagos ADD UNIQUE KEY uk_cliente_tipo_mes_anio (cliente_id, tipo_pago, mes, anio)",
        $mensajes, $errores, 'OK: Índice uk_cliente_tipo_mes_anio creado'
    );
} else {
    $mensajes[] = 'SKIP: uk_cliente_tipo_mes_anio ya existe';
}

// 7. Actualizar registros existentes
try {
    $n = $db->exec("UPDATE pagos SET tipo_pago = 'mantenimiento' WHERE tipo_pago IS NULL OR tipo_pago = ''");
    $mensajes[] = "OK: {$n} registros marcados como tipo_pago='mantenimiento'";
} catch (PDOException $e) {
    $errores[] = 'ERROR en UPDATE: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración tipo_pago</title>
    <style>
        body { font-family: monospace; max-width: 700px; margin: 40px auto; padding: 0 20px; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 8px; }
        .ok   { color: #1a7a1a; background: #e8f5e9; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        .skip { color: #555;    background: #f5f5f5; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        .err  { color: #b71c1c; background: #ffebee; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        a.btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1976d2; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
<h2>Migración: tipo_pago en tabla pagos</h2>

<?php foreach ($mensajes as $m): ?>
    <div class="<?php echo strpos($m, 'SKIP') === 0 ? 'skip' : 'ok'; ?>"><?php echo htmlspecialchars($m); ?></div>
<?php endforeach; ?>

<?php foreach ($errores as $e): ?>
    <div class="err"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<?php if (empty($errores)): ?>
    <p style="margin-top:20px; color:#1a7a1a; font-weight:bold;">✓ Migración completada sin errores.</p>
<?php else: ?>
    <p style="margin-top:20px; color:#b71c1c; font-weight:bold;">⚠ Hay errores. Revisa los mensajes rojos.</p>
<?php endif; ?>

<a class="btn" href="<?php echo APP_URL; ?>/index.php">Volver al Dashboard</a>
</body>
</html>
