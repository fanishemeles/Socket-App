<?php // path: app/helpers.php
require_once __DIR__ . '/config.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function normalize_phone(string $input): ?string
{
    $digits = preg_replace('/[^0-9+]/', '', $input);
    if ($digits === null) {
        return null;
    }
    if (str_starts_with($digits, '+251')) {
        $suffix = substr($digits, 4);
        if (preg_match('/^(9|7)\d{8}$/', $suffix)) {
            return '+251' . $suffix;
        }
    }
    if (str_starts_with($digits, '0') && strlen($digits) === 10 && in_array($digits[1], ['9', '7'], true)) {
        return '+251' . substr($digits, 1);
    }
    if (str_starts_with($digits, '251') && strlen($digits) === 12 && in_array($digits[3], ['9', '7'], true)) {
        return '+251' . substr($digits, 3);
    }
    return null;
}

function request_method(string $method): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === strtoupper($method);
}

function require_post(): void
{
    if (!request_method('POST')) {
        json_response(['error' => 'Method not allowed'], 405);
    }
}

function current_datetime(): string
{
    return date('Y-m-d H:i:s');
}

function days_from_now(int $days): string
{
    return date('Y-m-d H:i:s', strtotime("+{$days} days"));
}
