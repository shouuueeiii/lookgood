const PRODUCTS_API = '/lookgood/userBack_end/productsAPI.php';

let allProducts = []; // ✅ global source of truth

function getProductSearchTerm() {
  const searchInput = document.getElementById('searchInput');
  if (!searchInput) return '';

  const raw = String(searchInput.value || '').trim();
  return raw.toLowerCase();
}

// ============================================================
// MAIN RENDER
// ============================================================
function renderProducts(products) {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;

  if (!products.length) {
    grid.innerHTML = '<p class="no-products">No products found.</p>';
    return;
  }

  grid.innerHTML = products.map(p => {
    const outOfStock = p.stock <= 0;

    const stockLabel = outOfStock
      ? '<span class="stock-badge stock-badge--out"><i class="fas fa-times-circle"></i> Out of stock</span>'
      : `<span class="stock-badge"><i class="fas fa-box-open"></i> ${p.stock} in stock</span>`;

    return `
    <article class="product-col" data-category="${p.category}" data-product-id="${p.id}">
      <div class="product-card${outOfStock ? ' product-card--disabled' : ''}">
        <div class="product-image-wrapper">
          <img src="${p.image}" class="product-image"
            onerror="this.src='/lookgood/New%20folder/Resources/Images/glasses1.png';">
          ${stockLabel}
        </div>

        <div class="product-info">
          <h2>${p.name}</h2>
          <p>₱${p.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>

          <div class="product-actions">
            <button class="btn-buy-now" ${outOfStock ? 'disabled' : ''}
              data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-image="${p.image}">
              Buy Now
            </button>

            <button class="btn-add-to-cart" ${outOfStock ? 'disabled' : ''}
              data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-image="${p.image}">
              <i class="fas fa-cart-plus"></i>
            </button>
          </div>
        </div>
      </div>
    </article>`;
  }).join('');

  attachProductEvents();
}

// ============================================================
// EVENTS (BUY / CART / CLICK)
// ============================================================
function attachProductEvents() {

  document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
    btn.onclick = (e) => {
      e.stopPropagation();
      const product = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        price: parseFloat(btn.dataset.price),
        image: btn.dataset.image
      };

      if (window.cartManager) {
        window.cartManager.addToCart(product);
      }
    };
  });

  document.querySelectorAll('.btn-buy-now').forEach(btn => {
    btn.onclick = (e) => {
      e.stopPropagation();

      const product = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        price: parseFloat(btn.dataset.price),
        image: btn.dataset.image
      };

      if (window.cartManager?.buyNow) {
        window.cartManager.buyNow(product);
      } else {
        localStorage.setItem('lookgood_cart', JSON.stringify([
          { ...product, quantity: 1, selected: false }
        ]));
        window.location.href = '../Checkout/checkout.html';
      }
    };
  });

  document.querySelectorAll('.product-col').forEach(col => {
    col.onclick = (e) => {
      if (e.target.closest('button')) return;
      window.location.href = '/lookgood/home/product-detail.php?id=' + col.dataset.productId;
    };
  });
}

// ============================================================
// FILTER + SEARCH (COMBINED LOGIC)
// ============================================================
function applySearchAndFilter() {
  const term = getProductSearchTerm();

  const activeBtn = document.querySelector('.filter-btn.active');
  let filter = activeBtn ? activeBtn.dataset.filter : 'all';

  // normalize
  if (filter === 'men') filter = 'male';
  if (filter === 'women') filter = 'female';

  const filtered = allProducts.filter(p => {
    const matchSearch = p.name.toLowerCase().includes(term);
    const matchFilter = filter === 'all' || p.category.toLowerCase() === filter;
    return matchSearch && matchFilter;
  });

  renderProducts(filtered);
}

// ============================================================
// FILTER UI
// ============================================================
function initFilters() {
  const filterBtns = document.querySelectorAll('.filter-btn');
  const indicator = document.querySelector('.filter-indicator');

  function moveIndicator(btn) {
    if (!indicator) return;
    indicator.style.width = btn.offsetWidth + 'px';
    indicator.style.transform = `translateX(${btn.offsetLeft - 6}px)`;
  }

  const activeBtn = document.querySelector('.filter-btn.active');
  if (activeBtn) moveIndicator(activeBtn);

  filterBtns.forEach(btn => {
    btn.onclick = () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      moveIndicator(btn);
      applySearchAndFilter(); // ✅ FIXED
    };
  });

  window.addEventListener('resize', () => {
    const active = document.querySelector('.filter-btn.active');
    if (active) moveIndicator(active);
  });
}


function showLoading() {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;

  grid.innerHTML = Array(6).fill().map(() =>
    `<div class="product-card product-card--skeleton">
      <div class="skeleton skeleton-img"></div>
      <div class="skeleton skeleton-text"></div>
    </div>`
  ).join('');
}


document.addEventListener('DOMContentLoaded', async () => {
  showLoading();

  try {
    const res = await fetch(PRODUCTS_API);
    if (!res.ok) throw new Error();

    allProducts = await res.json();

    renderProducts(allProducts);
    initFilters();

  } catch (err) {
    console.error(err);
    document.getElementById('productsGrid').innerHTML =
      '<p style="color:red;">Failed to load products</p>';
    return;
  }

  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', applySearchAndFilter);
  }

  const params = new URLSearchParams(window.location.search);
  const searchQ = params.get('search');

  if (searchQ && searchInput) {
    searchInput.value = searchQ;
    applySearchAndFilter();
  }
});