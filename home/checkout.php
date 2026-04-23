<?php
// checkout.php
if (!defined('LG_SESSION_SCOPE')) define('LG_SESSION_SCOPE', 'user');
require_once __DIR__ . '/../session_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Checkout – LookGood</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/lookgood/css/User/checkout.css">
</head>

<body>

<header class="topbar">
    <div class="topbar-left">
        <a href="/index.php">
            <img src="/lookgood/home/Resources/Logos/lookgood-black.png" alt="LookGood" class="logo-img">
        </a>
        <div class="topbar-divider"></div>
        <span class="topbar-label">Checkout</span>
    </div>
    <button class="back-btn" onclick="history.back()">
        <i class="fas fa-arrow-left"></i> Back to cart
    </button>
</header>

<div class="checkout-page">

    <!-- LEFT: FORM COLUMN -->
    <div class="form-col">
        <div class="form-col-inner">
            <h1 class="section-title">Complete your order</h1>
            <p class="section-subtitle">We'll send your receipt once payment is confirmed.</p>

            <form id="checkoutForm" novalidate>

                <!-- CONTACT INFO -->
                <div class="field-section">
                    <div class="field-section-label"><i class="fas fa-user"></i> Contact Info</div>
                    <div class="float-group">
                        <input type="text" class="float-input" id="fullName" placeholder=" " required autocomplete="name">
                        <label class="float-label" for="fullName">Full Name</label>
                    </div>
                    <div class="field-row">
                        <div class="float-group">
                            <input type="email" class="float-input" id="email" placeholder=" " required autocomplete="email">
                            <label class="float-label" for="email">Email Address</label>
                        </div>
                        <div class="float-group">
                            <input type="tel" class="float-input" id="phone" placeholder=" " required autocomplete="tel">
                            <label class="float-label" for="phone">Phone Number</label>
                        </div>
                    </div>
                </div>

                <!-- DELIVERY ADDRESS -->
                <div class="field-section">
                    <div class="field-section-label"><i class="fas fa-location-dot"></i> Delivery Address</div>
                    <div class="float-group">
                        <input type="text" class="float-input" id="address1" placeholder=" " required autocomplete="address-line1">
                        <label class="float-label" for="address1">Address Line 1</label>
                    </div>
                    <div class="float-group">
                        <input type="text" class="float-input" id="address2" placeholder=" " autocomplete="address-line2">
                        <label class="float-label" for="address2">Address Line 2 <span class="optional-tag">(optional)</span></label>
                    </div>
                    <div class="field-row">
                        <div class="float-group select-wrap">
                            <select class="float-input" id="region" required>
                                <option value="" disabled selected hidden></option>
                                <option value="NCR">NCR — National Capital Region</option>
                                <option value="CAR">CAR — Cordillera Administrative Region</option>
                                <option value="I">Region I — Ilocos Region</option>
                                <option value="II">Region II — Cagayan Valley</option>
                                <option value="III">Region III — Central Luzon</option>
                                <option value="IV-A">Region IV-A — CALABARZON</option>
                                <option value="IV-B">Region IV-B — MIMAROPA</option>
                                <option value="V">Region V — Bicol Region</option>
                                <option value="VI">Region VI — Western Visayas</option>
                                <option value="VII">Region VII — Central Visayas</option>
                                <option value="VIII">Region VIII — Eastern Visayas</option>
                                <option value="IX">Region IX — Zamboanga Peninsula</option>
                                <option value="X">Region X — Northern Mindanao</option>
                                <option value="XI">Region XI — Davao Region</option>
                                <option value="XII">Region XII — SOCCSKSARGEN</option>
                                <option value="XIII">Region XIII — Caraga</option>
                                <option value="BARMM">BARMM — Bangsamoro Autonomous Region</option>
                            </select>
                            <label class="float-label" for="region">Region</label>
                        </div>
                        <div class="float-group">
                            <input type="text" class="float-input" id="province" placeholder=" " required autocomplete="address-level1">
                            <label class="float-label" for="province">Province</label>
                        </div>
                    </div>
                    <div class="field-row">
                        <div class="float-group">
                            <input type="text" class="float-input" id="city" placeholder=" " required autocomplete="address-level2">
                            <label class="float-label" for="city">City / Municipality</label>
                        </div>
                        <div class="float-group">
                            <input type="text" class="float-input" id="zip" placeholder=" " required autocomplete="postal-code" maxlength="4" inputmode="numeric">
                            <label class="float-label" for="zip">ZIP Code</label>
                        </div>
                    </div>

                    <!-- Save as default address button + custom confirmation -->
                    <div class="save-default-group">
                        <button type="button" id="saveDefaultAddressBtn" class="save-default-btn">
                            <i class="fas fa-save"></i> Save as Default Address
                        </button>
                        <span id="saveDefaultMsg" class="save-default-msg"></span>
                        <div id="saveConfirmBox" class="save-confirm-box" style="display: none;">
                            <span class="confirm-text">Save this address as default?</span>
                            <button type="button" id="confirmYes" class="confirm-btn yes">Yes</button>
                            <button type="button" id="confirmNo" class="confirm-btn no">No</button>
                        </div>
                    </div>
                </div>

                <!-- SHIPPING METHOD -->
                <div class="field-section">
                    <div class="field-section-label"><i class="fas fa-truck-fast"></i> Shipping Method</div>
                    <div class="icon-dropdown">
                        <div class="icon-dropdown-icon"><i class="fas fa-shipping-fast"></i></div>
                        <select class="icon-dropdown-select" id="shippingMethod" required>
                            <option value="free" data-fee="0">Free Shipping (5-7 days) – ₱0</option>
                            <option value="standard" data-fee="99" selected>Standard Shipping (3-5 days) – ₱99</option>
                            <option value="express" data-fee="199">Express Shipping (1-2 days) – ₱199</option>
                        </select>
                    </div>
                </div>

                <!-- COURIER SERVICE -->
                <div class="field-section">
                    <div class="field-section-label"><i class="fas fa-box"></i> Courier Service</div>
                    <div class="icon-dropdown">
                        <div class="icon-dropdown-icon"><i class="fas fa-truck"></i></div>
                        <select class="icon-dropdown-select" id="courierService" required>
                            <option value="LBC">LBC</option>
                            <option value="Ninja Van">Ninja Van</option>
                            <option value="J&T Express" selected>J&T Express</option>
                            <option value="Flash Express">Flash Express</option>
                        </select>
                    </div>
                </div>

                <!-- PAYMENT METHOD -->
                <div class="field-section">
                    <div class="field-section-label"><i class="fas fa-credit-card"></i> Payment Method</div>
                    <div class="icon-dropdown">
                        <div class="icon-dropdown-icon"><i class="fas fa-credit-card"></i></div>
                        <select class="icon-dropdown-select" id="paymentMethod" required>
                            <option value="" disabled selected>Select a payment method</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                </div>

                <!-- DELIVERY NOTE -->
                <div class="field-section">
                    <div class="field-section-label"><i class="fas fa-note-sticky"></i> Delivery Note <span class="optional-tag">(optional)</span></div>
                    <div class="float-group">
                        <textarea class="float-input" id="deliveryNote" placeholder=" "></textarea>
                        <label class="float-label" for="deliveryNote">Special instructions for your rider…</label>
                    </div>
                </div>

                <!-- TERMS & CONDITIONS -->
                <div class="terms-group">
                    <input type="checkbox" id="termsCheckbox" required>
                    <label for="termsCheckbox">
                        I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and
                        <a href="terms.php#return-policy" target="_blank">Refund Policy</a>.
                    </label>
                </div>

                <!-- ERROR MESSAGE -->
                <div class="error-message" id="paymentError">
                    <i class="fas fa-circle-exclamation"></i>
                    <span id="paymentErrorText">Something went wrong.</span>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" class="pay-btn" id="payNowBtn">
                    <span class="btn-text"><i class="fas fa-lock"></i> Place Order</span>
                    <span class="spinner"><i class="fas fa-spinner fa-spin"></i> Processing…</span>
                </button>

            </form>
        </div>
    </div>

    <!-- RIGHT: SUMMARY COLUMN -->
    <div class="summary-col">
        <div class="step-indicator">
            <div class="step-item done"><div class="step-circle"><i class="fas fa-check"></i></div><span class="step-name">Cart</span></div>
            <div class="step-connector done"></div>
            <div class="step-item active"><div class="step-circle">2</div><span class="step-name">Checkout</span></div>
            <div class="step-connector"></div>
            <div class="step-item"><div class="step-circle">3</div><span class="step-name">Confirm</span></div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Order Summary</div>
            <div class="summary-items" id="summaryItems"></div>
            <div class="summary-totals">
                <div class="summary-line"><span>Subtotal</span><span id="summarySubtotal">₱0.00</span></div>
                <div class="summary-line discount" id="discountLine" style="display:none;"><span id="discountLabel">Discount</span><span id="summaryDiscount">-₱0.00</span></div>
                <div class="summary-line"><span>Shipping</span><span id="summaryShipping">₱0.00</span></div>
                <div class="summary-line"><span>VAT (12%)</span><span id="summaryTax">₱0.00</span></div>
                <div class="summary-line grand"><span>Total</span><span id="summaryTotal">₱0.00</span></div>
            </div>
        </div>
    </div>
</div>

<script src="/lookgood/userActions/checkout.js"></script>
</body>
</html>