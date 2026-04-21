<?php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../../session_bootstrap.php';

// Get the pending order data if it exists (from create-payment-intent.php)
$pendingOrder = $_SESSION['pending_order'] ?? null;

// Clear the pending order from session so it doesn't show again if they refresh
unset($_SESSION['pending_order']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Payment Cancelled – LookGood</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .cancel-card {
            max-width: 500px;
            width: 100%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .cancel-header {
            background: #fff5f5;
            padding: 40px 20px 20px;
            border-bottom: 1px solid #ffe0e0;
        }
        .cancel-icon {
            width: 80px;
            height: 80px;
            background: #d0312d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .cancel-icon i {
            font-size: 40px;
            color: white;
        }
        .cancel-header h1 {
            font-family: 'Spectral', serif;
            font-size: 28px;
            font-weight: 700;
            color: #d0312d;
            margin-bottom: 8px;
        }
        .cancel-header p {
            color: #555;
            font-size: 14px;
        }
        .cancel-body {
            padding: 32px 28px;
        }
        .message-box {
            background: #f8f8f8;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #333;
            border-left: 4px solid #d0312d;
            text-align: left;
        }
        .message-box i {
            color: #d0312d;
            margin-right: 8px;
        }
        .order-summary {
            background: #fafaf7;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 28px;
            text-align: left;
            font-size: 13px;
        }
        .order-summary h3 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #8e8e93;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e2e4;
            font-size: 13px;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-total {
            margin-top: 12px;
            padding-top: 8px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #d4d4d6;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-primary {
            background: #111111;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #2c2c2c;
            transform: scale(0.98);
        }
        .btn-outline {
            background: transparent;
            border: 1.5px solid #e2e2e4;
            color: #555;
        }
        .btn-outline:hover {
            border-color: #111;
            color: #111;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 12px;
            color: #8e8e93;
        }
    </style>
</head>
<body>
<div class="cancel-card">
    <div class="cancel-header">
        <div class="cancel-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h1>Payment Cancelled</h1>
        <p>Your transaction was not completed</p>
    </div>
    <div class="cancel-body">
        <div class="message-box">
            <i class="fas fa-info-circle"></i> 
            No amount has been charged to your GCash account. You can safely try again.
        </div>

        <?php if ($pendingOrder && isset($pendingOrder['items']) && count($pendingOrder['items']) > 0): ?>
        <div class="order-summary">
            <h3><i class="fas fa-receipt"></i> Your Pending Order</h3>
            <?php 
            $subtotal = $pendingOrder['subtotal'] ?? 0;
            $discount = $pendingOrder['discount'] ?? 0;
            $shipping = $pendingOrder['shippingFee'] ?? 0;
            $total = $pendingOrder['total'] ?? ($subtotal - $discount + $shipping);
            ?>
            <?php foreach (array_slice($pendingOrder['items'], 0, 3) as $item): ?>
            <div class="order-item">
                <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (count($pendingOrder['items']) > 3): ?>
            <div class="order-item" style="color:#8e8e93;">
                <span>+ <?php echo count($pendingOrder['items']) - 3; ?> more item(s)</span>
                <span></span>
            </div>
            <?php endif; ?>
            <div class="order-total">
                <span>Total</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <a href="checkout.php" class="btn btn-primary">
                <i class="fas fa-arrow-rotate-left"></i> Try Again
            </a>
            <a href="../Homepage/index.php" class="btn btn-outline">
                <i class="fas fa-store"></i> Continue Shopping
            </a>
        </div>
        <div class="footer-note">
            <i class="fas fa-lock"></i> Secure payment powered by PayMongo
        </div>
    </div>
</div>
</body>
</html>