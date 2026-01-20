<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/http.php';
require_once __DIR__ . '/../src/leads.php';

$configPath = __DIR__ . '/../config/config.php';
if (!is_file($configPath)) {
    send_json(500, [
        'ok' => false,
        'error' => 'Backend no configurado: falta api/config/config.php',
    ]);
}

/** @var array $config */
$config = require $configPath;

// Very small protection: require a key in query string.
// Example: /Landing/api/admin/?key=TU_CLAVE
$expectedKey = (string)($config['admin']['key'] ?? '');
$providedKey = (string)($_GET['key'] ?? '');

header('X-Robots-Tag: noindex, nofollow', true);

if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden\n";
    exit;
}

$leadsFile = (string)($config['storage']['leads_file'] ?? (__DIR__ . '/../storage/leads.jsonl'));
$limit = safe_int($_GET['limit'] ?? 200, 200, 10, 2000);
$q = trim((string)($_GET['q'] ?? ''));

$rows = read_jsonl($leadsFile, $limit);

if ($q !== '') {
    $qLower = mb_strtolower($q);
    $rows = array_values(array_filter($rows, function ($r) use ($qLower) {
        $blob = (
            (string)($r['name'] ?? '') . ' ' .
            (string)($r['email'] ?? '') . ' ' .
            (string)($r['company'] ?? '') . ' ' .
            (string)($r['message'] ?? '')
        );
        return mb_strpos(mb_strtolower($blob), $qLower) !== false;
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
        <div class="sub">Archivo: <span class="mono"><?php echo h($leadsFile); ?></span></div>
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
