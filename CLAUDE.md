# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Sistema de Gestión para el Condominio Terrazas: PHP web app (no framework) for managing condo owners, monthly maintenance payments, SUNAT-ready receipts, meetings/agreements, and construction progress. Server-rendered, Spanish UI, targets PHP 7.4+ / MySQL 5.7+.

## Running / Developing

There is no build step, package manager, or test suite — it's plain PHP served by Apache.

- **Local server:** Laragon at `C:\laragon\www\condominioterrazas`, accessed via `http://localhost/condominioterrazas`.
- **Database setup:** Import `database/schema.sql` via phpMyAdmin. Creates DB `condominio_terrazas` with seed data.
- **Default login:** `admin@condominioterrazas.com` / `admin123`.
- **Entry points:** `login.php` (auth), `index.php` (admin dashboard). Everything else is reached through `controllers/*.php?accion=<name>`.
- **One-off migration scripts** live at repo root (`migrar_avances.php`, `migrar_avances_2.php`, `reset_admin_password.php`, `verificar_dni.php`, `listar_usuarios.php`) — run by hitting them in the browser. They are utilities, not part of the normal flow.

## Environment Switching

`config/config.php` auto-detects local vs production by inspecting `$_SERVER['HTTP_HOST']`:
- localhost / 127.0.0.1 / 192.168.* → local Laragon DB (`condominio_terrazas`, root/no password), `APP_ENV=development`, errors shown.
- anything else → InfinityFree production DB, `APP_ENV=production`, errors silenced. `APP_URL` is auto-derived from the request host.

Both credential sets are hardcoded in `config.php`. When adding env-sensitive config, follow the same `$is_local` branch pattern.

## Architecture

Simple MVC-ish layout, no router, no autoloader beyond a manual `require_once` list.

- **`config/autoload.php`** is the single bootstrap every page includes. It loads `config.php` → `helpers.php` → starts session → loads `database.php` → requires every model file. All controllers and top-level pages start with `require_once __DIR__ . '/../config/autoload.php';` (or `/config/autoload.php` from root). New models must be added to the require list in `autoload.php`.
- **`config/helpers.php`** provides globals used everywhere. Reach for these before inventing new ones.
- **Models** (`models/*.php`) extend `ModeloBase`, which provides `obtenerPorId`, `obtenerTodos`, `insertar`, `actualizar`, `eliminar`, `contar` using PDO prepared statements against `$this->tabla`. `Database` is a singleton — always access via `Database::getInstance()->getConnection()`.
- **Controllers** (`controllers/*.php`) are procedural: a top-level `switch ($_GET['accion'])` dispatches to functions in the same file. Auth gating goes at the very top of the file before the switch.
- **Views** use `ob_start()` / `ob_get_clean()` to capture a content block, then pass it to the layout via `vista('partials/admin-layout', compact('titulo','subtitulo','contenido','pagina_actual'))`. Use `vista('partials/cliente-layout', ...)` for the owner panel. Views can live in subdirectories (e.g., `views/avances/index.php` → `vista('avances/index', ...)`).

### Controller auth pattern

```php
require_once __DIR__ . '/../config/autoload.php';

// Admin-only controller:
requireAdmin();   // redirects to index.php if not admin; also calls requireAuth() implicitly

// Client-only controller:
requireCliente(); // redirects to index.php if not client

$accion = $_GET['accion'] ?? 'listar';
switch ($accion) { ... }
```

`requireAuth()`, `requireAdmin()`, and `requireCliente()` are defined in `autoload.php` and are the preferred shorthand over the raw `estaAutenticado() && esAdministrador()` checks.

### View rendering pattern

```php
ob_start();
// ... HTML output ...
$contenido = ob_get_clean();
vista('partials/admin-layout', compact('titulo', 'subtitulo', 'contenido', 'pagina_actual'));
```

## Conventions

- Spanish names for functions, variables, files, URL actions (`accion=listar`, `accion=crear`). Keep this consistent.
- All DB access must go through PDO prepared statements via `ModeloBase` methods or `$this->db->prepare(...)`. Never concatenate user input into SQL.
- Sanitize inputs with `sanear()` before use in filters/display. Use `e($valor)` (defined in `helpers.php`) for safe inline HTML echo.
- Money: `formatearMoneda()` + `CUOTA_MANTENIMIENTO` constant (S/. 70.00 default). Currency is Peruvian Soles (`MONEDA = 'S/.'`, `MONEDA_ISO = 'PEN'`).
- Timezone is `America/Lima` — set in `config.php`; don't override locally.
- Uploads go to `uploads/reuniones/` with size/type limits from `UPLOAD_MAX_SIZE` and `ALLOWED_FILE_TYPES`. Use `subirArchivo()` / `eliminarArchivo()` helpers.
- CSRF: generate a token with `generarTokenCSRF()` and include it in every POST form as a hidden field; validate with `validarTokenCSRF($_POST['csrf_token'])` at the top of any POST handler.
- User feedback after redirects: `setFlashMessage('success'|'error'|'warning'|'info', $mensaje)` before `redirigir(...)`, then `getFlashMessage()` in the view (it self-clears on read).
- Write operations should call `registrarAuditoria($db, $accion, $tabla, $id, $descripcion)` to log the activity.
- Pagination: use `obtenerPaginacion($total, $por_pagina, $pagina_actual)` which returns `['offset', 'total_paginas', 'tiene_anterior', 'tiene_siguiente', ...]`.

## Database

Schema lives only in `database/schema.sql`. Core tables: `usuarios`, `clientes`, `pagos`, `comprobantes`, `reuniones`, `acuerdos`, `archivos_adjuntos`, `configuracion`, `auditoria`, plus `avances` (construction progress, added via the `migrar_avances*.php` scripts). When changing schema, update `schema.sql` and provide an idempotent migration script at repo root if the change needs to be applied to existing installs.

Dynamic config values (e.g., cuota amount set by admin) are stored in the `configuracion` table as key-value pairs; read/write them with `getConfig($clave, $db)` / `setConfig($clave, $valor, $db)`.

## SUNAT Integration

Stubbed out in `config/config.php` (`SUNAT_*` constants, empty by default) and referenced but not wired in `controllers/comprobante_controller.php`. The receipts module generates numbered comprobantes locally; real e-invoicing requires filling in PSE credentials.
