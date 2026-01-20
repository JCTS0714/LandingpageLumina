<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/http.php';
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/leads.php';

$config = load_config();

if (empty($config)) {
  send_json(500, [
    'ok' => false,
    'error' => 'Backend no configurado: falta api/config/config.php o api/config/config.example.php',
  ]);
}
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('Pragma: no-cache', true);
header('Expires: 0', true);

// Recommended protection on hosting: HTTP Basic Auth via env vars
// (avoids secrets in URLs).
$basicUser = env_string('LUMINA_ADMIN_USER');
$basicPass = env_string('LUMINA_ADMIN_PASS');

// Fallback if host does not pass SetEnv -> PHP: read from config.php
if (($basicUser === null || $basicUser === '') && isset($config['admin']['user']) && is_string($config['admin']['user'])) {
  $basicUser = $config['admin']['user'];
}
if (($basicPass === null || $basicPass === '') && isset($config['admin']['pass']) && is_string($config['admin']['pass'])) {
  $basicPass = $config['admin']['pass'];
}

if ($basicUser !== null && $basicUser !== '' && $basicPass !== null && $basicPass !== '') {
  // Session-based login (most compatible on shared hosting)
  session_name('lumina_admin');
  if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
      'httponly' => true,
      'samesite' => 'Lax',
      'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
  }
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (session_id() !== '') {
      session_destroy();
    }
    header('Location: ./');
    exit;
  }

  $authOk = !empty($_SESSION['lumina_admin_authed']);
  $loginError = '';

  if (!$authOk && (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST')) {
    $postedUser = isset($_POST['username']) ? (string)$_POST['username'] : '';
    $postedPass = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($postedUser !== '' && $postedPass !== '' && hash_equals($basicUser, $postedUser) && hash_equals($basicPass, $postedPass)) {
      session_regenerate_id(true);
      $_SESSION['lumina_admin_authed'] = true;
      $authOk = true;

      // Redirect to GET to avoid form resubmission
      header('Location: ./');
      exit;
    }

    $loginError = 'Credenciales inválidas.';
  }

  if (!$authOk) {
    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es-PE"><head><meta charset="utf-8" />';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    echo '<title>Lumina CRM — Admin</title>';
    echo '<style>body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial;background:#f7fbff;color:#0B1020}';
    echo '.wrap{min-height:100vh;display:grid;place-items:center;padding:24px}';
    echo '.card{width:100%;max-width:420px;background:#fff;border:1px solid rgba(11,61,145,.14);border-radius:16px;box-shadow:0 10px 30px rgba(11,61,145,.08);padding:18px}';
    echo 'h1{margin:0 0 6px;font-size:18px;color:#0B3D91}';
    echo 'p{margin:0 0 14px;color:#4B5A78;font-size:13px}';
    echo 'label{display:block;margin:10px 0 6px;font-weight:700;font-size:13px;color:#1B2B4B}';
    echo 'input{width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(11,61,145,.14);font-size:14px}';
    echo '.btn{margin-top:14px;width:100%;padding:10px 12px;border-radius:12px;border:0;background:linear-gradient(135deg,#0B3D91,#1656c7);color:#fff;font-weight:800;font-size:14px;cursor:pointer}';
    echo '.err{margin-top:10px;color:#b42318;font-weight:700;font-size:13px}</style>';
    echo '</head><body><div class="wrap"><div class="card">';
    echo '<h1>Acceso al panel</h1><p>Ingresa tus credenciales de administrador.</p>';
    echo '<form method="post" autocomplete="off">';
    echo '<label for="u">Usuario</label><input id="u" name="username" required />';
    echo '<label for="p">Contraseña</label><input id="p" name="password" type="password" required />';
    echo '<button class="btn" type="submit">Ingresar</button>';
    if ($loginError !== '') {
      echo '<div class="err">' . htmlspecialchars($loginError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
    }
    echo '</form></div></div></body></html>';
    exit;
  }
} else {
  // Fallback protection: require a key in query string.
  // Example: /api/admin/?key=TU_CLAVE
  $expectedKey = (string)($config['admin']['key'] ?? '');
  $providedKey = (string)($_GET['key'] ?? '');

  if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden\n";
    exit;
  }
}

function get_basic_auth_credentials(): array
{
  $user = $_SERVER['PHP_AUTH_USER'] ?? null;
  $pass = $_SERVER['PHP_AUTH_PW'] ?? null;

  if (is_string($user) && is_string($pass)) {
    return [$user, $pass];
  }

  $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

  if (!is_string($auth) || $auth === '') {
    if (function_exists('getallheaders')) {
      $headers = getallheaders();
      if (is_array($headers)) {
        $h = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
        if (is_string($h)) {
          $auth = $h;
        }
      }
    }
  }

  if (!is_string($auth) || stripos($auth, 'Basic ') !== 0) {
    return [null, null];
  }

  $decoded = base64_decode(substr($auth, 6), true);
  if (!is_string($decoded) || $decoded === '' || strpos($decoded, ':') === false) {
    return [null, null];
  }

  [$u, $p] = explode(':', $decoded, 2);
  return [$u, $p];
}

$leadsFile = (string)($config['storage']['leads_file'] ?? (__DIR__ . '/../storage/leads.jsonl'));
$limit = safe_int($_GET['limit'] ?? 200, 200, 10, 2000);
$q = trim((string)($_GET['q'] ?? ''));

$leadsFileExists = is_file($leadsFile);
$leadsFileReadable = $leadsFileExists && is_readable($leadsFile);
$leadsFileReal = $leadsFileExists ? (realpath($leadsFile) ?: $leadsFile) : $leadsFile;

$readWarning = '';
if ($leadsFileExists && !$leadsFileReadable) {
  $readWarning = 'El archivo de leads existe pero PHP no puede leerlo (permisos).';
}

$readError = '';
if ($leadsFileReadable) {
  [$rows, $readError] = read_jsonl_result($leadsFile, $limit);
} else {
  $rows = [];
}

if ($readWarning === '' && $readError !== '' && $leadsFileExists) {
  $readWarning = $readError;
}

if ($q !== '') {
  $toLower = function (string $s): string {
    return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s);
  };
  $pos = function (string $haystack, string $needle): int|false {
    return function_exists('mb_strpos') ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);
  };

  $qLower = $toLower($q);
    $rows = array_values(array_filter($rows, function ($r) use ($qLower) {
        $blob = (
            (string)($r['name'] ?? '') . ' ' .
            (string)($r['email'] ?? '') . ' ' .
            (string)($r['company'] ?? '') . ' ' .
            (string)($r['message'] ?? '')
        );
    $blobLower = function_exists('mb_strtolower') ? mb_strtolower($blob) : strtolower($blob);
    return (function_exists('mb_strpos') ? mb_strpos($blobLower, $qLower) : strpos($blobLower, $qLower)) !== false;
    }));
}

if (($_GET['format'] ?? '') === 'csv') {
    $csv = rows_to_csv($rows);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lumina-leads.csv"');
    echo $csv;
    exit;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fmt_perms(string $path): string
{
  if (!file_exists($path)) {
    return '-';
  }
  $perms = @fileperms($path);
  if ($perms === false) {
    return '-';
  }
  return substr(sprintf('%o', $perms), -4);
}

?><!doctype html>
<html lang="es-PE">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lumina CRM — Solicitudes de demo</title>
  <style>
    :root{--navy:#0B3D91;--teal:#1ABC9C;--bg:#f7fbff;--ink:#0B1020;--muted:#4B5A78;--line:rgba(11,61,145,.14);--card:#fff}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial; background:var(--bg); color:var(--ink)}
    .wrap{max-width:1100px;margin:0 auto;padding:24px 18px}
    .top{display:flex;align-items:end;justify-content:space-between;gap:12px;flex-wrap:wrap}
    h1{margin:0;font-size:20px;color:var(--navy)}
    .sub{margin-top:6px;color:var(--muted);font-size:13px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 10px 30px rgba(11,61,145,.08)}
    form{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
    input,select{padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;font-size:14px}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:#fff;font-weight:700;color:#1B2B4B;text-decoration:none}
    .btn-primary{background:linear-gradient(135deg,var(--navy),#1656c7);color:#fff;border-color:transparent}
    .grid{margin-top:14px}
    table{width:100%;border-collapse:separate;border-spacing:0}
    th,td{padding:12px 12px;border-bottom:1px solid var(--line);vertical-align:top;text-align:left;font-size:13px}
    th{color:#1B2B4B;font-size:12px;letter-spacing:.02em;text-transform:uppercase}
    td .muted{color:var(--muted)}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:12px}
    .empty{padding:18px;color:var(--muted)}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div>
        <h1>Solicitudes de demo — Lumina CRM</h1>
        <div class="sub">Archivo: <span class="mono"><?php echo h($leadsFileReal); ?></span></div>
        <?php if ($readWarning !== ''): ?>
          <div class="sub" style="margin-top:8px; color:#b42318; font-weight:800;">
            <?php echo h($readWarning); ?>
            <span style="font-weight:700; color:#4B5A78;">Perms: <?php echo h(fmt_perms($leadsFileReal)); ?> (file), <?php echo h(fmt_perms(dirname($leadsFileReal))); ?> (dir)</span>
          </div>
        <?php endif; ?>
      </div>
      <div class="card" style="padding:12px 12px;">
        <form method="get">
          <input type="hidden" name="key" value="<?php echo h($providedKey); ?>" />
          <input name="q" value="<?php echo h($q); ?>" placeholder="Buscar (nombre, email, empresa…)" style="min-width:260px" />
          <select name="limit">
            <?php foreach ([50,100,200,500,1000,2000] as $n): ?>
              <option value="<?php echo $n; ?>" <?php echo ($limit===$n?'selected':''); ?>><?php echo $n; ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary" type="submit">Filtrar</button>
          <a class="btn" href="?key=<?php echo h($providedKey); ?>&limit=<?php echo (int)$limit; ?>&q=<?php echo rawurlencode($q); ?>&format=csv">Descargar CSV</a>
        </form>
      </div>
    </div>

    <div class="grid card" style="margin-top:14px; overflow:auto;">
      <?php if (count($rows) === 0): ?>
        <div class="empty">Aún no hay solicitudes registradas.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Empresa</th>
              <th>Mensaje</th>
              <th class="mono">IP</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="mono"><?php echo h((string)($r['ts'] ?? '')); ?></td>
                <td><?php echo h((string)($r['name'] ?? '')); ?></td>
                <td><?php echo h((string)($r['email'] ?? '')); ?></td>
                <td><?php echo h((string)($r['company'] ?? '')); ?></td>
                <td><?php echo h((string)($r['message'] ?? '')); ?></td>
                <td class="mono muted"><?php echo h((string)($r['ip'] ?? '')); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
