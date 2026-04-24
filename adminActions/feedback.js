const FEEDBACK_API_URL = '../adminBack_end/feedbackAPI.php';
let feedbacks = [];

// Reply templates
const replyTemplates = {
    5: "Thank you so much for your wonderful 5-star review! We're thrilled to hear you're satisfied with your purchase. Your feedback means a lot to us!",
    4: "Thank you for your positive feedback! We're glad you enjoyed your product. If there's anything we can do to make your next experience even better, please let us know.",
    3: "Thank you for your feedback. We appreciate your honest review and will continue working to improve our products and services.",
    2: "We're sorry to hear that our product didn't fully meet your expectations. We'd like to learn more about your experience. Please contact our support team so we can make this right.",
    1: "We sincerely apologize for your disappointing experience. This is not the standard we strive for. Please reach out to our customer support team immediately so we can resolve this issue for you."
};

// Global vars
let feedbackCurrentPage = 1;
const itemsPerPage = 6;
let selectedFeedbackId = null;
let filteredFeedbacks = [];

// DOM elements
const feedbackGrid = document.getElementById("feedbackGrid");
const searchInput = document.getElementById("feedbackSearchInput");
const ratingFilter = document.getElementById("ratingFilter");
const statusFilter = document.getElementById("statusFilter");
const dateFromFilter = document.getElementById("dateFromFilter");
const dateToFilter = document.getElementById("dateToFilter");
const paginationContainer = document.getElementById("feedbackPagination");
const totalFeedbackEl = document.getElementById("totalFeedback");
const avgRatingEl = document.getElementById("avgRating");
const positiveCountEl = document.getElementById("positiveCount");
const repliedCountEl = document.getElementById("repliedCount");
const feedbackModal = document.getElementById("feedbackModal");
const adminReplyInput = document.getElementById("adminReplyInput");
const sendReplyBtn = document.getElementById("sendReplyBtn");
const closeFeedbackModalBtn = document.getElementById("closeFeedbackModalBtn");
const useTemplateBtn = document.getElementById("useTemplateBtn");

// Utility functions
function renderStars(rating) {
    let stars = "";
    for (let i = 1; i <= 5; i++) {
        stars += i <= rating 
            ? `<i class="fas fa-star"></i>` 
            : `<i class="far fa-star"></i>`;
    }
    return `<div class="star-rating">${stars}</div>`;
}

function getInitials(name) {
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) {
        return "Unknown date";
    }
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Filter feedbacks
function filterFeedbacks() {
    const searchText = searchInput.value.toLowerCase();
    const selectedRating = ratingFilter.value;
    const selectedStatus = statusFilter.value;
    const selectedFromDate = dateFromFilter.value;
    const selectedToDate = dateToFilter.value;

    filteredFeedbacks = feedbacks.filter(f => {
        const customer = String(f.customer || '').toLowerCase();
        const product = String(f.product || '').toLowerCase();
        const comment = String(f.comment || '').toLowerCase();
        const matchesSearch = customer.includes(searchText) || product.includes(searchText) || comment.includes(searchText);
        const matchesRating = !selectedRating || f.rating == selectedRating;
        const matchesStatus = !selectedStatus || 
                            (selectedStatus === 'replied' && f.reply) ||
                            (selectedStatus === 'pending' && !f.reply);
        const matchesFromDate = !selectedFromDate || f.date >= selectedFromDate;
        const matchesToDate = !selectedToDate || f.date <= selectedToDate;
        return matchesSearch && matchesRating && matchesStatus && matchesFromDate && matchesToDate;
    });

    feedbackCurrentPage = 1;
    renderFeedbackGrid();
    updateStats();
}

// Render feedback grid
function renderFeedbackGrid() {
    if (!feedbackGrid || !paginationContainer) return;
    feedbackGrid.innerHTML = "";

    if (filteredFeedbacks.length === 0) {
        feedbackGrid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                <p style="font-size: 16px; font-weight: 500;">No feedback found</p>
                <p style="font-size: 14px;">Try adjusting your filters</p>
            </div>
        `;
        paginationContainer.innerHTML = "";
        return;
    }

    const totalPages = Math.ceil(filteredFeedbacks.length / itemsPerPage);
    if (feedbackCurrentPage > totalPages) feedbackCurrentPage = totalPages || 1;

    const startIndex = (feedbackCurrentPage - 1) * itemsPerPage;
    const paginatedFeedbacks = filteredFeedbacks.slice(startIndex, startIndex + itemsPerPage);

    paginatedFeedbacks.forEach(f => {
        const safeCustomer = String(f.customer || '').trim() || 'Customer';
        const safeProduct = String(f.product || '').trim() || 'Product';
        const safeComment = String(f.comment || '').trim() || 'No comment provided.';
        const safeRating = Math.max(1, Math.min(5, Number(f.rating) || 1));
        const safeDate = String(f.date || '');
        const feedbackItem = document.createElement("div");
        feedbackItem.className = "feedback-item";
        feedbackItem.dataset.feedbackId = String(f.id);
        feedbackItem.addEventListener('click', () => openFeedbackModal(f.id));
        
        const hasReply = f.reply && f.reply.trim() !== "";
        const statusClass = hasReply ? "replied" : "pending";
        const statusText = hasReply ? "Replied" : "Pending Reply";
        const statusIcon = hasReply ? "fa-check-circle" : "fa-clock";

        feedbackItem.innerHTML = `
            <div class="feedback-item-header">
                <div class="feedback-user">
                    <div class="feedback-avatar">${getInitials(safeCustomer)}</div>
                    <div class="feedback-user-info">
                        <h4>${escapeHtml(safeCustomer)}</h4>
                        <p>${formatDate(safeDate)}</p>
                    </div>
                </div>
                <div class="feedback-rating">${renderStars(safeRating)}</div>
            </div>
            <div class="feedback-product">
                <i class="fas fa-box"></i>
                ${escapeHtml(safeProduct)}
            </div>
            <div class="feedback-comment">${escapeHtml(safeComment)}</div>
            <div class="feedback-item-footer">
                <div class="feedback-status ${statusClass}">
                    <i class="fas ${statusIcon}"></i>
                    ${statusText}
                </div>
                <div class="feedback-actions">
                    <button class="action-btn" data-action="reply" title="Reply" type="button">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="action-btn" data-action="view" title="View Details" type="button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        `;

        const actions = feedbackItem.querySelector('.feedback-actions');
        if (actions) {
            actions.addEventListener('click', (event) => {
                event.stopPropagation();
                const actionBtn = event.target.closest('button.action-btn');
                if (!actionBtn) return;
                openFeedbackModal(f.id);
            });
        }
        
        feedbackGrid.appendChild(feedbackItem);
    });

    renderPagination(totalPages);
}

// Render pagination
function renderPagination(totalPages) {
    paginationContainer.innerHTML = "";
    if (totalPages <= 1) return;

    const createBtn = (text, page, disabled = false) => {
        const btn = document.createElement("button");
        btn.className = "pagination-btn";
        btn.innerHTML = text;
        btn.disabled = disabled;
        if (!disabled) {
            btn.onclick = () => {
                feedbackCurrentPage = page;
                renderFeedbackGrid();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };
        }
        if (page === feedbackCurrentPage) btn.classList.add("active");
        return btn;
    };

    paginationContainer.appendChild(createBtn("<", feedbackCurrentPage - 1, feedbackCurrentPage === 1));

    const maxVisible = 5;
    let startPage = Math.max(1, feedbackCurrentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationContainer.appendChild(createBtn(i, i));
    }

    paginationContainer.appendChild(createBtn(">", feedbackCurrentPage + 1, feedbackCurrentPage === totalPages));
}

// Update stats
function updateStats() {
    const total = feedbacks.length;
    totalFeedbackEl.textContent = total;

    const avgRating = total > 0
        ? (feedbacks.reduce((sum, f) => sum + f.rating, 0) / total).toFixed(1)
        : "0.0";
    avgRatingEl.textContent = avgRating;

    positiveCountEl.textContent = feedbacks.filter(f => f.rating >= 4).length;
    repliedCountEl.textContent = feedbacks.filter(f => f.reply && f.reply.trim() !== "").length;

    updateRatingDistribution();
}

// Update rating distribution
function updateRatingDistribution() {
    const ratingCounts = {1: 0, 2: 0, 3: 0, 4: 0, 5: 0};
    feedbacks.forEach(f => { ratingCounts[f.rating]++; });
    const total = feedbacks.length || 1;

    for (let rating = 1; rating <= 5; rating++) {
        const count = ratingCounts[rating];
        const percentage = (count / total) * 100;
        const barFill = document.getElementById(`rating-${rating}-bar`);
        const countEl = document.getElementById(`rating-${rating}-count`);
        if (barFill) barFill.style.width = `${percentage}%`;
        if (countEl) countEl.textContent = count;
    }

    document.querySelectorAll('.rating-bar-item').forEach(item => {
        item.onclick = () => {
            ratingFilter.value = item.dataset.rating;
            filterFeedbacks();
        };
    });
}

// Open feedback modal
function openFeedbackModal(id) {
    if (!feedbackModal) return;

    try {
        selectedFeedbackId = id;
        const feedback = feedbacks.find(f => f.id === id);
        if (!feedback) return;
        const safeCustomer = String(feedback.customer || '').trim() || 'Customer';
        const safeProduct = String(feedback.product || '').trim() || 'Product';
        const safeComment = String(feedback.comment || '').trim() || 'No comment provided.';
        const safeRating = Math.max(1, Math.min(5, Number(feedback.rating) || 1));

        const modalAvatar = document.getElementById("modalAvatar");
        const modalCustomer = document.getElementById("modalCustomer");
        const modalDate = document.getElementById("modalDate");
        const modalProduct = document.getElementById("modalProduct");
        const modalRating = document.getElementById("modalRating");
        const modalComment = document.getElementById("modalComment");
        const replySection = document.getElementById("replySection");

        if (modalAvatar) modalAvatar.textContent = getInitials(safeCustomer);
        if (modalCustomer) modalCustomer.textContent = safeCustomer;
        if (modalDate) modalDate.textContent = formatDate(feedback.date);
        if (modalProduct) modalProduct.textContent = safeProduct;
        if (modalRating) modalRating.innerHTML = renderStars(safeRating);
        if (modalComment) modalComment.textContent = safeComment;

        renderAdminReply(feedback);

        const hasReply = feedback.reply && feedback.reply.trim() !== "";
        if (replySection) replySection.style.display = hasReply ? "none" : "block";

        if (adminReplyInput) adminReplyInput.value = "";

        feedbackModal.classList.add("show");
    } catch (error) {
        console.error('Failed to open feedback modal:', error);
        showNotification('Unable to open this feedback right now.', 'error');
        feedbackModal.classList.remove("show");
    }
}

// Render admin reply
function renderAdminReply(feedback) {
    const container = document.getElementById("adminReplyContainer");
    if (!container) return;
    if (feedback.reply && feedback.reply.trim() !== "") {
        container.innerHTML = `
            <div class="admin-reply-box">
                <div class="admin-reply-label">
                    <i class="fas fa-reply"></i>
                    Your Reply
                </div>
                <div class="reply-text">${escapeHtml(feedback.reply)}</div>
            </div>
        `;
    } else {
        container.innerHTML = "";
    }
}

function refreshFeedbackCard(feedback) {
    if (!feedbackGrid || !feedback) return;

    const card = feedbackGrid.querySelector(`.feedback-item[data-feedback-id="${feedback.id}"]`);
    if (!card) return;

    const hasReply = feedback.reply && feedback.reply.trim() !== "";
    const status = card.querySelector('.feedback-status');
    if (status) {
        status.classList.remove('pending', 'replied');
        status.classList.add(hasReply ? 'replied' : 'pending');
        status.innerHTML = hasReply
            ? '<i class="fas fa-check-circle"></i> Replied'
            : '<i class="fas fa-clock"></i> Pending Reply';
    }
}

// Close feedback modal
function closeFeedbackModal() {
    if (feedbackModal) feedbackModal.classList.remove("show");
    selectedFeedbackId = null;
}

// Use template
function useTemplate() {
    if (!selectedFeedbackId) return;
    const feedback = feedbacks.find(f => f.id === selectedFeedbackId);
    if (!feedback) return;
    adminReplyInput.value = replyTemplates[feedback.rating] || "";
    adminReplyInput.focus();
    showNotification("Template loaded — feel free to edit before sending.");
}

// Send reply
if (sendReplyBtn) sendReplyBtn.onclick = async function() {
    if (!selectedFeedbackId) return;

    const reply = adminReplyInput.value.trim();
    if (!reply) {
        showNotification("Please enter a reply before sending.", "error");
        return;
    }

    const feedback = feedbacks.find(f => f.id === selectedFeedbackId);
    if (!feedback) return;

    try {
        const res = await fetch(FEEDBACK_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: selectedFeedbackId, reply })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.error || 'Failed to save reply');
        }
    } catch (error) {
        console.error('Failed to save reply:', error);
        showNotification('Failed to save reply to database.', 'error');
        return;
    }

    // Update local cache after successful DB write.
    feedback.reply = reply;

    // update reply display
    renderAdminReply(feedback);
    document.getElementById("replySection").style.display = "none";

    // Keep list stable by updating only the currently visible card.
    refreshFeedbackCard(feedback);
    updateStats();

    showNotification("Reply sent successfully!");
};

// Event listeners
if (searchInput) searchInput.addEventListener("input", filterFeedbacks);
if (ratingFilter) ratingFilter.addEventListener("change", filterFeedbacks);
if (statusFilter) statusFilter.addEventListener("change", filterFeedbacks);
if (dateFromFilter) dateFromFilter.addEventListener("change", filterFeedbacks);
if (dateToFilter) dateToFilter.addEventListener("change", filterFeedbacks);

// Close modal when clicking backdrop
if (feedbackModal) {
    feedbackModal.addEventListener("click", (e) => {
        if (e.target === feedbackModal) closeFeedbackModal();
    });
}

if (closeFeedbackModalBtn) {
    closeFeedbackModalBtn.addEventListener('click', closeFeedbackModal);
}

if (useTemplateBtn) {
    useTemplateBtn.addEventListener('click', useTemplate);
}

// Notification system
function showNotification(message, type = "success") {
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: ${type === "error" ? "#ef4444" : "#000"};
        color: #fff;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        z-index: 10001;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: opacity 0.3s;
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.opacity = "0";
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function handleNotificationDeepLink() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('source') !== 'notification') return;

    const feedbackId = parseInt(params.get('feedbackId'), 10);
    if (Number.isNaN(feedbackId)) return;

    searchInput.value = "";
    ratingFilter.value = "";
    statusFilter.value = "";
    dateFromFilter.value = "";
    dateToFilter.value = "";
    filteredFeedbacks = [...feedbacks];

    const index = filteredFeedbacks.findIndex((f) => f.id === feedbackId);
    if (index < 0) return;

    feedbackCurrentPage = Math.floor(index / itemsPerPage) + 1;
    renderFeedbackGrid();

    const targetCard = document.querySelector(`.feedback-item[data-feedback-id="${feedbackId}"]`);
    if (targetCard) {
        targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    openFeedbackModal(feedbackId);
}

async function loadFeedbacksFromDB() {
    try {
        const res = await fetch(`${FEEDBACK_API_URL}?_=${Date.now()}`, { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || data.error) {
            throw new Error(data.error || 'Failed to load feedback');
        }

        feedbacks = Array.isArray(data)
            ? data.map((row) => ({
                ...row,
                id: Number(row.id),
                customer: String(row.customer || 'Customer'),
                product: String(row.product || 'Product'),
                order_id: row.order_id !== null && row.order_id !== undefined ? Number(row.order_id) : null,
                comment: String(row.comment || ''),
                reply: String(row.reply || ''),
                rating: Number(row.rating || 0),
                date: String(row.date || '')
            }))
            : [];
        filteredFeedbacks = [...feedbacks];
    } catch (error) {
        console.error('Failed to load feedback:', error);
        feedbacks = [];
        filteredFeedbacks = [];
        alert('Unable to load feedback data from the database right now.');
    }
}

// Initialize
document.addEventListener("DOMContentLoaded", async () => {

    if (searchInput) searchInput.value = "";
    if (ratingFilter) ratingFilter.value = "";
    if (statusFilter) statusFilter.value = "";
    if (dateFromFilter) dateFromFilter.value = "";
    if (dateToFilter) dateToFilter.value = "";

    await loadFeedbacksFromDB();
    renderFeedbackGrid();
    updateStats();
    if (typeof initNotifications === 'function') {
        initNotifications();
    }
    handleNotificationDeepLink();
});

window.openFeedbackModal = openFeedbackModal;
window.closeFeedbackModal = closeFeedbackModal;
window.useTemplate = useTemplate;