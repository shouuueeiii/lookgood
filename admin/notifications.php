<?php
require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin('../index.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LookGood Admin</title>
    <link rel="stylesheet" href="../css/Admin/global.css">
    <link rel="stylesheet" href="../css/Admin/notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php $activePage = 'notifications'; require_once __DIR__ . '/sidebar.php'; ?>

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

            <div class="notification-container">
                <div class="notification-trigger" id="notificationTrigger">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                </div>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h4>Notifications</h4>
                        <button class="mark-all-read" id="markAllReadBtn" type="button">Mark all as read</button>
                    </div>
                    <div class="notification-list" id="notificationList"></div>
                    <div class="notification-footer">
                        <button type="button" class="view-all-notifications" id="viewAllNotificationsBtn" onclick="viewAllNotificationsHandler(event)">View All Notifications</button>
                    </div>
                </div>
            </div>

            <div class="profile-dropdown-container">
                    <div class="profile-trigger" id="profileTrigger">
                        <img src="/global/pic.png" alt="admin" class="avatar">
                        <span id="profileNameDisplay">...</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>

                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="dropdown-item" id="editProfileBtn">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-item" id="logoutBtn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </div>
                    </div>
                </div>
        </header>

        <section class="content">
            <div class="notifications-page">
                <header class="page-header with-actions">
                    <div>
                        <h1>Notifications</h1>
                        <p id="unreadSummary">All caught up</p>
                    </div>
                    <div class="header-actions">
                        <button id="markAllReadBtnPage" class="btn btn-secondary" type="button">Mark all as read</button>
                    </div>
                </header>

                <section class="filters" id="filterPills">
                    <button class="filter-pill active" data-filter="all" type="button">All</button>
                    <button class="filter-pill" data-filter="order" type="button">Orders</button>
                    <button class="filter-pill" data-filter="payment" type="button">Payments</button>
                    <button class="filter-pill" data-filter="stock" type="button">Low stock</button>
                    <button class="filter-pill" data-filter="cancel" type="button">Cancellations</button>
                    <button class="filter-pill" data-filter="return" type="button">Returns</button>
                    <button class="filter-pill" data-filter="status" type="button">Status updates</button>
                </section>

                <section class="notifications-panel">
                    <ul id="notificationsList" class="notifications-list"></ul>
                    <div class="pagination" id="paginationBar"></div>
                </section>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="editProfileModal">
        <div class="modal-container modal-edit-profile">

            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-btn" id="closeEditModal" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body" style="padding: 20px 24px;">
                <div class="ep-profile-card">
                        <div class="ep-avatar-wrapper">
                            <div class="ep-avatar-initials" id="epAvatarInitials"></div>
                            <img src="/global/pic.png" alt="Profile" id="epAvatarImg" class="ep-avatar-img" onerror="this.style.display='none'">
                            <label for="profileImageInput" class="ep-avatar-edit" title="Change photo">
                                <i class="fas fa-pencil-alt"></i>
                            </label>
                            <input type="file" id="profileImageInput" accept="image/*" style="display: none;" name='profileImageInput'>
                        </div>
                        <div class="ep-card-info">
                            <div class="ep-card-name" id="epCardName"></div>
                            <div class="ep-card-email" id="epCardEmail"></div>
                            <label for="profileImageInput" class="ep-change-photo-btn">
                                <i class="fas fa-camera"></i> Change photo
                            </label>
                        </div>
                    </div>

                    <!-- Two-column fields -->
                    <div class="ep-form-grid">
                        <div class="ep-form-group">
                            <label class="ep-label">FULL NAME</label>
                            <input type="text" class="ep-input" id="fullName" value="Erica Ramirez" name='fullName'>
                        </div>
                        <div class="ep-form-group">
                            <label class="ep-label">ROLE</label>
                            <input type="text" class="ep-input" id="profilePosition" value="Admin" readonly name='role'>
                        </div>
                    </div>

                    <!-- Full-width email -->
                    <div class="ep-form-group" style="margin-top: 14px;">
                        <label class="ep-label">EMAIL ADDRESS</label>
                        <input type="email" class="ep-input" id="profileEmail"  name='email'>
                    </div>

                <div class="ep-section-title" style="margin-top: 10px;">Change password</div>

                <div class="ep-form-group" style="margin-top: 12px;">
                        <label class="ep-label">CURRENT PASSWORD</label>
                        <input type="password" class="ep-input" id="currentPassword" placeholder="Enter current password" name='currentPassword'>
                    </div>

                <div class="ep-form-grid" style="margin-top: 12px; margin-bottom: 8px;">
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
                    <button class="btn btn-secondary" id="cancelEditModal" type="button">Cancel</button>
                    <button class="btn btn-primary" id="saveProfile" type="button">Save changes</button>
                </div>
            </div>

        </div>
    </div>

    <div class="modal-overlay" id="logoutModal">
        <div class="modal-container modal-logout" style="max-width: 380px;">

            <div class="modal-header">
                <div class="logout-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <button class="close-btn" id="closeLogoutModal" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body">
                <h2>Log out of your account?</h2>
                <p>Are you sure you want to log out?</p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelLogout" type="button">Cancel</button>
                <button class="btn btn-danger" id="confirmLogout" type="button">
                    <i class="fas fa-sign-out-alt"></i> Log out
                </button>
            </div>

        </div>
    </div>

    <script src="../adminActions/global.js?v=<?= urlencode((string)@filemtime(__DIR__ . '/../adminActions/global.js')); ?>"></script>
    <script src="../adminActions/notifications.js?v=<?= urlencode((string)@filemtime(__DIR__ . '/../adminActions/notifications.js')); ?>"></script>
</body>
</html>
