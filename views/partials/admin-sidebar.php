<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">CT</div>
            <div>
                <h1>Condominio</h1>
                <small>Sistema de Gestión</small>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <a href="<?php echo APP_URL; ?>/index.php" class="nav-link <?php echo ($pagina_actual === 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Administración</div>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_controller.php?accion=listar" class="nav-link <?php echo ($pagina_actual === 'clientes') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/pago_controller.php?accion=listar" class="nav-link <?php echo ($pagina_actual === 'pagos') ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Pagos</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/comprobante_controller.php?accion=listar" class="nav-link <?php echo ($pagina_actual === 'comprobantes') ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Comprobantes</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Comunidad</div>
            <a href="<?php echo APP_URL; ?>/controllers/avance_controller.php?accion=listar" class="nav-link <?php echo ($pagina_actual === 'avances') ? 'active' : ''; ?>">
                <i class="fas fa-images"></i>
                <span>Avances del Condominio</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/reunion_controller.php?accion=listar" class="nav-link <?php echo ($pagina_actual === 'reuniones') ? 'active' : ''; ?>">
                <i class="fas fa-handshake"></i>
                <span>Reuniones y Acuerdos</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Reportes</div>
            <a href="<?php echo APP_URL; ?>/controllers/reporte_controller.php?accion=index" class="nav-link <?php echo ($pagina_actual === 'reportes') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Sistema</div>
            <a href="<?php echo APP_URL; ?>/controllers/usuario_controller.php?accion=listar" class="nav-link <?php echo ($pagina_actual === 'usuarios') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>Usuarios</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/config_controller.php?accion=index" class="nav-link <?php echo ($pagina_actual === 'configuracion') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 2)); ?>
            </div>
            <div class="user-details">
                <div class="name"><?php echo $_SESSION['usuario_nombre']; ?></div>
                <div class="role">Administrador</div>
            </div>
        </div>
    </div>
</aside>
