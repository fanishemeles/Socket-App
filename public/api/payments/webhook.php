<?php // path: public/api/payments/webhook.php
require_once __DIR__ . '/../../../app/chapa.php';

require_post();
$pdo = get_pdo();

$payload = $_POST;
if (empty($payload)) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true) ?: [];
}

$txRef = $payload['tx_ref'] ?? $payload['reference'] ?? null;
$status = strtolower($payload['status'] ?? '');

if (!$txRef) {
    json_response(['error' => 'Missing tx_ref'], 422);
}

if ($status === 'success' && verify_chapa_transaction($txRef)) {
    mark_payment_success($pdo, $txRef);
    json_response(['status' => 'ok']);
}

mark_payment_failed($pdo, $txRef, $status ?: 'unknown');
json_response(['status' => 'failed']);
