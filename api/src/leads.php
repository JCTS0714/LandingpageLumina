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
    return read_jsonl_result($filePath, $maxRows)[0];
}

/**
 * Reads JSONL and also returns a human-readable error message when it fails.
 *
 * @return array{0: array, 1: string} [rows, error]
 */
function read_jsonl_result(string $filePath, int $maxRows): array
{
    if (!is_file($filePath)) {
        return [[], 'Archivo no encontrado.'];
    }

    $maxRows = max(1, min(2000, $maxRows));

    $fh = @fopen($filePath, 'rb');
    if ($fh === false) {
        return [[], 'No se pudo abrir el archivo para lectura (permisos u open_basedir).'];
    }

    $lines = [];
    while (!feof($fh)) {
        $line = fgets($fh);
        if (!is_string($line)) {
            break;
        }
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $lines[] = $line;
        if (count($lines) > $maxRows) {
            array_shift($lines);
        }
    }
    fclose($fh);

    $rows = [];
    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $rows[] = $decoded;
        }
    }

    // Newest first
    $rows = array_reverse($rows);

    if (count($rows) === 0 && filesize($filePath) > 0) {
        return [[], 'No se pudo parsear el JSONL (líneas inválidas o codificación).'];
    }

    return [$rows, ''];
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
