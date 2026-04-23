// checkout.js
let checkoutData = null;
let currentShippingFee = 99;
let currentShippingMethod = 'standard'; // tracks selected shipping method value

function saveDefaultAddress() {
    const defaultAddr = {
        fullName: document.getElementById('fullName').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        address1: document.getElementById('address1').value.trim(),
        address2: document.getElementById('address2').value.trim(),
        region: document.getElementById('region').value,
        province: document.getElementById('province').value.trim(),
        city: document.getElementById('city').value.trim(),
        zip: document.getElementById('zip').value.trim()
    };
    localStorage.setItem('lookgood_default_address', JSON.stringify(defaultAddr));
}

function loadDefaultAddress() {
    const defaultAddr = localStorage.getItem('lookgood_default_address');
    if (defaultAddr) {
        try {
            const addr = JSON.parse(defaultAddr);
            if (addr.fullName) document.getElementById('fullName').value = addr.fullName;
            if (addr.email) document.getElementById('email').value = addr.email;
            if (addr.phone) document.getElementById('phone').value = addr.phone;
            if (addr.address1) document.getElementById('address1').value = addr.address1;
            if (addr.address2) document.getElementById('address2').value = addr.address2;
            if (addr.region) document.getElementById('region').value = addr.region;
            if (addr.province) document.getElementById('province').value = addr.province;
            if (addr.city) document.getElementById('city').value = addr.city;
            if (addr.zip) document.getElementById('zip').value = addr.zip;
        } catch(e) {}
    }
}

function setupSaveDefaultButton() {
    const saveBtn = document.getElementById('saveDefaultAddressBtn');
    const confirmBox = document.getElementById('saveConfirmBox');
    const msgSpan = document.getElementById('saveDefaultMsg');
    let pendingSave = false;

    saveBtn.addEventListener('click', () => {
        const fullName = document.getElementById('fullName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const address1 = document.getElementById('address1').value.trim();
        const region = document.getElementById('region').value;
        const province = document.getElementById('province').value.trim();
        const city = document.getElementById('city').value.trim();
        const zip = document.getElementById('zip').value.trim();

        if (!fullName || !email || !phone || !address1 || !province || !city || !zip || !region) {
            msgSpan.textContent = 'Please fill in all required address fields first.';
            msgSpan.style.color = 'var(--red)';
            setTimeout(() => { msgSpan.textContent = ''; }, 3000);
            return;
        }

        confirmBox.style.display = 'flex';
        pendingSave = true;
    });

    document.getElementById('confirmYes').addEventListener('click', () => {
        if (pendingSave) {
            saveDefaultAddress();
            msgSpan.textContent = '✓ Default address saved!';
            msgSpan.style.color = 'var(--green)';
            setTimeout(() => { msgSpan.textContent = ''; }, 3000);
            confirmBox.style.display = 'none';
            pendingSave = false;
        }
    });

    document.getElementById('confirmNo').addEventListener('click', () => {
        confirmBox.style.display = 'none';
        pendingSave = false;
    });

    document.addEventListener('click', (e) => {
        if (!confirmBox.contains(e.target) && e.target !== saveBtn) {
            confirmBox.style.display = 'none';
            pendingSave = false;
        }
    });
}

function attachShippingListener() {
    const shippingSelect = document.getElementById('shippingMethod');
    if (shippingSelect) {
        // Set initial value
        currentShippingMethod = shippingSelect.value;
        const initialOption = shippingSelect.options[shippingSelect.selectedIndex];
        currentShippingFee = parseInt(initialOption.getAttribute('data-fee')) || 0;

        shippingSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            currentShippingFee = parseInt(selectedOption.getAttribute('data-fee')) || 0;
            currentShippingMethod = this.value; // 'free' | 'standard' | 'express'
            renderSummary();
        });
    }
}

function renderSummary() {
    if (!checkoutData) return;
    const itemsEl = document.getElementById('summaryItems');
    itemsEl.innerHTML = (checkoutData.items || []).map(item => `
        <div class="summary-item">
            <img class="summary-item-img" src="${item.image || ''}" onerror="this.src='https://placehold.co/52x52?text=Item'" alt="${esc(item.name)}">
            <div class="summary-item-info">
                <div class="summary-item-name">${esc(item.name)}</div>
                <div class="summary-item-qty">Qty: ${item.quantity}${item.brand ? ' · ' + esc(item.brand) : ''}</div>
            </div>
            <div class="summary-item-price">₱${(item.price * item.quantity).toFixed(2)}</div>
        </div>
    `).join('');
    const subtotal = checkoutData.subtotal || 0;
    const discount = checkoutData.discountAmount || 0;
    const taxableBase = (subtotal - discount) + currentShippingFee;
    const tax = taxableBase * 0.12;
    const total = taxableBase + tax;
    document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('summaryShipping').textContent = '₱' + currentShippingFee.toFixed(2);
    document.getElementById('summaryTax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('summaryTotal').textContent = '₱' + total.toFixed(2);
    if (discount > 0) {
        document.getElementById('discountLine').style.display = 'flex';
        document.getElementById('summaryDiscount').textContent = '-₱' + discount.toFixed(2);
        const vouchers = checkoutData.appliedVouchers;
        if (vouchers && vouchers.length) {
            document.getElementById('discountLabel').textContent = `Discount (${vouchers[0].code})`;
        }
    } else {
        document.getElementById('discountLine').style.display = 'none';
    }
    checkoutData.finalTotal = total;
    checkoutData.shippingFee = currentShippingFee;
}

function showError(msg) {
    const errBox = document.getElementById('paymentError');
    const errText = document.getElementById('paymentErrorText');
    const payBtn = document.getElementById('payNowBtn');
    errText.textContent = msg;
    errBox.classList.add('show');
    payBtn.classList.remove('loading');
    payBtn.disabled = false;
}

(function init() {
    const raw = localStorage.getItem('lookgood_checkout_data');
    if (!raw) {
        alert('No items selected. Please go back to your cart.');
        window.location.href = 'cart.php';
        return;
    }
    try { checkoutData = JSON.parse(raw); } catch(e) {
        alert('Cart data is corrupted. Please go back to your cart.');
        window.location.href = 'cart.php';
        return;
    }
    loadDefaultAddress();
    setupSaveDefaultButton();
    renderSummary();
    attachShippingListener();

    const form = document.getElementById('checkoutForm');
    const payBtn = document.getElementById('payNowBtn');
    const termsCheck = document.getElementById('termsCheckbox');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        document.getElementById('paymentError').classList.remove('show');

        const fullName = document.getElementById('fullName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const address1 = document.getElementById('address1').value.trim();
        const address2 = document.getElementById('address2').value.trim();
        const city = document.getElementById('city').value.trim();
        const province = document.getElementById('province').value.trim();
        const region = document.getElementById('region').value;
        const zip = document.getElementById('zip').value.trim();
        const payment = document.getElementById('paymentMethod').value;
        const note = document.getElementById('deliveryNote').value.trim();
        const courierService = document.getElementById('courierService').value;

        if (!fullName || !email || !phone || !address1 || !city || !province || !region || !zip) {
            showError('Please fill in all required fields.');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError('Please enter a valid email address.');
            return;
        }
        if (!payment) {
            showError('Please select a payment method.');
            return;
        }
        if (!termsCheck.checked) {
            showError('You must agree to the Terms & Conditions and Refund Policy.');
            return;
        }

        payBtn.disabled = true;
        payBtn.classList.add('loading');

        const amountCentavos = Math.round((checkoutData.finalTotal || 0) * 100);
        if (amountCentavos < 100) {
            showError('Order total is too low (minimum ₱1.00).');
            return;
        }

        // ── Build the order object saved to localStorage ──────────────────
        // shippingMethod uses the SELECT value ('free'|'standard'|'express')
        // save-order.php will resolve this to a human label + ETA
        const orderInfo = {
            clientOrderRef: checkoutData.clientOrderRef || null,
            items: checkoutData.items,
            subtotal: checkoutData.subtotal,
            discount: checkoutData.discountAmount,
            shippingFee: currentShippingFee,
            shippingMethod: currentShippingMethod,   // ← 'free' | 'standard' | 'express'
            courierService: courierService,
            tax: ((checkoutData.subtotal - (checkoutData.discountAmount || 0)) + currentShippingFee) * 0.12,
            total: checkoutData.finalTotal,
            appliedVouchers: checkoutData.appliedVouchers || [],
            customer: { fullName, email, phone, address1, address2, city, province, region, zip, note },
            paymentMethod: payment
        };
        localStorage.setItem('last_order', JSON.stringify(orderInfo));

        try {
            const paymentIntentUrl = new URL('create-payment-intent.php', window.location.href).href;
            const res = await fetch(paymentIntentUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amountCentavos, name: fullName, email })
            });
            const rawText = await res.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (parseError) {
                throw new Error('Unexpected payment response: ' + rawText);
            }
            if (!res.ok || !data.success) {
                const details = data.details?.errors?.map?.(err => err.detail || err.code).filter(Boolean).join('; ');
                const debug = [details, data.curl_error, data.http_code ? ('HTTP ' + data.http_code) : '']
                    .filter(Boolean).join(' | ');
                throw new Error(debug
                    ? ((data.error || 'Payment initialization failed.') + ' - ' + debug)
                    : (data.error || 'Payment initialization failed.'));
            }
            localStorage.removeItem('lookgood_cart');
            localStorage.removeItem('lookgood_buynow');
            localStorage.removeItem('lookgood_checkout_data');
            window.location.href = data.redirect_url;
        } catch (err) {
            showError(err.message);
            payBtn.disabled = false;
            payBtn.classList.remove('loading');
        }
    });
})();

function esc(str) {
    return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
}