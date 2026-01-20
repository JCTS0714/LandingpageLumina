# Lumina Landing – Mini backend (PHP)

This folder contains a minimal PHP backend used only to receive the landing form submission.

## Endpoints
- `POST /Landing/api/contact.php` → accepts JSON or form-encoded data and returns JSON.

## Local paths
- Config: `api/config/config.php` (copy from `config.example.php`)
- Storage: `api/storage/` (JSONL leads + rate limit file)

## Notes
- This backend is intentionally small: validation + honeypot + rate limit + storage.
- Email sending is optional and disabled by default (configure in `config.php`).
