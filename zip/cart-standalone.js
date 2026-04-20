// shared/cart-standalone.js
// Standalone Cart Overlay – fixed quantity buttons, reliable event delegation

(function () {
    // ============================================================
    // CART STATE
    // ============================================================
    let cart = [];

    // DOM elements (cached after injection)
    let cartSidebar, cartOverlay, cartBadge, cartItemsContainer, emptyCartEl, cartTotalEl;
    let selectAllChk, bulkBar, selectedCountEl, bulkDeleteBtn, checkoutBtn;

    // ============================================================
    // HELPERS
    // ============================================================
    function loadCart() {
        try {
            const saved = localStorage.getItem('lookgood_cart');
            if (saved) {
                cart = JSON.parse(saved);
                cart = cart.map(item => ({ ...item, selected: item.selected === true }));
            } else {
                cart = [];
            }
        } catch (e) { cart = []; }
    }

    function saveCart() {
        localStorage.setItem('lookgood_cart', JSON.stringify(cart));
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function showToast(message, showViewCart = false) {
        let toast = document.querySelector('.cart-toast');
        if (toast) toast.remove();

        toast = document.createElement('div');
        toast.className = 'cart-toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1a1a1a;
            color: white;
            padding: 12px 20px;
            border-radius: 9999px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            z-index: 10000;
            animation: fadeInUp 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;

        if (showViewCart) {
            const viewCartBtn = document.createElement('button');
            viewCartBtn.textContent = 'View Cart';
            viewCartBtn.style.cssText = 'margin-left:12px; background:#c8a96e; color:#fff; border:none; padding:4px 12px; border-radius:999px; cursor:pointer;';
            viewCartBtn.onclick = () => {
                toast.remove();
                openCart();
            };
            toast.appendChild(viewCartBtn);
        }

        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2500);
    }

    // ============================================================
    // INJECT CART HTML (only if not already present)
    // ============================================================
    function injectCartHTML() {
        if (!document.getElementById('cartSidebar')) {
            const overlay = document.createElement('div');
            overlay.className = 'cart-overlay';
            overlay.id = 'cartOverlay';
            document.body.appendChild(overlay);

            const sidebar = document.createElement('aside');
            sidebar.className = 'cart-sidebar';
            sidebar.id = 'cartSidebar';
            sidebar.setAttribute('aria-label', 'Shopping Cart');
            sidebar.innerHTML = `
                <div class="cart-header">
                    <h3 class="cart-title">Shopping Cart</h3>
                    <button class="cart-close" id="closeCart" aria-label="Close cart">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="cart-select-all-row" id="cartSelectAllRow" style="display: none;">
                    <label class="cart-select-all-label">
                        <input type="checkbox" id="selectAllCart"> Select all items
                    </label>
                </div>
                <div class="cart-bulk-bar" id="cartBulkBar">
                    <span class="cart-bulk-info"><strong id="selectedCount">0</strong> item(s) selected</span>
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
        }

        // Cache DOM references (they exist now)
        cartSidebar = document.getElementById('cartSidebar');
        cartOverlay = document.getElementById('cartOverlay');
        cartBadge = document.getElementById('cartBadge');
        cartItemsContainer = document.getElementById('cartItems');
        emptyCartEl = document.getElementById('emptyCart');
        cartTotalEl = document.getElementById('cartTotalPrice');
        selectAllChk = document.getElementById('selectAllCart');
        bulkBar = document.getElementById('cartBulkBar');
        selectedCountEl = document.getElementById('selectedCount');
        bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        checkoutBtn = document.getElementById('checkoutAllBtn');
    }

    // ============================================================
    // EVENT DELEGATION (robust – no need to reattach after every render)
    // ============================================================
    function setupDelegation() {
        if (!cartItemsContainer) return;

        // Handle clicks on any button inside cartItemsContainer
        cartItemsContainer.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action], .cart-item-remove');
            if (!btn) return;

            const idx = parseInt(btn.dataset.index);
            if (isNaN(idx)) return;

            // Remove button
            if (btn.classList.contains('cart-item-remove')) {
                cart.splice(idx, 1);
                saveCart();
                updateCartUI();
                showToast('Item removed');
                return;
            }

            // Quantity buttons
            const action = btn.dataset.action;
            if (action === 'increase') {
                if (cart[idx]) {
                    cart[idx].quantity += 1;
                    saveCart();
                    updateCartUI();
                }
            } else if (action === 'decrease') {
                if (cart[idx]) {
                    if (cart[idx].quantity > 1) {
                        cart[idx].quantity -= 1;
                        saveCart();
                        updateCartUI();
                    } else {
                        // remove item if quantity becomes 0
                        cart.splice(idx, 1);
                        saveCart();
                        updateCartUI();
                        showToast('Item removed');
                    }
                }
            }
        });

        // Handle checkbox changes (delegation)
        cartItemsContainer.addEventListener('change', (e) => {
            const chk = e.target.closest('.cart-item-checkbox');
            if (!chk) return;
            const idx = parseInt(chk.dataset.index);
            if (!isNaN(idx) && cart[idx]) {
                cart[idx].selected = chk.checked;
                saveCart();
                updateCartUI();
            }
        });
    }

    // ============================================================
    // UI UPDATE (render cart items)
    // ============================================================
    function getSelectedIndices() {
        return cart.reduce((acc, item, i) => {
            if (item.selected === true) acc.push(i);
            return acc;
        }, []);
    }

    function updateBulkUI() {
        if (!bulkBar || !selectedCountEl) return;
        const selected = getSelectedIndices();
        const count = selected.length;

        if (cart.length === 0) {
            bulkBar.classList.remove('visible');
            if (selectAllChk) {
                selectAllChk.checked = false;
                selectAllChk.indeterminate = false;
            }
            return;
        }

        if (count > 0) {
            bulkBar.classList.add('visible');
            selectedCountEl.textContent = count;
        } else {
            bulkBar.classList.remove('visible');
        }

        if (selectAllChk) {
            if (count === cart.length) {
                selectAllChk.checked = true;
                selectAllChk.indeterminate = false;
            } else if (count > 0) {
                selectAllChk.checked = false;
                selectAllChk.indeterminate = true;
            } else {
                selectAllChk.checked = false;
                selectAllChk.indeterminate = false;
            }
        }
    }

    function updateCartUI() {
        if (!cartItemsContainer) return;

        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

        if (cartBadge) cartBadge.textContent = totalItems > 0 ? totalItems : '0';
        // Also update any floating badge (e.g., from a custom floating cart button)
        const floatBadge = document.getElementById('cartBadgeFloat');
        if (floatBadge) floatBadge.textContent = totalItems > 0 ? totalItems : '0';

        if (cartTotalEl) cartTotalEl.textContent = '₱' + totalPrice.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        if (emptyCartEl) emptyCartEl.style.display = cart.length === 0 ? 'flex' : 'none';

        const selectAllRow = document.getElementById('cartSelectAllRow');
        if (selectAllRow) {
            selectAllRow.style.display = cart.length > 0 ? 'flex' : 'none';
        }

        if (cart.length > 0) {
            cartItemsContainer.innerHTML = cart.map((item, i) => {
                const subtotal = item.price * item.quantity;
                return `
                    <div class="cart-item${item.selected ? ' selected' : ''}" data-index="${i}">
                        <div class="cart-item-image-wrapper">
                            <input type="checkbox" class="cart-item-checkbox" data-index="${i}" ${item.selected ? 'checked' : ''}>
                            <img class="cart-item-image" src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}">
                        </div>
                        <div class="cart-item-body">
                            <div class="cart-item-row">
                                <p class="cart-item-name">${escapeHtml(item.name)}</p>
                                <button class="cart-item-remove" data-index="${i}" aria-label="Remove ${escapeHtml(item.name)}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="cart-item-qty-row">
                                <div class="cart-item-qty">
                                    <button class="qty-btn" data-index="${i}" data-action="decrease" aria-label="Decrease quantity">−</button>
                                    <span class="qty-value">${item.quantity}</span>
                                    <button class="qty-btn" data-index="${i}" data-action="increase" aria-label="Increase quantity">+</button>
                                    <span class="cart-item-unit-price">× ₱${item.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                                </div>
                                <span class="cart-item-subtotal">₱${subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            cartItemsContainer.innerHTML = '';
        }

        updateBulkUI();
    }

    // ============================================================
    // SIDEBAR OPEN / CLOSE
    // ============================================================
    function openCart() {
        if (cartSidebar && cartOverlay) {
            cartSidebar.classList.add('active');
            cartOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeCart() {
        if (cartSidebar && cartOverlay) {
            cartSidebar.classList.remove('active');
            cartOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // ============================================================
    // PUBLIC API
    // ============================================================
    function addToCart(product) {
        if (!product.id || !product.name || !product.price || !product.image) {
            console.error('CartManager: Invalid product object', product);
            return;
        }
        const existing = cart.find(item => item.id === product.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({ ...product, quantity: 1, selected: false });
        }
        saveCart();
        updateCartUI();
        showToast(`${product.name} added to cart!`, true);
    }

    function getCart() {
        return cart;
    }

    function buyNow(product) {
        if (!product.id || !product.name || !product.price || !product.image) {
            console.error('CartManager: Invalid product for buy now', product);
            return;
        }
        const buyNowItem = {
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            image: product.image,
            quantity: 1
        };
        localStorage.setItem('lookgood_buynow', JSON.stringify(buyNowItem));
        window.location.href = '../Checkout/cart.php';
    }

    // ============================================================
    // INITIALIZATION
    // ============================================================
    function init() {
        injectCartHTML();     // creates elements if missing
        loadCart();
        updateCartUI();
        setupDelegation();    // one-time event delegation

        // Close buttons
        document.getElementById('closeCart')?.addEventListener('click', closeCart);
        document.getElementById('cartOverlay')?.addEventListener('click', closeCart);
        document.getElementById('continueShopping')?.addEventListener('click', closeCart);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && cartSidebar?.classList.contains('active')) closeCart();
        });

        // Select all checkbox
        if (selectAllChk) {
            selectAllChk.addEventListener('change', () => {
                const isChecked = selectAllChk.checked;
                cart.forEach(item => item.selected = isChecked);
                saveCart();
                updateCartUI();
            });
        }

        // Bulk delete
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', () => {
                const beforeCount = cart.length;
                cart = cart.filter(item => !item.selected);
                if (cart.length < beforeCount) {
                    saveCart();
                    updateCartUI();
                    showToast('Selected items removed');
                }
            });
        }

        // Checkout
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                if (cart.length === 0) {
                    showToast('Your cart is empty');
                    return;
                }
                localStorage.removeItem('lookgood_buynow');
                localStorage.setItem('checkout_from_cart', 'true');
                window.location.href = '../Checkout/cart.php';
            });
        }

        // Open cart button (existing navbar button or create a floating one)
        let cartBtn = document.getElementById('cartBtn');
        if (!cartBtn) {
            cartBtn = document.createElement('button');
            cartBtn.id = 'cartBtn';
            cartBtn.className = 'floating-cart-btn';
            cartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i><span class="navbar-badge" id="cartBadgeFloat">0</span>';
            cartBtn.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: #1a1a1a;
                color: white;
                border: none;
                cursor: pointer;
                z-index: 999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
            `;
            document.body.appendChild(cartBtn);
        }
        cartBtn.addEventListener('click', openCart);
    }

    // Expose globally
    window.cartManager = {
        addToCart,
        getCart,
        openCart,
        closeCart,
        buyNow
    };

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();