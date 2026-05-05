<?php
/**
 * MIGRACIÓN: Crea usuarios de acceso para clientes que no tienen cuenta
 * Usuario = correo, Contraseña = DNI
 * Ejecutar desde: http://localhost/condominioterrazas/migrar_usuarios_clientes.php
 */
require_once __DIR__ . '/config/autoload.php';
requireAdmin();

$db           = Database::getInstance()->getConnection();
$modelo_usuario = new Usuario();
$creados      = 0;
$omitidos     = 0;
$detalle      = [];

// Clientes activos sin usuario, agrupados por correo (un usuario por correo)
$stmt = $db->query(
    "SELECT c.id, c.nombres, c.apellidos, c.dni, c.correo
     FROM clientes c
     WHERE c.correo IS NOT NULL
       AND c.correo != ''
       AND NOT EXISTS (
           SELECT 1 FROM usuarios u WHERE u.cliente_id = c.id
       )
     ORDER BY c.correo, c.id ASC"
);
$clientes = $stmt->fetchAll();

$correos_procesados = [];

foreach ($clientes as $c) {
    $correo = strtolower(trim($c['correo']));

    // Un correo = un usuario. Si ya procesamos este correo, solo vinculamos si aún no tiene usuario
    if (in_array($correo, $correos_procesados)) {
        $omitidos++;
        $detalle[] = [
            'tipo'    => 'omitido',
            'mensaje' => "ID {$c['id']} — {$c['nombres']} {$c['apellidos']} Lote: ya existe usuario para {$correo}",
        ];
        continue;
    }

    // Verificar que el correo no esté tomado por otro usuario existente
    if ($modelo_usuario->correoExiste($correo)) {
        $omitidos++;
        $correos_procesados[] = $correo;
        $detalle[] = [
            'tipo'    => 'omitido',
            'mensaje' => "ID {$c['id']} — {$c['nombres']} {$c['apellidos']}: correo {$correo} ya tiene usuario",
        ];
        continue;
    }

    $uid = $modelo_usuario->crearUsuario([
        'nombre_completo' => $c['nombres'] . ' ' . $c['apellidos'],
        'correo'          => $correo,
        'password'        => $c['dni'],
        'rol'             => 'cliente',
        'cliente_id'      => $c['id'],
    ]);

    if ($uid) {
        $creados++;
        $correos_procesados[] = $correo;
        $detalle[] = [
            'tipo'    => 'creado',
            'mensaje' => "ID {$c['id']} — {$c['nombres']} {$c['apellidos']} → usuario: {$correo} / contraseña: {$c['dni']}",
        ];
        registrarAuditoria($db, 'create', 'usuarios', $uid,
            "Usuario creado por migración para cliente ID {$c['id']}");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Migración Usuarios Clientes</title>
    <style>
        body { font-family: monospace; max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h2   { border-bottom: 2px solid #333; padding-bottom: 8px; }
        .ok  { color: #1a7a1a; background: #e8f5e9; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        .skip{ color: #555;    background: #f5f5f5; padding: 6px 12px; margin: 4px 0; border-radius: 4px; }
        .sum { font-size: 1.1rem; font-weight: bold; margin-top: 1.5rem; padding: 1rem;
               background: #e3f2fd; border-radius: 6px; }
        a.btn{ display: inline-block; margin-top: 20px; padding: 10px 20px;
               background: #1976d2; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
<h2>Migración: Usuarios para clientes sin cuenta</h2>

<?php foreach ($detalle as $d): ?>
    <div class="<?php echo $d['tipo'] === 'creado' ? 'ok' : 'skip'; ?>">
        <?php echo $d['tipo'] === 'creado' ? '✓' : '↷'; ?>
        <?php echo htmlspecialchars($d['mensaje']); ?>
    </div>
<?php endforeach; ?>

<?php if (empty($detalle)): ?>
    <div class="skip">No hay clientes pendientes de usuario.</div>
<?php endif; ?>

<div class="sum">
    ✓ <?php echo $creados; ?> usuario(s) creado(s) &nbsp;·&nbsp;
    <?php echo $omitidos; ?> omitido(s) (correo duplicado)
</div>

<a class="btn" href="<?php echo APP_URL; ?>/index.php">Volver al Dashboard</a>
</body>
</html>
