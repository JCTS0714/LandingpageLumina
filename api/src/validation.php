<?php

declare(strict_types=1);

function clean_text(?string $value, int $maxLen): string
{
    $value = $value ?? '';
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    $value = strip_tags($value);
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && mb_strlen($email) <= 254;
}

function parse_request_body(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    // Default: form-encoded / multipart
    return $_POST;
}
