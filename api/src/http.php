<?php

declare(strict_types=1);

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_ip(string $rawIp): string
{
    $ip = trim($rawIp);
    if ($ip === '') {
        return 'unknown';
    }
    // Do not trust forwarded headers here; keep it simple.
    return $ip;
}

function allow_cors(string $origin): void
{
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
