<?php
/**
 * MIGRACIÓN: Permite múltiples lotes por cliente
 * - Elimina UNIQUE en dni y correo
 * - Agrega UNIQUE en (dni, numero_lote, manzana, etapa)
 * Ejecutar UNA VEZ: http://localhost/condominioterrazas/migrar_clientes_lotes.php
 */
require_once __DIR__ . '/config/autoload.php';
requireAdmin();

$db  = Database::getInstance()->getConnection();
$ok  = [];
$err = [];

function run($db, $sql, &$ok, &$err, $msg) {
    try { $db->exec($sql); $ok[] = $msg; }
    catch (PDOException $e) { $err[] = $msg . ' — ' . $e->getMessage(); }
}

// 1. Eliminar UNIQUE de dni
$idx = $db->query("SHOW INDEX FROM clientes WHERE Key_name = 'dni'")->rowCount();
if ($idx > 0) {
    run($db, "ALTER TABLE clientes DROP INDEX dni", $ok, $err, 'OK: UNIQUE en dni eliminado');
} else {
    $ok[] = 'SKIP: UNIQUE en dni ya no existe';
}

// 2. Eliminar UNIQUE de correo
$idx = $db->query("SHOW INDEX FROM clientes WHERE Key_name = 'correo'")->rowCount();
if ($idx > 0) {
    run($db, "ALTER TABLE clientes DROP INDEX correo", $ok, $err, 'OK: UNIQUE en correo eliminado');
} else {
    $ok[] = 'SKIP: UNIQUE en correo ya no existe';
}

// 3. Agregar UNIQUE compuesto (lote + manzana + etapa)
// Un lote físico solo puede tener UN propietario registrado.
// El mismo propietario puede tener varios lotes distintos (filas repetidas con diferente lote).
$idx = $db->query("SHOW INDEX FROM clientes WHERE Key_name = 'uk_cliente_lote'")->rowCount();
if ($idx === 0) {
    run($db, "ALTER TABLE clientes ADD UNIQUE KEY uk_cliente_lote (numero_lote, manzana, etapa)",
        $ok, $err, 'OK: UNIQUE uk_cliente_lote (numero_lote, manzana, etapa) creado');
} else {
    // Si ya existe con la definición vieja (con dni), recrearlo
    run($db, "ALTER TABLE clientes DROP INDEX uk_cliente_lote",
        $ok, $err, 'OK: uk_cliente_lote antiguo eliminado para recrear');
    run($db, "ALTER TABLE clientes ADD UNIQUE KEY uk_cliente_lote (numero_lote, manzana, etapa)",
        $ok, $err, 'OK: UNIQUE uk_cliente_lote (numero_lote, manzana, etapa) recreado correctamente');
}

// 4. Asegurarse que idx_dni sigue existiendo como índice simple
$idx = $db->query("SHOW INDEX FROM clientes WHERE Key_name = 'idx_dni'")->rowCount();
if ($idx === 0) {
    run($db, "ALTER TABLE clientes ADD INDEX idx_dni (dni)",
        $ok, $err, 'OK: Índice simple idx_dni recreado');
} else {
    $ok[] = 'SKIP: idx_dni ya existe';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración clientes_lotes</title>
    <style>
        body { font-family:monospace; max-width:700px; margin:40px auto; padding:0 20px; }
        .ok  { color:#1a7a1a; background:#e8f5e9; padding:6px 12px; margin:4px 0; border-radius:4px; }
        .skip{ color:#555;    background:#f5f5f5; padding:6px 12px; margin:4px 0; border-radius:4px; }
        .err { color:#b71c1c; background:#ffebee; padding:6px 12px; margin:4px 0; border-radius:4px; }
        a.btn{ display:inline-block; margin-top:20px; padding:10px 20px;
               background:#1976d2; color:#fff; text-decoration:none; border-radius:4px; }
    </style>
</head>
<body>
<h2>Migración: múltiples lotes por cliente</h2>
<?php foreach ($ok as $m): ?>
    <div class="<?php echo strpos($m,'SKIP')===0?'skip':'ok'; ?>"><?php echo htmlspecialchars($m); ?></div>
<?php endforeach; ?>
<?php foreach ($err as $m): ?>
    <div class="err"><?php echo htmlspecialchars($m); ?></div>
<?php endforeach; ?>
<?php if (empty($err)): ?>
    <p style="margin-top:20px;color:#1a7a1a;font-weight:bold;">✓ Migración completada sin errores.</p>
<?php else: ?>
    <p style="margin-top:20px;color:#b71c1c;font-weight:bold;">⚠ Hay errores — revisa los mensajes rojos.</p>
<?php endif; ?>
<a class="btn" href="<?php echo APP_URL; ?>/index.php">Volver al Dashboard</a>
</body>
</html>
