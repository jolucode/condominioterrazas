<?php
/**
 * CONTROLADOR DE REUNIONES
 */
require_once __DIR__ . '/../config/autoload.php';

if (!estaAutenticado() || !esAdministrador()) {
    redirigir('login.php');
}

$accion = $_GET['accion'] ?? 'listar';
$modelo_reunion = new Reunion();

switch ($accion) {
    case 'listar':
        listarReuniones();
        break;
    case 'crear':
        crearReunion();
        break;
    case 'editar':
        editarReunion();
        break;
    case 'ver':
        verReunion();
        break;
    case 'eliminar':
        eliminarReunion();
        break;
    case 'publicar':
        publicarReunion();
        break;
    default:
        redirigir('controllers/reunion_controller.php?accion=listar');
        break;
}

function listarReuniones() {
    global $modelo_reunion;
    
    $pagina = intval($_GET['pagina'] ?? 1);
    $estado = sanear($_GET['estado'] ?? '');
    
    $resultado = $modelo_reunion->listarPaginado($pagina, 10, $estado ?: null);
    $reuniones = $resultado['datos'];
    $paginacion = $resultado['paginacion'];
    
    $titulo = 'Reuniones y Acuerdos';
    $subtitulo = 'Gestiona las reuniones del condominio';
    $pagina_actual = 'reuniones';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <h3>Reuniones</h3>
                <span class="badge badge-info"><?php echo $paginacion['total_registros']; ?> registros</span>
            </div>
            <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=crear" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nueva Reunión
            </a>
        </div>
        
        <div class="card-body">
            <?php if (!empty($reuniones)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Lugar</th>
                                <th>Acuerdos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reuniones as $reunion): ?>
                                <tr>
                                    <td><strong><?php echo $reunion['titulo']; ?></strong></td>
                                    <td><?php echo formatearFecha($reunion['fecha_reunion']); ?></td>
                                    <td><?php echo $reunion['lugar'] ?: '-'; ?></td>
                                    <td><span class="badge badge-info"><?php echo $reunion['total_acuerdos']; ?></span></td>
                                    <td>
                                        <span class="badge <?php echo claseEstadoReunion($reunion['estado']); ?>">
                                            <?php echo textoEstadoReunion($reunion['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=ver&id=<?php echo $reunion['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=editar&id=<?php echo $reunion['id']; ?>" 
                                           class="btn btn-sm btn-outline" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($reunion['estado'] === 'borrador'): ?>
                                            <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=publicar&id=<?php echo $reunion['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Publicar">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=eliminar&id=<?php echo $reunion['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           data-confirm-delete="¿Está seguro de eliminar esta reunión y todos sus acuerdos?">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($paginacion['total_paginas'] > 1): ?>
                    <div class="pagination">
                        <?php if ($paginacion['tiene_anterior']): ?>
                            <a href="?accion=listar&pagina=<?php echo $paginacion['pagina_actual'] - 1; ?>&estado=<?php echo urlencode($estado); ?>">Anterior</a>
                        <?php endif; ?>
                        <span class="active"><?php echo $paginacion['pagina_actual']; ?></span>
                        <?php if ($paginacion['tiene_siguiente']): ?>
                            <a href="?accion=listar&pagina=<?php echo $paginacion['pagina_actual'] + 1; ?>&estado=<?php echo urlencode($estado); ?>">Siguiente</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-handshake"></i>
                    <h3>No hay reuniones registradas</h3>
                    <p>Crea una nueva reunión para comenzar</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function crearReunion() {
    global $modelo_reunion;
    $errores = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titulo = sanear($_POST['titulo'] ?? '');
        $descripcion = sanear($_POST['descripcion'] ?? '');
        $fecha_reunion = sanear($_POST['fecha_reunion'] ?? '');
        $hora_reunion = sanear($_POST['hora_reunion'] ?? '');
        $lugar = sanear($_POST['lugar'] ?? '');
        $proxima_fecha = sanear($_POST['proxima_fecha'] ?? '');
        $estado = sanear($_POST['estado'] ?? 'borrador');
        $acuerdos = $_POST['acuerdos'] ?? [];
        
        if (empty($titulo)) $errores[] = 'El título es requerido';
        if (empty($descripcion)) $errores[] = 'La descripción es requerida';
        if (empty($fecha_reunion)) $errores[] = 'La fecha de reunión es requerida';
        
        // Filtrar acuerdos vacíos
        $acuerdos = array_filter($acuerdos, function($a) {
            return !empty($a['descripcion']);
        });
        
        if (empty($errores)) {
            $datos_reunion = [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'fecha_reunion' => $fecha_reunion,
                'hora_reunion' => $hora_reunion ?: null,
                'lugar' => $lugar,
                'proxima_fecha' => $proxima_fecha ?: null,
                'estado' => $estado,
                'creado_por' => $_SESSION['usuario_id']
            ];
            
            $reunion_id = $modelo_reunion->crearConAcuerdos($datos_reunion, $acuerdos);
            
            if ($reunion_id) {
                $db = Database::getInstance()->getConnection();
                registrarAuditoria($db, 'create', 'reuniones', $reunion_id, "Reunión creada: {$titulo}");
                setFlashMessage('success', 'Reunión creada exitosamente');
                redirigir('controllers/reunion_controller.php?accion=ver&id=' . $reunion_id);
            } else {
                $errores[] = 'Error al crear la reunión';
            }
        }
    }
    
    $titulo = 'Nueva Reunión';
    $subtitulo = 'Registrar una nueva reunión';
    $pagina_actual = 'reuniones';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Nueva Reunión</h3>
            <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=listar" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" data-validate>
                <div class="form-group">
                    <label>Título <span class="required">*</span></label>
                    <input type="text" name="titulo" class="form-control" required 
                           value="<?php echo isset($_POST['titulo']) ? $_POST['titulo'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Descripción <span class="required">*</span></label>
                    <textarea name="descripcion" class="form-control" rows="3" required><?php echo isset($_POST['descripcion']) ? $_POST['descripcion'] : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Reunión <span class="required">*</span></label>
                        <input type="date" name="fecha_reunion" class="form-control" required
                               value="<?php echo isset($_POST['fecha_reunion']) ? $_POST['fecha_reunion'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Hora</label>
                        <input type="time" name="hora_reunion" class="form-control"
                               value="<?php echo isset($_POST['hora_reunion']) ? $_POST['hora_reunion'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Lugar</label>
                        <input type="text" name="lugar" class="form-control"
                               value="<?php echo isset($_POST['lugar']) ? $_POST['lugar'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Próxima Fecha de Reunión</label>
                    <input type="date" name="proxima_fecha" class="form-control"
                           value="<?php echo isset($_POST['proxima_fecha']) ? $_POST['proxima_fecha'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Acuerdos</label>
                    <div id="acuerdos-container">
                        <?php 
                        $acuerdos = isset($_POST['acuerdos']) ? $_POST['acuerdos'] : [[]];
                        $index = 0;
                        foreach ($acuerdos as $acuerdo): 
                        ?>
                            <div class="acuerdo-item" style="padding: 0.75rem; background: var(--color-fondo); border-radius: var(--radio); margin-bottom: 0.5rem;">
                                <div class="form-row">
                                    <div class="form-group" style="flex: 3;">
                                        <input type="text" name="acuerdos[<?php echo $index; ?>][descripcion]" class="form-control" 
                                               placeholder="Descripción del acuerdo" 
                                               value="<?php echo isset($acuerdo['descripcion']) ? $acuerdo['descripcion'] : ''; ?>">
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <input type="text" name="acuerdos[<?php echo $index; ?>][responsable]" class="form-control" 
                                               placeholder="Responsable"
                                               value="<?php echo isset($acuerdo['responsable']) ? $acuerdo['responsable'] : ''; ?>">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger remove-acuerdo" style="align-self: center;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        <?php 
                            $index++;
                        endforeach; 
                        ?>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm mt-2" data-add-acuerdo>
                        <i class="fas fa-plus"></i> Agregar Acuerdo
                    </button>
                </div>
                
                <script type="text/template" id="acuerdo-template">
                    <div class="acuerdo-item" style="padding: 0.75rem; background: var(--color-fondo); border-radius: var(--radio); margin-bottom: 0.5rem;">
                        <div class="form-row">
                            <div class="form-group" style="flex: 3;">
                                <input type="text" name="acuerdos[__INDEX__][descripcion]" class="form-control" placeholder="Descripción del acuerdo">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <input type="text" name="acuerdos[__INDEX__][responsable]" class="form-control" placeholder="Responsable">
                            </div>
                            <button type="button" class="btn btn-sm btn-danger remove-acuerdo" style="align-self: center;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </script>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Reunión
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=listar" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function editarReunion() {
    global $modelo_reunion;
    $id = intval($_GET['id'] ?? 0);
    $reunion = $modelo_reunion->obtenerConAcuerdos($id);
    
    if (!$reunion) {
        setFlashMessage('error', 'Reunión no encontrada');
        redirigir('controllers/reunion_controller.php?accion=listar');
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titulo = sanear($_POST['titulo'] ?? '');
        $descripcion = sanear($_POST['descripcion'] ?? '');
        $fecha_reunion = sanear($_POST['fecha_reunion'] ?? '');
        $hora_reunion = sanear($_POST['hora_reunion'] ?? '');
        $lugar = sanear($_POST['lugar'] ?? '');
        $proxima_fecha = sanear($_POST['proxima_fecha'] ?? '');
        $estado = sanear($_POST['estado'] ?? 'borrador');
        $acuerdos = $_POST['acuerdos'] ?? [];
        
        $acuerdos = array_filter($acuerdos, function($a) {
            return !empty($a['descripcion']);
        });
        
        $datos_reunion = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'fecha_reunion' => $fecha_reunion,
            'hora_reunion' => $hora_reunion ?: null,
            'lugar' => $lugar,
            'proxima_fecha' => $proxima_fecha ?: null,
            'estado' => $estado
        ];
        
        if ($modelo_reunion->actualizarConAcuerdos($id, $datos_reunion, $acuerdos)) {
            setFlashMessage('success', 'Reunión actualizada exitosamente');
            redirigir('controllers/reunion_controller.php?accion=ver&id=' . $id);
        } else {
            setFlashMessage('error', 'Error al actualizar la reunión');
        }
    }
    
    $titulo = 'Editar Reunión';
    $subtitulo = 'Actualizar información de la reunión';
    $pagina_actual = 'reuniones';
    
    ob_start();
    ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Editar Reunión</h3>
            <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=listar" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <div class="form-group">
                    <label>Título <span class="required">*</span></label>
                    <input type="text" name="titulo" class="form-control" required value="<?php echo $reunion['titulo']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Descripción <span class="required">*</span></label>
                    <textarea name="descripcion" class="form-control" rows="3" required><?php echo $reunion['descripcion']; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha de Reunión <span class="required">*</span></label>
                        <input type="date" name="fecha_reunion" class="form-control" required value="<?php echo $reunion['fecha_reunion']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Hora</label>
                        <input type="time" name="hora_reunion" class="form-control" value="<?php echo $reunion['hora_reunion'] ?: ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Lugar</label>
                        <input type="text" name="lugar" class="form-control" value="<?php echo $reunion['lugar'] ?: ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Próxima Fecha de Reunión</label>
                    <input type="date" name="proxima_fecha" class="form-control" value="<?php echo $reunion['proxima_fecha'] ?: ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="borrador" <?php echo $reunion['estado'] === 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                        <option value="publicado" <?php echo $reunion['estado'] === 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                        <option value="finalizado" <?php echo $reunion['estado'] === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Acuerdos</label>
                    <div id="acuerdos-container">
                        <?php if (!empty($reunion['acuerdos'])): ?>
                            <?php $index = 0; foreach ($reunion['acuerdos'] as $acuerdo): ?>
                                <div class="acuerdo-item" style="padding: 0.75rem; background: var(--color-fondo); border-radius: var(--radio); margin-bottom: 0.5rem;">
                                    <div class="form-row">
                                        <div class="form-group" style="flex: 3;">
                                            <input type="text" name="acuerdos[<?php echo $index; ?>][descripcion]" class="form-control" 
                                                   value="<?php echo $acuerdo['descripcion']; ?>">
                                        </div>
                                        <div class="form-group" style="flex: 1;">
                                            <input type="text" name="acuerdos[<?php echo $index; ?>][responsable]" class="form-control" 
                                                   value="<?php echo $acuerdo['responsable'] ?: ''; ?>">
                                        </div>
                                        <button type="button" class="btn btn-sm btn-danger remove-acuerdo" style="align-self: center;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php $index++; endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm mt-2" data-add-acuerdo>
                        <i class="fas fa-plus"></i> Agregar Acuerdo
                    </button>
                </div>
                
                <script type="text/template" id="acuerdo-template">
                    <div class="acuerdo-item" style="padding: 0.75rem; background: var(--color-fondo); border-radius: var(--radio); margin-bottom: 0.5rem;">
                        <div class="form-row">
                            <div class="form-group" style="flex: 3;">
                                <input type="text" name="acuerdos[__INDEX__][descripcion]" class="form-control" placeholder="Descripción del acuerdo">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <input type="text" name="acuerdos[__INDEX__][responsable]" class="form-control" placeholder="Responsable">
                            </div>
                            <button type="button" class="btn btn-sm btn-danger remove-acuerdo" style="align-self: center;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </script>
                
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Reunión
                    </button>
                    <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=listar" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function verReunion() {
    global $modelo_reunion;
    $id = intval($_GET['id'] ?? 0);
    $reunion = $modelo_reunion->obtenerConAcuerdos($id);
    
    if (!$reunion) {
        setFlashMessage('error', 'Reunión no encontrada');
        redirigir('controllers/reunion_controller.php?accion=listar');
    }
    
    $titulo = $reunion['titulo'];
    $subtitulo = 'Detalle de la reunión';
    $pagina_actual = 'reuniones';
    
    ob_start();
    ?>
    
    <div class="card mb-3">
        <div class="card-header">
            <h3><?php echo $reunion['titulo']; ?></h3>
            <div class="d-flex gap-1">
                <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=editar&id=<?php echo $reunion['id']; ?>" 
                   class="btn btn-outline btn-sm">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=listar" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex gap-3 mb-3" style="flex-wrap: wrap;">
                <div>
                    <i class="fas fa-calendar"></i> <strong>Fecha:</strong> <?php echo formatearFecha($reunion['fecha_reunion']); ?>
                </div>
                <?php if ($reunion['hora_reunion']): ?>
                    <div>
                        <i class="fas fa-clock"></i> <strong>Hora:</strong> <?php echo date('H:i', strtotime($reunion['hora_reunion'])); ?>
                    </div>
                <?php endif; ?>
                <?php if ($reunion['lugar']): ?>
                    <div>
                        <i class="fas fa-map-marker-alt"></i> <strong>Lugar:</strong> <?php echo $reunion['lugar']; ?>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="badge <?php echo claseEstadoReunion($reunion['estado']); ?>">
                        <?php echo textoEstadoReunion($reunion['estado']); ?>
                    </span>
                </div>
            </div>
            
            <div style="padding: 1rem; background: var(--color-fondo); border-radius: var(--radio); margin-bottom: 1.5rem;">
                <strong>Descripción:</strong><br>
                <?php echo nl2br($reunion['descripcion']); ?>
            </div>
            
            <?php if ($reunion['proxima_fecha']): ?>
                <div class="alert alert-info">
                    <i class="fas fa-calendar-check"></i>
                    <strong>Próxima reunión:</strong> <?php echo formatearFecha($reunion['proxima_fecha']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($reunion['acuerdos'])): ?>
                <h4 style="margin-bottom: 1rem;"><i class="fas fa-handshake"></i> Acuerdos</h4>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Acuerdo</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reunion['acuerdos'] as $i => $acuerdo): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo $acuerdo['descripcion']; ?></td>
                                    <td><?php echo $acuerdo['responsable'] ?: '-'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $acuerdo['estado'] === 'cumplido' ? 'badge-success' : ($acuerdo['estado'] === 'en_proceso' ? 'badge-warning' : 'badge-secondary'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $acuerdo['estado'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $contenido = ob_get_clean();
    vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
}

function eliminarReunion() {
    global $modelo_reunion;
    $id = intval($_GET['id'] ?? 0);
    
    if ($modelo_reunion->eliminar($id)) {
        setFlashMessage('success', 'Reunión eliminada exitosamente');
    } else {
        setFlashMessage('error', 'Error al eliminar la reunión');
    }
    
    redirigir('controllers/reunion_controller.php?accion=listar');
}

function publicarReunion() {
    global $modelo_reunion;
    $id = intval($_GET['id'] ?? 0);
    
    if ($modelo_reunion->actualizar($id, ['estado' => 'publicado'])) {
        setFlashMessage('success', 'Reunión publicada exitosamente');
    } else {
        setFlashMessage('error', 'Error al publicar la reunión');
    }
    
    redirigir('controllers/reunion_controller.php?accion=listar');
}
