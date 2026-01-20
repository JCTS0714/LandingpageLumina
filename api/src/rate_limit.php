<?php

declare(strict_types=1);

function rate_limit_check(string $filePath, string $ip, int $perMinute, int $perHour): array
{
    $now = time();

    $state = [];
    if (is_file($filePath)) {
        $raw = @file_get_contents($filePath);
        $decoded = json_decode($raw ?: '[]', true);
        if (is_array($decoded)) {
            $state = $decoded;
        }
    }

    if (!isset($state[$ip]) || !is_array($state[$ip])) {
        $state[$ip] = [];
    }

    $events = array_filter($state[$ip], fn($t) => is_int($t) || ctype_digit((string)$t));
    $events = array_map('intval', $events);

    // Keep only last hour
    $events = array_values(array_filter($events, fn($t) => $t > ($now - 3600)));

    $countHour = count($events);
    $countMinute = count(array_filter($events, fn($t) => $t > ($now - 60)));

    if ($countMinute >= $perMinute) {
        return [false, 429, 'Demasiadas solicitudes. Intenta nuevamente en 1 minuto.'];
    }
    if ($countHour >= $perHour) {
        return [false, 429, 'Demasiadas solicitudes. Intenta nuevamente más tarde.'];
    }

    $events[] = $now;
    $state[$ip] = $events;

    @file_put_contents($filePath, json_encode($state, JSON_UNESCAPED_SLASHES), LOCK_EX);

    return [true, 200, 'ok'];
}
