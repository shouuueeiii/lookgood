<?php
// create-checkout-session.php
// Replacement for the old create-payment-intent.php
// Keeps the same request/response shape: amount, name, email -> redirect_url

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);

$amount = (int)($input['amount'] ?? 0); // amount in centavos
$name   = trim($input['name'] ?? 'Customer');
$email  = trim($input['email'] ?? 'customer@example.com');

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid amount'
    ]);
    exit;
}

$siteBase = rtrim((string)SITE_URL, '/');
$projectBase = preg_replace('#/New%20folder/Checkout$#i', '', $siteBase);
if ($projectBase === null || $projectBase === '') {
    $projectBase = $siteBase;
}

$successUrl = $projectBase . '/home/success.php';
$cancelUrl  = $projectBase . '/homes/cancel.php';

$payload = [
    'data' => [
        'attributes' => [
            'billing' => [
                'name' => $name,
                'email' => $email,
                'phone' => '09123456789',
                'address' => [
                    'line1' => 'N/A',
                    'line2' => '',
                    'city' => 'Manila',
                    'state' => 'Metro Manila',
                    'postal_code' => '1000',
                    'country' => 'PH'
                ]
            ],
            'send_email_receipt' => true,
            'show_description' => true,
            'show_line_items' => true,
            'description' => 'GCash Checkout Payment',
            'line_items' => [
                [
                    'currency' => 'PHP',
                    'amount' => $amount,
                    'name' => 'Order Payment',
                    'quantity' => 1
                ]
            ],
            'payment_method_types' => ['gcash'],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl
        ]
    ]
];

$ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'accept: application/json',
    'content-type: application/json',
    'authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Curl error',
        'details' => $curlError
    ]);
    exit;
}

$result = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300 || !isset($result['data']['attributes']['checkout_url'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'PayMongo Checkout API error',
        'details' => $result
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'redirect_url' => $result['data']['attributes']['checkout_url'],
    'checkout_session_id' => $result['data']['id'] ?? null
]);