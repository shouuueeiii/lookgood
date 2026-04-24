<?php
require_once '../config.php';
require_once '../auth_admin.php';
requireAdmin('../index.php');
requireRole([]);  // Head Admin only
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Settings | Look Good Frames Admin</title>
        <link rel="stylesheet" href="../css/Admin/global.css?v=<?= urlencode((string)@filemtime(__DIR__ . '/../css/Admin/global.css')); ?>">
        <link rel="stylesheet" href="../css/Admin/notifications.css?v=<?= urlencode((string)@filemtime(__DIR__ . '/../css/Admin/notifications.css')); ?>">
        <link rel="stylesheet" href="../css/Admin/setting.css?v=<?= urlencode((string)@filemtime(__DIR__ . '/../css/Admin/setting.css')); ?>">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>

    <body>
        <?php $activePage = 'settings'; require_once __DIR__ . '/sidebar.php'; ?>

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
                <div class="page-header settings-page-header">
                    <h1>Store Configuration</h1>
                    <p>Manage your store identity, admin accounts, and storefront appearance all in one place.</p>
                </div>

                <!-- Tabs -->
                <div class="settings-tabs" aria-label="Settings sections">
                    <button class="tab-btn active" data-tab="general">
                        <i class="fas fa-store"></i> General
                    </button>
                    <button class="tab-btn" data-tab="admins">
                        <i class="fas fa-users"></i> Admin Users
                    </button>
                    <button class="tab-btn" data-tab="appearance">
                        <i class="fas fa-palette"></i> Appearance
                    </button>
                </div>

                <!-- General Tab -->
                <div class="tab-content active" id="general">
                    <div class="card">
                        <div class="card-header"><h3>General Settings</h3></div>
                        <form class="settings-section" id="generalForm">
                            <div class="settings-block">
                                <div class="settings-block-header">
                                    <h4>Store Identity</h4>
                                    <p>Basic details customers see first across the storefront.</p>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Store Name</label>
                                        <input type="text" value="Look Good Frames" id="storeName" name='storeName'>
                                    </div>
                                    <div class="form-group">
                                        <label>Store URL</label>
                                        <input type="text" value="www.lookgoodframes.com" id="storeUrl" name='storeUrl'>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Store Description</label>
                                    <textarea rows="4" id="storeDescription" placeholder="Example: Premium eyewear crafted for everyday comfort and style."></textarea>
                                </div>
                            </div>

                            <div class="settings-block">
                                <div class="settings-block-header">
                                    <h4>Contact & Operations</h4>
                                    <p>How customers and couriers can reach your team.</p>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Contact Email</label>
                                        <input type="email" value="support@lookgoodframes.com" id="contactEmail" name='contactEmail'>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="tel" value="+1 234 567 8900" id="phoneNumber" name='phoneNumber'>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Store Address</label>
                                    <textarea rows="2" placeholder="Your store address..." id="storeAddress"></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Business Hours</label>
                                        <input type="text" value="Mon-Fri: 9AM-6PM, Sat: 9AM-4PM" id="businessHours" name='businessHours'>
                                    </div>
                                    <div class="form-group">
                                        <label>Currency</label>
                                        <select id="currency">
                                            <option value="PHP" selected>PHP (₱)</option>
                                            <option value="USD">USD ($)</option>
                                            <option value="EUR">EUR (€)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Media Links -->
                            <div class="settings-block">
                                <div class="section-title">
                                    <i class="fas fa-share-alt"></i>
                                    <h4>Social Media Links</h4>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Facebook</label>
                                        <input type="url" placeholder="https://facebook.com/yourpage" id="facebookUrl" name='facebookUrl'>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" placeholder="contact@yourstore.com" id="socialEmail" name='socialEmail'>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Twitter</label>
                                        <input type="url" placeholder="https://twitter.com/yourhandle" id="twitterUrl" name='twitterUrl'>
                                    </div>
                                    <div class="form-group">
                                        <label>Instagram</label>
                                        <input type="url" placeholder="https://instagram.com/yourhandle" id="instagramUrl" name='instagramUrl'>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Admin Users Tab -->
                <div class="tab-content" id="admins">
                    <div class="card">
                        <div class="card-header">
                            <h3>Admin Users</h3>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                            <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="adminTableBody">
                                    <tr data-id="1">
                                        <td><strong>Erica Ramirez</strong></td>
                                        <td>erica.ramirez@lookgoodframes.com</td>
                                        <td><span class="badge badge-info">Main Admin</span></td>
                                        <td><button class="btn btn-secondary btn-sm edit-admin-btn"><i class="fas fa-edit"></i></button></td>
                                    </tr>
                                    <tr data-id="2">
                                        <td><strong>Pollyne Anne</strong></td>
                                        <td>pollyneanne@lookgoodframes.com</td>
                                        <td><span class="badge badge-success">Admin</span></td>
                                        <td><button class="btn btn-secondary btn-sm edit-admin-btn"><i class="fas fa-edit"></i></button></td>
                                    </tr>
                                    <tr data-id="3">
                                        <td><strong>Eds Halili</strong></td>
                                        <td>edsedseds@lookgoodframes.com</td>
                                        <td><span class="badge badge-success">Admin</span></td>
                                        <td><button class="btn btn-secondary btn-sm edit-admin-btn"><i class="fas fa-edit"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Appearance Tab -->
                <div class="tab-content" id="appearance">
                    <div class="card">
                        <div class="card-header"><h3>Appearance Settings</h3></div>
                        <form class="settings-section" id="appearanceForm">
                            <!-- Branding Section -->
                            <div class="settings-group">
                                <h4>Branding</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Logo Upload</label>
                                        <div class="file-upload">
                                            <input type="file" id="logoUpload" accept="image/*" style="display: none;" name='logoUpload'>
                                            <button type="button" class="btn btn-secondary" id="logoBtn">
                                                <i class="fas fa-upload"></i> Choose Logo
                                            </button>
                                            <span id="logoFileName">lookgood-black.png</span>
                                        </div>
                                        <small>Recommended: PNG, 200x60px</small>
                                        <div id="logoPreview" style="margin-top: 12px; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); display: none;">
                                            <img id="logoPreviewImg" src="" alt="Logo preview" style="max-width: 100%; max-height: 100px; display: block;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Favicon Upload</label>
                                        <div class="file-upload">
                                            <input type="file" id="faviconUpload" accept="image/*" style="display: none;" name='faviconUpload'>
                                            <button type="button" class="btn btn-secondary" id="faviconBtn">
                                                <i class="fas fa-upload"></i> Choose Favicon
                                            </button>
                                            <span id="faviconFileName">favicon.ico</span>
                                        </div>
                                        <small>Recommended: ICO/PNG, 32x32px</small>
                                        <div id="faviconPreview" style="margin-top: 12px; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); display: none;">
                                            <img id="faviconPreviewImg" src="" alt="Favicon preview" style="width: 48px; height: 48px; display: block;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <!-- Edit Admin Modal -->
            <div class="modal-overlay" id="editAdminModal">
                <div class="modal-container admin-modal">
                    <div class="modal-header">
                        <h2>Admin Details</h2>
                        <button class="close-btn" id="closeEditAdminModal"><i class="fas fa-times"></i></button>
                    </div>
                    <form class="modal-body" id="editAdminForm">
                        <input type="hidden" id="editAdminId" name='editAdminId'>
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="editAdminName" required name='editAdminName'>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="editAdminEmail" required name='editAdminEmail'>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select id="editAdminRole">
                                <option value="Admin">Admin</option>
                                <option value="Main Admin">Main Admin</option>
                                <option value="Chat Support">Chat Support</option>
                            </select>
                        </div>
                        <div class="form-group setting-item">
                            <div class="setting-info">
                                <div class="setting-title">Account Access</div>
                            </div>
                            <div class="toggle-switch">
                                <input type="checkbox" id="adminAccess" checked name='adminAccess'>
                                <label for="adminAccess" class="toggle-label"><span class="toggle-slider"></span></label>
                                <div class="setting-description" id="accessStatus">Active</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" id="cancelEditAdmin">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Toast Notification -->
            <div class="toast" id="toast">
                <i class="fas fa-check-circle"></i>
                <span id="toastMessage"></span>
            </div>
        </main>

        <!--Edit Profile Modal-->
        <div class="modal-overlay" id="editProfileModal">
            <div class="modal-container modal-edit-profile">

                <div class="modal-header">
                    <h2>Edit Profile</h2>
                    <button class="close-btn" id="closeEditModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body" style="padding: 20px 24px;">

                    <!-- Profile card -->
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
                            <input type="text" class="ep-input" id="profilePosition" readonly name='role'>
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
                        <button class="btn btn-secondary" id="cancelEditModal">Cancel</button>
                        <button class="btn btn-primary" id="saveProfile">Save changes</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- Logout Modal -->
        <div class="modal-overlay" id="logoutModal">
            <div class="modal-container modal-logout" style="max-width: 380px;">

                <div class="modal-header">
                    <div class="logout-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <button class="close-btn" id="closeLogoutModal">
                        <i class="fas fa-times"></i>
                    </button>
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

        <script src="../adminActions/global.js?v=<?= urlencode((string)@filemtime(__DIR__ . '/../adminActions/global.js')); ?>"></script>
        <script src="../adminActions/settings.js?v=<?= urlencode((string)@filemtime(__DIR__ . '/../adminActions/settings.js')); ?>"></script>
    </body>
</html>