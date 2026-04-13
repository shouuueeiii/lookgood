<?php
// create-payment-intent.php, dito napupunta after checkout.php and before success.php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? 0;
$name = $input['name'] ?? 'Customer';
$email = $input['email'] ?? 'customer@example.com';

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

// Build callback base from project root only, never from "New folder" path.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = trim((string)($_SERVER['SCRIPT_NAME'] ?? '/lookgood/New%20folder/Checkout/create-payment-intent.php'));

// Example: /lookgood/New%20folder/Checkout/create-payment-intent.php -> /lookgood
$parts = array_values(array_filter(explode('/', $scriptPath), static function ($p) {
    return $p !== '';
}));
$projectRoot = isset($parts[0]) ? '/' . $parts[0] : '/lookgood';

// PayMongo rejects encoded-space callback URLs, so route through /payment endpoints.
$callbackBase = $scheme . '://' . $host . $projectRoot . '/payment';
$successUrl = $callbackBase . '/success.php';
$failedUrl  = $callbackBase . '/cancel.php';

$payload = json_encode([
    'data' => [
        'attributes' => [
            'amount'   => $amount,
            'type'     => 'gcash',
            'currency' => 'PHP',
            'redirect' => [
                'success' => $successUrl,
                'failed'  => $failedUrl
            ],
            'billing' => [
                'name'  => $name,
                'email' => $email
            ]
        ]
    ]
]);

$response = null;
$httpCode = 0;
$curlErr  = '';

// Retry once for transient upstream/network issues.
for ($attempt = 1; $attempt <= 2; $attempt++) {
    $ch = curl_init('https://api.paymongo.com/v1/sources');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response !== false && $httpCode === 200) {
        break;
    }
}

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode([
        'error' => 'PayMongo API error',
        'http_code' => $httpCode,
        'curl_error' => $curlErr,
        'details' => json_decode($response, true),
        'raw_response' => is_string($response) ? $response : null
    ]);
    exit;
}

$result = json_decode($response, true);
$checkoutUrl = $result['data']['attributes']['redirect']['checkout_url'] ?? null;

if (!$checkoutUrl) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing checkout URL from PayMongo']);
    exit;
}

echo json_encode([
    'success'      => true,
    'redirect_url' => $checkoutUrl
]);