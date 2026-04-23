<?php
require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin('../index.php');
requireRole(['inventory_orderAdmin']);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Orders | Look Good Frames Admin</title>
        <link rel="stylesheet" href="../css/Admin/global.css?v=20260411c">
        <link rel="stylesheet" href="../css/Admin/notifications.css?v=20260411c">
        <link rel="stylesheet" href="../css/Admin/orders.css?v=20260411c">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>

    <body>
        <?php $activePage = 'orders'; require_once __DIR__ . '/sidebar.php'; ?>

        <main class="main">
            <header class="header">
                <div class="sidebar-header">
                    <div class="logo-icon">
                        <img src="../uploads/logo/lookgood-black.png" alt="look good logo" class="logo-img">
                    </div>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="Search anything..." name='search_anything_1'>
                </div>

                <!-- Notification Container -->
                <div class="notification-container">
                    <div class="notification-trigger" id="notificationTrigger">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;"></span>
                    </div>

                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <button class="mark-all-read" id="markAllReadBtn">Mark all as read</button>
                        </div>

                        <div class="notification-list" id="notificationList">
                            <!-- Notifications will be populated here -->
                        </div>

                        <div class="notification-footer">
                            <button type="button" class="view-all-notifications" id="viewAllNotificationsBtn" onclick="viewAllNotificationsHandler(event)">View All Notifications</button>
                        </div>
                    </div>
                </div>

                <div class="profile-dropdown-container">
                    <div class="profile-trigger" id="profileTrigger">
                        <img src="/global/pic.png" alt="admin" class="avatar">
                        <span>Erica R.</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-item" id="editProfileBtn">
                            <i class="fas fa-user-edit"></i><span>Edit Profile</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item" id="logoutBtn">
                            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                        </div>
                    </div>
                </div>
            </header>

            <section class="content">

                <div class="page-header" style="margin-bottom:20px;">
                    <h2 id="orderSectionHeading">Order Overview</h2>
                </div>

                <div class="tabs">
                    <button class="tab-link active" data-tab="ordersTab">Orders</button>
                    <button class="tab-link" data-tab="paymentsTab">Payments</button>
                </div>

                <!-- ORDERS TAB -->
                <div class="tab-content active" id="ordersTab">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-shopping-bag"></i></div>
                            </div>
                            <div class="stat-value" id="totalOrders">0</div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fas fa-clock"></i></div>
                            </div>
                            <div class="stat-value" id="processingOrders">0</div>
                            <div class="stat-label">Processing</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#e0e7ff;color:#4338ca;"><i class="fas fa-truck"></i></div>
                            </div>
                            <div class="stat-value" id="shippedOrders">0</div>
                            <div class="stat-label">Shipped</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-times-circle"></i></div>
                            </div>
                            <div class="stat-value" id="cancelledOrders">0</div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="table-row">
                                <div class="card-search-controls">
                                    <div class="card-search-bar">
                                        <i class="fas fa-search"></i>
                                        <input type="text" id="orderSearchInput" class="card-search-input" placeholder="Search orders..." name='orderSearchInput' autocomplete="off">
                                    </div>
                                    <select id="orderStatusFilter" class="card-filter-input">
                                        <option value="">All Orders</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Processing">Processing</option>
                                        <option value="Shipped">Shipped</option>
                                        <option value="Delivered">Delivered</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                    <div class="card-date-filter">
                                        <label for="fromDate">From:</label>
                                        <input type="date" id="fromDate" class="card-filter-input card-date-input" name='fromDate'>
                                    </div>
                                    <div class="card-date-filter">
                                        <label for="toDate">To:</label>
                                        <input type="date" id="toDate" class="card-filter-input card-date-input" name='toDate'>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Product</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="pagination" id="ordersPagination"></div>
                    </div>
                </div>

                <!-- PAYMENTS TAB -->
                <div class="tab-content" id="paymentsTab">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#e0f2fe;color:#0ea5e9;"><i class="fas fa-credit-card"></i></div>
                            </div>
                            <div class="stat-value" id="totalPayments">0</div>
                            <div class="stat-label">Total Payments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#dcfce7;color:#10b981;"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <div class="stat-value" id="completedPayments">0</div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fas fa-clock"></i></div>
                            </div>
                            <div class="stat-value" id="pendingPayments">0</div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon" style="background:#ecfdf5;color:#059669;"><i class="fas fa-peso-sign"></i></div>
                            </div>
                            <div class="stat-value" id="totalAmount">₱0</div>
                            <div class="stat-label">Total Amount</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <div class="table-row">
                                <div class="card-search-controls">
                                    <div class="card-search-bar">
                                        <i class="fas fa-search"></i>
                                        <input type="text" id="paymentSearchInput" class="card-search-input" placeholder="Search payments..." name='paymentSearchInput'>
                                    </div>
                                    <select id="payStatFilter" class="card-filter-input">
                                        <option value="">All Status</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Failed">Failed</option>
                                    </select>
                                    <div class="card-date-filter">
                                        <label for="paymentFromDate">From:</label>
                                        <input type="date" id="paymentFromDate" class="card-filter-input card-date-input" name='paymentFromDate'>
                                    </div>
                                    <div class="card-date-filter">
                                        <label for="paymentToDate">To:</label>
                                        <input type="date" id="paymentToDate" class="card-filter-input card-date-input" name='paymentToDate'>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Status</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div class="pagination" id="paymentsPagination"></div>
                    </div>
                </div>

            </section>
        </main>



        <!-- ORDER DETAILS MODAL (ALL SECTIONS HEADER OUTSIDE CARD) -->
        <div id="orderModal" class="modal-overlay">
            <div class="order-modal-card">
                <div class="order-modal-header">
                    <div style="display: flex; flex-direction: column; align-items: flex-start; flex: 1; gap: 2px;">
                        <span class="order-modal-eyebrow">ORDER DETAILS</span>
                        <span class="order-modal-header-id order-modal-header-id-small" id="modalHeaderOrderID">Order #—</span>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px; min-width: 90px;">
                        <button class="close-btn close-btn-small" id="closeOrderModal">
                            <i class="fas fa-times"></i>
                        </button>
                        <span class="om-status-badge om-status-badge-small" id="modalOrderStatus">PENDING</span>
                    </div>
                </div>
                <hr class="order-modal-header-divider" />
                <div class="order-modal-body" style="padding: 24px 32px 0 32px; max-height: 80vh; overflow-y: auto;">
                    <!-- Customer Information header OUTSIDE card -->
                    <div class="om-section-header" style="margin-bottom: 8px;">
                        <i class="fas fa-user" style="color: #6366f1;"></i>
                        Customer Information
                    </div>
                    <div class="shipping-method-card-exact" style="margin-top: 0;">
                        <div class="shipping-method-grid">
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">NAME</div>
                                <div class="shipping-method-cell-value" id="modalCustomerName">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">EMAIL</div>
                                <div class="shipping-method-cell-value" id="modalCustomerEmail">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">PHONE</div>
                                <div class="shipping-method-cell-value" id="modalCustomerPhone">—</div>
                            </div>
                        </div>
                    </div>
                    <!-- Shipping Address header OUTSIDE card -->
                    <div class="om-section-header" style="margin-top: 24px; margin-bottom: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: #6366f1;"></i>
                        Shipping Address
                    </div>
                    <div class="shipping-method-card-exact" style="margin-top: 0;">
                        <div class="shipping-method-grid">
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">FULL NAME</div>
                                <div class="shipping-method-cell-value" id="modalShippingFullName">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">PHONE</div>
                                <div class="shipping-method-cell-value" id="modalShippingPhone">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">ADDRESS LINE 1</div>
                                <div class="shipping-method-cell-value" id="modalAddressLine1">—</div>
                            </div>
                        </div>
                        <div class="shipping-method-grid" style="margin-top: 12px;">
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">ADDRESS LINE 2</div>
                                <div class="shipping-method-cell-value" id="modalAddressLine2">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">CITY</div>
                                <div class="shipping-method-cell-value" id="modalCity">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">PROVINCE</div>
                                <div class="shipping-method-cell-value" id="modalProvince">—</div>
                            </div>
                        </div>
                        <div class="shipping-method-grid" style="margin-top: 12px;">
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">ZIP CODE</div>
                                <div class="shipping-method-cell-value" id="modalZip">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">REGION</div>
                                <div class="shipping-method-cell-value" id="modalRegion">—</div>
                            </div>
                            <div class="shipping-method-cell">
                                <div class="shipping-method-cell-label">DELIVERY NOTE</div>
                                <div class="shipping-method-cell-value" id="modalDeliveryNote">—</div>
                            </div>
                        </div>
                    </div>
                    <!-- Shipping Method header OUTSIDE card -->
                    <div class="order-modal-section">
                        <div class="om-section-header" style="margin-top: 24px; margin-bottom: 8px;">
                            <i class="fas fa-truck" style="color: #6366f1;"></i>
                            Shipping Method
                        </div>
                        <div class="order-modal-card shipping-method-card-exact">
                            <div class="shipping-method-grid">
                                <div class="shipping-method-cell">
                                    <div class="shipping-method-cell-label">SHIPPING METHOD</div>
                                    <div class="shipping-method-cell-value" id="modalCourierName">—</div>
                                </div>
                                <div class="shipping-method-cell">
                                    <div class="shipping-method-cell-label">ESTIMATED DELIVERY</div>
                                    <div class="shipping-method-cell-value" id="modalEstimatedDelivery">—</div>
                                </div>
                                <div class="shipping-method-cell">
                                    <div class="shipping-method-cell-label">TRACKING NUMBER</div>
                                    <div class="shipping-method-cell-value" id="modalTrackingNumber">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Items Ordered header OUTSIDE card -->
                    <div class="order-modal-section">
                        <div class="om-section-header" style="margin-top: 24px; margin-bottom: 8px;">
                            <i class="fas fa-shopping-bag" style="color: #6366f1;"></i>
                            Items Ordered
                        </div>
                        <div class="order-modal-card items-ordered-card-exact">
                            <div id="modalItemsList">
                                <!-- Example item row for reference:
                                <div class="item-row-exact">
                                    <img src="../uploads/products/example.jpg" class="item-image-exact" alt="Steel Nomad">
                                    <div class="item-details-exact">
                                        <div class="item-title-exact">Steel Nomad</div>
                                        <div class="item-qty-exact">Qty: 1</div>
                                    </div>
                                    <div class="item-price-exact">₱3299.00</div>
                                </div>
                                -->
                            </div>
                        </div>
                    </div>
                    <!-- Order Summary header OUTSIDE card -->
                    <div class="om-section-header" style="margin-top: 24px; margin-bottom: 8px;">
                        <i class="fas fa-receipt" style="color: #6366f1;"></i>
                        Order Summary
                    </div>
                    <div class="order-summary-card">
                        <div class="order-summary-row">
                            <span>Subtotal</span>
                            <span id="modalSubtotal">₱0.00</span>
                        </div>
                        <div class="order-summary-row">
                            <span>Shipping Fee</span>
                            <span id="modalShippingFee">₱0.00</span>
                        </div>
                        <div class="order-summary-row" id="modalDiscountRow" style="display:none;">
                            <span>Discount</span>
                            <span id="modalDiscount">-₱0.00</span>
                        </div>
                        <div class="order-summary-divider"></div>
                        <div class="order-summary-row order-summary-total">
                            <span>Total</span>
                            <span id="modalTotalAmount">₱0.00</span>
                        </div>
                        <div class="order-summary-row">
                            <span>Payment Method</span>
                            <span id="modalPaymentMethodOrder">—</span>
                        </div>
                        <div class="order-summary-row">
                            <span>Payment Status</span>
                            <span class="om-payment-status" id="modalPaymentStatus">—</span>
                        </div>
                    </div>
                    <!-- Order Timeline header OUTSIDE card -->
                    <div class="om-section-header" style="margin-top: 24px; margin-bottom: 8px;">
                        <i class="fas fa-clock" style="color: #6366f1;"></i>
                        Order Timeline
                    </div>
                    <div class="om-section" style="margin-top: 0;">
                        <div id="modalTimeline">
                            <!-- Timeline will be populated by JS -->
                        </div>
                    </div>
                </div>
                <hr class="order-modal-footer-divider" />
                <div class="order-modal-footer" style="padding: 0 32px 24px 32px; margin-top: 12px; display: flex; justify-content: flex-end;">
                    <button class="btn btn-secondary" id="printOrderBtn" style="background: #fff; color: #1e293b; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 14px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 7px; cursor: pointer;">
                        <i class="fas fa-print"></i> Print Order Slip
                    </button>
                </div>
            </div>
        </div>
        <!-- END ORDER DETAILS MODAL -->
        <!-- PAYMENT DETAILS MODAL -->
        <div id="paymentModal" class="modal-overlay">
            <div class="pay-modal-card">

                <div class="pm-header">
                    <div class="pm-header-text">
                        <span class="pm-eyebrow">Payment Details</span>
                        <h2 class="pm-customer-name" id="modalPaymentCustomerName">—</h2>
                        <span class="pm-order-id" id="modalPaymentOrderID">—</span>
                    </div>
                    <button class="close-btn" id="closePaymentModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="pm-body">
                    <div class="pm-amount-hero">
                        <div class="pm-amount-label">Total Amount</div>
                        <div class="pm-amount-value" id="modalPaymentAmount">—</div>
                    </div>
                    <div class="pm-grid">
                        <div class="pm-field">
                            <span class="pm-field-label">Payment Status</span>
                            <span class="pm-field-value" id="modalPaymentStatusPayment">—</span>
                        </div>
                        <div class="pm-field">
                            <span class="pm-field-label">Payment Method</span>
                            <span class="pm-field-value" id="modalPaymentMethod">—</span>
                        </div>
                        <div class="pm-field">
                            <span class="pm-field-label">Order ID</span>
                            <span class="pm-field-value" id="modalPaymentOrderIDField">—</span>
                        </div>
                        <div class="pm-field">
                            <span class="pm-field-label">Payment Date</span>
                            <span class="pm-field-value" id="modalPaymentDate">—</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- CONFIRM STATUS MODAL -->
        <div class="modal-overlay" id="confirmStatusModal">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 id="confirmModalTitle">Update Order Status</h2>
                    <button class="close-btn" id="closeConfirmModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="confirmModalBody"></p>
                </div>
                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;">
                    <button class="btn btn-secondary" id="cancelConfirmModal">Cancel</button>
                    <button class="btn-confirm" id="confirmStatusBtn">Confirm</button>
                </div>
            </div>
        </div>

        <!-- EDIT PROFILE MODAL -->
        <div class="modal-overlay" id="editProfileModal">
            <div class="modal-container modal-edit-profile">
                <div class="modal-header">
                    <h2>Edit Profile</h2>
                    <button class="close-btn" id="closeEditModal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="ep-profile-card">
                        <div class="ep-avatar-wrapper">
                            <div class="ep-avatar-initials" id="epAvatarInitials">ER</div>
                            <img src="/global/pic.png" alt="Profile" id="epAvatarImg" class="ep-avatar-img" onerror="this.style.display='none'">
                            <label for="profileImageInput" class="ep-avatar-edit" title="Change photo">
                                <i class="fas fa-pencil-alt"></i>
                            </label>
                            <input type="file" id="profileImageInput" accept="image/*" style="display:none;" name='profileImageInput'>
                        </div>
                        <div class="ep-card-info">
                            <div class="ep-card-name">Erica Ramirez</div>
                            <div class="ep-card-email">ericakes.ramirez@lookgoodframes.com</div>
                            <label for="profileImageInput" class="ep-change-photo-btn">
                                <i class="fas fa-camera"></i> Change photo
                            </label>
                        </div>
                    </div>
                    <div class="ep-form-grid">
                        <div class="ep-form-group">
                            <label class="ep-label">FULL NAME</label>
                            <input type="text" class="ep-input" id="fullName" value="Erica Ramirez" name='fullName'>
                        </div>
                        <div class="ep-form-group">
                            <label class="ep-label">ROLE</label>
                            <input type="text" class="ep-input" id="role" value="Admin" readonly name='role'>
                        </div>
                    </div>
                    <div class="ep-form-group" style="margin-top:14px;">
                        <label class="ep-label">EMAIL ADDRESS</label>
                        <input type="email" class="ep-input" id="email" value="ericakes.ramirez@lookgoodframes.com" name='email'>
                    </div>
                    <div class="ep-section-title" style="margin-top:10px;">Change password</div>
                    <div class="ep-form-grid" style="margin-top:12px;margin-bottom:8px;">
                        <div class="ep-form-group">
                            <label class="ep-label">NEW PASSWORD</label>
                            <input type="password" class="ep-input" id="newPassword" placeholder="Enter new password" name='newPassword'>
                        </div>
                        <div class="ep-form-group">
                            <label class="ep-label">CONFIRM PASSWORD</label>
                            <input type="password" class="ep-input" id="confirmPassword" placeholder="Confirm password" name='confirmPassword'>
                        </div>
                    </div>
                </div>
                <div class="ep-modal-footer">
                    <div>
                        <button class="btn btn-secondary" id="cancelEditModal">Cancel</button>
                        <button class="btn btn-primary" id="saveProfile">Save changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOGOUT MODAL -->
        <div class="modal-overlay" id="logoutModal">
            <div class="modal-container modal-logout" style="max-width:380px;">
                <div class="modal-header">
                    <div class="logout-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <button class="close-btn" id="closeLogoutModal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <h2>Log out of your account?</h2>
                    <p>Are you sure you want to log out?</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="cancelLogout">Cancel</button>
                    <button class="btn btn-danger" id="confirmLogout">
                        <i class="fas fa-sign-out-alt"></i> Log out
                    </button>
                </div>
            </div>
        </div>

        <!-- TOAST -->
        <div id="statusToast" class="status-toast">
            <i class="fas fa-check-circle"></i>
            <span id="toastMessage"></span>
        </div>

        <script src="../adminActions/orders.js?v=20260411c"></script>
        <script src="../adminActions/global.js?v=20260411c"></script>
    </body>
</html>