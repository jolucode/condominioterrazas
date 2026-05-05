<?php
/**
 * MIGRACIÓN: Agrega columna fecha_compra a clientes
 * Ejecutar UNA VEZ: http://localhost/condominioterrazas/migrar_fecha_compra.php
 */
require_once __DIR__ . '/config/autoload.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$ok = []; $err = [];

$col = $db->query("SHOW COLUMNS FROM clientes LIKE 'fecha_compra'")->rowCount();
if ($col === 0) {
    try {
        $db->exec("ALTER TABLE clientes ADD COLUMN fecha_compra DATE NULL AFTER correo");
        $ok[] = 'OK: Columna fecha_compra añadida';
    } catch (PDOException $e) {
        $err[] = 'ERROR: ' . $e->getMessage();
    }
} else {
    $ok[] = 'SKIP: fecha_compra ya existe';
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Migración fecha_compra</title>
<style>body{font-family:monospace;max-width:600px;margin:40px auto;padding:0 20px}
.ok{color:#1a7a1a;background:#e8f5e9;padding:6px 12px;margin:4px 0;border-radius:4px}
.skip{color:#555;background:#f5f5f5;padding:6px 12px;margin:4px 0;border-radius:4px}
.err{color:#b71c1c;background:#ffebee;padding:6px 12px;margin:4px 0;border-radius:4px}
a.btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#1976d2;color:#fff;text-decoration:none;border-radius:4px}</style>
</head><body>
<h2>Migración: fecha_compra en clientes</h2>
<?php foreach ($ok  as $m): ?><div class="<?php echo strpos($m,'SKIP')===0?'skip':'ok'; ?>"><?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
<?php foreach ($err as $m): ?><div class="err"><?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
<p style="margin-top:20px;font-weight:bold;color:<?php echo empty($err)?'#1a7a1a':'#b71c1c'; ?>">
    <?php echo empty($err) ? '✓ Migración completada.' : '⚠ Hay errores.'; ?>
</p>
<a class="btn" href="<?php echo APP_URL; ?>/index.php">Volver al Dashboard</a>
</body></html>
