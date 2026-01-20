# Lumina CRM — Mini backend (PHP)

Este backend es minimalista: recibe el formulario del landing, valida, aplica anti-spam básico y persiste las solicitudes en un archivo JSONL.

## Endpoints
- `POST /api/contact.php` (o `/Landing/api/contact.php` si estás en subcarpeta)
	- Acepta JSON (`Content-Type: application/json`) o form (`multipart/form-data` / `application/x-www-form-urlencoded`).
	- Responde JSON `{ ok: true, id: "..." }`.

## Persistencia
- Leads: `api/storage/leads.jsonl` (1 JSON por línea)
- Rate limit: `api/storage/ratelimit.json`

## Configuración
Por seguridad, `api/config/config.php` NO se commitea al repo.

### Opción recomendada: crear config.php en el servidor
1. Copia `api/config/config.example.php` → `api/config/config.php`.
2. Ajusta:
	 - `admin.user` y `admin.pass` (recomendado) para login por sesión.
	 - `admin.key` (fallback) para `?key=...`.

### Overrides por variables de entorno (si tu hosting lo soporta)
El loader soporta `LUMINA_ADMIN_KEY`. En el panel admin también se leen `LUMINA_ADMIN_USER` y `LUMINA_ADMIN_PASS`.

Ejemplo en `.htaccess` (si `SetEnv` funciona en tu host):
- `SetEnv LUMINA_ADMIN_USER "admin"`
- `SetEnv LUMINA_ADMIN_PASS "CONTRASEÑA_LARGA"`
- `SetEnv LUMINA_ADMIN_KEY "KEY_LARGA_URL_SAFE"`

## Admin
- URL:
	- Root del dominio: `/api/admin/`
	- Subcarpeta (ej. `/Landing`): `/Landing/api/admin/`

### Autenticación (orden)
1. Si hay `admin.user`/`admin.pass` (por env o config), se usa login por sesión (más compatible en hosting compartido).
2. Si NO hay credenciales, se usa fallback `?key=...`.
	 - Importante: evita `#` en la key (es un fragmento del navegador y no llega al servidor).

### Export y diagnóstico
- CSV: `?format=csv`
- Debug JSON (requiere estar autenticado): `?debug=1`
	- Devuelve estado del archivo (exists/readable/size/perms) + conteo de filas.

## Notas
- Anti-spam: honeypot + rate limit básico por IP.
- Envío de email está deshabilitado por defecto (`email.enabled=false`). Si luego quieres SMTP (PHPMailer), se puede agregar.
