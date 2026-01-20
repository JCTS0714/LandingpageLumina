<?php

declare(strict_types=1);

require_once __DIR__ . '/src/http.php';
require_once __DIR__ . '/src/validation.php';
require_once __DIR__ . '/src/rate_limit.php';
require_once __DIR__ . '/src/storage.php';

$configPath = __DIR__ . '/config/config.php';
if (!is_file($configPath)) {
    allow_cors('*');
    send_json(500, [
        'ok' => false,
        'error' => 'Backend no configurado: copia api/config/config.example.php a api/config/config.php',
    ]);
}

/** @var array $config */
$config = require $configPath;

$allowOrigin = $config['cors']['allow_origin'] ?? '*';
allow_cors(is_string($allowOrigin) ? $allowOrigin : '*');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    send_json(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$ip = normalize_ip($_SERVER['REMOTE_ADDR'] ?? '');

// Rate limit
$rlFile = (string)($config['storage']['rate_limit_file'] ?? (__DIR__ . '/storage/ratelimit.json'));
$perMinute = (int)($config['rate_limit']['per_minute'] ?? 5);
$perHour = (int)($config['rate_limit']['per_hour'] ?? 30);
[$ok, $status, $msg] = rate_limit_check($rlFile, $ip, $perMinute, $perHour);
if (!$ok) {
    send_json($status, ['ok' => false, 'error' => $msg]);
}

$data = parse_request_body();

// Honeypot (bots usually fill this)
$website = isset($data['website']) ? clean_text((string)$data['website'], 200) : '';
if ($website !== '') {
    // Pretend success (do not help spammers)
    send_json(200, ['ok' => true]);
}

$name = clean_text($data['name'] ?? '', 80);
$email = clean_text($data['email'] ?? '', 254);
$company = clean_text($data['company'] ?? '', 120);
$message = clean_text($data['message'] ?? '', 1200);

$errors = [];
if ($name === '' || mb_strlen($name) < 2) {
    $errors['name'] = 'Ingresa tu nombre.';
}
if (!is_valid_email($email)) {
    $errors['email'] = 'Ingresa un email válido.';
}
if ($message !== '' && mb_strlen($message) < 5) {
    $errors['message'] = 'Mensaje muy corto.';
}

if (!empty($errors)) {
    send_json(422, ['ok' => false, 'error' => 'Validación', 'fields' => $errors]);
}

$lead = [
    'ts' => gmdate('c'),
    'ip' => $ip,
    'ua' => clean_text($_SERVER['HTTP_USER_AGENT'] ?? '', 220),
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'message' => $message,
];

$leadsFile = (string)($config['storage']['leads_file'] ?? (__DIR__ . '/storage/leads.jsonl'));
if (!append_jsonl($leadsFile, $lead)) {
    send_json(500, ['ok' => false, 'error' => 'No se pudo guardar el registro.']);
}

// Optional email notification
$emailCfg = $config['email'] ?? [];
if (is_array($emailCfg) && !empty($emailCfg['enabled'])) {
    $to = (string)($emailCfg['to'] ?? '');
    $from = (string)($emailCfg['from'] ?? 'no-reply@localhost');
    $subject = (string)($emailCfg['subject'] ?? 'Nueva solicitud de demo');

    if ($to !== '') {
        $body = "Nueva solicitud de demo (Lumina CRM)\n\n" .
            "Nombre: {$name}\n" .
            "Email: {$email}\n" .
            "Empresa: {$company}\n" .
            "Mensaje: {$message}\n" .
            "IP: {$ip}\n" .
            "Fecha: " . date('c') . "\n";

        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $email,
            'Content-Type: text/plain; charset=utf-8',
        ];

        // Suppress errors; storage is the source of truth
        @mail($to, $subject, $body, implode("\r\n", $headers));
    }
}

send_json(200, ['ok' => true]);
