console.log('orders.js loaded');
// Data store (populated from backend)
const mockData = { orders: [] };

function isLikelyAutofilledEmail(value) {
    const text = String(value || '').trim();
    if (!text) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text);
}

function clearOrderSearchIfAutofilledEmail() {
    const searchInput = document.getElementById('orderSearchInput');
    if (!searchInput) return;
    if (isLikelyAutofilledEmail(searchInput.value)) searchInput.value = '';
}

// Load orders from backend
function loadOrders() {
    fetch('../adminBack_end/orders.php')
        .then(res => res.json())
        .then(data => {
            mockData.orders = data;
            clearOrderSearchIfAutofilledEmail();
            updateOrderStats();
            updatePaymentStats();
            renderOrders();
            renderPayments();
        })
        .catch(err => console.error('Failed to load orders:', err));
}

// Status workflow
const statusWorkflow = {
    'Pending':    { label: 'Process',   icon: 'fa-arrows-rotate', next: 'Processing', btnClass: 'action-process'   },
    'Processing': { label: 'Ship',      icon: 'fa-truck',         next: 'Shipped',    btnClass: 'action-ship'      },
    'Shipped':    { label: 'Delivered', icon: 'fa-check-circle',  next: 'Delivered',  btnClass: 'action-delivered' },
};

function getActionButton(orderId, status) {
    const step = statusWorkflow[status];
    if (!step) return '';
    return `
        <button type="button" class="btn-action ${step.btnClass} action-btn"
                data-id="${orderId}"
                data-next="${step.next}"
                onclick="openAdminOrderProcess('${orderId}', '${step.next}')"
                title="Mark as ${step.next}">
            <i class="fas ${step.icon}"></i> ${step.label}
        </button>
    `;
}

window.openAdminOrderModal = function(orderId) {
    const order = mockData.orders.find(o => String(o.id) === String(orderId));
    if (order) openOrderModal(order);
};

window.openAdminOrderProcess = function(orderId, nextStatus) {
    const order = mockData.orders.find(o => String(o.id) === String(orderId));
    if (order) openConfirmModal(order, nextStatus);
};

// Tabs
const tabs = document.querySelectorAll('.tab-link');
const tabContents = document.querySelectorAll('.tab-content');
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
    });
});

// Status badge helper
function getStatusBadgeClass(status) {
    const s = (status || '').toLowerCase();
    if (['active', 'delivered', 'paid', 'in stock'].includes(s))                   return 'badge-success';
    if (['processing', 'pending', 'low stock'].includes(s))                         return 'badge-warning';
    if (['inactive', 'cancelled', 'failed', 'out of stock', 'banned', 'refunded'].includes(s)) return 'badge-danger';
    if (['shipped'].includes(s))                                                     return 'badge-info';
    return 'badge-info';
}

// Stats
function updateOrderStats() {
    const o = mockData.orders;
    document.getElementById('totalOrders').textContent      = o.length;
    document.getElementById('processingOrders').textContent = o.filter(x => x.status === 'Processing').length;
    document.getElementById('shippedOrders').textContent    = o.filter(x => x.status === 'Shipped').length;
    document.getElementById('cancelledOrders').textContent  = o.filter(x => x.status === 'Cancelled').length;
}

function updatePaymentStats() {
    const o = mockData.orders;
    document.getElementById('totalPayments').textContent     = o.length;
    document.getElementById('completedPayments').textContent = o.filter(x => x.paymentStatus === 'Paid').length;
    document.getElementById('pendingPayments').textContent   = o.filter(x => x.paymentStatus === 'Pending').length;
    const total = o.reduce((acc, curr) => acc + parseFloat(curr.total || 0), 0);
    document.getElementById('totalAmount').textContent = `₱${total.toFixed(2)}`;
}

// Pagination
let currentOrdersPage   = 1;
let currentPaymentsPage = 1;
const itemsPerPage = 4;

function renderOrdersPagination(container, currentPage, totalPages, onPageChange) {
    if (!container) return;
    container.innerHTML = '';
    if (totalPages <= 1) return;

    const createBtn = (text, page, disabled = false) => {
        const btn = document.createElement('button');
        btn.className = 'pagination-btn';
        btn.innerHTML = text;
        btn.disabled  = disabled;
        if (!disabled) btn.onclick = () => onPageChange(page);
        if (page === currentPage) btn.classList.add('active');
        return btn;
    };

    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end   = Math.min(totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

    container.appendChild(createBtn('<', currentPage - 1, currentPage === 1));
    if (start > 1) container.appendChild(createBtn(1, 1));
    if (start > 2) container.appendChild(document.createTextNode(' … '));
    for (let i = start; i <= end; i++) container.appendChild(createBtn(i, i));
    if (end < totalPages - 1) container.appendChild(document.createTextNode(' … '));
    if (end < totalPages) container.appendChild(createBtn(totalPages, totalPages));
    container.appendChild(createBtn('>', currentPage + 1, currentPage === totalPages));
}

// Render orders table
function renderOrders() {
    const tableBody = document.querySelector('#ordersTable tbody');
    if (!tableBody) return;

    const searchInput = document.getElementById('orderSearchInput');
    const searchRaw   = String(searchInput?.value || '').trim();
    const search      = isLikelyAutofilledEmail(searchRaw) ? '' : searchRaw.toLowerCase();
    const orderStatus = document.getElementById('orderStatusFilter')?.value;
    const fromDate    = document.getElementById('fromDate')?.value;
    const toDate      = document.getElementById('toDate')?.value;

    const filtered = mockData.orders.filter(o => {
        const matchesSearch = o.id.toLowerCase().includes(search) || o.customerName.toLowerCase().includes(search);
        const matchesOrder  = !orderStatus || o.status.toLowerCase() === orderStatus.toLowerCase();
        let matchesDate = true;
        if (fromDate || toDate) {
            const orderDate = new Date(o.date);
            if (fromDate && orderDate < new Date(fromDate)) matchesDate = false;
            if (toDate   && orderDate > new Date(toDate + 'T23:59:59')) matchesDate = false;
        }
        return matchesSearch && matchesOrder && matchesDate;
    });

    const totalPages = Math.ceil(filtered.length / itemsPerPage) || 1;
    if (currentOrdersPage > totalPages) currentOrdersPage = totalPages;

    const start     = (currentOrdersPage - 1) * itemsPerPage;
    const paginated = filtered.slice(start, start + itemsPerPage);

    tableBody.innerHTML = paginated.map(o => `
        <tr>
            <td><strong>${o.order_id || 'LG-' + String(o.id).padStart(6, '0')}</strong></td>
            <td>${o.customerName}</td>
            <td>${o.product}</td>
            <td><span class="badge ${getStatusBadgeClass(o.status)}">${o.status}</span></td>
            <td>${o.date}</td>
            <td><strong>₱${parseFloat(o.total).toFixed(2)}</strong></td>
            <td class="actions-cell">
                <button type="button" class="btn btn-secondary btn-sm view-order-btn" data-id="${o.id}" title="View details">
                    <i class="fas fa-eye"></i>
                </button>
                ${getActionButton(o.id, o.status)}
            </td>
        </tr>
    `).join('');

    renderOrdersPagination(
        document.getElementById('ordersPagination'),
        currentOrdersPage, totalPages,
        page => { currentOrdersPage = page; renderOrders(); }
    );
}

// Render payments table
function renderPayments() {
    const tableBody = document.querySelector('#paymentsTable tbody');
    if (!tableBody) return;

    const search  = (document.getElementById('paymentSearchInput')?.value || '').toLowerCase();
    const payStat = document.getElementById('payStatFilter')?.value;
    const fromDate = document.getElementById('paymentFromDate')?.value;
    const toDate   = document.getElementById('paymentToDate')?.value;

    const filtered = mockData.orders.filter(o => {
        const matchesSearch = o.id.toLowerCase().includes(search) || o.customerName.toLowerCase().includes(search);
        const matchesStat   = !payStat || o.paymentStatus === payStat;
        let matchesDate = true;
        if (fromDate || toDate) {
            const orderDate = new Date(o.date);
            if (fromDate && orderDate < new Date(fromDate)) matchesDate = false;
            if (toDate   && orderDate > new Date(toDate + 'T23:59:59')) matchesDate = false;
        }
        return matchesSearch && matchesStat && matchesDate;
    });

    const totalPages = Math.ceil(filtered.length / itemsPerPage) || 1;
    if (currentPaymentsPage > totalPages) currentPaymentsPage = totalPages;

    const start     = (currentPaymentsPage - 1) * itemsPerPage;
    const paginated = filtered.slice(start, start + itemsPerPage);

    tableBody.innerHTML = paginated.map(o => {
        const formattedId = o.order_id || 'LG-' + String(o.id).padStart(6, '0');
        let refundBtn = '';
        if (o.status === 'Cancelled' && o.paymentStatus === 'Paid') {
            refundBtn = `<button class="btn btn-danger btn-sm refund-payment-btn" data-id="${o.id}" title="Refund">
                <i class="fas fa-undo"></i> Refund
            </button>`;
        }
        return `
        <tr>
            <td><strong>${formattedId}</strong></td>
            <td>${o.customerName}</td>
            <td><span class="badge ${getStatusBadgeClass(o.paymentStatus)}">${o.paymentStatus}</span></td>
            <td>${o.paymentMethod}</td>
            <td><strong>₱${parseFloat(o.total).toFixed(2)}</strong></td>
            <td>${o.date}</td>
            <td>
                <button class="btn btn-secondary btn-sm view-payment-btn" data-id="${o.id}" title="View details">
                    <i class="fas fa-eye"></i>
                </button>
                ${refundBtn}
            </td>
        </tr>
        `;
    }).join('');

    renderOrdersPagination(
        document.getElementById('paymentsPagination'),
        currentPaymentsPage, totalPages,
        page => { currentPaymentsPage = page; renderPayments(); }
    );
}

// ─── Confirm status modal ─────────────────────────────────────────────────────
const confirmModal      = document.getElementById('confirmStatusModal');
const confirmModalTitle = document.getElementById('confirmModalTitle');
const confirmModalBody  = document.getElementById('confirmModalBody');
const confirmModalBtn   = document.getElementById('confirmStatusBtn');

document.getElementById('closeConfirmModal').addEventListener('click',  () => confirmModal.classList.remove('show'));
document.getElementById('cancelConfirmModal').addEventListener('click', () => confirmModal.classList.remove('show'));
confirmModal.addEventListener('click', e => { if (e.target === confirmModal) confirmModal.classList.remove('show'); });

const confirmMeta = {
    'Processing': { label: 'Process Order',  icon: 'fa-arrows-rotate', btnClass: 'confirm-processing' },
    'Shipped':    { label: 'Ship Order',      icon: 'fa-truck',         btnClass: 'confirm-shipped'    },
    'Delivered':  { label: 'Mark Delivered',  icon: 'fa-check-circle',  btnClass: 'confirm-delivered'  },
};

function openConfirmModal(order, nextStatus) {
    const meta = confirmMeta[nextStatus] || { label: `Mark as ${nextStatus}`, icon: 'fa-arrow-right', btnClass: '' };

    confirmModalTitle.textContent = meta.label;
    confirmModalBody.innerHTML = `
        Are you sure you want to mark <strong>${order.order_id || order.id}</strong>
        (<em>${order.customerName}</em>) as <strong>${nextStatus}</strong>?
    `;

    confirmModalBtn.className = `btn-confirm ${meta.btnClass}`;
    confirmModalBtn.innerHTML = `<i class="fas ${meta.icon}"></i> Confirm`;

    confirmModalBtn.onclick = () => {
        // ── FIX: use order.orderId (the real integer DB primary key) ──────
        fetch('../adminBack_end/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: order.orderId, status: nextStatus.toLowerCase() })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update local data so UI reflects change immediately
                order.status = nextStatus;
                // Use the payment status returned by the server
                if (data.paymentStatus) order.paymentStatus = data.paymentStatus;

                // Derive estimated delivery from shipping method for display
                const estMap = {
                    'standard shipping': '3–5 business days',
                    'free shipping':     '5–7 business days',
                    'express shipping':  '1–2 business days',
                };
                const smKey = (order.shippingMethod || '').toLowerCase().split('(')[0].trim();
                order.estimatedDelivery = estMap[smKey] || order.estimatedDelivery || '3–5 business days';

                confirmModal.classList.remove('show');
                updateOrderStats();
                updatePaymentStats();
                renderOrders();
                renderPayments();
                showToast(`${order.order_id || order.id} marked as ${nextStatus}`);
            } else {
                alert('Status update failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(() => alert('Network error: status not saved.'));
    };

    confirmModal.classList.add('show');
}

// Toast
function showToast(message) {
    const toast    = document.getElementById('statusToast');
    const toastMsg = document.getElementById('toastMessage');
    toastMsg.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// ─── Order details modal ──────────────────────────────────────────────────────
const orderModal = document.getElementById('orderModal');

document.getElementById('closeOrderModal').addEventListener('click', () => orderModal.classList.remove('show'));
orderModal.addEventListener('click', e => { if (e.target === orderModal) orderModal.classList.remove('show'); });

function openOrderModal(order) {
    if (!orderModal) return;

    const toMoney = v => `₱${Number(v || 0).toFixed(2)}`;
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    // ── Address parsing ───────────────────────────────────────────────────
    const rawAddress   = String(order.shippingAddress || '').trim();
    const addressParts = rawAddress ? rawAddress.split(',').map(s => s.trim()).filter(Boolean) : [];
    const addressLine1 = order.addressLine1 || addressParts[0] || rawAddress || '—';
    const city         = order.city         || addressParts[1] || '—';
    const province     = order.province     || addressParts[2] || '—';
    const region       = order.region       || addressParts[3] || '—';

    const shippingFee = Number(order.shippingFee || 0);
    const subtotal    = Math.max(0, Number(order.total || 0) - shippingFee);

    setText('modalCustomerName',      order.customerName || '—');
    setText('modalHeaderOrderID',     order.order_id || ('LG-' + String(order.id).padStart(6, '0')));
    setText('modalOrderID',           order.order_id || ('LG-' + String(order.id).padStart(6, '0')));
    setText('modalCustomerEmail',     order.customerEmail  || '—');
    setText('modalCustomerPhone',     order.customerPhone  || '—');
    setText('modalPaymentMethodOrder',order.paymentMethod  || '—');
    setText('modalShippingMethod',    order.shippingMethod || '—');
    setText('modalTotalAmount',       toMoney(order.total));
    setText('modalPaymentStatus',     order.paymentStatus  || '—');
    setText('modalShippingFullName',  order.customerName   || '—');
    setText('modalShippingPhone',     order.customerPhone  || '—');
    setText('modalAddressLine1',      addressLine1);
    setText('modalAddressLine2',      order.shippingAddress2 || '—');
    setText('modalCity',              city);
    setText('modalProvince',          province);
    setText('modalZip',               order.zip    || '—');
    setText('modalRegion',            region);
    setText('modalDeliveryNote',      order.deliveryNote || '—');

    // ── Shipping method section ───────────────────────────────────────────
    // These now come from the DB — no more hardcoding
    setText('modalCourierName',        order.shippingMethod    || '—');
    setText('modalEstimatedDelivery',  order.estimatedDelivery || '—');
    setText('modalTrackingNumber',     order.trackingNumber    || 'Not yet assigned');

    setText('modalSubtotal',     toMoney(subtotal));
    setText('modalShippingFee',  toMoney(shippingFee));

    // Payment status badge colour
    const payStatusEl = document.getElementById('modalPaymentStatus');
    if (payStatusEl) {
        payStatusEl.textContent = order.paymentStatus || '—';
        payStatusEl.className   = `om-payment-status ${(order.paymentStatus || '').toLowerCase()}`;
    }

    // Order status badge
    const statusEl = document.getElementById('modalOrderStatus');
    if (statusEl) {
        statusEl.textContent = order.status;
        statusEl.className   = `om-status-badge ${String(order.status || '').toLowerCase()}`;
    }

    // Items list
    const itemsList = document.getElementById('modalItemsList');
    if (itemsList) {
        const items = Array.isArray(order.items) ? order.items : [];
        itemsList.innerHTML = items.length
            ? items.map(item => `
                <div class="item-row-exact">
                    <img src="${item.image || '/global/jin.jpg'}" class="item-image-exact" alt="${item.name || 'Item'}">
                    <div class="item-details-exact">
                        <div class="item-title-exact">${item.name || 'Item'}</div>
                        <div class="item-qty-exact">Qty: ${item.qty ?? 1}</div>
                    </div>
                    <div class="item-price-exact">${toMoney(item.price)}</div>
                </div>
            `).join('')
            : `<div class="om-empty-state">${order.product || 'No items available'}</div>`;
    }

    // Timeline
    const timeline = document.getElementById('modalTimeline');
    if (timeline) {
        const steps = [
            { icon: 'fa-shopping-cart', title: 'Order Placed',       date: order.date || '—' },
            { icon: 'fa-credit-card',   title: 'Payment Confirmed',   date: order.paymentConfirmedAt || (order.paymentStatus === 'Paid' ? order.date : '—') },
            { icon: 'fa-cogs',          title: 'Order Processed',     date: order.processedAt || '—' },
            { icon: 'fa-truck',         title: 'Order Shipped',       date: order.shippedAt   || '—' },
            { icon: 'fa-check-circle',  title: 'Order Delivered',     date: order.deliveredAt || '—' },
        ];
        timeline.innerHTML = `
            <div class="order-timeline-list">
                ${steps.map(step => `
                    <div class="order-timeline-item">
                        <span class="order-timeline-icon"><i class="fas ${step.icon}"></i></span>
                        <div class="order-timeline-content">
                            <div class="order-timeline-title">${step.title}</div>
                            <div class="order-timeline-date">${step.date}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    orderModal.classList.add('show');
}

// ─── Payment details modal ────────────────────────────────────────────────────
const paymentModal = document.getElementById('paymentModal');

document.getElementById('closePaymentModal').addEventListener('click', () => paymentModal.classList.remove('show'));
paymentModal.addEventListener('click', e => { if (e.target === paymentModal) paymentModal.classList.remove('show'); });

function openPaymentModal(order) {
    const formattedId = order.order_id || ('LG-' + String(order.id).padStart(6, '0'));
    document.getElementById('modalPaymentCustomerName').textContent = order.customerName;
    document.getElementById('modalPaymentOrderID').textContent      = formattedId;
    document.getElementById('modalPaymentOrderIDField').textContent = formattedId;
    document.getElementById('modalPaymentAmount').textContent       = `₱${parseFloat(order.total).toFixed(2)}`;
    document.getElementById('modalPaymentMethod').textContent       = order.paymentMethod;
    document.getElementById('modalPaymentDate').textContent         = order.date;

    const statusEl = document.getElementById('modalPaymentStatusPayment');
    statusEl.textContent = order.paymentStatus;
    statusEl.className   = `pm-pay-status ${(order.paymentStatus || '').toLowerCase()}`;

    paymentModal.classList.add('show');
}

// Global escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        orderModal.classList.remove('show');
        paymentModal.classList.remove('show');
        document.getElementById('confirmStatusModal').classList.remove('show');
    }
});

// Table click delegation
document.getElementById('ordersTable')?.addEventListener('click', (event) => {
    const viewBtn = event.target.closest('.view-order-btn');
    if (viewBtn) {
        event.preventDefault(); event.stopPropagation();
        const order = mockData.orders.find(o => o.id === viewBtn.dataset.id);
        if (order) openOrderModal(order);
        return;
    }
    const actionBtn = event.target.closest('.action-btn');
    if (actionBtn) {
        event.preventDefault(); event.stopPropagation();
        const order = mockData.orders.find(o => o.id === actionBtn.dataset.id);
        if (order) openConfirmModal(order, actionBtn.dataset.next);
    }
});

document.getElementById('paymentsTable')?.addEventListener('click', (event) => {
    const viewBtn = event.target.closest('.view-payment-btn');
    if (viewBtn) {
        event.preventDefault(); event.stopPropagation();
        const order = mockData.orders.find(o => o.id === viewBtn.dataset.id);
        if (order) openPaymentModal(order);
        return;
    }
    const refundBtn = event.target.closest('.refund-payment-btn');
    if (refundBtn) {
        event.preventDefault(); event.stopPropagation();
        const order = mockData.orders.find(o => o.id === refundBtn.dataset.id);
        if (!order) return;
        fetch('../adminBack_end/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: order.orderId, status: 'refunded' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                order.status        = 'Cancelled';
                order.paymentStatus = 'Refunded';
                renderPayments();
                renderOrders();
                updateOrderStats();
                updatePaymentStats();
                showToast(`Order ${order.order_id || ('LG-' + String(order.id).padStart(6, '0'))} marked as Refunded`);
            } else {
                alert('Refund failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(() => alert('Network error: refund not saved.'));
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    const orderStatusFilter = document.getElementById('orderStatusFilter');
    const payStatFilter     = document.getElementById('payStatFilter');
    if (orderStatusFilter) orderStatusFilter.value = '';
    if (payStatFilter)     payStatFilter.value     = '';

    loadOrders();

    ['orderSearchInput', 'orderStatusFilter', 'fromDate', 'toDate'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => { currentOrdersPage = 1; renderOrders(); });
    });

    ['paymentSearchInput', 'payStatFilter', 'paymentFromDate', 'paymentToDate'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => { currentPaymentsPage = 1; renderPayments(); });
    });
});