<?php
/**
 * CONTROLADOR DE CONFIGURACIÓN
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'index';

switch ($accion) {
    case 'index':
        mostrarConfiguracion();
        break;
    case 'guardar':
        guardarConfiguracion();
        break;
    default:
        mostrarConfiguracion();
        break;
}

function mostrarConfiguracion() {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM configuracion ORDER BY clave";
    $stmt = $db->query($sql);
    $configuraciones = $stmt->fetchAll();
    
    // Convertir a array asociativo
    $config = [];
    foreach ($configuraciones as $cfg) {
        $config[$cfg['clave']] = $cfg['valor'];
    }
    
    $titulo = 'Configuración del Sistema';
    $subtitulo = 'Administra los parámetros del condominio';
    $pagina_actual = 'configuracion';
    
    ob_start();
    ?>

    <form method="POST" action="<?php echo APP_URL; ?>/controllers/config_controller.php?accion=guardar">
    <input type="hidden" name="csrf_token" value="<?php echo generarTokenCSRF(); ?>">

    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fas fa-building"></i> Información del Condominio</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre del Condominio</label>
                    <input type="text" name="nombre_condominio" class="form-control"
                           value="<?php echo $config['nombre_condominio'] ?? APP_NAME; ?>">
                </div>
                <div class="form-group">
                    <label>Razón Social</label>
                    <input type="text" name="razon_social" class="form-control"
                           value="<?php echo $config['razon_social'] ?? ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>RUC del Condominio</label>
                    <input type="text" name="ruc_condominio" class="form-control" maxlength="11" data-type="ruc"
                           value="<?php echo $config['ruc_condominio'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono de Contacto</label>
                    <input type="text" name="telefono_condominio" class="form-control"
                           value="<?php echo $config['telefono_condominio'] ?? ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Correo de Contacto</label>
                <input type="email" name="correo_condominio" class="form-control"
                       value="<?php echo $config['correo_condominio'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label>Dirección del Condominio</label>
                <input type="text" name="direccion_condominio" class="form-control"
                       value="<?php echo $config['direccion_condominio'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label>Dirección Fiscal (para facturación)</label>
                <input type="text" name="direccion_fiscal" class="form-control"
                       value="<?php echo $config['direccion_fiscal'] ?? ''; ?>">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fas fa-money-bill-wave"></i> Configuración de Pagos</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Cuota de Mantenimiento (S/.)</label>
                    <input type="number" name="cuota_mantenimiento" class="form-control" step="0.01" min="0"
                           value="<?php echo $config['cuota_mantenimiento'] ?? CUOTA_MANTENIMIENTO; ?>">
                </div>
                <div class="form-group">
                    <label>Moneda</label>
                    <input type="text" name="moneda" class="form-control" value="<?php echo $config['moneda'] ?? 'PEN'; ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fas fa-file-invoice"></i> Facturación Electrónica</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Serie de Boletas</label>
                    <input type="text" name="serie_boleta" class="form-control"
                           value="<?php echo $config['serie_boleta'] ?? 'B001'; ?>">
                </div>
                <div class="form-group">
                    <label>Serie de Facturas</label>
                    <input type="text" name="serie_factura" class="form-control"
                           value="<?php echo $config['serie_factura'] ?? 'F001'; ?>">
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Configuración de SUNAT:</strong> Para habilitar la facturación electrónica real,
                configure las credenciales en <code>config/config.php</code>.
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>API URL de SUNAT</label>
                    <input type="text" name="sunat_api_url" class="form-control" placeholder="https://api.facturador.pe/v1"
                           value="<?php echo SUNAT_API_URL; ?>" disabled>
                    <small style="color: var(--color-texto-claro);">Editar en config/config.php</small>
                </div>
                <div class="form-group">
                    <label>Ambiente</label>
                    <input type="text" class="form-control" value="<?php echo SUNAT_AMBIENTE; ?>" disabled>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Configuración
        </button>
    </div>

    </form>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function guardarConfiguracion() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir('controllers/config_controller.php?accion=index');
    }
    
    // Validar CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token de seguridad inválido');
        redirigir('controllers/config_controller.php?accion=index');
    }
    
    $db = Database::getInstance()->getConnection();
    
    $configuraciones = [
        'nombre_condominio',
        'razon_social',
        'ruc_condominio',
        'telefono_condominio',
        'correo_condominio',
        'direccion_condominio',
        'direccion_fiscal',
        'cuota_mantenimiento',
        'serie_boleta',
        'serie_factura'
    ];
    
    $guardados = 0;
    foreach ($configuraciones as $clave) {
        $valor = sanear($_POST[$clave] ?? '');
        if (setConfig($clave, $valor, $db)) {
            $guardados++;
        }
    }
    
    if ($guardados > 0) {
        registrarAuditoria($db, 'update', 'configuracion', null, "{$guardados} configuraciones actualizadas");
        setFlashMessage('success', "Configuración actualizada correctamente ({$guardados} campos)");
    } else {
        setFlashMessage('warning', 'No se realizaron cambios');
    }
    
    redirigir('controllers/config_controller.php?accion=index');
}
