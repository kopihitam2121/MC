<?php
declare(strict_types=1);

function mc_load_env_file(string $path): void
{
    $GLOBALS['MC_ENV_DIAGNOSTICS'] = [
        'path' => $path,
        'exists' => is_file($path) && is_readable($path),
        'loaded_keys' => [],
        'preexisting_keys' => [],
    ];
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') {
            continue;
        }
        // Process/server environment variables take precedence over .env. Track skipped
        // keys so local diagnostics can explain why .env values may not be active.
        if (getenv($name) !== false) {
            $GLOBALS['MC_ENV_DIAGNOSTICS']['preexisting_keys'][] = $name;
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $GLOBALS['MC_ENV_DIAGNOSTICS']['loaded_keys'][] = $name;
    }
}

function mc_env_diagnostics(): array
{
    return $GLOBALS['MC_ENV_DIAGNOSTICS'] ?? ['path' => '', 'exists' => false, 'loaded_keys' => [], 'preexisting_keys' => []];
}

mc_load_env_file(dirname(__DIR__) . '/.env');

function mc_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : (string) $value;
}

function mc_env_bool(string $key, bool $default = false): bool
{
    $value = getenv($key);
    if ($value === false || trim((string) $value) === '') {
        return $default;
    }
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

return [
    'app_name' => mc_env('MC_APP_NAME', 'Money Changer Dashboard'),
    'timezone' => mc_env('MC_TIMEZONE', 'Asia/Jakarta'),
    'environment' => mc_env('MC_ENV', 'production'),
    'debug' => mc_env_bool('MC_DEBUG', false),
];
