# 🏢 Sistema de Gestión - Condominio Terrazas

Sistema web completo para la administración de condominios de casas de playa, desarrollado en PHP puro, MySQL, HTML, CSS y JavaScript.

## 🎯 Características Principales

- ✅ **Gestión de Clientes/Propietarios** - CRUD completo con búsqueda avanzada
- ✅ **Control de Pagos** - Pagos mensuales de mantenimiento con estados
- ✅ **Facturación Electrónica** - Preparado para integración con SUNAT
- ✅ **Reuniones y Acuerdos** - Registro y seguimiento de reuniones
- ✅ **Dashboard Administrativo** - Estadísticas y gráficos en tiempo real
- ✅ **Panel del Cliente** - Vista personalizada para propietarios
- ✅ **Reportes Exportables** - Excel/CSV de todos los módulos
- ✅ **Sistema de Login** - Autenticación segura con roles
- ✅ **Diseño Responsivo** - Funciona en móvil, tablet y PC
- ✅ **Código Limpio y Modular** - Arquitectura MVC simple

## 🚀 Inicio Rápido

### 1. Instalar Base de Datos
```
1. Abrir phpMyAdmin
2. Importar: database/schema.sql
3. La BD se crea automáticamente
```

### 2. Acceder al Sistema
```
URL: http://localhost/condominioterrazas/login.php
Usuario: admin@condominioterrazas.com
Contraseña: admin123
```

### 3. ¡Listo!
El sistema está funcional. Revisar `INSTALL.md` para detalles completos.

## 📁 Estructura del Proyecto

```
condominioterrazas/
├── assets/              # CSS, JS, imágenes
├── config/              # Configuración y helpers
├── controllers/         # Controladores de la aplicación
├── database/            # Script SQL de la BD
├── models/              # Modelos de datos
├── uploads/             # Archivos subidos
├── views/               # Vistas y layouts
├── index.php            # Dashboard admin
└── login.php            # Página de login
```

## 🛠️ Tecnologías

- **Backend:** PHP 7.4+ (puro, sin frameworks)
- **Base de Datos:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript vanilla
- **Seguridad:** PDO, password_hash, sanitización, CSRF tokens
- **Diseño:** CSS custom properties, Flexbox, Grid

## 📊 Módulos

| Módulo | Descripción |
|--------|-------------|
| **Login** | Autenticación con roles (Admin/Cliente) |
| **Dashboard** | Panel con estadísticas y gráficos |
| **Clientes** | CRUD completo con búsqueda y filtros |
| **Pagos** | Registro, seguimiento y generación masiva |
| **Comprobantes** | Boletas/facturas listo para SUNAT |
| **Reuniones** | Gestión de reuniones con acuerdos |
| **Panel Cliente** | Vista personalizada para propietarios |
| **Reportes** | Exportación a Excel/CSV |
| **Usuarios** | Gestión de usuarios y roles |
| **Configuración** | Parámetros del sistema |

## 🔒 Seguridad

- ✅ Contraseñas hasheadas con bcrypt
- ✅ Consultas preparadas (SQL Injection)
- ✅ Sanitización de datos (XSS)
- ✅ Tokens CSRF en formularios
- ✅ Protección de archivos con .htaccess
- ✅ Sesiones seguras

## 📱 Responsivo

El sistema se adapta perfectamente a:
- 📱 Teléfonos móviles
- 📱 Tablets
- 💻 Computadoras

## 🎨 Personalización

### Cambiar colores
Editar `assets/css/styles.css`:
```css
:root {
    --color-primario: #2563eb;
    --color-exito: #10b981;
    /* etc. */
}
```

### Cambiar cuota de mantenimiento
Editar `config/config.php` o ir a Configuración en el panel admin.

## 🔌 Integración con SUNAT

El sistema está preparado para facturación electrónica real:

1. Obtener credenciales de proveedor PSE
2. Configurar en `config/config.php`:
   ```php
   define('SUNAT_API_URL', 'https://api.proveedor.pe/v1');
   define('SUNAT_API_KEY', 'tu_api_key');
   ```
3. El código de integración está comentado en `comprobante_controller.php`

## 📖 Documentación Completa

Ver `INSTALL.md` para:
- Instrucciones detalladas de instalación
- Configuración en XAMPP/Laragon
- Despliegue en servidor de producción
- Solución de problemas
- Backup y restauración

## 💾 Base de Datos

El script `database/schema.sql` crea:
- 8 tablas principales
- Relaciones con claves foráneas
- Índices para optimización
- Vista de resumen de pagos
- Procedimiento almacenado para actualización automática
- Datos iniciales de prueba

### Tablas Principales

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Usuarios del sistema (Admin/Cliente) |
| `clientes` | Propietarios del condominio |
| `pagos` | Pagos mensuales de mantenimiento |
| `comprobantes` | Boletas y facturas emitidas |
| `reuniones` | Reuniones del condominio |
| `acuerdos` | Acuerdos de cada reunión |
| `archivos_adjuntos` | Archivos de reuniones |
| `configuracion` | Parámetros del sistema |
| `auditoria` | Log de actividades |

## 🚀 Funcionalidades Destacadas

### Pagos Automáticos
- Generación masiva de pagos mensuales
- Cálculo automático de estados (pendiente/pagado/vencido)
- Múltiples métodos de pago (efectivo, yape, plin, transferencia)

### Facturación
- Numeración automática de comprobantes
- Impresión de boletas/facturas
- Preparado para envío a SUNAT
- Anulación de comprobantes

### Reuniones
- Timeline visual de reuniones
- Gestión de acuerdos con responsables
- Estados: Borrador, Publicado, Finalizado
- Archivos adjuntos

### Reportes
- Exportación a CSV (compatible Excel)
- Filtros por fecha, estado, cliente
- Vista de impresión para PDF

## 📝 Notas Importantes

1. ⚠️ **Cambiar credenciales de administrador inmediatamente**
2. 💾 Realizar backups regularmente
3. 🔒 Usar HTTPS en producción
4. 🔄 Mantener PHP y MySQL actualizados
5. ✅ Probar en desarrollo antes de producción

## 🐛 Solución de Problemas

Ver sección "Solución de Problemas" en `INSTALL.md` para:
- Errores de conexión a BD
- Problemas de permisos
- Configuración de .htaccess
- Y más...

## 📄 Licencia

Este proyecto es de uso interno para el Condominio Terrazas.

## 👨‍💻 Desarrollo

Desarrollado con las siguientes buenas prácticas:
- Código limpio y documentado
- Arquitectura MVC simple
- Patrón Singleton para BD
- Consultas preparadas (PDO)
- Validación en frontend y backend
- Namespaces y organización modular

---

**Versión:** 1.0.0  
**Fecha:** Abril 2026  
**Estado:** ✅ Funcional y listo para producción
