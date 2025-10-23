<?php // path: app/auth.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

start_session();

function find_user_by_phone(PDO $pdo, string $phone): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute(['phone' => $phone]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function current_user(): ?array
{
    $pdo = get_pdo();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache !== null && ($cache['id'] ?? null) === $_SESSION['user_id']) {
        return $cache;
    }
    $user = find_user_by_id($pdo, (int)$_SESSION['user_id']);
    if ($user) {
        $cache = $user;
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || ($user['role'] ?? 'user') !== 'admin') {
        redirect('../login.php');
    }
}

function register_user(string $phone, string $password): array
{
    $pdo = get_pdo();
    $normalized = normalize_phone($phone);
    if ($normalized === null) {
        throw new InvalidArgumentException('Enter a valid Ethiopian phone number.');
    }
    if (strlen($password) < 6) {
        throw new InvalidArgumentException('Password must be at least 6 characters.');
    }
    if (find_user_by_phone($pdo, $normalized)) {
        throw new InvalidArgumentException('An account with this phone already exists.');
    }
    $config = app_config();
    $stmt = $pdo->prepare('INSERT INTO users (phone, password_hash, role, premium_until, created_at) VALUES (:phone, :password_hash, :role, :premium_until, :created_at)');
    $premiumUntil = days_from_now($config['trial_days']);
    $stmt->execute([
        'phone' => $normalized,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user',
        'premium_until' => $premiumUntil,
        'created_at' => current_datetime(),
    ]);

    $_SESSION['user_id'] = (int)$pdo->lastInsertId();
    return current_user();
}

function login_user(string $phone, string $password): array
{
    $pdo = get_pdo();
    $normalized = normalize_phone($phone);
    if ($normalized === null) {
        throw new InvalidArgumentException('Enter a valid Ethiopian phone number.');
    }
    $user = find_user_by_phone($pdo, $normalized);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        throw new InvalidArgumentException('Incorrect phone or password.');
    }
    $_SESSION['user_id'] = (int)$user['id'];
    return $user;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function user_has_active_subscription(array $user): bool
{
    if (empty($user['premium_until'])) {
        return false;
    }
    return strtotime($user['premium_until']) >= time();
}

function ensure_subscription_extension(PDO $pdo, int $userId, int $days): void
{
    $current = find_user_by_id($pdo, $userId);
    if (!$current) {
        return;
    }
    $base = !empty($current['premium_until']) && strtotime($current['premium_until']) > time()
        ? strtotime($current['premium_until'])
        : time();
    $newDate = date('Y-m-d H:i:s', strtotime("+{$days} days", $base));
    $stmt = $pdo->prepare('UPDATE users SET premium_until = :until WHERE id = :id');
    $stmt->execute([
        'until' => $newDate,
        'id' => $userId,
    ]);
}
