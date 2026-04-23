// shared/cart-standalone.js
// Cart overlay — DB-first for logged-in users, localStorage for guests.
// Logged-out state always shows an empty cart (session destroyed = 401 from API).

(function () {

    // ── State ──────────────────────────────────────────────────────────────────
    let cart = [];   // mirror of DB for logged-in users; localStorage for guests

    // DOM refs (populated after injectCartHTML)
    let cartSidebar, cartOverlay, cartBadge, cartItemsContainer,
        emptyCartEl, cartTotalEl, selectAllChk, bulkBar,
        selectedCountEl, bulkDeleteBtn, checkoutBtn;

    // ── Constants ──────────────────────────────────────────────────────────────
    const CART_API       = '/lookgood/userBack_end/cartAPI.php';
    const GUEST_CART_API = '/lookgood/userBack_end/guestAddToCart.php';

    // ── Login detection ────────────────────────────────────────────────────────
    // Priority:
    //  1. window.LG_IS_LOGGED_IN  — PHP injects this before this script loads:
    //       <script>const LG_IS_LOGGED_IN = <?= !empty($_SESSION['user_id']) ? 'true' : 'false' ?>;</script>
    //  2. window.LG_CHAT_USER.isLoggedIn (older pages)
    //  3. non-HttpOnly cookie  lg_logged_in=1  (set by session_bootstrap.php)
    function isLoggedIn() {
        if (typeof window.LG_IS_LOGGED_IN === 'boolean') return window.LG_IS_LOGGED_IN;
        if (window.LG_CHAT_USER && typeof window.LG_CHAT_USER.isLoggedIn === 'boolean')
            return window.LG_CHAT_USER.isLoggedIn;
        return document.cookie.split(';').some(c => c.trim().startsWith('lg_logged_in=1'));
    }

    // ── Backend sync ───────────────────────────────────────────────────────────
    // Always awaited — errors are logged so you can see them in DevTools.
    async function syncToBackend(action, productId, quantity) {
        const loggedIn = isLoggedIn();
        const api      = loggedIn ? CART_API : GUEST_CART_API;

        console.log(
            `[cart] syncToBackend | api=${loggedIn ? 'CART_API' : 'GUEST_CART_API'}`,
            `action=${action} product_id=${productId} quantity=${quantity} loggedIn=${loggedIn}`
        );

        try {
            const body = { action };
            if (productId != null) body.product_id = productId;
            if (quantity  != null) body.quantity   = quantity;

            const res  = await fetch(api, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(body)
            });
            const data = await res.json();
            console.log('[cart] syncToBackend response:', data);
            if (!data.success) console.error('[cart] Backend error:', data.error || data.message);
        } catch (e) {
            console.warn('[cart] syncToBackend network/parse error:', e);
        }
    }

    // ── Load cart ──────────────────────────────────────────────────────────────
    async function loadCart() {
        if (isLoggedIn()) {
            // ── Logged-in: DB is the single source of truth ──────────────────
            // Never read localStorage for cart items when logged in.
            localStorage.removeItem('lookgood_cart');
            cart = [];

            try {
                const res = await fetch(CART_API);

                // 401 = session expired / logged out → show empty cart
                if (res.status === 401) {
                    console.log('[cart] Session not active — showing empty cart');
                    return;
                }

                const data = await res.json();
                if (data.success && Array.isArray(data.items)) {
                    cart = data.items.map(row => ({
                        id:       row.product_id,
                        name:     row.name     || row.product_id,
                        price:    parseFloat(row.price    || 0),
                        image:    row.image
                                    ? '/lookgood/uploads/products/' + row.image
                                    : '',
                        stock:    row.stock != null ? Number(row.stock) : 999,
                        quantity: parseInt(row.quantity   || 1),
                        brand:    row.brand    || '',
                        selected: false,
                    }));
                }
            } catch (e) {
                console.warn('[cart] Could not load cart from DB:', e);
                cart = [];
            }

        } else {
            // ── Guest: try guest_carts API, fall back to localStorage ─────────
            try {
                const res  = await fetch(GUEST_CART_API);
                const data = await res.json();
                if (data.success && Array.isArray(data.items) && data.items.length > 0) {
                    cart = data.items.map(row => ({
                        id:       row.product_id,
                        name:     row.name     || row.product_id,
                        price:    parseFloat(row.price    || 0),
                        image:    row.image
                                    ? '/lookgood/uploads/products/' + row.image
                                    : '',
                        stock:    row.stock != null ? Number(row.stock) : 999,
                        quantity: parseInt(row.quantity   || 1),
                        brand:    row.brand    || '',
                        selected: false,
                    }));
                    return;
                }
            } catch (e) {
                console.warn('[cart] Could not load guest cart from API:', e);
            }

            // localStorage fallback for guests
            try {
                const saved = localStorage.getItem('lookgood_cart');
                cart = saved
                    ? JSON.parse(saved).map(i => ({ ...i, selected: i.selected === true }))
                    : [];
            } catch (_) { cart = []; }
        }
    }

    // ── Persist (localStorage only for guests; DB handles logged-in) ───────────
    function saveLocalCart() {
        if (isLoggedIn()) {
            localStorage.removeItem('lookgood_cart');
        } else {
            localStorage.setItem('lookgood_cart', JSON.stringify(cart));
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    function escHtml(str) {
        return String(str).replace(/[&<>"']/g, m =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]
        );
    }

    function showToast(message, showViewCart = false) {
        document.querySelector('.cart-toast')?.remove();
        const toast = document.createElement('div');
        toast.className = 'cart-toast';
        toast.textContent = message;
        Object.assign(toast.style, {
            position: 'fixed', bottom: '20px', left: '50%',
            transform: 'translateX(-50%)',
            background: '#1a1a1a', color: '#fff',
            padding: '12px 20px', borderRadius: '9999px',
            fontFamily: "'DM Sans', sans-serif", fontSize: '14px',
            zIndex: '10000', boxShadow: '0 4px 12px rgba(0,0,0,.15)',
            whiteSpace: 'nowrap'
        });
        if (showViewCart) {
            const btn = document.createElement('button');
            btn.textContent = 'View Cart';
            btn.style.cssText = 'margin-left:12px;background:#c8a96e;color:#fff;border:none;padding:4px 12px;border-radius:999px;cursor:pointer;';
            btn.onclick = () => { toast.remove(); openCart(); };
            toast.appendChild(btn);
        }
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    // ── Inject cart HTML ───────────────────────────────────────────────────────
    function injectCartHTML() {
        if (document.getElementById('cartSidebar')) return;   // already injected

        const overlay = document.createElement('div');
        overlay.className = 'cart-overlay';
        overlay.id        = 'cartOverlay';
        document.body.appendChild(overlay);

        const sidebar = document.createElement('aside');
        sidebar.className = 'cart-sidebar';
        sidebar.id        = 'cartSidebar';
        sidebar.setAttribute('aria-label', 'Shopping Cart');
        sidebar.innerHTML = `
            <div class="cart-header">
                <h3 class="cart-title">Shopping Cart</h3>
                <button class="cart-close" id="closeCart" aria-label="Close cart">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="cart-select-all-row" id="cartSelectAllRow" style="display:none;">
                <label class="cart-select-all-label">
                    <input type="checkbox" id="selectAllCart"> Select all items
                </label>
            </div>
            <div class="cart-bulk-bar" id="cartBulkBar">
                <span class="cart-bulk-info">
                    <strong id="selectedCount">0</strong> item(s) selected
                </span>
                <button class="btn-bulk-delete" id="bulkDeleteBtn">
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </div>
            <div class="cart-content">
                <div class="cart-empty" id="emptyCart" style="display:flex;">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                </div>
                <div id="cartItems"></div>
            </div>
            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total:</span>
                    <span class="cart-total-price" id="cartTotalPrice">₱0.00</span>
                </div>
                <button class="btn btn--primary btn--full" id="checkoutAllBtn">
                    Proceed to Checkout
                </button>
                <button class="btn btn--secondary btn--full" id="continueShopping">
                    Continue Shopping
                </button>
            </div>
        `;
        document.body.appendChild(sidebar);

        // Cache refs
        cartSidebar        = sidebar;
        cartOverlay        = overlay;
        cartBadge          = document.getElementById('cartBadge');
        cartItemsContainer = document.getElementById('cartItems');
        emptyCartEl        = document.getElementById('emptyCart');
        cartTotalEl        = document.getElementById('cartTotalPrice');
        selectAllChk       = document.getElementById('selectAllCart');
        bulkBar            = document.getElementById('cartBulkBar');
        selectedCountEl    = document.getElementById('selectedCount');
        bulkDeleteBtn      = document.getElementById('bulkDeleteBtn');
        checkoutBtn        = document.getElementById('checkoutAllBtn');
    }

    // ── Event delegation (attached once, survives re-renders) ──────────────────
    function setupDelegation() {
        if (!cartItemsContainer) return;

        cartItemsContainer.addEventListener('click', e => {
            const btn = e.target.closest('[data-action], .cart-item-remove');
            if (!btn) return;
            const idx = parseInt(btn.dataset.index);
            if (isNaN(idx) || !cart[idx]) return;

            if (btn.classList.contains('cart-item-remove')) {
                const removedId = cart[idx].id;
                cart.splice(idx, 1);
                saveLocalCart();
                updateCartUI();
                showToast('Item removed');
                syncToBackend('remove', removedId);
                return;
            }

            const action = btn.dataset.action;
            if (action === 'increase') {
                cart[idx].quantity += 1;
                saveLocalCart();
                updateCartUI();
                syncToBackend('update', cart[idx].id, cart[idx].quantity);
            } else if (action === 'decrease') {
                if (cart[idx].quantity > 1) {
                    cart[idx].quantity -= 1;
                    saveLocalCart();
                    updateCartUI();
                    syncToBackend('update', cart[idx].id, cart[idx].quantity);
                } else {
                    const removedId = cart[idx].id;
                    cart.splice(idx, 1);
                    saveLocalCart();
                    updateCartUI();
                    showToast('Item removed');
                    syncToBackend('remove', removedId);
                }
            }
        });

        cartItemsContainer.addEventListener('change', e => {
            const chk = e.target.closest('.cart-item-checkbox');
            if (!chk) return;
            const idx = parseInt(chk.dataset.index);
            if (!isNaN(idx) && cart[idx]) {
                cart[idx].selected = chk.checked;
                saveLocalCart();
                updateCartUI();
            }
        });
    }

    // ── Render ─────────────────────────────────────────────────────────────────
    function updateCartUI() {
        if (!cartItemsContainer) return;

        const totalQty   = cart.reduce((s, i) => s + i.quantity, 0);
        const totalPrice = cart.reduce((s, i) => s + i.price * i.quantity, 0);

        // Update badges
        if (cartBadge) cartBadge.textContent = totalQty;
        const floatBadge = document.getElementById('cartBadgeFloat');
        if (floatBadge) floatBadge.textContent = totalQty;

        if (cartTotalEl)
            cartTotalEl.textContent = '₱' + totalPrice.toLocaleString('en-PH', { minimumFractionDigits: 2 });

        if (emptyCartEl)
            emptyCartEl.style.display = cart.length === 0 ? 'flex' : 'none';

        const selectAllRow = document.getElementById('cartSelectAllRow');
        if (selectAllRow)
            selectAllRow.style.display = cart.length > 0 ? 'flex' : 'none';

        // Render rows
        cartItemsContainer.innerHTML = cart.map((item, i) => {
            const subtotal = item.price * item.quantity;
            return `
                <div class="cart-item${item.selected ? ' selected' : ''}" data-index="${i}">
                    <div class="cart-item-image-wrapper">
                        <input type="checkbox" class="cart-item-checkbox" data-index="${i}" ${item.selected ? 'checked' : ''}>
                        <img class="cart-item-image" src="${escHtml(item.image)}" alt="${escHtml(item.name)}">
                    </div>
                    <div class="cart-item-body">
                        <div class="cart-item-row">
                            <p class="cart-item-name">${escHtml(item.name)}</p>
                            <button class="cart-item-remove" data-index="${i}" aria-label="Remove">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="cart-item-qty-row">
                            <div class="cart-item-qty">
                                <button class="qty-btn" data-index="${i}" data-action="decrease" aria-label="Decrease">−</button>
                                <span class="qty-value">${item.quantity}</span>
                                <button class="qty-btn" data-index="${i}" data-action="increase" aria-label="Increase">+</button>
                                <span class="cart-item-unit-price">× ₱${item.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                            </div>
                            <span class="cart-item-subtotal">₱${subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        updateBulkUI();
    }

    function updateBulkUI() {
        if (!bulkBar || !selectedCountEl) return;
        const count = cart.filter(i => i.selected).length;

        bulkBar.classList.toggle('visible', count > 0);
        if (count > 0) selectedCountEl.textContent = count;

        if (selectAllChk) {
            selectAllChk.indeterminate = count > 0 && count < cart.length;
            selectAllChk.checked       = cart.length > 0 && count === cart.length;
        }
    }

    // ── Sidebar open / close ───────────────────────────────────────────────────
    function openCart() {
        cartSidebar?.classList.add('active');
        cartOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeCart() {
        cartSidebar?.classList.remove('active');
        cartOverlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * addToCart(product, quantity)
     *
     * For logged-in users:
     *   - Updates local cart array immediately (optimistic UI).
     *   - Sends delta quantity to the backend (ADD action = server increments existing row
     *     OR inserts a new row — both handled correctly in cartAPI.php).
     *
     * For guests:
     *   - Updates localStorage + guest_carts API.
     */
    function addToCart(product, quantity) {
        if (!product.id || !product.name || !product.price || !product.image) {
            console.error('[cart] addToCart: invalid product object', product);
            return;
        }
        const qty      = Math.max(1, parseInt(quantity) || 1);
        const existing = cart.find(i => i.id === product.id);

        if (existing) {
            // Same product — just add to quantity locally
            existing.quantity += qty;
        } else {
            // New product — push a new entry
            cart.push({ ...product, quantity: qty, selected: false });
        }

        saveLocalCart();
        updateCartUI();
        showToast(`${product.name} added to cart!`, true);

        // Tell the backend: ADD delta qty.
        // cartAPI.php ADD: same product → UPDATE quantity + delta; new product → INSERT row.
        syncToBackend('add', product.id, qty);
    }

    function getCart() { return cart; }

    function buyNow(product) {
        if (!product.id || !product.name || !product.price || !product.image) {
            console.error('[cart] buyNow: invalid product', product);
            return;
        }
        localStorage.setItem('lookgood_buynow', JSON.stringify({
            id:       product.id,
            name:     product.name,
            price:    parseFloat(product.price),
            image:    product.image,
            quantity: 1
        }));
        window.location.href = '../home/cart.php';
    }

    // ── Init ───────────────────────────────────────────────────────────────────
    async function init() {
        // Inject zoom modal script if not already present
        if (!document.getElementById('imageModal')) {
            const s  = document.createElement('script');
            s.src    = '/lookgood/userActions/zoomImage.js';
            document.head.appendChild(s);
        }

        injectCartHTML();
        await loadCart();
        updateCartUI();
        setupDelegation();

        // Close triggers
        document.getElementById('closeCart')?.addEventListener('click', closeCart);
        document.getElementById('cartOverlay')?.addEventListener('click', closeCart);
        document.getElementById('continueShopping')?.addEventListener('click', closeCart);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && cartSidebar?.classList.contains('active')) closeCart();
        });

        // Select all
        selectAllChk?.addEventListener('change', () => {
            cart.forEach(i => i.selected = selectAllChk.checked);
            saveLocalCart();
            updateCartUI();
        });

        // Bulk delete
        bulkDeleteBtn?.addEventListener('click', () => {
            const removedIds = cart.filter(i => i.selected).map(i => i.id);
            cart = cart.filter(i => !i.selected);
            if (removedIds.length) {
                saveLocalCart();
                updateCartUI();
                showToast('Selected items removed');
                removedIds.forEach(id => syncToBackend('remove', id));
            }
        });

        // Checkout
        checkoutBtn?.addEventListener('click', () => {
            if (!cart.length) { showToast('Your cart is empty'); return; }
            localStorage.removeItem('lookgood_buynow');
            localStorage.setItem('checkout_from_cart', 'true');
            window.location.href = '../home/cart.php';
        });

        // Cart open button (use existing #cartBtn or create a floating one)
        let cartBtn = document.getElementById('cartBtn');
        if (!cartBtn) {
            cartBtn = document.createElement('button');
            cartBtn.id        = 'cartBtn';
            cartBtn.className = 'floating-cart-btn';
            cartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i><span class="navbar-badge" id="cartBadgeFloat">0</span>';
            Object.assign(cartBtn.style, {
                position: 'fixed', bottom: '20px', right: '20px',
                width: '56px', height: '56px', borderRadius: '50%',
                background: '#1a1a1a', color: '#fff', border: 'none',
                cursor: 'pointer', zIndex: '999',
                boxShadow: '0 4px 12px rgba(0,0,0,.2)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: '24px'
            });
            document.body.appendChild(cartBtn);
        }
        cartBtn.addEventListener('click', openCart);
    }

    // ── Expose globally ────────────────────────────────────────────────────────
    window.cartManager = { addToCart, getCart, openCart, closeCart, buyNow };

    // ── Boot ───────────────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();