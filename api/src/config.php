<?php

declare(strict_types=1);

/**
 * Loads configuration for the mini backend.
 *
 * Priority:
 * 1) api/config/config.php (not committed)
 * 2) api/config/config.example.php (committed)
 *
 * Supports environment overrides (useful on shared hosting):
 * - LUMINA_ADMIN_KEY
 */
function load_config(): array
{
    $baseDir = __DIR__ . '/..';

    $configPath = $baseDir . '/config/config.php';
    $examplePath = $baseDir . '/config/config.example.php';

    $config = [];

    if (is_file($configPath)) {
        $loaded = require $configPath;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    } elseif (is_file($examplePath)) {
        $loaded = require $examplePath;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    }

    // Env overrides
    $adminKey = env_string('LUMINA_ADMIN_KEY');
    if ($adminKey !== null && $adminKey !== '') {
        if (!isset($config['admin']) || !is_array($config['admin'])) {
            $config['admin'] = [];
        }
        $config['admin']['key'] = $adminKey;
    }

    return $config;
}

function env_string(string $name): ?string
{
    $v = getenv($name);
    if ($v !== false) {
        return (string)$v;
    }

    // Some hosts expose env vars via $_SERVER/$_ENV
    if (isset($_SERVER[$name])) {
        return (string)$_SERVER[$name];
    }
    if (isset($_ENV[$name])) {
        return (string)$_ENV[$name];
    }

    return null;
}
