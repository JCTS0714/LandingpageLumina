# Proceso (Lumina CRM Landing + Backend + Admin)

Este documento resume el trabajo realizado para dejar el landing operativo con un backend PHP mínimo, panel admin y deploy en hosting compartido (Hostinger).

## Objetivo
- Landing corporativo (HTML/CSS/JS) con formulario de “solicitar demo”.
- Backend PHP simple para recibir el formulario y guardar leads sin depender de base de datos.
- Panel admin para ver solicitudes, buscar y exportar CSV.
- Deploy en hosting compartido con hardening razonable.

## Estructura del proyecto
- `index.html`: landing principal + JS mínimo para enviar el formulario.
- `api/contact.php`: endpoint que valida y guarda leads en JSONL.
- `api/admin/index.php`: panel admin (login por sesión o fallback por key).
- `api/src/*`: helpers (config, lectura de JSONL, rate limit, etc.).
- `api/config/config.example.php`: ejemplo versionado.
- `api/config/config.php`: configuración real (NO versionada).
- `api/storage/`: archivos de datos (`leads.jsonl`, `ratelimit.json`).
- `.htaccess` y `api/**/.htaccess`: reglas compatibles con hosting y bloqueo de carpetas sensibles.

## Decisiones clave
- Persistencia en JSONL: simple, portable y suficiente para un landing.
- Sin envío de emails por defecto: evita depender de SMTP/`mail()` del servidor.
- Seguridad del admin:
  - Recomendado: usuario/contraseña con sesión (no expone secretos en URL).
  - Fallback: `?key=...` si no hay credenciales configuradas.

## Configuración (servidor)
1. Crear `api/config/config.php` copiando `api/config/config.example.php`.
2. Definir credenciales admin (recomendado):
   - `admin.user`
   - `admin.pass`
3. Definir key de fallback (opcional):
   - `admin.key` (usar string URL-safe, sin `#`, sin espacios)
4. Verificar permisos de escritura:
   - PHP debe poder escribir en `api/storage/`.

## Deploy en Hostinger (checklist)
- Subir el proyecto a la carpeta que realmente sirve el dominio.
  - Si tu dominio apunta a `public_html/inicio` como raíz, entonces las rutas serán `/api/...`.
  - Si subes a una subcarpeta, las rutas serán `/<subcarpeta>/api/...`.
- Crear `api/config/config.php` en el servidor.
- Confirmar que `api/storage/` permite escritura.
- Probar:
  - Landing carga correctamente.
  - Enviar formulario y confirmar respuesta `ok:true`.
  - Abrir panel admin y verificar que aparece el lead.

## Troubleshooting (casos reales)

### 1) Error 500 (Internal Server Error)
Causa típica: reglas inválidas en `.htaccess` (directivas que no aplican en este contexto).
- Solución: usar solo directivas permitidas en `.htaccess` (por ejemplo, `RewriteRule`, `Options -Indexes`, etc.).

### 2) 403 Forbidden en el admin usando `?key=`
Causas comunes:
- La key es incorrecta.
- La key contiene `#`.
  - `#` es un fragmento del navegador: NO se envía al servidor.
  - Resultado: el backend recibe la key truncada/vacía y responde 403.
- Solución: usar key URL-safe (ej. letras/números/guiones/guiones bajos).

### 3) “Se guardan leads pero el admin sale vacío”
Causas posibles (observadas en hosting):
- Lectura del JSONL fallando por permisos o por métodos de lectura menos compatibles.
- Caché del hosting/navegador mostrando HTML anterior.

Mitigaciones implementadas:
- Lectura de JSONL más robusta (stream con `fopen/fgets`).
- Headers `no-store` en admin.
- Diagnóstico visible (exists/readable/bytes/filas) y debug autenticado `?debug=1`.

### 4) “El endpoint responde ok pero no aparece el lead”
Causa típica: honeypot activado por autofill.
- Solución aplicada: cuando el honeypot viene lleno, no se descarta silenciosamente; se guarda marcado como `spam:true` y se devuelve `id`.

## Operación diaria
- Ver leads: abrir `/api/admin/` (o `/<subcarpeta>/api/admin/`).
- Buscar: `?q=texto`.
- Exportar CSV: `?format=csv`.
- Debug: `?debug=1` (estando autenticado).

## Recomendaciones de hardening (opcional)
- Proteger `/api/admin/` desde el panel del hosting (Directory Password Protection).
- Restringir por IP si tu operación lo permite.
- Mantener `api/config/config.php` fuera de Git y rotar credenciales ante sospecha.
