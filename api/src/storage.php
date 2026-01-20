<?php

declare(strict_types=1);

function append_jsonl(string $filePath, array $row): bool
{
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $line = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    return @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX) !== false;
}
