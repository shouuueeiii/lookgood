<?php
// success.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Order Confirmed – LookGood</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ─── RESET & VARS ────────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --ink:       #111111;
            --ink-mid:   #555555;
            --ink-soft:  #8e8e93;
            --ink-faint: #e2e2e4;
            --gold:      #c8a96e;
            --bg:        #f5f5f5;
            --bg-panel:  #fafaf7;
            --surface:   #ffffff;
            --green:     #1e7e34;
            --green-bg:  #e6f4ea;
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-pill:40px;
        }

        html, body {
            min-height: 100%;
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        /* ─── TOPBAR ─────────────────────────────────────────────────── */
        .topbar {
            height: 60px;
            background: var(--surface);
            border-bottom: 1px solid var(--ink-faint);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .logo-img    { height: 28px; width: auto; }
        .topbar-divider { width: 1px; height: 20px; background: var(--ink-faint); }
        .topbar-label   { font-size: 14px; font-weight: 600; }

        /* ─── PAGE WRAPPER ───────────────────────────────────────────── */
        .page-wrap {
            max-width: 700px;
            margin: 40px auto 80px;
            padding: 0 24px;
            animation: fadeUp .5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── STEP INDICATOR ─────────────────────────────────────────── */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 36px;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .step-circle {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: 2px solid var(--ink-faint);
            background: var(--surface);
            color: #bbb;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            position: relative; z-index: 1;
        }
        .step-item.done .step-circle {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }
        .step-name {
            font-size: 10px; font-weight: 600;
            color: #bbb;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .step-item.done .step-name { color: var(--ink-mid); }
        .step-connector {
            height: 2px; width: 64px;
            background: var(--ink-faint);
            margin-bottom: 22px;
            flex-shrink: 0;
        }
        .step-connector.done { background: var(--ink); }

        /* ─── SUCCESS HERO ───────────────────────────────────────────── */
        .success-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 20px 40px -12px rgba(0,0,0,.09);
        }

        /* Green header band */
        .success-header {
            background: #111;
            padding: 36px 32px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .success-header::before {
            content: '';
            position: absolute; inset: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,.02) 10px,
                rgba(255,255,255,.02) 20px
            );
        }
        .check-icon-wrap {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,.12);
            border: 2px solid rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            animation: popIn .5s .15s cubic-bezier(.34,1.56,.64,1) both;
        }
        .check-icon-wrap i { font-size: 28px; color: #fff; }
        @keyframes popIn {
            from { opacity: 0; transform: scale(.5); }
            to   { opacity: 1; transform: scale(1); }
        }
        .success-title {
            font-family: 'Spectral', serif;
            font-size: 26px; font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        .success-subtitle { font-size: 13px; color: rgba(255,255,255,.65); line-height: 1.5; }
        .success-subtitle strong { color: rgba(255,255,255,.9); }

        /* Order ID band */
        .order-id-band {
            background: var(--bg-panel);
            border-bottom: 1px solid var(--ink-faint);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .order-id-label { font-size: 11px; color: var(--ink-soft); font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .order-id-val   { font-family: 'Spectral', serif; font-size: 15px; font-weight: 700; }
        .payment-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px;
            border-radius: var(--radius-pill);
            font-size: 11px; font-weight: 700;
            letter-spacing: .03em;
        }
        .payment-badge.gcash { background: #e8f0fe; color: #1a56db; }
        .payment-badge.cod   { background: #f0f0f0; color: var(--ink-mid); }

        /* Receipt body */
        .receipt-body { padding: 28px 32px 32px; }

        /* Customer info */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 24px;
            background: var(--bg-panel);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .info-item {}
        .info-label { font-size: 10px; color: var(--ink-soft); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
        .info-val   { font-size: 13px; font-weight: 500; color: var(--ink); line-height: 1.4; }

        /* Items */
        .items-title {
            font-family: 'Spectral', serif;
            font-size: 14px; font-weight: 700;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--ink-faint);
        }
        .order-item {
            display: flex; align-items: center; gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-img {
            width: 56px; height: 56px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            border: 1px solid #eee;
            flex-shrink: 0;
        }
        .order-item-info { flex: 1; min-width: 0; }
        .order-item-name {
            font-family: 'Spectral', serif;
            font-size: 14px; font-weight: 700;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .order-item-meta { font-size: 11px; color: var(--ink-soft); }
        .order-item-price { font-size: 14px; font-weight: 700; flex-shrink: 0; }

        /* Totals */
        .totals-block {
            border-top: 1px solid var(--ink-faint);
            margin-top: 12px;
            padding-top: 14px;
        }
        .total-line {
            display: flex; justify-content: space-between;
            font-size: 13px; color: var(--ink-mid);
            margin-bottom: 8px;
        }
        .total-line.discount { color: var(--green); }
        .total-line.grand {
            font-size: 17px; font-weight: 700; color: var(--ink);
            font-family: 'Spectral', serif;
            border-top: 2px solid var(--ink);
            margin-top: 10px; padding-top: 12px;
            margin-bottom: 0;
        }

        /* Note block */
        .note-block {
            background: #fffdf5;
            border: 1px solid #f0e6c8;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: 12px;
            color: #7a6a3e;
            margin-top: 16px;
            display: flex; align-items: flex-start; gap: 8px;
        }
        .note-block i { flex-shrink: 0; margin-top: 1px; color: var(--gold); }

        /* Actions */
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .btn-primary {
            flex: 1;
            padding: 12px 20px;
            background: var(--ink); color: #fff;
            border: none; border-radius: var(--radius-pill);
            font-size: 14px; font-weight: 700; font-family: 'DM Sans', sans-serif;
            cursor: pointer; text-decoration: none; text-align: center;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: background .15s;
        }
        .btn-primary:hover { background: #2c2c2c; }
        .btn-secondary {
            flex: 1;
            padding: 12px 20px;
            background: transparent; color: var(--ink);
            border: 1.5px solid var(--ink-faint); border-radius: var(--radius-pill);
            font-size: 14px; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; text-decoration: none; text-align: center;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: border-color .15s, background .15s;
        }
        .btn-secondary:hover { border-color: var(--ink); background: #f7f7f9; }

        /* ─── EMPTY STATE ────────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
        }
        .empty-state i { font-size: 56px; color: var(--ink-faint); margin-bottom: 20px; display: block; }
        .empty-state h2 { font-size: 22px; margin-bottom: 8px; }
        .empty-state p { color: var(--ink-soft); font-size: 14px; margin-bottom: 24px; }

        /* ─── RESPONSIVE ─────────────────────────────────────────────── */
        @media (max-width: 600px) {
            .topbar { padding: 0 20px; }
            .topbar-label, .topbar-divider { display: none; }
            .page-wrap { margin: 24px auto 60px; padding: 0 16px; }
            .success-header { padding: 28px 20px 24px; }
            .order-id-band { padding: 12px 20px; }
            .receipt-body { padding: 20px 20px 24px; }
            .info-grid { grid-template-columns: 1fr; }
            .step-connector { width: 36px; }
            .actions { flex-direction: column; }
        }
        @media (max-width: 380px) {
            .step-connector { width: 20px; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR ──────────────────────────────────────────────────────────── -->
<header class="topbar">
    <div class="topbar-left">
        <a href="../Homepage/index.php">
            <img src="../Resources/Logos/lookgood-black.png" alt="LookGood" class="logo-img">
        </a>
        <div class="topbar-divider"></div>
        <span class="topbar-label">Order Confirmed</span>
    </div>
</header>

<!-- ── PAGE ────────────────────────────────────────────────────────────── -->
<div class="page-wrap" id="pageWrap">

    <!-- STEP INDICATOR -->
    <div class="step-indicator">
        <div class="step-item done">
            <div class="step-circle"><i class="fas fa-check" style="font-size:11px;"></i></div>
            <span class="step-name">Cart</span>
        </div>
        <div class="step-connector done"></div>
        <div class="step-item done">
            <div class="step-circle"><i class="fas fa-check" style="font-size:11px;"></i></div>
            <span class="step-name">Checkout</span>
        </div>
        <div class="step-connector done"></div>
        <div class="step-item done">
            <div class="step-circle"><i class="fas fa-check" style="font-size:11px;"></i></div>
            <span class="step-name">Confirmed</span>
        </div>
    </div>

    <!-- SUCCESS CARD (injected by JS) -->
    <div id="successCard"></div>

</div>

<script>
/* ═══════════════════════════════════════════════════════
   RENDER ORDER CONFIRMATION
   ═══════════════════════════════════════════════════════ */
(function render() {
    const card = document.getElementById('successCard');
    let order = null;

    try { order = JSON.parse(localStorage.getItem('last_order') || 'null'); } catch(e) {}

    if (!order) {
        card.innerHTML = `
            <div class="success-card">
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h2>No order found</h2>
                    <p>We couldn't find a recent order. You may have already visited this page.</p>
                    <a href="../Homepage/index.php" class="btn-primary" style="max-width:220px;margin:0 auto;">
                        <i class="fas fa-house"></i> Back to Homepage
                    </a>
                </div>
            </div>`;
        return;
    }

    const c         = order.customer || {};
    const pm        = order.paymentMethod || 'gcash';
    const pmLabel   = pm === 'cod' ? 'Cash on Delivery' : 'GCash';
    const pmClass   = pm === 'cod' ? 'cod' : 'gcash';
    const pmIcon    = pm === 'cod' ? 'fa-box' : 'fa-mobile-screen-button';

    const subtotal  = order.subtotal  || 0;
    const discount  = order.discount  || 0;
    const tax       = order.tax       || 0;
    const total     = order.total     || 0;

    const orderId   = 'LG-' + Date.now().toString(36).toUpperCase().slice(-8);

    // Build address string
    const addressParts = [c.address1, c.address2, c.city, c.province, c.region, c.zip].filter(Boolean);
    const addressStr   = addressParts.join(', ') || '—';

    // Items HTML
    const itemsHtml = (order.items || []).map(item => `
        <div class="order-item">
            <img class="order-item-img"
                 src="${item.image || ''}"
                 onerror="this.src='https://placehold.co/56x56?text=Item'"
                 alt="${esc(item.name)}">
            <div class="order-item-info">
                <div class="order-item-name">${esc(item.name)}</div>
                <div class="order-item-meta">Qty: ${item.quantity}${item.brand ? ' · ' + esc(item.brand) : ''}</div>
            </div>
            <div class="order-item-price">₱${(item.price * item.quantity).toFixed(2)}</div>
        </div>
    `).join('');

    const discountHtml = discount > 0 ? `
        <div class="total-line discount">
            <span>Discount${order.appliedVouchers && order.appliedVouchers.length ? ' (' + esc(order.appliedVouchers[0].code) + ')' : ''}</span>
            <span>-₱${discount.toFixed(2)}</span>
        </div>` : '';

    const noteHtml = c.note ? `
        <div class="note-block">
            <i class="fas fa-note-sticky"></i>
            <span><strong>Delivery note:</strong> ${esc(c.note)}</span>
        </div>` : '';

    card.innerHTML = `
        <div class="success-card">

            <!-- Header -->
            <div class="success-header">
                <div class="check-icon-wrap">
                    <i class="fas fa-check"></i>
                </div>
                <div class="success-title">Order Confirmed!</div>
                <p class="success-subtitle">
                    A receipt has been sent to <strong>${esc(c.email || 'your email')}</strong>.<br>
                    We'll notify you once your order is on its way.
                </p>
            </div>

            <!-- Order ID band -->
            <div class="order-id-band">
                <div>
                    <div class="order-id-label">Order ID</div>
                    <div class="order-id-val">${orderId}</div>
                </div>
                <div class="payment-badge ${pmClass}">
                    <i class="fas ${pmIcon}"></i> ${pmLabel}
                </div>
            </div>

            <!-- Receipt body -->
            <div class="receipt-body">

                <!-- Customer details -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-val">${esc(c.fullName || '—')}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-val">${esc(c.phone || '—')}</div>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label">Delivery Address</div>
                        <div class="info-val">${esc(addressStr)}</div>
                    </div>
                </div>

                <!-- Items -->
                <div class="items-title">Items Ordered (${(order.items || []).reduce((s,i) => s + i.quantity, 0)})</div>
                ${itemsHtml}

                <!-- Totals -->
                <div class="totals-block">
                    <div class="total-line"><span>Subtotal</span><span>₱${subtotal.toFixed(2)}</span></div>
                    ${discountHtml}
                    <div class="total-line"><span>Shipping</span><span>Free</span></div>
                    <div class="total-line"><span>VAT (12%)</span><span>₱${tax.toFixed(2)}</span></div>
                    <div class="total-line grand"><span>Total Paid</span><span>₱${total.toFixed(2)}</span></div>
                </div>

                ${noteHtml}

                <!-- Actions -->
                <div class="actions">
                    <a href="../Homepage/index.php" class="btn-primary">
                        <i class="fas fa-bag-shopping"></i> Continue Shopping
                    </a>
                    <button class="btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>

            </div>
        </div>
    `;

    // Clear cart & checkout data; keep last_order for this session
    localStorage.removeItem('lookgood_cart');
    localStorage.removeItem('lookgood_buynow');
    localStorage.removeItem('lookgood_checkout_data');
})();

function esc(str) {
    return String(str).replace(/[&<>"']/g,
        m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
</script>
</body>
</html>