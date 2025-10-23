<?php // path: app/chapa.php
require_once __DIR__ . '/auth.php';

function initialize_chapa_payment(PDO $pdo, int $userId): array
{
    $config = app_config();
    $txRef = 'SOCKET-' . $userId . '-' . bin2hex(random_bytes(4));
    $amount = $config['subscription_amount'];
    $callbackUrl = $config['base_url'] . '/public/api/payments/webhook.php';
    $returnUrl = $config['base_url'] . '/public/dashboard.php';

    $pdo->prepare('INSERT INTO payments (user_id, amount_etb, tx_ref, status, created_at) VALUES (:user_id, :amount_etb, :tx_ref, :status, :created_at)')
        ->execute([
            'user_id' => $userId,
            'amount_etb' => $amount,
            'tx_ref' => $txRef,
            'status' => 'pending',
            'created_at' => current_datetime(),
        ]);

    $payload = [
        'amount' => number_format($amount, 2, '.', ''),
        'currency' => 'ETB',
        'email' => 'customer@socket.com',
        'first_name' => 'SOCKET',
        'last_name' => 'Driver',
        'tx_ref' => $txRef,
        'callback_url' => $callbackUrl,
        'return_url' => $returnUrl,
        'customization' => [
            'title' => 'SOCKET Subscription',
            'description' => 'Unlock 30 days of premium station data.',
        ],
    ];

    $secret = env('CHAPA_SECRET', '');
    $public = env('CHAPA_PUBLIC', '');
    $checkoutUrl = $config['base_url'] . '/public/dashboard.php?tx_ref=' . urlencode($txRef);

    if ($secret && !str_contains($secret, 'YOUR_')) {
        $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secret,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            return [
                'tx_ref' => $txRef,
                'checkout_url' => $checkoutUrl,
                'message' => 'Using fallback checkout URL because the Chapa API request failed: ' . $error,
            ];
        }
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            $checkoutUrl = $data['data']['checkout_url'] ?? $checkoutUrl;
        } else {
            $message = $data['message'] ?? 'Unable to initialize payment with Chapa.';
            return [
                'tx_ref' => $txRef,
                'checkout_url' => $checkoutUrl,
                'message' => $message,
            ];
        }
    }

    return [
        'tx_ref' => $txRef,
        'checkout_url' => $checkoutUrl,
        'public_key' => $public,
    ];
}

function verify_chapa_transaction(string $txRef): bool
{
    $secret = env('CHAPA_SECRET', '');
    if (!$secret || str_contains($secret, 'YOUR_')) {
        return true; // sandbox fallback
    }
    $url = 'https://api.chapa.co/v1/transaction/verify/' . urlencode($txRef);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secret,
        ],
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        return false;
    }
    $data = json_decode($response, true);
    return isset($data['status']) && $data['status'] === 'success' && ($data['data']['status'] ?? '') === 'success';
}

function mark_payment_success(PDO $pdo, string $txRef): void
{
    $stmt = $pdo->prepare('SELECT * FROM payments WHERE tx_ref = :tx_ref LIMIT 1');
    $stmt->execute(['tx_ref' => $txRef]);
    $payment = $stmt->fetch();
    if (!$payment) {
        return;
    }
    if ($payment['status'] === 'success') {
        return;
    }
    $pdo->prepare('UPDATE payments SET status = :status WHERE id = :id')->execute([
        'status' => 'success',
        'id' => $payment['id'],
    ]);
    ensure_subscription_extension($pdo, (int)$payment['user_id'], app_config()['subscription_days']);
}

function mark_payment_failed(PDO $pdo, string $txRef, string $reason = ''): void
{
    $stmt = $pdo->prepare('UPDATE payments SET status = :status WHERE tx_ref = :tx_ref');
    $stmt->execute([
        'status' => $reason ? 'failed: ' . $reason : 'failed',
        'tx_ref' => $txRef,
    ]);
}
