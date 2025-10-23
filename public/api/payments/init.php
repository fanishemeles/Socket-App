<?php // path: public/api/payments/init.php
require_once __DIR__ . '/../../../app/chapa.php';

start_session();
if (!is_logged_in()) {
    json_response(['error' => 'Authentication required'], 401);
}
require_post();

$pdo = get_pdo();
$user = current_user();

try {
    $payload = initialize_chapa_payment($pdo, (int)$user['id']);
    json_response($payload);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
