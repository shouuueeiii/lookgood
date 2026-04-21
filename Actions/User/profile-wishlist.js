// profile-wishlist.js
// Reads and manages the wishlist from the DB via wishlistAPI.php
// Path: New folder/Profile/ → userBack_end/
const WISHLIST_API = '../../userBack_end/wishlistAPI.php';

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
      <button class="wishlist-remove" data-id="${item.id}" aria-label="Remove from wishlist">
        <i class="fas fa-times"></i>
      </button>
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
                  data-image="${item.image}">
            <i class="fas fa-cart-plus"></i> Add to Cart
          </button>
          <button class="btn-wishlist-buy"
                  data-id="${item.id}"
                  data-name="${escapeHtml(item.name)}"
                  data-price="${item.price}"
                  data-image="${item.image}">
            <i class="fas fa-bolt"></i> Buy Now
          </button>
        </div>
      </div>
    </div>
  `).join('');

  attachWishlistEvents();
}

// ── Events ────────────────────────────────────────────────────────────────────
function attachWishlistEvents() {
  // Remove button
  document.querySelectorAll('.wishlist-remove').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      removeFromWishlist(btn.dataset.id);
    });
  });

  // Click image / name → go to product detail
  document.querySelectorAll('.wishlist-image-wrapper, .wishlist-name').forEach(el => {
    el.addEventListener('click', () => {
      window.location.href = '../Products/product-detail.php?id=' + el.dataset.id;
    });
  });

  // Add to cart
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
        showToast('Added ' + btn.dataset.name + ' to cart', 'success');
      }
    });
  });

  // Buy now
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
}

// ── Remove from DB ────────────────────────────────────────────────────────────
async function removeFromWishlist(productId) {
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
  showToast('Removed from wishlist', 'info');
}

// ── Badge ─────────────────────────────────────────────────────────────────────
function updateWishlistBadge() {
  const badge = document.getElementById('wishlistTabCount');
  if (badge) badge.textContent = wishlistItems.length;
}

// ── Helper ────────────────────────────────────────────────────────────────────
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]
  );
}

document.addEventListener('DOMContentLoaded', loadWishlist);
