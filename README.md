# Landing - Lumina

Landing page estática pensada para servir desde XAMPP.

## Abrir
- URL local típica: `http://localhost/Landing/`
- Archivo principal: `index.html`

## Deploy (Hostinger)
- Sube el contenido a `public_html/` (o a una subcarpeta).
- Importante: `api/config/config.php` **no se sube al repo** (está en `.gitignore`).
	- Opción recomendada: crea `api/config/config.php` en el servidor copiando `api/config/config.example.php` y cambia `admin.key`.
	- Alternativa: define `SetEnv LUMINA_ADMIN_KEY "TU_CLAVE"` en tu `.htaccess` (ver detalles en `api/README.md`).

## Personalizar
- Edita el copy y links en `index.html`.
- Reemplaza placeholders de screenshots en la sección “Vista del producto”.
- `assets/favicon.svg` y `assets/og-image.svg` son editables.

## Nota sobre formulario
El formulario está listo en UI pero no envía emails por defecto (para evitar depender de configuración SMTP de XAMPP). Si quieres, puedo agregar un `contact.php` con validación + envío (requiere configurar mail/SMTP en tu servidor).
