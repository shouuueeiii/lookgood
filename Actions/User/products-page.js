// products-page.js – Product listing & filters
// ============================================================
// DATA — fetched live from the database via productsAPI.php
// ============================================================

// Path from New folder/Products/ → userBack_end/
const PRODUCTS_API = '../../userBack_end/productsAPI.php';

// ============================================================
// RENDER PRODUCTS
// ============================================================
function renderProducts(products) {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;

  if (!products.length) {
    grid.innerHTML = '<p class="no-products">No products available at the moment.</p>';
    return;
  }

  grid.innerHTML = products.map(p => {
    const outOfStock = p.stock <= 0;
    const stockLabel = outOfStock
      ? '<span class="stock-badge stock-badge--out" aria-label="Out of stock"><i class="fas fa-times-circle" aria-hidden="true"></i> Out of stock</span>'
      : `<span class="stock-badge" aria-label="${p.stock} in stock"><i class="fas fa-box-open" aria-hidden="true"></i> ${p.stock} in stock</span>`;

    return `
    <article class="product-col" data-category="${p.category}" data-product-id="${p.id}" role="listitem">
      <div class="product-card${outOfStock ? ' product-card--disabled' : ''}" tabindex="0" role="button" aria-label="View details for ${p.name}">
        <div class="product-image-wrapper">
          <img src="${p.image}" alt="${p.name} frames" class="product-image" loading="lazy"
               onerror="this.src='../../Resources/Images/glasses1.png'">
          ${stockLabel}
        </div>
        <div class="product-info">
          <h2 class="product-name">${p.name}</h2>
          <p class="product-price">&#8369;${p.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
          <div class="product-actions">
            <button class="btn-buy-now"     ${outOfStock ? 'disabled' : ''} data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-image="${p.image}">Buy Now</button>
            <button class="btn-add-to-cart" ${outOfStock ? 'disabled' : ''} data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-image="${p.image}">
              <i class="fas fa-cart-plus" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </div>
    </article>`;
  }).join('');

  // ADD TO CART
  document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const product = { id: btn.dataset.id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), image: btn.dataset.image };
      if (window.cartManager) window.cartManager.addToCart(product);
    });
  });

  // BUY NOW
  document.querySelectorAll('.btn-buy-now').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation(); e.preventDefault();
      const product = { id: btn.dataset.id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), image: btn.dataset.image };
      if (window.cartManager && typeof window.cartManager.buyNow === 'function') {
        window.cartManager.buyNow(product);
      } else {
        localStorage.setItem('lookgood_cart', JSON.stringify([{ ...product, quantity: 1, selected: false }]));
        window.location.href = '../Checkout/checkout.html';
      }
    });
  });

  // CARD CLICK → product detail
  document.querySelectorAll('.product-col').forEach(col => {
    col.addEventListener('click', (e) => {
      if (e.target.closest('.btn-add-to-cart') || e.target.closest('.btn-buy-now')) return;
      window.location.href = '../Products/product-detail.php?id=' + col.dataset.productId;
    });
  });
}

// ============================================================
// FILTER BUTTONS + SLIDING PILL INDICATOR
// ============================================================
function initFilters() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const indicator  = document.querySelector('.filter-indicator');

  function moveIndicator(btn) {
    if (!indicator) return;
    indicator.style.width     = btn.offsetWidth + 'px';
    indicator.style.transform = 'translateX(' + (btn.offsetLeft - 6) + 'px)';
  }

  function filterProducts(filter) {
    document.querySelectorAll('.product-col').forEach(col => {
      col.classList.toggle('hidden', filter !== 'all' && col.dataset.category !== filter);
    });
  }

  const activeBtn = document.querySelector('.filter-btn.active');
  if (activeBtn) moveIndicator(activeBtn);

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      moveIndicator(btn);
      filterProducts(btn.dataset.filter);
    });
  });

  window.addEventListener('resize', () => {
    const active = document.querySelector('.filter-btn.active');
    if (active) moveIndicator(active);
  });
}

// ============================================================
// LOADING SKELETON
// ============================================================
function showLoading() {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;
  grid.innerHTML = Array(6).fill(0).map(() =>
    '<article class="product-col" role="listitem">' +
    '<div class="product-card product-card--skeleton">' +
    '<div class="skeleton skeleton-img"></div>' +
    '<div class="product-info"><div class="skeleton skeleton-text"></div>' +
    '<div class="skeleton skeleton-text skeleton-text--short"></div></div></div></article>'
  ).join('');
}

// ============================================================
// MAIN — fetch from DB then render
// ============================================================
document.addEventListener('DOMContentLoaded', async () => {
  showLoading();
  let products = [];

  try {
    const res = await fetch(PRODUCTS_API);
    if (!res.ok) throw new Error('Server error ' + res.status);
    products = await res.json();
    if (!Array.isArray(products)) throw new Error('Bad response');
  } catch (err) {
    console.error('Could not load products:', err);
    const grid = document.getElementById('productsGrid');
    if (grid) grid.innerHTML = '<p class="no-products" style="color:red;"><i class="fas fa-exclamation-triangle"></i> Unable to load products. Please try again later.</p>';
    return;
  }

  const params    = new URLSearchParams(window.location.search);
  const searchQ   = params.get('search') || '';
  const urlFilter = params.get('filter') || '';

  let displayed = products;
  if (searchQ) {
    const q = searchQ.toLowerCase();
    displayed = products.filter(p => p.name.toLowerCase().includes(q) || (p.description||'').toLowerCase().includes(q) || p.category.toLowerCase().includes(q));
  }

  renderProducts(displayed);
  initFilters();

  if (urlFilter) {
    const btn = document.querySelector('.filter-btn[data-filter="' + urlFilter + '"]');
    if (btn) btn.click();
  }

  if (searchQ) {
    const si = document.getElementById('searchInput');
    if (si) si.value = searchQ;
  }
});
