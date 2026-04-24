
(function () {

    let cart = [];   

    // DOM refs (populated after injectCartHTML)
    let cartSidebar, cartOverlay, cartBadge, cartItemsContainer,
        emptyCartEl, cartTotalEl, selectAllChk, bulkBar,
        selectedCountEl, bulkDeleteBtn, bulkWishlistBtn, checkoutBtn;

    const CART_API       = '/lookgood/userBack_end/cartAPI.php';
    const GUEST_CART_API = '/lookgood/userBack_end/guestAddToCart.php';


    function isLoggedIn() {
        if (typeof window.LG_IS_LOGGED_IN === 'boolean') return window.LG_IS_LOGGED_IN;
        if (window.LG_CHAT_USER && typeof window.LG_CHAT_USER.isLoggedIn === 'boolean')
            return window.LG_CHAT_USER.isLoggedIn;
        return document.cookie.split(';').some(c => c.trim().startsWith('lg_logged_in=1'));
    }

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

    async function loadCart() {
        if (isLoggedIn()) {

            localStorage.removeItem('lookgood_cart');
            cart = [];

            try {
                const res = await fetch(CART_API);

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

            try {
                const saved = localStorage.getItem('lookgood_cart');
                cart = saved
                    ? JSON.parse(saved).map(i => ({ ...i, selected: i.selected === true }))
                    : [];
            } catch (_) { cart = []; }
        }
    }

    function saveLocalCart() {
        if (isLoggedIn()) {
            localStorage.removeItem('lookgood_cart');
        } else {
            localStorage.setItem('lookgood_cart', JSON.stringify(cart));
        }
    }

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

    function injectCartHTML() {
        if (document.getElementById('cartSidebar')) return;

        if (!document.getElementById('cartStandaloneStyles')) {
            const style = document.createElement('style');
            style.id = 'cartStandaloneStyles';
            style.textContent = `
                .btn-bulk-wishlist {
                    display: inline-flex; align-items: center; gap: 5px;
                    padding: 5px 10px; border-radius: 6px; font-size: 12px;
                    background: transparent; border: 1px solid #e53935;
                    color: #e53935; cursor: pointer; transition: background .15s, color .15s;
                    white-space: nowrap;
                }
                .btn-bulk-wishlist:hover:not(:disabled) { background: #e53935; color: #fff; }
                .btn-bulk-wishlist:disabled { opacity: .4; cursor: not-allowed; }
            `;
            document.head.appendChild(style);
        }

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
                <button class="btn-bulk-wishlist" id="bulkWishlistBtn" title="Move selected to wishlist">
                    <i class="far fa-heart"></i> Wishlist
                </button>
                <button class="btn-bulk-delete" id="bulkDeleteBtn">
                    <i class="fas fa-trash-alt"></i> Delete
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
        bulkWishlistBtn    = document.getElementById('bulkWishlistBtn');
        checkoutBtn        = document.getElementById('checkoutAllBtn');
    }

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


    function addToCart(product, quantity) {
        if (!product.id || !product.name || !product.price || !product.image) {
            console.error('[cart] addToCart: invalid product object', product);
            return;
        }
        const qty      = Math.max(1, parseInt(quantity) || 1);
        const existing = cart.find(i => i.id === product.id);

        if (existing) {

            existing.quantity += qty;
        } else {

            cart.push({ ...product, quantity: qty, selected: false });
        }

        saveLocalCart();
        updateCartUI();
        showToast(`${product.name} added to cart!`, true);

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

        // Move selected to wishlist
        bulkWishlistBtn?.addEventListener('click', async () => {
            const selectedItems = cart.filter(i => i.selected);
            if (!selectedItems.length) return;

            bulkWishlistBtn.disabled = true;

            if (isLoggedIn()) {
                // DB-backed wishlist for logged-in users
                await Promise.all(selectedItems.map(item =>
                    fetch('/lookgood/userBack_end/wishlistAPI.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_id: item.id })
                    }).catch(e => console.warn('[cart] Wishlist add failed for', item.id, e))
                ));
            } else {
                // Guest fallback: localStorage wishlist
                let wishlist = [];
                try { wishlist = JSON.parse(localStorage.getItem('lookgood_wishlist') || '[]'); } catch(e) {}
                selectedItems.forEach(item => {
                    if (!wishlist.find(w => w.id === item.id))
                        wishlist.push({ id: item.id, name: item.name, price: item.price, image: item.image });
                });
                localStorage.setItem('lookgood_wishlist', JSON.stringify(wishlist));
            }

            // Remove moved items from cart
            const movedIds = selectedItems.map(i => i.id);
            cart = cart.filter(i => !i.selected);
            saveLocalCart();
            movedIds.forEach(id => syncToBackend('remove', id));
            updateCartUI();

            const count = movedIds.length;
            showToast(`${count} item${count > 1 ? 's' : ''} moved to wishlist`);
        });

        checkoutBtn?.addEventListener('click', () => {
            if (!cart.length) { showToast('Your cart is empty'); return; }
            localStorage.removeItem('lookgood_buynow');
            localStorage.setItem('checkout_from_cart', 'true');
            window.location.href = '../home/cart.php';
        });
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

    window.cartManager = { addToCart, getCart, openCart, closeCart, buyNow };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();