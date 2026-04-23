// profile-purchases.js
// Fetches real orders from the DB via ordersAPI.php
// Path: New folder/Profile/ → userBack_end/
const ORDERS_API = '../userBack_end/ordersAPI.php';
const FEEDBACK_SYNC_API = '../userBack_end/feedbackSync.php';

// ── Status config — maps tab keys to display config ──────────────────────────
// tabKey       : matches the data-ptab attribute and list-/empty-/count- IDs in HTML
// statusValues : DB status values (after mapStatusForProfile) that belong in this tab
const STATUS_CONFIG = {
  'to-ship': {
    statusValues: ['paid', 'processing'],
    label: 'To Ship', badgeClass: 'status-processing', icon: 'fa-box',
    actions: (order) => `
      <button class="btn-order-action btn-order-danger" onclick="window.cancelOrder('${order.order_id}','${order.raw_order_id}')">Cancel</button>
      <button class="btn-order-action btn-order-secondary" onclick="showToast('Contact our team via the chat button','info')">Contact Seller</button>
    `
  },
  'to-receive': {
    statusValues: ['shipped'],
    label: 'To Receive', badgeClass: 'status-shipped', icon: 'fa-truck',
    actions: () => '<button class="btn-order-action btn-order-secondary" onclick="showToast(\'Tracking info will be sent to your email\',\'info\')">Track Shipment</button>'
  },
  'delivered': {
    statusValues: ['delivered'],
    label: 'Delivered', badgeClass: 'status-delivered', icon: 'fa-check-circle',
    actions: (order) => `<button class="btn-order-action btn-order-primary" onclick="window.confirmOrder('${order.order_id}','${order.raw_order_id}')">Confirm Order</button>`
  },
  'completed': {
    statusValues: ['completed'],
    label: 'Completed', badgeClass: 'status-completed', icon: 'fa-history',
    actions: (order) => `<button class="btn-order-action btn-order-primary" onclick="window.buyAgain('${order.order_id}')"><i class="fas fa-redo"></i> Buy Again</button>`
  },
  'cancelled': {
    statusValues: ['cancelled'],
    label: 'Cancelled', badgeClass: 'status-cancelled', icon: 'fa-times-circle',
    actions: () => '<span class="order-cancelled-badge"><i class="fas fa-ban"></i> Cancelled</span>'
  }
};

// Helper: get the config for a given order status value
function getConfigForStatus(statusValue) {
  for (const [tabKey, config] of Object.entries(STATUS_CONFIG)) {
    if (config.statusValues.includes(statusValue)) return { tabKey, config };
  }
  // fallback — treat unknown as to-ship
  return { tabKey: 'to-ship', config: STATUS_CONFIG['to-ship'] };
}

let allOrders = [];
// { "orderId|productId": { rating, comment } }
let submittedRatings = {};
let currentRatingContext = null; // { orderId, rawOrderId, productId, productName }

// ── Load already-submitted ratings from DB ────────────────────────────────────
async function loadSubmittedRatings() {
  try {
    const res = await fetch(FEEDBACK_SYNC_API);
    if (!res.ok) return;
    const data = await res.json();
    // data: { "orderId|productId": { rating, comment } }
    if (data && typeof data === 'object' && !data.error) {
      submittedRatings = data;
    }
  } catch (e) {
    console.warn('Could not load submitted ratings:', e);
  }
}

function getRatingKey(orderId, productId) {
  return `${orderId}|${productId}`;
}

function getExistingRating(orderId, productId) {
  return submittedRatings[getRatingKey(orderId, productId)] || null;
}

async function submitRatingToDB(rawOrderId, productId, rating, comment) {
  const res = await fetch(FEEDBACK_SYNC_API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_id: rawOrderId, product_id: productId, rating, comment })
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    throw new Error(data.error || 'Failed to save rating');
  }
  return data;
}

// ── Fetch orders from DB ──────────────────────────────────────────────────────
async function loadOrders() {
  const allTabKeys = ['all', ...Object.keys(STATUS_CONFIG)];
  // Show loading skeletons in all tab lists
  allTabKeys.forEach(tabKey => {
    const listEl = document.getElementById('list-' + tabKey);
    if (listEl) listEl.innerHTML = '<p style="padding:1rem;color:#999;"><i class="fas fa-spinner fa-spin"></i> Loading orders…</p>';
  });

  try {
    const res = await fetch(ORDERS_API);
    if (!res.ok) throw new Error('HTTP ' + res.status);
    allOrders = await res.json();
    if (!Array.isArray(allOrders)) throw new Error('Bad response');
  } catch (err) {
    console.error('Could not load orders:', err);
    Object.keys(STATUS_CONFIG).concat(['all']).forEach(tabKey => {
      const listEl = document.getElementById('list-' + tabKey);
      if (listEl) listEl.innerHTML = '<p style="padding:1rem;color:#c00;"><i class="fas fa-exclamation-triangle"></i> Could not load orders.</p>';
    });
    return;
  }

  await loadSubmittedRatings();
  distributeOrders();
}

// ── Distribute orders to their tabs ──────────────────────────────────────────
function distributeOrders() {
  // ── "All Orders" tab ──────────────────────────────────────────────────────
  const allListEl  = document.getElementById('list-all');
  const allEmptyEl = document.getElementById('empty-all');
  const allCountEl = document.getElementById('count-all');
  if (allCountEl) allCountEl.textContent = allOrders.length;
  if (allListEl) {
    if (allOrders.length === 0) {
      allListEl.innerHTML = '';
      if (allEmptyEl) allEmptyEl.style.display = 'flex';
    } else {
      if (allEmptyEl) allEmptyEl.style.display = 'none';
      allListEl.innerHTML = allOrders.map(order => {
        const { tabKey, config } = getConfigForStatus(order.status);
        return renderOrderCard(order, config, tabKey);
      }).join('');
    }
  }

  // ── Per-status tabs ───────────────────────────────────────────────────────
  for (const [tabKey, config] of Object.entries(STATUS_CONFIG)) {
    const filtered = allOrders.filter(o => config.statusValues.includes(o.status));
    const listEl   = document.getElementById('list-' + tabKey);
    const emptyEl  = document.getElementById('empty-' + tabKey);
    const countEl  = document.getElementById('count-' + tabKey);
    if (countEl) countEl.textContent = filtered.length;
    if (!listEl) continue;
    if (filtered.length === 0) {
      listEl.innerHTML = '';
      if (emptyEl) emptyEl.style.display = 'flex';
      continue;
    }
    if (emptyEl) emptyEl.style.display = 'none';
    listEl.innerHTML = filtered.map(order => renderOrderCard(order, config, tabKey)).join('');
  }
  attachRatingButtons();
}

// ── Render a single order card ────────────────────────────────────────────────
function renderOrderCard(order, config, tabKey) {
  const itemsHtml = order.items.map(item => {
    const existingRating = getExistingRating(order.order_id, item.id);
    const ratingDisplay  = existingRating ? `
      <div class="rating-display" style="margin-top:0.5rem;font-size:0.8rem;">
        <span>⭐ ${existingRating.rating}/5</span>
        ${existingRating.comment ? `<span style="color:#666;">"${escapeHtml(existingRating.comment)}"</span>` : ''}
      </div>` : '';
    const rateButton = (tabKey === 'completed') ? (
      existingRating
        ? `<button class="btn-rate-product-yellow btn-rated" data-order-id="${order.order_id}" data-raw-order-id="${order.raw_order_id}" data-product-id="${item.id}" data-product-name="${escapeHtml(item.name)}"
              style="background:#e0e0e0;color:#555;border:none;padding:0.25rem 0.75rem;border-radius:4px;cursor:pointer;margin-left:1rem;font-weight:bold;">
            <i class="fas fa-edit"></i> Edit Rating
          </button>`
        : `<button class="btn-rate-product-yellow" data-order-id="${order.order_id}" data-raw-order-id="${order.raw_order_id}" data-product-id="${item.id}" data-product-name="${escapeHtml(item.name)}"
              style="background:#f1c40f;color:#333;border:none;padding:0.25rem 0.75rem;border-radius:4px;cursor:pointer;margin-left:1rem;font-weight:bold;">
            <i class="fas fa-star"></i> Rate
          </button>`
    ) : '';
    return `
      <div class="order-item" data-product-id="${item.id}">
        <img class="order-item-image" src="${item.image}" alt="${escapeHtml(item.name)}"
             onerror="this.onerror=null;this.src='/lookgood/New%20folder/Resources/Images/glasses1.png';">
        <div class="order-item-info">
          <p class="order-item-name">${escapeHtml(item.name)}</p>
          <span class="order-item-meta">Qty: ${item.qty}</span>
          ${ratingDisplay}
        </div>
        <span class="order-item-price">&#8369;${(item.price * item.qty).toLocaleString('en-PH',{minimumFractionDigits:2})}</span>
        ${rateButton}
      </div>`;
  }).join('');

  const actionsHtml = config.actions(order);
  return `
    <div class="order-card" data-order-id="${order.order_id}">
      <div class="order-header">
        <span class="order-id">Order ${order.order_id}</span>
        <span class="order-date"><i class="fas fa-calendar-alt"></i> ${order.date}</span>
        <span class="order-status-badge ${config.badgeClass}"><i class="fas ${config.icon}"></i> ${config.label}</span>
      </div>
      <div class="order-items">${itemsHtml}</div>
      <div class="order-footer">
        <div class="order-total-row">Total: &#8369;${order.total.toLocaleString('en-PH',{minimumFractionDigits:2})}</div>
        <div class="order-actions">${actionsHtml}</div>
      </div>
    </div>`;
}

// ── Rating modal ─────────────────────────────────────────────────────────────
function openRatingModal(orderId, rawOrderId, productId, productName) {
  currentRatingContext = { orderId, rawOrderId, productId, productName };
  const nameEl = document.getElementById('ratingProductName');
  if (nameEl) nameEl.innerText = 'Rate: ' + productName;
  const valEl = document.getElementById('ratingValue');
  if (valEl) valEl.value = '0';
  const comEl = document.getElementById('ratingComment');
  if (comEl) comEl.value = '';
  const ccEl = document.getElementById('charCount');
  if (ccEl) ccEl.innerText = '0';

  // Pre-fill if already rated this order item
  const existing = getExistingRating(orderId, productId);
  if (existing) {
    if (valEl) valEl.value = existing.rating;
    if (comEl) comEl.value = existing.comment || '';
    if (ccEl) ccEl.innerText = (existing.comment || '').length;
  }

  document.querySelectorAll('.star').forEach((s, idx) => {
    const filled = existing ? idx < existing.rating : false;
    s.classList.toggle('active', filled);
    s.innerText = filled ? '★' : '☆';
  });

  const modal = document.getElementById('ratingModal');
  if (modal) { modal.style.display = 'flex'; modal.classList.add('active'); }
}

function closeRatingModal() {
  const modal = document.getElementById('ratingModal');
  if (modal) { modal.style.display = 'none'; modal.classList.remove('active'); }
  currentRatingContext = null;
}

function initRatingModal() {
  document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', () => {
      const rating = parseInt(star.dataset.star);
      const valEl = document.getElementById('ratingValue');
      if (valEl) valEl.value = rating;
      document.querySelectorAll('.star').forEach((s, idx) => {
        s.classList.toggle('active', idx < rating);
        s.innerText = idx < rating ? '★' : '☆';
      });
    });
  });

  const commentArea = document.getElementById('ratingComment');
  if (commentArea) {
    commentArea.addEventListener('input', () => {
      const ccEl = document.getElementById('charCount');
      if (ccEl) ccEl.innerText = commentArea.value.length;
    });
  }

  document.getElementById('submitRatingBtn')?.addEventListener('click', async () => {
    const rating = parseInt(document.getElementById('ratingValue')?.value || '0');
    if (!rating) { showToast('Please select a star rating (1–5).', 'error'); return; }
    const comment = document.getElementById('ratingComment')?.value.trim() || '';
    if (comment.length > 600) { showToast('Comment exceeds 600 characters.', 'error'); return; }
    if (!currentRatingContext) { showToast('Rating context lost. Please try again.', 'error'); return; }

    const { orderId, rawOrderId, productId } = currentRatingContext;

    try {
      await submitRatingToDB(rawOrderId, productId, rating, comment);
      // Store locally so UI updates without refetch
      submittedRatings[getRatingKey(orderId, productId)] = { rating, comment };
      closeRatingModal();
      showToast('Thank you for your rating!', 'success');
      distributeOrders();
    } catch (err) {
      console.error('Rating submit failed:', err);
      showToast('Could not save your rating: ' + err.message, 'error');
    }
  });

  document.querySelector('.rating-modal-close')?.addEventListener('click', closeRatingModal);
  window.addEventListener('click', (e) => {
    if (e.target === document.getElementById('ratingModal')) closeRatingModal();
  });
}

function attachRatingButtons() {
  document.querySelectorAll('.btn-rate-product-yellow').forEach(btn => {
    btn.replaceWith(btn.cloneNode(true)); // remove old listeners
  });
  document.querySelectorAll('.btn-rate-product-yellow').forEach(btn => {
    btn.addEventListener('click', () => openRatingModal(
      btn.dataset.orderId,
      btn.dataset.rawOrderId,
      btn.dataset.productId,
      btn.dataset.productName
    ));
  });
}

// ── Order actions — call DB ───────────────────────────────────────────────────
async function callOrderAPI(rawOrderId, action) {
  try {
    const res = await fetch(ORDERS_API, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ order_id: rawOrderId, action })
    });
    return await res.json();
  } catch (e) {
    console.error('Order API error:', e);
    return { success: false };
  }
}

window.cancelOrder = async function (displayId, rawId) {
  if (!confirm('Cancel order ' + displayId + '?')) return;
  const result = await callOrderAPI(rawId, 'cancel');
  if (result.success) {
    const order = allOrders.find(o => o.order_id === displayId);
    if (order) order.status = 'cancelled';
    distributeOrders();
    showToast('Order ' + displayId + ' cancelled', 'success');
  } else {
    showToast(result.error || 'Cannot cancel this order now', 'error');
  }
};

window.confirmOrder = async function (displayId, rawId) {
  if (!confirm('Confirm you received order ' + displayId + '?')) return;
  const result = await callOrderAPI(rawId, 'confirm');
  if (result.success) {
    const order = allOrders.find(o => o.order_id === displayId);
    if (order) order.status = 'completed';
    distributeOrders();
    showToast('Order completed! You can now rate products.', 'success');
  } else {
    showToast(result.error || 'Cannot confirm this order', 'error');
  }
};

window.buyAgain = function (orderId) {
  showToast('Items from order ' + orderId + ' added to cart!', 'success');
};

// ── Tab switching ─────────────────────────────────────────────────────────────
function initPurchaseTabs() {
  const tabs   = document.querySelectorAll('.purchase-tab');
  const panels = document.querySelectorAll('.purchase-panel');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const targetKey = tab.dataset.ptab;
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      panels.forEach(p => p.classList.remove('active'));
      const panel = document.getElementById('ppanel-' + targetKey);
      if (panel) panel.classList.add('active');
      attachRatingButtons();
    });
  });
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
}

document.addEventListener('DOMContentLoaded', () => {
  initPurchaseTabs();
  initRatingModal();
  loadOrders();
});