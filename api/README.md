# Lumina Landing – Mini backend (PHP)

This folder contains a minimal PHP backend used only to receive the landing form submission.

## Endpoints
- `POST /Landing/api/contact.php` → accepts JSON or form-encoded data and returns JSON.

## Local paths
- Config: `api/config/config.php` (copy from `config.example.php`)
- Storage: `api/storage/` (JSONL leads + rate limit file)

## Deploy (Hostinger)
This project does **not** commit `api/config/config.php` to Git (it may contain secrets).

You have two options:

### Option A (recommended): create `config.php` on the server
1. In Hostinger File Manager, go to `public_html/Landing/api/config/` (or your site folder).
2. Copy `config.example.php` → `config.php`.
3. Edit `config.php` and set a strong admin key in `admin.key`.

### Option B: set admin key via environment variable (no `config.php` needed)
Add this line to your web root `.htaccess` (or a per-folder `.htaccess`):

`SetEnv LUMINA_ADMIN_KEY "PUT_A_LONG_RANDOM_KEY_HERE"`

Then open:
- If you uploaded the project into the domain root: `/api/admin/?key=PUT_A_LONG_RANDOM_KEY_HERE`
- If you uploaded into a subfolder named `Landing`: `/Landing/api/admin/?key=PUT_A_LONG_RANDOM_KEY_HERE`

### Recommended (more secure): HTTP Basic Auth (no secret in URL)
On shared hosting, query-string keys can leak in browser history and server logs.

Add to your web root `.htaccess`:

`SetEnv LUMINA_ADMIN_USER "admin"`

`SetEnv LUMINA_ADMIN_PASS "PUT_A_LONG_RANDOM_PASSWORD_HERE"`

Then open the admin URL (without `?key=`):
- Domain root: `/api/admin/`
- Subfolder `Landing`: `/Landing/api/admin/`

If `SetEnv` does not work on your hosting, you can also set credentials in `api/config/config.php` (server-only):

```php
return [
	'admin' => [
		'user' => 'admin',
		'pass' => 'PUT_A_LONG_RANDOM_PASSWORD_HERE',
	],
];
```

## Notes
- This backend is intentionally small: validation + honeypot + rate limit + storage.
- Email sending is optional and disabled by default (configure in `config.php`).
