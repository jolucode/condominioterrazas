<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">CT</div>
            <div>
                <h1>Condominio</h1>
                <small>Mi Panel</small>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Mi Cuenta</div>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=inicio" class="nav-link <?php echo ($pagina_actual === 'inicio') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Inicio</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=mis_pagos" class="nav-link <?php echo ($pagina_actual === 'mis_pagos') ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Mis Pagos</span>
            </a>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=mis_comprobantes" class="nav-link <?php echo ($pagina_actual === 'mis_comprobantes') ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Mis Comprobantes</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Comunidad</div>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=reuniones" class="nav-link <?php echo ($pagina_actual === 'reuniones') ? 'active' : ''; ?>">
                <i class="fas fa-handshake"></i>
                <span>Reuniones y Acuerdos</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Mi Perfil</div>
            <a href="<?php echo APP_URL; ?>/controllers/cliente_panel.php?accion=mi_perfil" class="nav-link <?php echo ($pagina_actual === 'mi_perfil') ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Mi Perfil</span>
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
                <div class="role">Propietario</div>
            </div>
        </div>
    </div>
</aside>
