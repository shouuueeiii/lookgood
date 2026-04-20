// ============================================================
// global.js  –  Profile, Sidebar, Search, Notifications
// Profile section fully wired to the real `admin` table:
//   admin_id | admin_name | email | password | position
// ============================================================

// ============================================================
// PROFILE
// ============================================================
function initProfileFeatures() {
    const profileTrigger   = document.getElementById('profileTrigger');
    const profileDropdown  = document.getElementById('profileDropdown');
    const editProfileBtn   = document.getElementById('editProfileBtn');
    const logoutBtn        = document.getElementById('logoutBtn');
    const editProfileModal = document.getElementById('editProfileModal');
    const logoutModal      = document.getElementById('logoutModal');

    if (!profileTrigger) return;

    // ── Load profile from DB ──────────────────────────────────
    async function loadProfile() {
        try {
            const res  = await fetch('../adminBack_end/adminProfile.php');
            const data = await res.json();

            if (!data.success || !data.admin) return;

            const a = data.admin;

            // Header name + position
            const nameDisplay = document.getElementById('profileNameDisplay');
            if (nameDisplay) nameDisplay.textContent = a.admin_name || '';

            const posDisplay = document.getElementById('profilePositionDisplay');
            if (posDisplay) posDisplay.textContent = formatPosition(a.position || '');

            // Store position globally so other scripts can read it
            window.adminPosition = a.position || '';

            // Apply sidebar visibility based on role (belt-and-suspenders, server already guards)
            applySidebarAccessControl(window.adminPosition);

            // Modal fields (pre-fill)
            const fullNameInput = document.getElementById('fullName');
            if (fullNameInput) fullNameInput.value = a.admin_name || '';

            const emailInput = document.getElementById('profileEmail');
            if (emailInput) emailInput.value = a.email || '';

            const positionInput = document.getElementById('profilePosition');
            if (positionInput) {
                positionInput.value       = a.position || '';
                positionInput.disabled    = true; // position is read-only for self
                positionInput.title       = 'Position can only be changed by the Head admin';
            }

        } catch (err) {
            console.error('Failed to load profile:', err);
        }
    }

    loadProfile();

    // ── Dropdown toggle ───────────────────────────────────────
    profileTrigger.addEventListener('click', e => {
        e.stopPropagation();
        profileDropdown?.classList.toggle('show');
    });

    document.addEventListener('click', () => profileDropdown?.classList.remove('show'));

    editProfileBtn?.addEventListener('click', () => {
        editProfileModal?.classList.add('show');
        profileDropdown?.classList.remove('show');
    });

    logoutBtn?.addEventListener('click', () => {
        logoutModal?.classList.add('show');
        profileDropdown?.classList.remove('show');
    });

    // ── Close modals ──────────────────────────────────────────
    ['closeEditModal', 'cancelEditModal'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            editProfileModal?.classList.remove('show');
            clearPasswordFields();
        });
    });

    ['closeLogoutModal', 'cancelLogout'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            logoutModal?.classList.remove('show');
        });
    });

    document.getElementById('confirmLogout')?.addEventListener('click', () => {
        window.location.href = 'signOut.php';
    });

    // ── Save profile ──────────────────────────────────────────
    document.getElementById('saveProfile')?.addEventListener('click', async () => {
        const fullName        = document.getElementById('fullName')?.value.trim()        || '';
        const email           = document.getElementById('profileEmail')?.value.trim()    || '';
        const currentPassword = document.getElementById('currentPassword')?.value        || '';
        const newPassword     = document.getElementById('newPassword')?.value            || '';
        const confirmPassword = document.getElementById('confirmPassword')?.value        || '';

        // Client-side validation
        if (!fullName) {
            showProfileToast('Name cannot be empty', 'error');
            return;
        }

        if (newPassword) {
            if (!currentPassword) {
                showProfileToast('Enter your current password to change it', 'error');
                return;
            }
            if (newPassword.length < 8) {
                showProfileToast('New password must be at least 8 characters', 'error');
                return;
            }
            if (newPassword !== confirmPassword) {
                showProfileToast('Passwords do not match', 'error');
                return;
            }
        }

        const payload = { admin_name: fullName };
        if (email)           payload.email            = email;
        if (newPassword)     payload.password         = newPassword;
        if (currentPassword) payload.current_password = currentPassword;

        try {
            const res  = await fetch('../adminBack_end/adminProfile.php', {
                method  : 'POST',
                headers : { 'Content-Type': 'application/json' },
                body    : JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                showProfileToast('Profile updated successfully!', 'success');

                // Update header immediately with fresh DB data
                if (data.admin) {
                    const nameDisplay = document.getElementById('profileNameDisplay');
                    if (nameDisplay) nameDisplay.textContent = data.admin.admin_name || '';

                    const posDisplay = document.getElementById('profilePositionDisplay');
                    if (posDisplay) posDisplay.textContent = formatPosition(data.admin.position || '');
                }

                clearPasswordFields();
                editProfileModal?.classList.remove('show');

            } else {
                showProfileToast(data.error || 'Update failed', 'error');
            }

        } catch (err) {
            console.error(err);
            showProfileToast('Something went wrong', 'error');
        }
    });

    // ── Profile image (local only — no DB column for it) ─────
    document.getElementById('profileImageInput')?.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => {
            const data = ev.target.result;
            localStorage.setItem('profileImage', data);
            applyProfileImage(data);
        };
        reader.readAsDataURL(file);
    });

    const savedImage = localStorage.getItem('profileImage');
    if (savedImage) applyProfileImage(savedImage);
}

function applyProfileImage(src) {
    const avatarImg    = document.getElementById('epAvatarImg');
    const headerAvatar = document.querySelector('.avatar');
    if (avatarImg)    { avatarImg.src = src; avatarImg.style.display = 'block'; }
    if (headerAvatar)   headerAvatar.src = src;
}

function clearPasswordFields() {
    ['currentPassword', 'newPassword', 'confirmPassword'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
}

// Converts DB enum value to a human-readable label

// ------------------------------------------------------------------
// applySidebarAccessControl(position)
//   Hides sidebar links the current admin role cannot access.
//   The server already blocks unauthorized pages, but this also
//   removes the links from the UI cleanly.
// ------------------------------------------------------------------
function applySidebarAccessControl(position) {
    const ACCESS_MAP = {
        'head': null,                           // null = show everything
        'inventory_orderAdmin': ['dashboard', 'product', 'orders', 'notifications'],
        'message_feedbackAdmin': ['dashboard', 'messages', 'feedback', 'notifications'],
    };

    const allowed = ACCESS_MAP[position];
    if (!allowed) return;  // head admin sees all

    // Each <a> in the sidebar has an href we can match
    const PAGE_SLUGS = {
        'dashboard.php':     'dashboard',
        'product.php':       'product',
        'orders.php':        'orders',
        'users.php':         'users',
        'messages.php':      'messages',
        'feedback.php':      'feedback',
        'report.php':        'report',
        'notifications.php': 'notifications',
        'settings.php':      'settings',
    };

    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        const href = (link.getAttribute('href') || '').split('?')[0].split('/').pop();
        const slug = PAGE_SLUGS[href];
        if (slug && !allowed.includes(slug)) {
            const li = link.closest('li');
            if (li) li.style.display = 'none';
        }
    });
}

function formatPosition(pos) {
    const map = {
        'head'                    : 'Head Admin',
        'inventory_orderAdmin'    : 'Inventory & Orders Admin',
        'message_feedbackAdmin'   : 'Messages & Feedback Admin'
    };
    return map[pos] || pos;
}

// Lightweight toast for profile actions (falls back to alert if element missing)
function showProfileToast(message, type = 'success') {
    const toast     = document.getElementById('toast');
    const toastMsg  = document.getElementById('toastMessage');

    if (toast && toastMsg) {
        toastMsg.textContent = message;
        toast.className = 'toast' + (type === 'error' ? ' error' : '');
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    } else {
        // fallback
        alert(message);
    }
}

// ============================================================
// SIDEBAR TOGGLE
// ============================================================
function initSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.querySelector('.sidebar');
    const main          = document.querySelector('.main');
    if (!sidebarToggle) return;

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        main.classList.toggle('expanded');
    });
}

// ============================================================
// ACTIVE NAV LINK
// ============================================================
function setActiveNavLink() {
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkPath = link.getAttribute('href');
        if (currentPath.endsWith(linkPath)) link.classList.add('active');
    });
}

// ============================================================
// GLOBAL SEARCH
// ============================================================
const SEARCH_API_URL = '../adminBack_end/searchAPI.php';

const typeIcons = {
    Order  : 'fa-shopping-cart',
    Product: 'fa-box',
    User   : 'fa-user',
};
const typeColors = {
    Order  : '#dbeafe',
    Product: '#dcfce7',
    User   : '#fce7f3',
};

function initSearchBar() {
    const searchInput = document.querySelector('.search-input');
    const searchBar   = document.querySelector('.search-bar');
    if (!searchInput || !searchBar) return;

    let searchDebounceTimer    = null;
    let activeSearchController = null;

    const dropdown = document.createElement('div');
    dropdown.className = 'search-dropdown';
    searchBar.appendChild(dropdown);

    function renderDropdown(query, results) {
        dropdown.innerHTML = '';
        if (!query) { dropdown.classList.remove('show'); return; }

        if (!results.length) {
            dropdown.innerHTML = `<div class="search-no-results">No results for "${query}"</div>`;
            dropdown.classList.add('show');
            return;
        }

        const grouped = results.reduce((acc, item) => {
            if (!acc[item.type]) acc[item.type] = [];
            acc[item.type].push(item);
            return acc;
        }, {});

        Object.entries(grouped).forEach(([type, items]) => {
            const group = document.createElement('div');
            group.className = 'search-group';
            group.innerHTML = `<div class="search-group-label">${type}s</div>`;

            items.forEach(item => {
                const el = document.createElement('div');
                el.className = 'search-result-item';
                el.innerHTML = `
                    <div class="search-result-dot" style="background:${typeColors[type] || '#e5e7eb'};"></div>
                    <div class="search-result-text">
                        <div class="search-result-label">${item.label}</div>
                        <div class="search-result-sublabel">${item.sublabel}</div>
                    </div>`;
                el.addEventListener('click', () => { window.location.href = item.href; });
                group.appendChild(el);
            });

            dropdown.appendChild(group);
        });

        dropdown.classList.add('show');
    }

    function showSearchingState(query) {
        dropdown.innerHTML = `<div class="search-no-results">Searching for "${query}"...</div>`;
        dropdown.classList.add('show');
    }

    async function runSearch(query) {
        if (!query) { renderDropdown('', []); return; }
        if (activeSearchController) activeSearchController.abort();
        activeSearchController = new AbortController();
        showSearchingState(query);
        try {
            const response = await fetch(`${SEARCH_API_URL}?q=${encodeURIComponent(query)}&_=${Date.now()}`, {
                cache : 'no-store',
                signal: activeSearchController.signal
            });
            const json    = await response.json();
            const results = Array.isArray(json.results) ? json.results : [];
            renderDropdown(query, results);
        } catch (error) {
            if (error.name === 'AbortError') return;
            dropdown.innerHTML = `<div class="search-no-results">Search failed. Please try again.</div>`;
            dropdown.classList.add('show');
        }
    }

    searchInput.addEventListener('input', e => {
        const query = e.target.value.trim();
        clearTimeout(searchDebounceTimer);
        if (!query) { renderDropdown('', []); return; }
        searchDebounceTimer = setTimeout(() => runSearch(query), 250);
    });

    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Escape') { searchInput.value = ''; dropdown.classList.remove('show'); }
    });

    document.addEventListener('click', e => {
        if (!searchBar.contains(e.target)) dropdown.classList.remove('show');
    });
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    initProfileFeatures();
    initSidebarToggle();
    setActiveNavLink();
    initSearchBar();

    if (document.getElementById('notificationsList')) initNotificationsPage();
});

// ============================================================
// NOTIFICATIONS  (unchanged from original — kept intact below)
// ============================================================

const NOTIFICATIONS_API_URL = '../adminBack_end/notificationsAPI.php';
let pageNotifications          = [];
let activeFilter               = 'all';
let notificationsCurrentPage   = 1;
const pageSize                 = 10;

const FILTER_LABELS = {
    all     : 'All',
    order   : 'Orders',
    payment : 'Payments',
    message : 'Messages',
    feedback: 'Feedback',
    product : 'Products',
    stock   : 'Low stock',
    cancel  : 'Cancellations',
    return  : 'Returns',
    status  : 'Status updates'
};

async function pageLoadNotifications() {
    try {
        const response = await fetch(`${NOTIFICATIONS_API_URL}?limit=200&_=${Date.now()}`, { cache: 'no-store' });
        const json     = await response.json();
        pageNotifications = (json.notifications || []).map(item => ({
            ...item,
            timestamp: item.timestamp ? new Date(item.timestamp) : new Date()
        }));
    } catch { pageNotifications = []; }
}

function pageSaveNotifications() {}

function syncHeaderNotifications() {
    if (typeof window.loadNotifications === 'function') window.loadNotifications();
}

function getFilteredNotifications() {
    return activeFilter === 'all'
        ? pageNotifications
        : pageNotifications.filter(n => n.type === activeFilter);
}

function getTotalPages(totalItems) { return Math.max(1, Math.ceil(totalItems / pageSize)); }

function getPagedNotifications(items) {
    const totalPages = getTotalPages(items.length);
    if (notificationsCurrentPage > totalPages) notificationsCurrentPage = totalPages;
    if (notificationsCurrentPage < 1)          notificationsCurrentPage = 1;
    const start = (notificationsCurrentPage - 1) * pageSize;
    return items.slice(start, start + pageSize);
}

function renderPagination(totalItems) {
    const paginationBar = document.getElementById('paginationBar');
    if (!paginationBar) return;
    paginationBar.innerHTML = '';
    if (!totalItems) { paginationBar.style.display = 'none'; return; }
    paginationBar.style.display = 'flex';
    const totalPages  = getTotalPages(totalItems);
    const createBtn   = (text, page, disabled = false) => {
        const btn     = document.createElement('button');
        btn.className = 'pagination-btn';
        btn.innerHTML = text;
        btn.disabled  = disabled;
        if (!disabled) btn.onclick = () => { notificationsCurrentPage = page; renderNotificationPage(); };
        if (page === notificationsCurrentPage) btn.classList.add('active');
        return btn;
    };
    paginationBar.appendChild(createBtn('<', notificationsCurrentPage - 1, notificationsCurrentPage === 1));
    const maxVisible = 5;
    let startPage    = Math.max(1, notificationsCurrentPage - Math.floor(maxVisible / 2));
    let endPage      = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) startPage = Math.max(1, endPage - maxVisible + 1);
    for (let i = startPage; i <= endPage; i++) paginationBar.appendChild(createBtn(i, i));
    paginationBar.appendChild(createBtn('>', notificationsCurrentPage + 1, notificationsCurrentPage === totalPages));
}

function updateUnreadSummary() {
    const unreadCount = pageNotifications.filter(n => !n.read).length;
    const summary     = document.getElementById('unreadSummary');
    if (!summary) return;
    summary.textContent = unreadCount === 0 ? 'All caught up' : `${unreadCount} unread`;
}

function formatRelativeTime(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 60)    return 'Just now';
    if (seconds < 3600)  return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

function renderNotificationPage() {
    const list = document.getElementById('notificationsList');
    if (!list)  return;
    const filteredItems = getFilteredNotifications();
    const items         = getPagedNotifications(filteredItems);
    if (!filteredItems.length) {
        const label = FILTER_LABELS[activeFilter] || 'Notifications';
        list.innerHTML = `<li class="empty-state">No ${label.toLowerCase()} found.</li>`;
        renderPagination(0);
        return;
    }
    list.innerHTML = items.map(item => {
        const readClass = item.read ? 'read' : 'unread';
        const type      = item.type || 'general';
        const typeLabel = FILTER_LABELS[type] || 'General';
        return `
            <li class="notification-row ${readClass}" data-id="${item.id}" data-type="${type}">
                <span class="unread-dot" aria-hidden="true"></span>
                <div class="notification-content">
                    <p class="notification-title">${item.title}</p>
                    <p class="notification-message">${item.message}</p>
                    <p class="notification-meta">
                        <span class="notification-type-pill ${type}">${typeLabel}</span>
                        <span class="notification-time-text">${formatRelativeTime(item.timestamp)}</span>
                    </p>
                </div>
            </li>`;
    }).join('');
    renderPagination(filteredItems.length);
}

async function markNotificationAsRead(id) {
    const notification = pageNotifications.find(item => item.id === id);
    if (!notification || notification.read) return;
    try {
        await fetch(NOTIFICATIONS_API_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ action: 'mark_read', id })
        });
        notification.read = true;
    } catch (error) { console.error('Failed to mark notification as read:', error); }
    syncHeaderNotifications();
    updateUnreadSummary();
    renderNotificationPage();
}

function getNotificationTargetUrl(notification) {
    if (!notification?.type) return '';
    const customUrl  = notification.data?.url;
    if (customUrl)   return customUrl;
    const orderId    = normalizeOrderId((notification.data?.orderId) || extractOrderIdFromText(notification.message));
    const productId  = notification.data?.productId || extractProductIdFromText(notification.message);
    switch (notification.type) {
        case 'order'   : return `orders.php?source=notification&orderId=${encodeURIComponent(orderId || '')}`;
        case 'payment' : return `orders.php?source=notification&tab=payments&orderId=${encodeURIComponent(orderId || '')}`;
        case 'message' : return `messages.php?source=notification&category=inbox&conversationId=${encodeURIComponent(notification.data?.senderId || '')}`;
        case 'feedback': return `feedback.php?source=notification&feedbackId=${encodeURIComponent(notification.data?.feedbackId || '')}`;
        case 'product' : return `product.php?source=notification&productId=${encodeURIComponent(productId || '')}`;
        case 'cancel'  : return `orders.php?source=notification&orderId=${encodeURIComponent(orderId || '')}`;
        case 'status'  : return `orders.php?source=notification&tab=status&orderId=${encodeURIComponent(orderId || '')}`;
        case 'return'  : return `orders.php?source=notification&tab=returns&orderId=${encodeURIComponent(orderId || '')}`;
        case 'stock'   : return `product.php?source=notification&tab=inventory&productId=${encodeURIComponent(productId || '')}`;
        default        : return 'dashboard.php';
    }
}

function extractOrderIdFromText(text) {
    if (!text) return '';
    const match = text.match(/#([A-Za-z]+-\d+)/);
    return match ? match[1] : '';
}

function extractProductIdFromText(text) {
    if (!text) return '';
    const match = text.match(/\b(\d{1,3})\b/);
    return match ? match[1] : '';
}

function normalizeOrderId(orderId) {
    if (!orderId) return '';
    const upper = String(orderId).toUpperCase();
    if (upper.startsWith('ORD-')) return upper;
    const m = upper.match(/(\d+)/);
    if (!m) return upper;
    return `ORD-${m[1].padStart(3, '0')}`;
}

async function openNotificationTarget(id) {
    const notification = pageNotifications.find(item => item.id === id);
    if (!notification) return;
    if (!notification.read) await markNotificationAsRead(id);
    const targetUrl = getNotificationTargetUrl(notification);
    if (targetUrl) window.location.href = targetUrl;
}

async function markAllAsRead() {
    try {
        await fetch(NOTIFICATIONS_API_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ action: 'mark_all_read' })
        });
    } catch (error) { console.error('Failed to mark all notifications as read:', error); }
    pageNotifications.forEach(item => { item.read = true; });
    syncHeaderNotifications();
    updateUnreadSummary();
    renderNotificationPage();
}

function bindNotificationEvents() {
    const filterPills    = document.getElementById('filterPills');
    const list           = document.getElementById('notificationsList');
    const markAllReadBtn = document.getElementById('markAllReadBtnPage');

    filterPills?.addEventListener('click', event => {
        const pill = event.target.closest('.filter-pill');
        if (!pill) return;
        activeFilter = pill.dataset.filter || 'all';
        notificationsCurrentPage = 1;
        document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.toggle('active', btn === pill));
        renderNotificationPage();
    });

    list?.addEventListener('click', event => {
        const row = event.target.closest('.notification-row');
        if (!row) return;
        openNotificationTarget(Number(row.dataset.id));
    });

    markAllReadBtn?.addEventListener('click', markAllAsRead);
}

async function initNotificationsPage() {
    await pageLoadNotifications();
    bindNotificationEvents();
    initProfileFeatures();
    updateUnreadSummary();
    renderNotificationPage();
}

// ── Header notifications ──────────────────────────────────────
let notifications            = [];
let showAllNotifications     = false;
let notificationsInitialized = false;
let notificationsPollTimer   = null;

function initNotifications() {
    if (notificationsInitialized) return;
    notificationsInitialized = true;

    const notificationTrigger  = document.getElementById('notificationTrigger');
    if (!notificationTrigger)  return;

    const notificationDropdown = document.getElementById('notificationDropdown') || document.querySelector('.notification-dropdown');
    const notificationList     = document.getElementById('notificationList')     || document.querySelector('.notification-list');
    const markAllReadBtn       = document.querySelector('.mark-all-read');
    const viewAllBtn           = document.getElementById('viewAllNotificationsBtn') || document.querySelector('.view-all-notifications');

    if (!notificationDropdown || !notificationList) return;

    notificationDropdown.style.zIndex = '1100';

    const toggleNotificationDropdown = e => {
        e.stopPropagation();
        const isShown = notificationDropdown.classList.contains('show');
        notificationDropdown.classList.toggle('show');
        if (!isShown) {
            showAllNotifications = false;
            notificationList.classList.remove('expanded');
            notificationDropdown.classList.remove('expanded');
            notificationList.style.maxHeight = '';
            renderNotifications();
        }
        document.getElementById('profileDropdown')?.classList.remove('show');
    };

    notificationTrigger.addEventListener('click', toggleNotificationDropdown);
    notificationDropdown.addEventListener('click', e => e.stopPropagation());

    document.addEventListener('click', e => {
        if (!notificationTrigger.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('show');
            showAllNotifications = false;
            notificationList.classList.remove('expanded');
            notificationDropdown.classList.remove('expanded');
            notificationList.style.maxHeight = '';
        }
    });

    markAllReadBtn?.addEventListener('click', e => {
        e.stopPropagation();
        fetch(NOTIFICATIONS_API_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ action: 'mark_all_read' })
        })
        .then(() => loadNotifications())
        .catch(error => console.error('Failed to mark all as read:', error));
    });

    if (viewAllBtn) viewAllBtn.onclick = e => { e.preventDefault(); e.stopPropagation(); viewAllNotificationsHandler(e); };

    loadNotifications();
}

document.addEventListener('click', e => {
    const viewAllButton = e.target.closest('.view-all-notifications');
    if (!viewAllButton) return;
    e.preventDefault(); e.stopPropagation();
    viewAllNotificationsHandler(e);
});

function viewAllNotificationsHandler(event) {
    if (event) { event.preventDefault(); event.stopPropagation(); }
    const currentPath  = window.location.pathname || '';
    const adminMarker  = '/admin/';
    const markerIndex  = currentPath.toLowerCase().indexOf(adminMarker);
    if (markerIndex !== -1) {
        window.location.href = `${currentPath.slice(0, markerIndex + adminMarker.length)}notifications.php`;
        return;
    }
    window.location.href = 'admin/notifications.php';
}

window.viewAllNotificationsHandler = viewAllNotificationsHandler;

function addNotification(type, title, message, data = {}) {
    fetch(NOTIFICATIONS_API_URL, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ action: 'create', type, title, message, data })
    })
    .then(() => loadNotifications())
    .catch(error => console.error('Failed to create notification:', error));
}

function updateNotificationBadge() {
    const badge      = document.getElementById('notificationBadge');
    if (!badge)      return;
    const unreadCount = notifications.filter(n => !n.read).length;
    if (unreadCount > 0) {
        badge.textContent    = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display  = 'flex';
    } else {
        badge.style.display  = 'none';
    }
}

function renderNotifications() {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    const notificationsToShow = notifications.slice(0, 10);
    if (!notificationsToShow.length) {
        notificationList.innerHTML = `
            <div class="notification-item">
                <div style="text-align:center;padding:40px 20px;color:var(--text-muted);">
                    <i class="fas fa-bell-slash" style="font-size:24px;margin-bottom:8px;"></i>
                    <p>No notifications yet</p>
                </div>
            </div>`;
        return;
    }
    notificationList.innerHTML = notificationsToShow.map(notification => {
        const timeAgo   = getTimeAgo(notification.timestamp);
        const iconClass = getNotificationIconClass(notification.type);
        const unreadCls = notification.read ? '' : 'unread';
        return `
            <div class="notification-item ${unreadCls}" onclick="handleNotificationClick(${notification.id})">
                <div class="notification-item-content">
                    <div class="notification-icon ${iconClass}">
                        <i class="fas ${getNotificationIcon(notification.type)}"></i>
                    </div>
                    <div class="notification-text">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                </div>
            </div>`;
    }).join('');
}

function handleNotificationClick(notificationId) {
    const notification = markAsRead(notificationId);
    if (!notification) return;
    const targetUrl = getNotificationTargetUrl(notification);
    if (targetUrl) window.location.href = targetUrl;
}

function markAsRead(notificationId) {
    const notification = notifications.find(n => n.id === notificationId);
    if (notification) {
        notification.read = true;
        fetch(NOTIFICATIONS_API_URL, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : JSON.stringify({ action: 'mark_read', id: notificationId })
        })
        .then(() => { updateNotificationBadge(); renderNotifications(); })
        .catch(error => console.error('Failed to mark notification as read:', error));
    }
    return notification || null;
}

function getNotificationIconClass(type) {
    const classes = { order:'order', payment:'payment', message:'status', feedback:'status', product:'order', stock:'stock', cancel:'cancel', status:'status', return:'return' };
    return classes[type] || 'order';
}

function getNotificationIcon(type) {
    const icons = { order:'fa-shopping-cart', payment:'fa-credit-card', message:'fa-envelope', feedback:'fa-star', product:'fa-box', stock:'fa-exclamation-triangle', cancel:'fa-times-circle', status:'fa-truck', return:'fa-undo' };
    return icons[type] || 'fa-bell';
}

function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 60)    return 'Just now';
    if (seconds < 3600)  return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

function loadNotifications() {
    fetch(`${NOTIFICATIONS_API_URL}?limit=100&_=${Date.now()}`, { cache: 'no-store' })
        .then(res  => res.json())
        .then(json => {
            notifications = (json.notifications || []).map(item => ({
                ...item,
                timestamp: item.timestamp ? new Date(item.timestamp) : new Date()
            }));
            updateNotificationBadge();
            renderNotifications();
            showAllNotifications = false;
        })
        .catch(error => {
            console.error('Failed to load notifications:', error);
            notifications = [];
            updateNotificationBadge();
            renderNotifications();
        });
}

document.addEventListener('DOMContentLoaded', () => {
    initNotifications();
    if (!notificationsPollTimer) notificationsPollTimer = setInterval(loadNotifications, 15000);
});