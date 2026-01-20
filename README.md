# Landing — Lumina CRM

Landing corporativo (HTML/CSS/JS) + mini-backend PHP para guardar solicitudes del formulario.

## Abrir (local / XAMPP)
- URL típica: `http://localhost/Landing/`
- Archivo principal: `index.html`
- Admin (si estás en XAMPP y el proyecto está en `/Landing`): `http://localhost/Landing/api/admin/`

## Deploy (Hostinger)
1. Sube el contenido a `public_html/` (o a una subcarpeta).
2. Crea `api/config/config.php` en el servidor (NO se versiona; está en `.gitignore`).
	 - Copia `api/config/config.example.php` → `api/config/config.php`.
	 - Configura credenciales del admin (`admin.user` / `admin.pass`) y/o la key (`admin.key`).
3. Verifica permisos de escritura:
	 - `api/storage/` debe poder escribirse por PHP para crear/actualizar `leads.jsonl` y `ratelimit.json`.

### Rutas en Hostinger (root vs subcarpeta)
- Si el dominio apunta a la carpeta donde está el proyecto (ej. `public_html/inicio` como raíz del dominio), entonces las rutas públicas son:
	- Admin: `/api/admin/`
	- Endpoint: `/api/contact.php`
- Si subiste el proyecto a una subcarpeta (ej. `/Landing`), entonces:
	- Admin: `/Landing/api/admin/`
	- Endpoint: `/Landing/api/contact.php`

## Admin (seguridad)
- Recomendado: login por sesión (usuario/contraseña) configurado en `api/config/config.php`.
- Fallback: `?key=...` si NO hay `admin.user/admin.pass` configurados.
	- Usa una key URL-safe (evita `#` porque es un fragment y NO llega al servidor).

## Personalizar
- Edita el copy y links en `index.html`.
- Reemplaza screenshots/imagenes de la sección “Vista del producto”.
- `assets/favicon.svg` y `assets/og-image.svg` son editables.

## Formulario
- El formulario guarda solicitudes en JSONL (no envía email por defecto).
- Si luego quieres envío por SMTP (PHPMailer), se puede añadir como mejora.

## Documentación
- Backend y configuración: `api/README.md`
- Proceso, checklist y troubleshooting: `docs/PROCESO.md`
