// profile-wishlist.js
// Reads and manages the wishlist from the DB via wishlistAPI.php
// Path: New folder/Profile/ → userBack_end/
const WISHLIST_API = '/lookgood/userBack_end/wishlistAPI.php';

let wishlistItems = [];

// ── Fetch wishlist from DB ────────────────────────────────────────────────────
async function loadWishlist() {
  try {
    const res = await fetch(WISHLIST_API);
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    wishlistItems = Array.isArray(data) ? data : [];
  } catch (err) {
    console.warn('Could not load wishlist from DB:', err);
    wishlistItems = [];
  }
  renderWishlist();
  updateWishlistBadge();
}

// ── Render ────────────────────────────────────────────────────────────────────
function renderWishlist() {
  const grid  = document.getElementById('wishlistGrid');
  const empty = document.getElementById('wishlist-empty');
  if (!grid) return;

  if (!wishlistItems.length) {
    grid.innerHTML = '';
    if (empty) empty.style.display = 'flex';
    return;
  }
  if (empty) empty.style.display = 'none';

  grid.innerHTML = wishlistItems.map(item => `
    <div class="wishlist-card" data-id="${item.id}">
      <div class="wishlist-image-wrapper" data-id="${item.id}">
        <img class="wishlist-image" src="${item.image}" alt="${escapeHtml(item.name)}"
             onerror="this.onerror=null;this.src='/lookgood/New%20folder/Resources/Images/glasses1.png';">
      </div>
      <div class="wishlist-info">
        <span class="wishlist-category">${escapeHtml(item.category)}</span>
        <h3 class="wishlist-name" data-id="${item.id}">${escapeHtml(item.name)}</h3>
        <div class="wishlist-price">&#8369;${item.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
        <div class="wishlist-actions">
          <button class="btn-wishlist-cart"
                  data-id="${item.id}"
                  data-name="${escapeHtml(item.name)}"
                  data-price="${item.price}"
                  data-image="${item.image}"
                  title="Add to cart and remove from wishlist">
            <i class="fas fa-cart-arrow-down"></i> Move to Cart
          </button>
          <button class="btn-wishlist-buy"
                  data-id="${item.id}"
                  data-name="${escapeHtml(item.name)}"
                  data-price="${item.price}"
                  data-image="${item.image}"
                  title="Buy now">
            <i class="fas fa-bolt"></i> Buy Now
          </button>
        </div>
        <button class="btn-wishlist-delete"
                data-id="${item.id}"
                aria-label="Remove from wishlist"
                title="Remove from wishlist">
          <i class="fas fa-trash-alt"></i> Delete
        </button>
      </div>
    </div>
  `).join('');

  attachWishlistEvents();
}

// ── Events ────────────────────────────────────────────────────────────────────
function attachWishlistEvents() {
  // Click image / name → go to product detail
  document.querySelectorAll('.wishlist-image-wrapper, .wishlist-name').forEach(el => {
    el.addEventListener('click', () => {
      window.location.href = '../Products/product-detail.php?id=' + el.dataset.id;
    });
  });

  // Move to Cart — adds to cart AND removes from wishlist
  document.querySelectorAll('.btn-wishlist-cart').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const product = {
        id:    btn.dataset.id,
        name:  btn.dataset.name,
        price: parseFloat(btn.dataset.price),
        image: btn.dataset.image,
      };
      if (window.cartManager) {
        window.cartManager.addToCart(product);
      } else {
        // Fallback: add to cart manually
        try {
          const cart = JSON.parse(localStorage.getItem('lookgood_cart') || '[]');
          const existing = cart.find(c => String(c.id) === String(product.id));
          if (existing) {
            existing.quantity = (existing.quantity || 1) + 1;
          } else {
            cart.push({ ...product, quantity: 1, selected: true });
          }
          localStorage.setItem('lookgood_cart', JSON.stringify(cart));
        } catch (err) {
          console.warn('Cart fallback failed:', err);
        }
      }
      // Remove from wishlist after adding to cart
      removeFromWishlist(btn.dataset.id, false);
      showToast(btn.dataset.name + ' moved to cart', 'success');
    });
  });

  // Buy Now — keep in wishlist, go to checkout
  document.querySelectorAll('.btn-wishlist-buy').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const product = {
        id:    btn.dataset.id,
        name:  btn.dataset.name,
        price: parseFloat(btn.dataset.price),
        image: btn.dataset.image,
      };
      if (window.cartManager && typeof window.cartManager.buyNow === 'function') {
        window.cartManager.buyNow(product);
      } else {
        localStorage.setItem('lookgood_cart', JSON.stringify([{ ...product, quantity: 1, selected: false }]));
        window.location.href = '../Checkout/checkout.html';
      }
    });
  });

  // Delete — removes from wishlist only
  document.querySelectorAll('.btn-wishlist-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      removeFromWishlist(btn.dataset.id, true);
    });
  });
}

// ── Remove from DB ────────────────────────────────────────────────────────────
async function removeFromWishlist(productId, showMsg = true) {
  try {
    await fetch(WISHLIST_API, {
      method:  'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ product_id: productId }),
    });
  } catch (err) {
    console.warn('Could not remove from wishlist:', err);
  }
  wishlistItems = wishlistItems.filter(i => String(i.id) !== String(productId));
  renderWishlist();
  updateWishlistBadge();
  if (showMsg) showToast('Removed from wishlist', 'info');
}

// ── Badge ─────────────────────────────────────────────────────────────────────
function updateWishlistBadge() {
  const badge = document.getElementById('wishlistTabCount');
  if (badge) {
    badge.textContent = wishlistItems.length;
    badge.classList.toggle('visible', wishlistItems.length > 0);
  }
}

// ── Helper ────────────────────────────────────────────────────────────────────
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]
  );
}

document.addEventListener('DOMContentLoaded', loadWishlist);