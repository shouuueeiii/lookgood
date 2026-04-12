let currentUsersPage = 1;
const itemsPerPage = 10;
let userToDeleteIndex = null;
let userToBanIndex = null;

const mockData = {
    users: []
};

// Add these API functions
async function apiBanUser(userId, reason) {
    const response = await fetch('../adminBack_end/usersAPI.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId, reason: reason })
    });
    if (!response.ok) throw new Error('Failed to ban user');
    return response.json();
}

async function apiDeleteUser(userId) {
    const response = await fetch('../adminBack_end/usersAPI.php', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
    });
    if (!response.ok) throw new Error('Failed to delete user');
    return response.json();
}

async function fetchUsers() {
    try {
        const res = await fetch('../adminBack_end/usersAPI.php'); 
        if (!res.ok) throw new Error('Failed to fetch users');
        const data = await res.json();
        mockData.users = Array.isArray(data)
            ? data.map((u) => ({
                ...u,
                id: Number(u.id ?? u.user_id ?? 0),
                user_id: Number(u.user_id ?? u.id ?? 0),
                name: u.name || u.email || 'Unknown User',
                email: u.email || '',
                number: u.number || u.phone || 'N/A',
                status: String(u.status || 'Active').replace(/^./, (s) => s.toUpperCase())
            }))
            : [];
        renderUsers();
        updateUserStats();
    } catch (err) {
        console.error('Error fetching users:', err);
        const tableBody = document.querySelector('#usersTable tbody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Error loading users. Please try again.</td></tr>';
        }
    }
}

function openDeleteUserModal(index) {
    userToDeleteIndex = index;
    const user = mockData.users[index];
    if (user) {
        const nameElement = document.getElementById('deleteUserName');
        if (nameElement) nameElement.textContent = user.name || user.email;
        const modal = document.getElementById('deleteUserModal');
        if (modal) modal.classList.add('show');
    }
}

function openBanUserModal(index) {
    userToBanIndex = index;
    const user = mockData.users[index];
    if (user) {
        const nameElement = document.getElementById('banUserName');
        if (nameElement) nameElement.textContent = user.name || user.email;
        const modal = document.getElementById('banUserModal');
        if (modal) modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('show');
    if (modalId === 'deleteUserModal') userToDeleteIndex = null;
    if (modalId === 'banUserModal') userToBanIndex = null;
}

async function confirmBan() {
    if (userToBanIndex !== null && mockData.users[userToBanIndex]) {
        const user = mockData.users[userToBanIndex];
        try {
            await apiBanUser(user.user_id, 'Banned');
            user.status = 'Banned'; // update local copy
            closeModal('banUserModal');
            renderUsers();
            updateUserStats();
            showNotification('User banned successfully', 'success');
        } catch (err) {
            console.error('Error banning user:', err);
            showNotification('Failed to ban user', 'error');
        }
    }
}

async function confirmDelete() {
    
    if (userToDeleteIndex !== null && mockData.users[userToDeleteIndex]) {
        const user = mockData.users[userToDeleteIndex];
        try {
            await apiDeleteUser(user.user_id);
            mockData.users.splice(userToDeleteIndex, 1); // remove from local copy
            closeModal('deleteUserModal');
            
            // Adjust current page if needed
            const totalItems = mockData.users.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            if (currentUsersPage > totalPages && totalPages > 0) {
                currentUsersPage = totalPages;
            } else if (totalPages === 0) {
                currentUsersPage = 1;
            }
            
            renderUsers();
            updateUserStats();
            showNotification('User deleted successfully', 'success');
        } catch (err) {
            console.error('Error deleting user:', err);
            showNotification('Failed to delete user', 'error');
        }
    }
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'Active':   return 'badge-success';
        case 'Inactive': return 'badge-warning';
        case 'Banned':   return 'badge-danger';
        default:         return 'badge-secondary';
    }
}

function updateUserStats() {
    const users = mockData.users;
    const totalCustomersEl = document.getElementById('totalCustomers');
    const suspendedUsersEl = document.getElementById('suspendedUsers');
    
    if (totalCustomersEl) totalCustomersEl.textContent = users.length;
    if (suspendedUsersEl) {
        suspendedUsersEl.textContent = users.filter(u => u.status === 'Banned' || u.status === 'Inactive').length;
    }
}

function renderUsers() {
    const tableBody = document.querySelector('#usersTable tbody');
    if (!tableBody) return;

    const searchInput = document.getElementById('userSearchInput');
    const statusFilter = document.getElementById('userStatusFilter');
    
    const search = searchInput ? searchInput.value.toLowerCase() : '';
    const status = statusFilter ? statusFilter.value : '';

    const filtered = mockData.users.filter(u => {
        const matchesSearch = (u.name && u.name.toLowerCase().includes(search)) ||
                            (u.email && u.email.toLowerCase().includes(search));
        const matchesStatus = !status || u.status === status;
        return matchesSearch && matchesStatus;
    });

    const totalPages = Math.ceil(filtered.length / itemsPerPage);
    if (currentUsersPage > totalPages && totalPages > 0) {
        currentUsersPage = totalPages;
    } else if (totalPages === 0) {
        currentUsersPage = 1;
    }

    const startIndex = (currentUsersPage - 1) * itemsPerPage;
    const paginatedData = filtered.slice(startIndex, startIndex + itemsPerPage);

    if (paginatedData.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No users found</td></tr>';
        renderPagination('usersPagination', currentUsersPage, totalPages);
        return;
    }

    tableBody.innerHTML = paginatedData.map((u, index) => {
        const userIndex = mockData.users.findIndex(user => Number(user.user_id) === Number(u.user_id));
        return `
            <tr>
                <td><strong>${escapeHtml(u.name || 'N/A')}</strong></td>
                <td>${escapeHtml(u.email || 'N/A')}</td>
                <td>${escapeHtml(u.number || 'N/A')}</td>
                <td><span class="badge ${getStatusBadgeClass(u.status)}">${u.status || 'Unknown'}</span></td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        ${u.status !== 'Banned' ? `
                        <button class="btn btn-danger btn-sm" onclick="openBanUserModal(${userIndex})">
                            <i class="fas fa-ban"></i>
                        </button>` : ''}
                        <button class="btn btn-secondary btn-sm" style="color: var(--danger);"
                            onclick="openDeleteUserModal(${userIndex})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    renderPagination('usersPagination', currentUsersPage, totalPages);
}

// Add changePage function
function changePage(page) {
    currentUsersPage = page;
    renderUsers();
}

// Add escapeHtml to prevent XSS attacks
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderPagination(containerId, currentPage, totalPages) {
    const container = document.getElementById(containerId);
    if (!container || totalPages <= 1) {
        if (container) container.innerHTML = '';
        return;
    }

    let html = `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''}
        onclick="changePage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;

    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="changePage(1)">1</button>`;
        if (startPage > 2) html += `<span class="pagination-dots">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}"
            onclick="changePage(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="pagination-dots">...</span>`;
        html += `<button class="pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
    }

    html += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''}
        onclick="changePage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
    container.innerHTML = html;
}

// Add notification function
function showNotification(message, type = 'info') {
    // You can implement this based on your notification system
    console.log(`${type.toUpperCase()}: ${message}`);
    // Example using alert (replace with your preferred notification method)
    // alert(message);
}

// Add initNotifications function
function initNotifications() {
    // Initialize any notification system you're using
    console.log('Notifications initialized');
}

// Add refresh function for manual refresh
function refreshUsers() {
    currentUsersPage = 1;
    fetchUsers();
}

document.addEventListener('DOMContentLoaded', () => {
    fetchUsers();

    const searchInput = document.getElementById('userSearchInput');
    const statusFilter = document.getElementById('userStatusFilter');
    const clearFilters = document.getElementById('clearUserFilters');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentUsersPage = 1;
            renderUsers();
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            currentUsersPage = 1;
            renderUsers();
        });
    }
    
    if (clearFilters) {
        clearFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            currentUsersPage = 1;
            renderUsers();
        });
    }

    initNotifications();
});