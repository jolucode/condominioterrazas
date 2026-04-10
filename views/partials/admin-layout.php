<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo) ? $titulo . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($css_extra)): ?>
        <?php foreach ($css_extra as $css): ?>
            <link rel="stylesheet" href="<?php echo APP_URL . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php partial('admin-sidebar', ['pagina_actual' => isset($pagina_actual) ? $pagina_actual : '']); ?>
        
        <!-- Overlay for mobile -->
        <div class="sidebar-overlay"></div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" aria-label="Menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <?php if (isset($titulo)): ?>
                            <h2><?php echo $titulo; ?></h2>
                        <?php endif; ?>
                        <?php if (isset($subtitulo)): ?>
                            <p><?php echo $subtitulo; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="topbar-right">
                    <a href="<?php echo APP_URL; ?>/controllers/auth_controller.php?accion=cerrar" class="btn btn-outline btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content">
                <?php
                $flash = getFlashMessage();
                if ($flash):
                ?>
                    <div class="alert alert-<?php echo $flash['tipo']; ?> alert-dismissible">
                        <i class="fas fa-<?php echo $flash['tipo'] === 'success' ? 'check-circle' : ($flash['tipo'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                        <?php echo $flash['mensaje']; ?>
                        <button class="close" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($contenido)): ?>
                    <?php echo $contenido; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    <?php if (isset($js_extra)): ?>
        <?php foreach ($js_extra as $js): ?>
            <script src="<?php echo APP_URL . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
