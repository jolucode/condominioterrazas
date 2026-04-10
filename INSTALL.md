# SISTEMA DE GESTIÓN - CONDOMINIO TERRAZAS

## Manual de Instalación y Configuración

---

## 📋 REQUERIMIENTOS

### Servidor
- **Servidor Web:** Apache 2.4+ o Nginx
- **PHP:** 7.4 o superior
- **MySQL:** 5.7 o superior / MariaDB 10.3+
- **Espacio en disco:** Mínimo 100MB libres
- **RAM:** Mínimo 512MB

### Navegadores Soportados
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## 🚀 INSTALACIÓN EN XAMPP (WINDOWS)

### Paso 1: Instalar XAMPP
1. Descargar XAMPP desde: https://www.apachefriends.org/
2. Instalar XAMPP en `C:\xampp`
3. Iniciar los servicios **Apache** y **MySQL** desde el panel de XAMPP

### Paso 2: Copiar Archivos del Sistema
1. Copiar la carpeta `condominioterrazas` en:
   ```
   C:\xampp\htdocs\condominioterrazas
   ```
   O si usa Laragon (como en este caso):
   ```
   C:\laragon\www\condominioterrazas
   ```

### Paso 3: Crear Base de Datos
1. Abrir el navegador e ir a: http://localhost/phpmyadmin
2. Hacer clic en **"Importar"** (o "Import")
3. Seleccionar el archivo:
   ```
   condominioterrazas/database/schema.sql
   ```
4. Hacer clic en **"Continuar"** (o "Go")
5. La base de datos `condominio_terrazas` se creará automáticamente con todas las tablas

### Paso 4: Configurar Conexión a BD
1. Abrir el archivo: `config/config.php`
2. Verificar que los datos de conexión sean correctos:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'condominio_terrazas');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
3. Si tiene contraseña en MySQL, cambiar `DB_PASS`

### Paso 5: Configurar URL del Sistema
1. En el mismo archivo `config/config.php`, ajustar:
   ```php
   define('APP_URL', 'http://localhost/condominioterrazas');
   ```
   Si usa Laragon con dominio virtual:
   ```php
   define('APP_URL', 'http://condominioterrazas.test');
   ```

### Paso 6: Crear Carpeta de Uploads
1. Crear la carpeta `uploads/reuniones` si no existe:
   ```
   condominioterrazas/uploads/reuniones
   ```
2. Dar permisos de escritura a esta carpeta

### Paso 7: Acceder al Sistema
1. Abrir el navegador e ir a:
   ```
   http://localhost/condominioterrazas/login.php
   ```

2. **Credenciales de Administrador por defecto:**
   - **Correo:** `admin@condominioterrazas.com`
   - **Contraseña:** `admin123`

   ⚠️ **IMPORTANTE:** Cambiar esta contraseña inmediatamente después del primer acceso

---

## 🚀 INSTALACIÓN EN SERVIDOR LINUX (PRODUCCIÓN)

### Paso 1: Requisitos del Servidor
```bash
# Instalar Apache, PHP, MySQL (Ubuntu/Debian)
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql php-mbstring php-xml php-curl

# Instalar en CentOS/RHEL
sudo yum install httpd mysql-server php php-mysqlnd php-mbstring php-xml php-curl
```

### Paso 2: Subir Archivos
```bash
# Subir archivos al directorio del servidor
sudo cp -r condominioterrazas /var/www/html/
sudo chown -R www-data:www-data /var/www/html/condominioterrazas
sudo chmod -R 755 /var/www/html/condominioterrazas
```

### Paso 3: Crear Base de Datos
```bash
# Acceder a MySQL
mysql -u root -p

# Crear base de datos y usuario
CREATE DATABASE condominio_terrazas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'condominio_user'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON condominio_terrazas.* TO 'condominio_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar schema
mysql -u condominio_user -p condominio_terrazas < /var/www/html/condominioterrazas/database/schema.sql
```

### Paso 4: Configurar Aplicación
Editar `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'condominio_terrazas');
define('DB_USER', 'condominio_user');
define('DB_PASS', 'tu_contraseña_segura');
define('APP_URL', 'https://tudominio.com');
define('APP_ENV', 'production');
```

### Paso 5: Configurar Apache
Crear archivo de VirtualHost `/etc/apache2/sites-available/condominio.conf`:
```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/html/condominioterrazas
    
    <Directory /var/www/html/condominioterrazas>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Activar sitio y reiniciar Apache:
```bash
sudo a2ensite condominio
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Paso 6: Habilitar SSL (Recomendado)
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tudominio.com
```

---

## 📁 ESTRUCTURA DEL PROYECTO

```
condominioterrazas/
├── assets/
│   ├── css/
│   │   └── styles.css              # Estilos principales
│   ├── js/
│   │   └── main.js                 # JavaScript principal
│   └── img/                        # Imágenes del sistema
├── config/
│   ├── config.php                  # Configuración general
│   ├── database.php                # Clase de conexión PDO
│   ├── helpers.php                 # Funciones auxiliares
│   └── autoload.php                # Autocarga de clases
├── controllers/
│   ├── auth_controller.php         # Autenticación
│   ├── cliente_controller.php      # CRUD Clientes
│   ├── pago_controller.php         # CRUD Pagos
│   ├── comprobante_controller.php  # Facturación electrónica
│   ├── reunion_controller.php      # Reuniones y acuerdos
│   ├── cliente_panel.php           # Panel del cliente
│   ├── usuario_controller.php      # Gestión de usuarios
│   ├── reporte_controller.php      # Reportes
│   └── config_controller.php       # Configuración del sistema
├── database/
│   └── schema.sql                  # Script de base de datos
├── models/
│   ├── ModeloBase.php              # Clase base
│   ├── Usuario.php                 # Modelo usuarios
│   ├── Cliente.php                 # Modelo clientes
│   ├── Pago.php                    # Modelo pagos
│   ├── Comprobante.php             # Modelo comprobantes
│   ├── Reunion.php                 # Modelo reuniones
│   └── ArchivoAdjunto.php          # Modelo archivos
├── uploads/
│   └── reuniones/                  # Archivos adjuntos
├── views/
│   └── partials/
│       ├── admin-layout.php        # Layout administrador
│       ├── admin-sidebar.php       # Sidebar admin
│       ├── cliente-layout.php      # Layout cliente
│       └── cliente-sidebar.php     # Sidebar cliente
├── index.php                       # Dashboard admin
├── login.php                       # Página de login
└── .htaccess                       # Configuración Apache
```

---

## 🔐 SEGURIDAD

### Medidas Implementadas
1. **Contraseñas hasheadas** con `password_hash()` (bcrypt)
2. **Consultas preparadas** con PDO para prevenir SQL Injection
3. **Sanitización de datos** con `htmlspecialchars()` contra XSS
4. **Tokens CSRF** para proteger formularios
5. **Validación de datos** en frontend y backend
6. **Protección de archivos** con `.htaccess`
7. **Sesiones seguras** con configuración personalizada

### Recomendaciones de Seguridad
1. Cambiar credenciales de administrador por defecto
2. Usar HTTPS en producción
3. Actualizar PHP y MySQL regularmente
4. Realizar backups periódicos
5. Restringir acceso phpMyAdmin en producción
6. Configurar firewall del servidor

---

## 💾 BACKUP Y RESTAURACIÓN

### Realizar Backup
```bash
# Desde línea de comandos
mysqldump -u root -p condominio_terrazas > backup_$(date +%Y%m%d).sql

# Desde phpMyAdmin
# 1. Seleccionar base de datos
# 2. Clic en "Exportar"
# 3. Seleccionar formato SQL
# 4. Descargar archivo
```

### Restaurar Backup
```bash
# Desde línea de comandos
mysql -u root -p condominio_terrazas < backup_20260408.sql

# Desde phpMyAdmin
# 1. Seleccionar base de datos
# 2. Clic en "Importar"
# 3. Seleccionar archivo SQL
# 4. Ejecutar
```

---

## 📊 MÓDULOS DEL SISTEMA

### 1. Login y Autenticación
- Inicio de sesión con correo y contraseña
- Roles: Administrador y Cliente
- Cierre de sesión
- Protección de rutas

### 2. Gestión de Clientes (Admin)
- Crear, editar, eliminar clientes
- Buscar por nombre, DNI, lote
- Ver historial completo
- Crear usuario asociado al cliente

### 3. Gestión de Pagos (Admin)
- Registrar pagos manualmente
- Generar pagos mensuales masivos
- Marcar pagos como pagados
- Filtrar por estado, mes, año
- Ver estadísticas

### 4. Facturación Electrónica (SUNAT Ready)
- Emitir boletas o facturas
- Numeración automática
- Impresión de comprobantes
- Anulación de comprobantes
- **Preparado para integración con SUNAT**
  - Solo configurar credenciales en `config/config.php`
  - Código de ejemplo incluido en controlador

### 5. Reuniones y Acuerdos
- Crear reuniones con acuerdos
- Estados: Borrador, Publicado, Finalizado
- Timeline de reuniones
- Panel de acuerdos por reunión
- Vista para clientes

### 6. Dashboard Administrativo
- Estadísticas en tiempo real
- Gráficos de pagos por mes
- Accesos rápidos
- Últimos pagos registrados
- Próxima reunión

### 7. Panel del Cliente
- Ver estado de pagos
- Historial de pagos
- Comprobantes emitidos
- Reuniones y acuerdos publicados
- Información personal

### 8. Reportes
- Clientes registrados (Excel/PDF)
- Pagos por mes
- Pagos pendientes
- Pagos por cliente
- Historial de reuniones
- Exportación a CSV (compatible Excel)

### 9. Gestión de Usuarios
- Crear/editar usuarios
- Asignar roles
- Activar/desactivar
- Cambiar contraseñas

### 10. Configuración del Sistema
- Datos del condominio
- Cuota de mantenimiento
- Series de comprobantes
- Credenciales SUNAT

---

## 🔧 PERSONALIZACIÓN

### Cambiar Cuota de Mantenimiento
1. Ir a **Configuración** en el panel admin
2. Modificar "Cuota de Mantenimiento"
3. O editar `config/config.php`:
   ```php
   define('CUOTA_MANTENIMIENTO', 70.00);
   ```

### Cambiar Colores del Sistema
Editar `assets/css/styles.css`:
```css
:root {
    --color-primario: #2563eb;      /* Color principal */
    --color-primario-oscuro: #1e40af;
    --color-exito: #10b981;         /* Verde */
    --color-peligro: #ef4444;       /* Rojo */
    /* etc. */
}
```

### Integrar con SUNAT (Facturación Real)
1. Obtener credenciales de proveedor PSE
2. Editar `config/config.php`:
   ```php
   define('SUNAT_API_URL', 'https://api.proveedor-pe/v1');
   define('SUNAT_API_KEY', 'tu_api_key');
   define('SUNAT_RUC', 'tu_ruc');
   define('SUNAT_USUARIO_SOL', 'usuario_sol');
   define('SUNAT_CLAVE_SOL', 'clave_sol');
   ```
3. Implementar llamadas a API en `comprobante_controller.php`
   (código de ejemplo incluido en comentarios)

---

## ❓ SOLUCIÓN DE PROBLEMAS

### Error: "Error de conexión a la base de datos"
**Solución:**
1. Verificar que MySQL esté corriendo
2. Revisar credenciales en `config/config.php`
3. Confirmar que la base de datos existe

### Error: "Página no encontrada" o 404
**Solución:**
1. Verificar que `APP_URL` en `config/config.php` sea correcto
2. Comprobar que `.htaccess` existe
3. Activar `mod_rewrite` en Apache

### Error: "No se puede escribir en uploads"
**Solución:**
```bash
# Linux
chmod -R 775 uploads/
chown -R www-data:www-data uploads/

# Windows
# Clic derecho en carpeta > Propiedades > Seguridad
# Dar permisos de escritura
```

### Los pagos no se generan mensualmente
**Solución:**
- Ejecutar manualmente desde **Pagos > Generar Pagos Mensuales**
- O activar el evento MySQL:
  ```sql
  SET GLOBAL event_scheduler = ON;
  ```

### Sesión expira muy rápido
**Solución:**
Editar `config/config.php`:
```php
define('SESSION_LIFETIME', 7200); // 2 horas en segundos
```

---

## 📞 SOPORTE

Para soporte técnico o personalizaciones:
- Revisar la documentación en los comentarios del código
- Los controladores tienen ejemplos de integración SUNAT
- Las funciones auxiliares están en `config/helpers.php`

---

## 📝 NOTAS IMPORTANTES

1. **Cambiar credenciales de administrador inmediatamente**
2. **Realizar backups regularmente**
3. **Usar HTTPS en producción**
4. **No compartir credenciales de base de datos**
5. **Mantener PHP y MySQL actualizados**
6. **Probar en entorno de desarrollo antes de producción**

---

## ✅ CHECKLIST POST-INSTALACIÓN

- [ ] Cambiar contraseña de administrador
- [ ] Configurar datos del condominio
- [ ] Configurar cuota de mantenimiento
- [ ] Crear usuarios de prueba
- [ ] Crear clientes de prueba
- [ ] Generar pagos de prueba
- [ ] Configurar series de comprobantes
- [ ] Probar emisión de boletas/facturas
- [ ] Configurar backups automáticos
- [ ] Activar HTTPS (producción)
- [ ] Restringir acceso a phpMyAdmin (producción)

---

**Versión:** 1.0.0  
**Última actualización:** Abril 2026  
**Desarrollado con:** PHP 7.4+, MySQL 5.7+, HTML5, CSS3, JavaScript
