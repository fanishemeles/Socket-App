<?php // path: app/config.php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!function_exists('load_env_file')) {
    function load_env_file(string $file): array
    {
        $vars = [];
        if (!is_file($file)) {
            return $vars;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"' ");
            $vars[$key] = $value;
        }
        return $vars;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        static $cache = null;
        if ($cache === null) {
            $cache = load_env_file(BASE_PATH . '/.env');
        }
        return $cache[$key] ?? $default;
    }
}

if (!function_exists('app_config')) {
    function app_config(): array
    {
        return [
            'app_name' => 'SOCKET',
            'base_url' => rtrim(env('BASE_URL', 'http://localhost'), '/'),
            'trial_days' => (int)env('FREE_TRIAL_DAYS', 5),
            'subscription_days' => (int)env('SUBSCRIPTION_DAYS', 30),
            'subscription_amount' => (float)env('SUBSCRIPTION_ETB', 150),
        ];
    }
}

date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Addis_Ababa'));
