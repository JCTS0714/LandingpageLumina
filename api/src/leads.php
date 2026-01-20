<?php

declare(strict_types=1);

function safe_int(mixed $v, int $default, int $min, int $max): int
{
    if (!is_numeric($v)) {
        return $default;
    }
    $i = (int)$v;
    if ($i < $min) {
        return $min;
    }
    if ($i > $max) {
        return $max;
    }
    return $i;
}

/**
 * Reads leads from a JSONL file.
 *
 * This is intentionally simple: it loads up to $maxRows lines.
 */
function read_jsonl(string $filePath, int $maxRows): array
{
    if (!is_file($filePath)) {
        return [];
    }

    $maxRows = max(1, min(2000, $maxRows));

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    // Keep last N lines (most recent at bottom)
    if (count($lines) > $maxRows) {
        $lines = array_slice($lines, -$maxRows);
    }

    $rows = [];
    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $rows[] = $decoded;
        }
    }

    // Newest first
    $rows = array_reverse($rows);

    return $rows;
}

function rows_to_csv(array $rows): string
{
    $headers = ['ts', 'name', 'email', 'company', 'message', 'ip', 'ua'];

    $fh = fopen('php://temp', 'r+');
    fputcsv($fh, $headers);

    foreach ($rows as $r) {
        $line = [];
        foreach ($headers as $h) {
            $line[] = isset($r[$h]) ? (string)$r[$h] : '';
        }
        fputcsv($fh, $line);
    }

    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);

    return $csv === false ? '' : $csv;
}
