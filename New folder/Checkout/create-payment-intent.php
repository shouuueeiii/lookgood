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

$successUrl = SITE_URL . '/success.php';
$failedUrl  = SITE_URL . '/cancel.php';   // optional cancel page

$ch = curl_init('https://api.paymongo.com/v1/sources');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, PAYMONGO_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
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
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'PayMongo API error', 'details' => json_decode($response)]);
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