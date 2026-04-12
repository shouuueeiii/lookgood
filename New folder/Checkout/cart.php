<?php
// cart.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Shopping Cart – LookGood</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ─── RESET & BASE ─────────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --gold:        #c8a96e;
            --gold-light:  #e8d5b0;
            --ink:         #1a1a1a;
            --ink-mid:     #555;
            --ink-soft:    #aaa;
            --ink-faint:   #ddd;
            --bg:          #f5f5f5;
            --surface:     #ffffff;
            --surface-2:   #fafafa;
            --green:       #2e7d32;
            --green-bg:    #e8f5e9;
            --red:         #e53935;
            --red-bg:      #ffebee;
            --red-dark:    #b71c1c;
            --radius-sm:   8px;
            --radius-md:   12px;
            --radius-lg:   16px;
            --radius-pill: 40px;
            --shadow-sm:   0 1px 8px rgba(0,0,0,.06);
            --shadow-md:   0 4px 16px rgba(0,0,0,.10);
            --footer-h:    118px; /* approx fixed footer height on desktop */
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ─── TOPBAR ───────────────────────────────────────────────────── */
        .cart-topbar {
            background: var(--surface);
            padding: 0 48px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #eaeaea;
        }
        .cart-topbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logo-img { height: 32px; width: auto; display: block; }
        .cart-topbar-divider { width: 1px; height: 22px; background: var(--ink-faint); }
        .cart-topbar-label { font-size: 15px; color: var(--ink); font-weight: 600; }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--gold);
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            padding: 6px 0;
            transition: opacity .15s;
        }
        .back-btn:hover { opacity: .7; }

        /* ─── MAIN CONTENT ─────────────────────────────────────────────── */
        .cart-main {
            flex: 1;
            max-width: 1300px;
            width: 100%;
            margin: 28px auto;
            padding: 0 48px calc(var(--footer-h) + 32px);
        }

        /* ─── TABLE WRAPPER ────────────────────────────────────────────── */
        .cart-table-wrap {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        /* shared grid: checkbox | product | price | qty | total | action */
        .cart-table-header,
        .buy-now-row,
        .cart-row {
            display: grid;
            grid-template-columns: 28px 2fr 1fr 1fr 1fr 72px;
            gap: 12px;
            align-items: center;
        }

        .cart-table-header {
            padding: 12px 24px;
            background: var(--surface-2);
            border-bottom: 1px solid #f0f0f0;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--ink-soft);
            font-weight: 600;
        }
        .cart-table-header div:nth-child(n+3) { text-align: center; }

        .buy-now-row {
            padding: 20px 24px;
            background: var(--surface);
            border-bottom: 2px solid #ebebeb;
        }

        .cart-row {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
        }
        .cart-row:last-child { border-bottom: none; }
        .cart-row.is-checked { background: var(--surface-2); }

        /* ─── PRODUCT CELL ─────────────────────────────────────────────── */
        .product-cell {
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            min-width: 0;
        }
        .product-img {
            width: 76px;
            height: 76px;
            border-radius: var(--radius-md);
            object-fit: cover;
            border: 1px solid #eee;
            flex-shrink: 0;
        }
        .product-info { min-width: 0; }
        .product-name {
            font-family: 'Spectral', serif;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-meta { font-size: 12px; color: var(--ink-soft); }

        .out-of-stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--red-bg);
            color: #c62828;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: var(--radius-pill);
            margin-left: 6px;
            vertical-align: middle;
        }

        /* ─── QTY CONTROL ──────────────────────────────────────────────── */
        .qty-ctrl {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .qty-btn {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--surface);
            border: 1.5px solid var(--ink-faint);
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            line-height: 1;
            color: var(--ink);
            transition: border-color .15s, background .15s;
            display: flex; align-items: center; justify-content: center;
        }
        .qty-btn:hover:not(:disabled) { border-color: var(--ink); background: var(--ink); color: #fff; }
        .qty-btn:disabled { opacity: .35; cursor: not-allowed; }
        .qty-input {
            width: 52px;
            text-align: center;
            border: 1.5px solid var(--ink-faint);
            border-radius: var(--radius-pill);
            padding: 5px 4px;
            font-size: 13px;
            font-family: inherit;
            color: var(--ink);
            outline: none;
            transition: border-color .15s;
        }
        .qty-input:focus { border-color: var(--ink); }
        /* hide spinners */
        .qty-input::-webkit-inner-spin-button,
        .qty-input::-webkit-outer-spin-button { -webkit-appearance: none; }
        .qty-input[type=number] { appearance: textfield; -moz-appearance: textfield; }

        /* ─── PRICE CELLS ──────────────────────────────────────────────── */
        .cell-price { font-size: 13px; font-weight: 500; text-align: center; color: var(--ink-mid); }
        .cell-total  { font-size: 15px; font-weight: 700; text-align: center; font-family: 'Spectral', serif; }

        /* ─── DELETE BTN ───────────────────────────────────────────────── */
        .delete-item {
            background: none; border: none;
            color: #ccc;
            font-size: 16px; cursor: pointer;
            width: 100%; text-align: center;
            transition: color .15s, transform .15s;
            padding: 4px;
        }
        .delete-item:hover { color: var(--red); transform: scale(1.1); }

        /* ─── DIVIDER ──────────────────────────────────────────────────── */
        .also-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px 6px;
        }
        .divider-line { flex: 1; height: 1px; background: #f0f0f0; }
        .divider-txt  { font-size: 11px; color: var(--ink-soft); letter-spacing: .04em; text-transform: uppercase; }

        /* ─── EMPTY STATE ──────────────────────────────────────────────── */
        .empty-cart {
            padding: 48px 24px;
            text-align: center;
            color: var(--ink-soft);
            display: none;
        }
        .empty-cart i { font-size: 40px; display: block; margin-bottom: 12px; color: var(--ink-faint); }

        /* ─────────────────────────────────────────────────────────────── */
        /*  FIXED FOOTER                                                   */
        /* ─────────────────────────────────────────────────────────────── */
        .cart-footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--surface);
            border-top: 2px solid var(--ink);
            z-index: 200;
            box-shadow: 0 -4px 20px rgba(0,0,0,.09);
        }

        /* — Top strip: select all + move to wishlist — */
        .footer-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 48px;
            border-bottom: 1px solid #f0f0f0;
        }
        .select-all-wrapper { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .select-all-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: 500; cursor: pointer;
            user-select: none;
        }
        .total-items-badge {
            font-size: 11px; background: #f0f0f0;
            padding: 3px 10px; border-radius: var(--radius-pill);
            color: var(--ink-mid); font-weight: 500;
        }
        .btn-move-wishlist {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 16px;
            background: transparent; color: var(--ink-mid);
            border: 1.5px solid var(--ink-faint);
            border-radius: var(--radius-pill);
            font-size: 12px; font-weight: 600; cursor: pointer;
            transition: border-color .15s, color .15s;
            font-family: inherit;
        }
        .btn-move-wishlist:hover:not(:disabled) { border-color: var(--ink); color: var(--ink); }
        .btn-move-wishlist:disabled { opacity: .4; cursor: not-allowed; }

        /* — Bottom strip: voucher | totals | checkout — */
        /*
         *  KEY LAYOUT: 3 columns in a grid so the totals + checkout column
         *  NEVER moves when the voucher input opens/closes.
         *
         *  [voucher-col]  [spacer]  [right-col: totals + checkout]
         */
        .footer-bottom {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 10px 48px;
        }

        /* — Voucher column — */
        .voucher-col {
            display: flex;
            align-items: center;
            gap: 0;
        }

        /* The entire voucher pill: icon + sliding input area */
        .voucher-pill {
            display: flex;
            align-items: center;
            background: var(--surface-2);
            border: 1.5px solid var(--ink-faint);
            border-radius: var(--radius-pill);
            overflow: hidden;
            transition: border-color .2s;
            height: 42px;
        }
        .voucher-pill:focus-within { border-color: var(--ink); }

        .voucher-icon-btn {
            flex-shrink: 0;
            width: 42px; height: 42px;
            background: var(--ink);
            color: #fff;
            border: none;
            border-radius: var(--radius-pill);
            cursor: pointer;
            font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
            margin: -1px; /* compensate pill border */
        }
        .voucher-icon-btn:hover { background: var(--gold); }
        .voucher-icon-btn.active { background: var(--gold); }

        /* Sliding input area — width animates open/close */
        .voucher-slide {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow: hidden;
            max-width: 0;
            padding: 0;
            transition: max-width .3s cubic-bezier(.4,0,.2,1), padding .3s;
        }
        .voucher-slide.open {
            max-width: 280px;
            padding: 0 10px 0 10px;
        }
        .voucher-text-input {
            border: none;
            background: transparent;
            font-size: 13px;
            font-family: inherit;
            color: var(--ink);
            outline: none;
            width: 160px;
            min-width: 0;
        }
        .voucher-text-input::placeholder { color: var(--ink-soft); }
        .voucher-apply-btn {
            flex-shrink: 0;
            background: var(--ink);
            color: #fff;
            border: none;
            padding: 5px 12px;
            border-radius: var(--radius-pill);
            font-size: 11px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            letter-spacing: .04em;
            text-transform: uppercase;
            transition: background .15s;
            white-space: nowrap;
        }
        .voucher-apply-btn:hover { background: var(--gold); }

        /* Applied voucher badge (shown inline, replaces input) */
        .applied-voucher-badge {
            display: none;
            align-items: center;
            gap: 8px;
            background: var(--green-bg);
            color: var(--green);
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: var(--radius-pill);
            margin-left: 8px;
            white-space: nowrap;
        }
        .applied-voucher-badge.visible { display: inline-flex; }
        .remove-voucher-btn {
            background: none; border: none;
            color: var(--green);
            cursor: pointer;
            font-size: 13px;
            line-height: 1;
            padding: 0;
            transition: color .15s;
        }
        .remove-voucher-btn:hover { color: var(--red); }

        /* Discount info line */
        .discount-info {
            font-size: 11px;
            color: var(--green);
            margin-top: 3px;
            white-space: nowrap;
            height: 16px; /* always reserves space so footer doesn't jump */
        }

        /* — Right column: totals + checkout — */
        .footer-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }
        .totals-pill {
            display: flex;
            align-items: center;
            gap: 24px;
            background: var(--surface-2);
            border: 1.5px solid #ebebeb;
            padding: 8px 20px;
            border-radius: var(--radius-pill);
        }
        .total-item { text-align: right; }
        .total-label { font-size: 10px; color: var(--ink-soft); text-transform: uppercase; letter-spacing: .05em; line-height: 1.2; }
        .total-val { font-family: 'Spectral', serif; font-size: 19px; font-weight: 700; line-height: 1.2; }
        .total-sep { width: 1px; height: 32px; background: var(--ink-faint); }

        .checkout-btn {
            background: var(--ink); color: #fff;
            border: none;
            padding: 11px 24px;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
            transition: background .15s, transform .1s;
            letter-spacing: .02em;
        }
        .checkout-btn:hover:not(:disabled) { background: var(--gold); }
        .checkout-btn:active:not(:disabled) { transform: scale(.97); }
        .checkout-btn:disabled { background: #ccc; cursor: not-allowed; }

        /* ─── CHECKBOX STYLE ───────────────────────────────────────────── */
        input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--ink);
            cursor: pointer;
        }

        /* ─── SCROLLBAR ────────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--ink-faint); border-radius: 3px; }

        /* ════════════════════════════════════════════════════════════════ */
        /*  RESPONSIVE BREAKPOINTS                                         */
        /* ════════════════════════════════════════════════════════════════ */

        /* — Tablet (≤ 900px) — */
        @media (max-width: 900px) {
            :root { --footer-h: 160px; }

            .cart-topbar { padding: 0 20px; }
            .cart-main   { padding: 0 16px calc(var(--footer-h) + 32px); margin: 16px auto; }

            .cart-table-header { display: none; }

            .buy-now-row,
            .cart-row {
                grid-template-columns: 28px 1fr auto;
                grid-template-rows: auto auto;
                row-gap: 10px;
                column-gap: 10px;
                padding: 16px;
            }
            /* checkbox: col1, row span 2 */
            .buy-now-row > input[type="checkbox"],
            .cart-row    > input[type="checkbox"] {
                grid-column: 1; grid-row: 1 / 3;
                align-self: center;
            }
            /* product info: col2, row1 */
            .product-cell  { grid-column: 2; grid-row: 1; }
            /* qty: col2, row2 + price+total hidden */
            .qty-ctrl      { grid-column: 2; grid-row: 2; justify-content: flex-start; }
            .cell-price    { display: none; }
            .cell-total    {
                grid-column: 3; grid-row: 1;
                font-size: 15px;
                align-self: center;
                min-width: 72px;
                text-align: right;
            }
            /* delete: col3, row2 */
            .buy-now-row > div:last-child,
            .cart-row    > div:last-child {
                grid-column: 3; grid-row: 2;
                text-align: right;
            }
            .delete-item { width: auto; }

            .product-img { width: 64px; height: 64px; }
            .product-name { font-size: 14px; white-space: normal; }

            /* Footer top */
            .footer-top { padding: 8px 16px; gap: 8px; }

            /* Footer bottom: stack vertically */
            .footer-bottom {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto;
                gap: 10px;
                padding: 10px 16px;
            }

            .voucher-col   { grid-column: 1; }
            .footer-spacer { display: none; }
            .footer-right  {
                grid-column: 1;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 10px;
            }

            .totals-pill {
                gap: 16px;
                padding: 7px 16px;
                flex: 1;
            }
            .total-val { font-size: 17px; }

            .voucher-slide.open { max-width: 220px; }
            .voucher-text-input { width: 110px; }
        }

        /* — Mobile (≤ 480px) — */
        @media (max-width: 480px) {
            :root { --footer-h: 180px; }

            .cart-topbar-label { display: none; }
            .cart-topbar-divider { display: none; }

            .footer-right { gap: 8px; }
            .totals-pill  { gap: 10px; padding: 6px 12px; }
            .total-val    { font-size: 15px; }
            .checkout-btn { padding: 10px 16px; font-size: 12px; }

            .voucher-slide.open { max-width: 180px; }
            .voucher-text-input { width: 90px; }

            .also-divider { padding: 10px 16px 4px; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR ─────────────────────────────────────────────────────────── -->
<header class="cart-topbar">
    <div class="cart-topbar-left">
        <a href="../Homepage/index.php">
            <img src="../Resources/Logos/lookgood-black.png" alt="LookGood" class="logo-img">
        </a>
        <div class="cart-topbar-divider"></div>
        <span class="cart-topbar-label">Shopping Cart</span>
    </div>
    <button class="back-btn" id="backBtn">
        <i class="fas fa-arrow-left"></i> Back to shopping
    </button>
</header>

<!-- ── MAIN TABLE ─────────────────────────────────────────────────────── -->
<main class="cart-main">
    <div class="cart-table-wrap">
        <div class="cart-table-header">
            <div></div>
            <div>Product Name</div>
            <div>Unit Price</div>
            <div>Quantity</div>
            <div>Total Price</div>
            <div>Actions</div>
        </div>
        <div id="buyNowRow" style="display:none;" class="buy-now-row"></div>
        <div id="alsoDivider" class="also-divider" style="display:none;">
            <div class="divider-line"></div>
            <span class="divider-txt">Also in your cart</span>
            <div class="divider-line"></div>
        </div>
        <div id="cartItemsContainer"></div>
        <div id="emptyCartMsg" class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            No other items in your cart
        </div>
    </div>
</main>

<!-- ── FIXED FOOTER ──────────────────────────────────────────────────── -->
<div class="cart-footer" id="cartFooter" style="display:none;">

    <!-- Top strip -->
    <div class="footer-top">
        <div class="select-all-wrapper">
            <label class="select-all-label">
                <input type="checkbox" id="selectAllCheckbox">
                Select all items
            </label>
            <span class="total-items-badge" id="totalItemsBadge">0 items selected</span>
        </div>
        <button class="btn-move-wishlist" id="moveWishlistBtn" disabled>
            <i class="far fa-heart"></i> Move to wishlist
        </button>
    </div>

    <!-- Bottom strip -->
    <div class="footer-bottom">

        <!-- LEFT: voucher -->
        <div class="voucher-col">
            <div class="voucher-pill" id="voucherPill">
                <!-- Icon toggles the sliding input -->
                <button class="voucher-icon-btn" id="voucherIconBtn" title="Apply voucher">
                    <i class="fas fa-tag"></i>
                </button>
                <!-- Sliding input area — toggled via .open class -->
                <div class="voucher-slide" id="voucherSlide">
                    <input
                        type="text"
                        class="voucher-text-input"
                        id="voucherCodeInput"
                        placeholder="Voucher code…"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <button class="voucher-apply-btn" id="applyVoucherBtn">Apply</button>
                </div>
            </div>

            <!-- Applied badge shown outside the pill -->
            <div class="applied-voucher-badge" id="appliedVoucherDisplay">
                <i class="fas fa-check-circle"></i>
                <span id="appliedVoucherText"></span>
                <button class="remove-voucher-btn" id="removeVoucherBtn" title="Remove voucher">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Discount info line: always reserves height so nothing shifts -->
            <div class="discount-info" id="discountInfo"></div>
        </div>

        <!-- SPACER (invisible, pushes right col to the right) -->
        <div class="footer-spacer"></div>

        <!-- RIGHT: totals + checkout — NEVER moves -->
        <div class="footer-right">
            <div class="totals-pill">
                <div class="total-item">
                    <div class="total-label">Subtotal</div>
                    <div class="total-val" id="subtotalDisplay">₱0.00</div>
                </div>
                <div class="total-sep"></div>
                <div class="total-item">
                    <div class="total-label">Total</div>
                    <div class="total-val" id="grandTotalDisplay">₱0.00</div>
                </div>
            </div>
            <button class="checkout-btn" id="proceedCheckoutBtn">
                Checkout&nbsp;<i class="fas fa-arrow-right"></i>
            </button>
        </div>

    </div>
</div>

<!-- ── SCRIPT ─────────────────────────────────────────────────────────── -->
<script>
/* ===================================================================
   VALID VOUCHERS
   =================================================================== */
const VALID_VOUCHERS = [
    { code: 'NEWUSER50',   discountPercent: 50,  description: '50% off for new users' },
    { code: 'FLASHSALE100',discountPercent: 100, description: '100% off flash sale'   },
    { code: 'WELCOME20',   discountPercent: 20,  description: '20% off welcome'        }
];

/* ===================================================================
   STATE
   =================================================================== */
let activeVoucher = null;
let buyNowItem    = null;
let cartItems     = [];
let bnSelected    = true;

/* ===================================================================
   LOAD
   =================================================================== */
function loadData() {
    try {
        const raw = localStorage.getItem('lookgood_buynow');
        if (raw) buyNowItem = JSON.parse(raw);
    } catch(e) { buyNowItem = null; }

    try {
        const raw = localStorage.getItem('lookgood_cart');
        if (raw) cartItems = JSON.parse(raw).map(i => ({ ...i, selected: false, quantity: i.quantity || 1 }));
    } catch(e) { cartItems = []; }

    // If buyNow item is also in cart, merge quantities
    if (buyNowItem) {
        const dup = cartItems.findIndex(i => i.id === buyNowItem.id);
        if (dup !== -1) {
            buyNowItem.quantity = (buyNowItem.quantity || 1) + cartItems[dup].quantity;
            cartItems.splice(dup, 1);
            localStorage.setItem('lookgood_buynow', JSON.stringify(buyNowItem));
            localStorage.setItem('lookgood_cart',   JSON.stringify(cartItems));
        }
    }
    renderCart();
}

/* ===================================================================
   RENDER
   =================================================================== */
function renderCart() {
    const buyNowDiv     = document.getElementById('buyNowRow');
    const cartContainer = document.getElementById('cartItemsContainer');
    const alsoDivider   = document.getElementById('alsoDivider');
    const emptyMsg      = document.getElementById('emptyCartMsg');
    const footer        = document.getElementById('cartFooter');

    /* — Buy Now Row — */
    if (buyNowItem) {
        buyNowDiv.style.display = 'grid';
        const STOCK = 10;
        const qty   = buyNowItem.quantity || 1;
        const outOfStock = STOCK === 0;
        const exceeds    = qty > STOCK;
        const badge = (outOfStock || exceeds)
            ? `<span class="out-of-stock-badge"><i class="fas fa-exclamation-triangle"></i> ${outOfStock ? 'Out of Stock' : `Only ${STOCK} left`}</span>` : '';

        buyNowDiv.innerHTML = `
            <input type="checkbox" id="bnCheckbox" ${bnSelected ? 'checked' : ''}>
            <div class="product-cell">
                <img class="product-img" src="${buyNowItem.image || ''}" onerror="this.src='https://placehold.co/80x80?text=No+Image'" alt="">
                <div class="product-info">
                    <div class="product-name">${esc(buyNowItem.name)} ${badge}</div>
                    <div class="product-meta">${esc(buyNowItem.brand || '')}</div>
                </div>
            </div>
            <div class="cell-price">₱${buyNowItem.price.toFixed(2)}</div>
            <div class="qty-ctrl">
                <button class="qty-btn bn-decr" ${outOfStock || exceeds ? 'disabled' : ''}>−</button>
                <input type="number" class="qty-input bn-qty" value="${qty}" min="1" ${outOfStock || exceeds ? 'disabled' : ''}>
                <button class="qty-btn bn-incr" ${outOfStock || exceeds ? 'disabled' : ''}>+</button>
            </div>
            <div class="cell-total">₱${(buyNowItem.price * qty).toFixed(2)}</div>
            <div><button class="delete-item bn-delete" title="Remove"><i class="fas fa-trash-alt"></i></button></div>
        `;
        attachBuyNowEvents();
    } else {
        buyNowDiv.style.display = 'none';
    }

    /* — Cart Rows — */
    const hasItems = cartItems.length > 0;
    alsoDivider.style.display = (hasItems && buyNowItem) ? 'flex' : 'none';
    emptyMsg.style.display    = hasItems ? 'none' : (buyNowItem ? 'none' : 'block');
    footer.style.display      = (hasItems || buyNowItem) ? 'block' : 'none';

    if (hasItems) {
        cartContainer.innerHTML = cartItems.map((item, idx) => {
            const STOCK = 10;
            const outOfStock = STOCK === 0;
            const exceeds    = item.quantity > STOCK;
            const badge = (outOfStock || exceeds)
                ? `<span class="out-of-stock-badge"><i class="fas fa-exclamation-triangle"></i> ${outOfStock ? 'Out of Stock' : `Only ${STOCK} left`}</span>` : '';
            return `
                <div class="cart-row ${item.selected ? 'is-checked' : ''}" data-index="${idx}">
                    <input type="checkbox" class="cart-checkbox" ${item.selected ? 'checked' : ''} ${outOfStock || exceeds ? 'disabled' : ''}>
                    <div class="product-cell">
                        <img class="product-img" src="${item.image || ''}" onerror="this.src='https://placehold.co/80x80?text=No+Image'" alt="">
                        <div class="product-info">
                            <div class="product-name">${esc(item.name)} ${badge}</div>
                            <div class="product-meta">${esc(item.brand || '')}</div>
                        </div>
                    </div>
                    <div class="cell-price">₱${item.price.toFixed(2)}</div>
                    <div class="qty-ctrl">
                        <button class="qty-btn cart-decr" data-index="${idx}" ${outOfStock || exceeds ? 'disabled' : ''}>−</button>
                        <input type="number" class="qty-input cart-qty" value="${item.quantity}" min="1" data-index="${idx}" ${outOfStock || exceeds ? 'disabled' : ''}>
                        <button class="qty-btn cart-incr" data-index="${idx}" ${outOfStock || exceeds ? 'disabled' : ''}>+</button>
                    </div>
                    <div class="cell-total">₱${(item.price * item.quantity).toFixed(2)}</div>
                    <div><button class="delete-item cart-delete" data-index="${idx}" title="Remove"><i class="fas fa-trash-alt"></i></button></div>
                </div>
            `;
        }).join('');
        attachCartEvents();
    } else {
        cartContainer.innerHTML = '';
    }

    updateTotalsAndFooter();
}

/* ===================================================================
   ATTACH EVENTS – BUY NOW ROW
   =================================================================== */
function attachBuyNowEvents() {
    const cb       = document.getElementById('bnCheckbox');
    const decrBtn  = document.querySelector('.bn-decr');
    const incrBtn  = document.querySelector('.bn-incr');
    const qtyInp   = document.querySelector('.bn-qty');
    const delBtn   = document.querySelector('.bn-delete');

    if (cb)      cb.onchange       = () => { bnSelected = cb.checked; updateTotalsAndFooter(); };
    if (decrBtn) decrBtn.onclick   = () => changeBuyNowQty(-1);
    if (incrBtn) incrBtn.onclick   = () => changeBuyNowQty(+1);
    if (qtyInp)  qtyInp.onchange   = () => changeBuyNowQty(0, parseInt(qtyInp.value) || 1);
    if (delBtn)  delBtn.onclick    = () => {
        if (confirm('Remove this item from your cart?')) {
            buyNowItem = null;
            localStorage.removeItem('lookgood_buynow');
            renderCart();
        }
    };
}
function changeBuyNowQty(delta, newVal = null) {
    if (!buyNowItem) return;
    let qty = buyNowItem.quantity || 1;
    qty = newVal !== null ? newVal : qty + delta;
    if (qty < 1) qty = 1;
    buyNowItem.quantity = qty;
    localStorage.setItem('lookgood_buynow', JSON.stringify(buyNowItem));
    renderCart();
}

/* ===================================================================
   ATTACH EVENTS – CART ROWS
   =================================================================== */
function attachCartEvents() {
    document.querySelectorAll('.cart-checkbox').forEach(chk => {
        chk.onchange = e => {
            const idx = parseInt(e.target.closest('.cart-row').dataset.index);
            if (!isNaN(idx) && cartItems[idx]) cartItems[idx].selected = e.target.checked;
            saveCart(); renderCart();
        };
    });
    document.querySelectorAll('.cart-decr').forEach(btn => {
        btn.onclick = () => { const i = +btn.dataset.index; if (cartItems[i]) changeCartQty(i, -1); };
    });
    document.querySelectorAll('.cart-incr').forEach(btn => {
        btn.onclick = () => { const i = +btn.dataset.index; if (cartItems[i]) changeCartQty(i, +1); };
    });
    document.querySelectorAll('.cart-qty').forEach(inp => {
        inp.onchange = () => { const i = +inp.dataset.index; if (cartItems[i]) changeCartQty(i, 0, parseInt(inp.value) || 1); };
    });
    document.querySelectorAll('.cart-delete').forEach(btn => {
        btn.onclick = () => {
            const idx = +btn.dataset.index;
            if (!isNaN(idx) && cartItems[idx]) {
                if (confirm('Remove this item from your cart?')) {
                    cartItems.splice(idx, 1);
                    saveCart(); renderCart();
                }
            }
        };
    });
}
function changeCartQty(idx, delta, newVal = null) {
    let qty = cartItems[idx].quantity;
    qty = newVal !== null ? newVal : qty + delta;
    if (qty < 1) qty = 1;
    cartItems[idx].quantity = qty;
    saveCart(); renderCart();
}
function saveCart() {
    localStorage.setItem('lookgood_cart', JSON.stringify(
        cartItems.map(i => ({ id: i.id, name: i.name, price: i.price, image: i.image, quantity: i.quantity, brand: i.brand }))
    ));
}

/* ===================================================================
   TOTALS & FOOTER STATE
   =================================================================== */
function getSelectedSubtotal() {
    let sum = 0;
    if (buyNowItem && bnSelected) sum += buyNowItem.price * (buyNowItem.quantity || 1);
    cartItems.forEach(i => { if (i.selected) sum += i.price * i.quantity; });
    return sum;
}

function updateTotalsAndFooter() {
    const subtotal       = getSelectedSubtotal();
    const discountPct    = activeVoucher ? Math.min(activeVoucher.discountPercent, 100) : 0;
    const discountAmount = subtotal * (discountPct / 100);
    const grandTotal     = subtotal - discountAmount;

    document.getElementById('subtotalDisplay').textContent  = '₱' + subtotal.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = '₱' + grandTotal.toFixed(2);

    /* Discount info line (always reserves space – just text changes) */
    const infoEl = document.getElementById('discountInfo');
    if (activeVoucher && discountAmount > 0) {
        infoEl.innerHTML = `<i class="fas fa-gift"></i> ${activeVoucher.code} saves you ₱${discountAmount.toFixed(2)}`;
    } else {
        infoEl.innerHTML = '';
    }

    /* Applied badge */
    const badge    = document.getElementById('appliedVoucherDisplay');
    const badgeTxt = document.getElementById('appliedVoucherText');
    if (activeVoucher) {
        badgeTxt.textContent = `${activeVoucher.code} (${activeVoucher.discountPercent}% off)`;
        badge.classList.add('visible');
    } else {
        badge.classList.remove('visible');
    }

    /* Selected count */
    let selectedCount  = (buyNowItem && bnSelected) ? 1 : 0;
    selectedCount     += cartItems.filter(i => i.selected).length;
    document.getElementById('totalItemsBadge').textContent =
        `${selectedCount} item${selectedCount !== 1 ? 's' : ''} selected`;

    /* Wishlist btn */
    document.getElementById('moveWishlistBtn').disabled = (selectedCount === 0);

    /* Select all checkbox state */
    const selectAll = document.getElementById('selectAllCheckbox');
    const totalCheckable = (buyNowItem ? 1 : 0) + cartItems.length;
    const allSelected    = selectedCount === totalCheckable && totalCheckable > 0;
    selectAll.checked       = allSelected;
    selectAll.indeterminate = selectedCount > 0 && !allSelected;

    /* Checkout btn */
    const hasValid =
        (buyNowItem && bnSelected && (buyNowItem.quantity || 1) <= 10) ||
        cartItems.some(i => i.selected && i.quantity <= 10);
    document.getElementById('proceedCheckoutBtn').disabled = !hasValid;
}

/* ===================================================================
   VOUCHER UI
   — The input slides open inline inside the pill, so no other
     element on the page shifts when it opens/closes.
   =================================================================== */
const voucherIconBtn  = document.getElementById('voucherIconBtn');
const voucherSlide    = document.getElementById('voucherSlide');
const voucherCodeInput= document.getElementById('voucherCodeInput');
const applyVoucherBtn = document.getElementById('applyVoucherBtn');

let voucherOpen = false;

function openVoucherInput() {
    voucherOpen = true;
    voucherSlide.classList.add('open');
    voucherIconBtn.classList.add('active');
    setTimeout(() => voucherCodeInput.focus(), 300);
}
function closeVoucherInput() {
    voucherOpen = false;
    voucherSlide.classList.remove('open');
    voucherIconBtn.classList.remove('active');
    voucherCodeInput.value = '';
}

voucherIconBtn.addEventListener('click', e => {
    e.stopPropagation();
    voucherOpen ? closeVoucherInput() : openVoucherInput();
});

function applyVoucher() {
    const code  = voucherCodeInput.value.trim().toUpperCase();
    if (!code)  { shake(voucherCodeInput); return; }
    const found = VALID_VOUCHERS.find(v => v.code === code);
    if (!found) {
        shake(voucherCodeInput);
        voucherCodeInput.value = '';
        voucherCodeInput.placeholder = 'Invalid code – try again';
        setTimeout(() => voucherCodeInput.placeholder = 'Voucher code…', 2500);
        return;
    }
    activeVoucher = { code: found.code, discountPercent: found.discountPercent };
    updateTotalsAndFooter();
    closeVoucherInput();
}

applyVoucherBtn.addEventListener('click', applyVoucher);
voucherCodeInput.addEventListener('keydown', e => { if (e.key === 'Enter') applyVoucher(); });

/* Remove voucher */
document.getElementById('removeVoucherBtn').addEventListener('click', () => {
    activeVoucher = null;
    updateTotalsAndFooter();
});

/* Close on outside click */
document.addEventListener('click', e => {
    if (voucherOpen && !document.getElementById('voucherPill').contains(e.target)) {
        closeVoucherInput();
    }
});

/* Shake animation for invalid input */
function shake(el) {
    el.style.animation = 'none';
    el.offsetHeight; // reflow
    el.style.animation = 'shake .35s ease';
}

/* ===================================================================
   SELECT ALL
   =================================================================== */
document.getElementById('selectAllCheckbox').addEventListener('change', e => {
    const checked = e.target.checked;
    if (buyNowItem) bnSelected = checked;
    cartItems.forEach(i => i.selected = checked);
    saveCart(); renderCart();
});

/* ===================================================================
   MOVE TO WISHLIST
   =================================================================== */
document.getElementById('moveWishlistBtn').addEventListener('click', () => {
    let wishlist = [];
    try { wishlist = JSON.parse(localStorage.getItem('lookgood_wishlist') || '[]'); } catch(e) {}

    cartItems.filter(i => i.selected).forEach(item => {
        if (!wishlist.find(w => w.id === item.id))
            wishlist.push({ id: item.id, name: item.name, price: item.price, image: item.image });
    });

    if (buyNowItem && bnSelected) {
        if (!wishlist.find(w => w.id === buyNowItem.id))
            wishlist.push({ id: buyNowItem.id, name: buyNowItem.name, price: buyNowItem.price, image: buyNowItem.image });
        buyNowItem = null;
        localStorage.removeItem('lookgood_buynow');
    }

    cartItems = cartItems.filter(i => !i.selected);
    localStorage.setItem('lookgood_wishlist', JSON.stringify(wishlist));
    localStorage.setItem('lookgood_cart', JSON.stringify(
        cartItems.map(i => ({ id: i.id, name: i.name, price: i.price, image: i.image, quantity: i.quantity }))
    ));
    renderCart();
});

/* ===================================================================
   CHECKOUT
   =================================================================== */
document.getElementById('proceedCheckoutBtn').addEventListener('click', () => {
    const selectedItems = [];
    if (buyNowItem && bnSelected) selectedItems.push({ ...buyNowItem, quantity: buyNowItem.quantity || 1 });
    cartItems.forEach(i => { if (i.selected) selectedItems.push({ ...i }); });
    if (!selectedItems.length) { alert('No items selected.'); return; }

    const subtotal       = getSelectedSubtotal();
    const discountPct    = activeVoucher ? Math.min(activeVoucher.discountPercent, 100) : 0;
    const discountAmount = subtotal * (discountPct / 100);
    const total          = subtotal - discountAmount;
    const clientOrderRef = 'ORD-' + Date.now().toString(36).toUpperCase() + '-' + Math.random().toString(36).slice(2, 8).toUpperCase();

    localStorage.setItem('lookgood_checkout_data', JSON.stringify({
        clientOrderRef,
        items: selectedItems,
        appliedVouchers: activeVoucher ? [activeVoucher] : [],
        subtotal, discountAmount, total
    }));
    window.location.href = 'checkout.php';
});

/* ===================================================================
   UTILS
   =================================================================== */
function esc(str) {
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

document.getElementById('backBtn').addEventListener('click', () => history.back());

/* ===================================================================
   SHAKE KEYFRAME (injected once)
   =================================================================== */
const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
@keyframes shake {
    0%,100%{ transform: translateX(0); }
    20%    { transform: translateX(-5px); }
    40%    { transform: translateX(5px); }
    60%    { transform: translateX(-4px); }
    80%    { transform: translateX(4px); }
}`;
document.head.appendChild(shakeStyle);

/* ===================================================================
   INIT
   =================================================================== */
loadData();
</script>
</body>
</html>