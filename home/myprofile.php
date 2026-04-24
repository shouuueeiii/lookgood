<?php
require_once __DIR__ . '/../session_bootstrap.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
  $_SESSION['redirect_after_login'] = '/lookgood/home/myprofile.php';
  header('Location: /lookgood/home/user-login.php');
  exit();
}

$isLoggedIn = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? 'user') !== 'admin');
$username = $isLoggedIn ? htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['email']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile</title>

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <link rel="stylesheet" href="/lookgood/css/User/navbar.css">
  <link rel="stylesheet" href="/lookgood/css/User/footer.css">
  <link rel="stylesheet" href="/lookgood/css/User/cart.css">
  <link rel="stylesheet" href="/lookgood/css/User/profile-base.css">
  <link rel="stylesheet" href="/lookgood/css/User/profile-account.css">
  <link rel="stylesheet" href="/lookgood/css/User/profile-purchases.css">
  <link rel="stylesheet" href="/lookgood/css/User/profile-wishlist.css">
</head>
<body>

  <!-- ── NAVBAR ── -->
  <section>
    <?php include 'navbar.php'; ?>
  </section>

  <!-- ── PROFILE LAYOUT ── -->
  <main class="profile-main">
    <div class="profile-layout">

      <!-- ══ LEFT SIDEBAR ══ -->
      <aside class="profile-sidebar">

        <!-- Avatar + Identity -->
        <div class="sidebar-identity">
          <div class="profile-avatar" id="profileAvatar">
            <img id="avatarImg" src="" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:none;">
            <i class="fas fa-glasses" id="avatarPlaceholderIcon" style="font-size: 2rem;"></i>
          </div>
          <h2 class="sidebar-name" id="heroName">Juan dela Cruz</h2>
          <p class="sidebar-username" id="heroUsername">@onedelacruz</p>
          <span class="profile-member-badge"><i class="fas fa-star"></i> Member since Feb 2026</span>
        </div>

        <!-- Nav Menu -->
        <nav class="sidebar-nav" role="tablist">
          <button class="sidebar-tab active" data-tab="account">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
          </button>
          <button class="sidebar-tab" data-tab="wishlist">
            <i class="fas fa-heart"></i>
            <span>My Wishlist</span>
            <span class="tab-badge" id="wishlistTabCount">0</span>
          </button>
          <button class="sidebar-tab" data-tab="address">
            <i class="fas fa-map-marker-alt"></i>
            <span>My Addresses</span>
          </button>
          <button class="sidebar-tab" data-tab="purchases">
            <i class="fas fa-box"></i>
            <span>My Purchases</span>
          </button>
        </nav>

      </aside>

      <!-- ══ RIGHT CONTENT ══ -->
      <div class="profile-content">

        <!-- ── MY PROFILE PANEL ── -->
        <section id="panel-account" class="profile-panel active">
          <div class="panel-section-header">
            <h2 class="panel-section-title">My Profile</h2>
            <p class="panel-section-sub">Manage your personal information</p>
          </div>

          <!-- Personal Info Card -->
          <div class="profile-card" id="card-profile">
            <div class="card-header">
              <div class="card-header-left"><i class="fas fa-id-card card-icon"></i><h2 class="card-title">Personal Info</h2></div>
              <button class="btn-edit" data-target="profile-form"><i class="fas fa-pen"></i> Edit</button>
            </div>
            <div class="card-view" id="profile-view">
              <div class="info-row"><span class="info-label">Full Name</span><span class="info-value" id="view-fullname">Juan dela Cruz</span></div>
              <div class="info-row"><span class="info-label">Username</span><span class="info-value" id="view-username">@juandelacruz</span></div>
              <div class="info-row"><span class="info-label">Email</span><span class="info-value" id="view-email">juan@example.com</span></div>
              <div class="info-row"><span class="info-label">Phone</span><span class="info-value" id="view-phone">+63 917 123 4567</span></div>
              <div id="usernameCooldownMsg" style="font-size:0.75rem; color:#888; margin-top:0.5rem;"></div>
            </div>
            <form class="card-form hidden" id="profile-form" novalidate>
              <div class="form-row two-col">
                <div class="form-group"><label class="form-label">First Name</label><input class="form-input" type="text" id="firstName" value="Juan" placeholder="Enter first name"><span class="form-error" id="err-firstName"></span></div>
                <div class="form-group"><label class="form-label">Last Name</label><input class="form-input" type="text" id="lastName" value="dela Cruz" placeholder="Enter last name"><span class="form-error" id="err-lastName"></span></div>
              </div>
              <div class="form-group"><label class="form-label">Username <span class="optional">(can change every 90 days)</span></label><input class="form-input" type="text" id="username" value="juandelacruz" placeholder="Enter username (3-20 chars, letters/numbers/_)"><span class="form-error" id="err-username"></span></div>
              <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" id="email" value="juan@example.com" placeholder="you@example.com"><span class="form-error" id="err-email"></span></div>
              <div class="form-group"><label class="form-label">Phone</label><input class="form-input" type="tel" id="phone" value="+63 917 123 4567" placeholder="+63 XXX XXX XXXX"><span class="form-error" id="err-phone"></span></div>
              <div class="form-actions">
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Save Changes</button>
                <button type="button" class="btn-cancel" data-target="profile-form">Cancel</button>
              </div>
            </form>
          </div>

          <!-- Avatar Upload Card -->
          <div class="profile-card avatar-card">
            <div class="card-header">
              <div class="card-header-left"><i class="fas fa-camera card-icon"></i><h2 class="card-title">Profile Picture</h2></div>
            </div>
            <div class="avatar-upload-area">
              <div class="avatar-preview" id="avatarPreview">
                <img id="avatarPreviewImg" src="" alt="Preview" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:none;">
                <i class="fas fa-user-circle" id="avatarPreviewIcon" style="font-size:64px; color:#ccc;"></i>
              </div>
              <div class="avatar-upload-controls">
                <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/jpg" style="display:none;">
                <button class="btn-upload-avatar" id="btnUploadAvatar"><i class="fas fa-upload"></i> Upload New Picture</button>
                <button class="btn-remove-avatar" id="btnRemoveAvatar" style="display:none;"><i class="fas fa-trash-alt"></i> Remove</button>
              </div>
            </div>
          </div>

          <!-- Change Password Card -->
          <div class="profile-card password-card">
            <div class="card-header">
              <div class="card-header-left"><i class="fas fa-lock card-icon"></i><h2 class="card-title">Security</h2></div>
            </div>
            <form id="changePasswordForm" novalidate>
              <div class="form-group"><label class="form-label">Current Password</label><input class="form-input" type="password" id="currentPassword" placeholder="Enter current password"><span class="form-error" id="err-currentPassword"></span></div>
              <div class="form-group"><label class="form-label">New Password</label><input class="form-input" type="password" id="newPassword" placeholder="Min. 8 characters"><span class="form-error" id="err-newPassword"></span></div>
              <div class="form-group"><label class="form-label">Confirm New Password</label><input class="form-input" type="password" id="confirmPassword" placeholder="Re-enter new password"><span class="form-error" id="err-confirmPassword"></span></div>
              <div class="form-actions">
                <button type="submit" class="btn-save"><i class="fas fa-key"></i> Change Password</button>
              </div>
            </form>
          </div>

          <!-- Danger Zone -->
          <div class="danger-zone-card">
            <div class="danger-zone-header">
              <i class="fas fa-exclamation-triangle danger-zone-icon"></i>
              <div>
                <h3 class="danger-zone-title">Danger Zone</h3>
                <p class="danger-zone-sub">Permanent actions that cannot be undone</p>
              </div>
            </div>
            <div class="danger-zone-action">
              <div class="danger-zone-desc">
                <strong>Delete Account</strong>
                <p>Permanently delete your account and all associated data including orders history, wishlist, and addresses.</p>
              </div>
              <button class="btn-delete-account" id="btnDeleteAccount">
                <i class="fas fa-trash-alt"></i> Delete Account
              </button>
            </div>
          </div>
        </section>

        <!-- ── MY WISHLIST PANEL ── -->
        <section id="panel-wishlist" class="profile-panel">
          <div class="panel-section-header">
            <h2 class="panel-section-title">My Wishlist</h2>
            <p class="panel-section-sub">Items you've saved from the store</p>
          </div>
          <div class="empty-state" id="wishlist-empty" style="display: none;"><i class="fas fa-heart-broken empty-state-icon"></i><p class="empty-state-text">Your wishlist is empty.</p><a href="/lookgood/home/index.php" class="btn-browse"><i class="fas fa-glasses"></i> Browse Frames</a></div>
          <div class="wishlist-grid" id="wishlistGrid"></div>
        </section>

        <!-- ── MY ADDRESSES PANEL ── -->
        <section id="panel-address" class="profile-panel">
          <div class="panel-section-header">
            <h2 class="panel-section-title">My Addresses</h2>
            <p class="panel-section-sub">Manage your shipping addresses</p>
          </div>
          <div id="addressList"></div>
          <button class="btn-add-address" id="btnAddAddress"><i class="fas fa-plus"></i> Add New Address</button>

          <div class="profile-card address-form-card hidden" id="card-address-form">
            <div class="card-header">
              <div class="card-header-left"><i class="fas fa-map-marker-alt card-icon"></i><h2 class="card-title" id="addressFormTitle">Add New Address</h2></div>
            </div>
            <form class="card-form" id="address-form" novalidate>
              <input type="hidden" id="editAddressId" value="">
              <div class="form-group"><label class="form-label">Address Label <span class="optional">(e.g. Home, Work, Office)</span></label><input class="form-input" type="text" id="addrLabel" placeholder="e.g. Home, Work, Office" maxlength="30"><span class="form-error" id="err-addrLabel"></span></div>
              <div class="form-group"><label class="form-label">Address Line 1</label><input class="form-input" type="text" id="addr1" placeholder="House/Unit No., Street"><span class="form-error" id="err-addr1"></span></div>
              <div class="form-group"><label class="form-label">Address Line 2 <span class="optional">(optional)</span></label><input class="form-input" type="text" id="addr2" placeholder="Barangay, Subdivision, Landmark"></div>
              <div class="form-row two-col">
                <div class="form-group"><label class="form-label">City</label><input class="form-input" type="text" id="city" placeholder="City/Municipality"><span class="form-error" id="err-city"></span></div>
                <div class="form-group"><label class="form-label">Province</label><input class="form-input" type="text" id="province" placeholder="Province"><span class="form-error" id="err-province"></span></div>
              </div>
              <div class="form-row two-col">
                <div class="form-group"><label class="form-label">ZIP Code</label><input class="form-input" type="text" id="zip" maxlength="4" placeholder="e.g., 1210"><span class="form-error" id="err-zip"></span></div>
                <div class="form-group"><label class="form-label">Region</label><select class="form-input" id="region"><option value="">Select Region</option><option value="NCR">NCR — National Capital Region</option><option value="CAR">CAR — Cordillera Administrative Region</option><option value="I">Region I — Ilocos Region</option><option value="II">Region II — Cagayan Valley</option><option value="III">Region III — Central Luzon</option><option value="IV-A">Region IV-A — CALABARZON</option><option value="IV-B">Region IV-B — MIMAROPA</option><option value="V">Region V — Bicol Region</option><option value="VI">Region VI — Western Visayas</option><option value="VII">Region VII — Central Visayas</option><option value="VIII">Region VIII — Eastern Visayas</option><option value="IX">Region IX — Zamboanga Peninsula</option><option value="X">Region X — Northern Mindanao</option><option value="XI">Region XI — Davao Region</option><option value="XII">Region XII — SOCCSKSARGEN</option><option value="XIII">Region XIII — Caraga</option><option value="BARMM">BARMM — Bangsamoro Autonomous Region</option></select><span class="form-error" id="err-region"></span></div>
              </div>
              <div class="form-group"><label class="form-label">Delivery Notes <span class="optional">(optional)</span></label><input class="form-input" type="text" id="addrNotes" placeholder="e.g., Leave at gate, call upon arrival"></div>
              <div class="form-group address-default-toggle"><label class="toggle-label"><input type="checkbox" id="addrIsDefault"><span class="toggle-text">Set as default address</span></label></div>
              <div class="form-actions"><button type="submit" class="btn-save"><i class="fas fa-check"></i> Save Address</button><button type="button" class="btn-cancel" id="btnCancelAddressForm">Cancel</button></div>
            </form>
          </div>
        </section>

        <!-- ── MY PURCHASES PANEL ── -->
        <section id="panel-purchases" class="profile-panel">
          <div class="panel-section-header">
            <h2 class="panel-section-title">My Purchases</h2>
            <p class="panel-section-sub">Track all your orders here</p>
          </div>
          <div class="purchase-tabs-wrapper">
            <nav class="purchase-tabs" role="tablist">
              <button class="purchase-tab active" data-ptab="all">All Orders <span class="ptab-badge" id="count-all">0</span></button>
              <button class="purchase-tab" data-ptab="to-ship">To Ship <span class="ptab-badge" id="count-to-ship">0</span></button>
              <button class="purchase-tab" data-ptab="to-receive">To Receive <span class="ptab-badge" id="count-to-receive">0</span></button>
              <button class="purchase-tab" data-ptab="delivered">Delivered <span class="ptab-badge" id="count-delivered">0</span></button>
              <button class="purchase-tab" data-ptab="completed">Completed <span class="ptab-badge" id="count-completed">0</span></button>
              <button class="purchase-tab" data-ptab="cancelled">Cancelled <span class="ptab-badge" id="count-cancelled">0</span></button>
            </nav>
          </div>
          <div class="purchase-panel active" id="ppanel-all"><div class="empty-state" id="empty-all"><i class="fas fa-shopping-bag"></i><p>No orders yet.</p></div><div id="list-all" class="orders-list"></div></div>
          <div class="purchase-panel" id="ppanel-to-ship"><div class="empty-state" id="empty-to-ship"><i class="fas fa-box"></i><p>No orders to ship.</p></div><div id="list-to-ship" class="orders-list"></div></div>
          <div class="purchase-panel" id="ppanel-to-receive"><div class="empty-state" id="empty-to-receive"><i class="fas fa-truck"></i><p>No orders out for delivery.</p></div><div id="list-to-receive" class="orders-list"></div></div>
          <div class="purchase-panel" id="ppanel-delivered"><div class="empty-state" id="empty-delivered"><i class="fas fa-check-circle"></i><p>No delivered orders.</p></div><div id="list-delivered" class="orders-list"></div></div>
          <div class="purchase-panel" id="ppanel-completed"><div class="empty-state" id="empty-completed"><i class="fas fa-history"></i><p>No completed orders.</p></div><div id="list-completed" class="orders-list"></div></div>
          <div class="purchase-panel" id="ppanel-cancelled"><div class="empty-state" id="empty-cancelled"><i class="fas fa-times-circle"></i><p>No cancelled orders.</p></div><div id="list-cancelled" class="orders-list"></div></div>
        </section>

      </div>
    </div>
  </main>

  <footer class="footer"></footer>

  <!-- Cancel Order Overlay -->
  <div id="cancelOrderOverlay" class="modal-overlay" style="display:none;"><div class="modal-box"><button class="modal-close-btn" id="cancelOverlayClose"><i class="fas fa-times"></i></button><div class="modal-icon-wrap cancel-icon-wrap"><i class="fas fa-ban"></i></div><h3 class="modal-title">Cancel Order</h3><p class="modal-subtitle">Order <strong id="cancelOrderIdDisplay"></strong></p><div class="modal-body"><label class="form-label" for="cancelReasonInput">Reason for cancellation <span style="color:#e53935;">*</span></label><textarea id="cancelReasonInput" class="form-input" rows="4" placeholder="Please tell us why you want to cancel this order..." maxlength="300" style="resize:vertical;"></textarea><div style="text-align:right;font-size:0.75rem;color:#aaa;margin-top:4px;"><span id="cancelReasonCount">0</span>/300</div><span class="form-error" id="err-cancelReason"></span></div><div class="modal-actions"><button class="btn-save btn-confirm-cancel" id="btnConfirmCancel"><i class="fas fa-ban"></i> Confirm Cancellation</button><button class="btn-cancel" id="btnCancelOverlayDismiss">Keep Order</button></div></div></div>


  <!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal-overlay" style="display:none;">
  <div class="modal-box order-details-modal">
    <button class="modal-close-btn" id="orderDetailsModalClose"><i class="fas fa-times"></i></button>
    <div class="modal-body" id="orderDetailsModalBody">
      <!-- dynamic content here -->
    </div>
  </div>
</div>

<!-- Confirm Delivery Overlay -->
<div id="confirmDeliveryOverlay" class="modal-overlay" style="display:none;">
  <div class="modal-box">
    <button class="modal-close-btn" id="confirmDeliveryOverlayClose"><i class="fas fa-times"></i></button>
    <div class="modal-icon-wrap confirm-icon-wrap">
      <i class="fas fa-check-circle"></i>
    </div>
    <h3 class="modal-title">Confirm Delivery</h3>
    <p class="modal-subtitle">Order <strong id="confirmOrderIdDisplay"></strong></p>
    <div class="modal-body">
      <p style="font-size:14px; color:#555; margin-bottom:12px;">
        Have you received this order? Once confirmed, you can leave a review.
      </p>
      <span class="form-error" id="err-confirmDelivery"></span>
    </div>
    <div class="modal-actions">
      <button class="btn-save btn-confirm-delivery" id="btnConfirmDelivery">
        <i class="fas fa-check-circle"></i> Yes, Confirm Delivery
      </button>
      <button class="btn-cancel" id="btnConfirmDeliveryOverlayDismiss">Cancel</button>
    </div>
  </div>
</div>

  <!-- Delete Account Overlay -->
  <div id="deleteAccountOverlay" class="modal-overlay" style="display:none;"><div class="modal-box"><button class="modal-close-btn" id="deleteOverlayClose"><i class="fas fa-times"></i></button><div class="modal-icon-wrap delete-icon-wrap"><i class="fas fa-trash-alt"></i></div><h3 class="modal-title">Delete Account</h3><p class="modal-subtitle">This action is <strong>permanent and cannot be undone.</strong></p><div class="modal-body"><p style="font-size:14px;color:#555;margin-bottom:12px;">All your data — orders, wishlist, addresses — will be permanently deleted.</p><label class="form-label" for="deleteConfirmInput">Type <strong>DELETE</strong> to confirm</label><input type="text" id="deleteConfirmInput" class="form-input" placeholder='Type "DELETE" here'><span class="form-error" id="err-deleteConfirm"></span></div><div class="modal-actions"><button class="btn-save btn-danger-confirm" id="btnConfirmDelete"><i class="fas fa-trash-alt"></i> Permanently Delete</button><button class="btn-cancel" id="btnDeleteOverlayDismiss">Cancel</button></div></div></div>

  <!-- Address Delete Confirmation Overlay -->
  <div id="deleteAddressOverlay" class="modal-overlay" style="display:none;"><div class="modal-box"><button class="modal-close-btn" id="deleteAddressOverlayClose"><i class="fas fa-times"></i></button><div class="modal-icon-wrap delete-icon-wrap"><i class="fas fa-trash-alt"></i></div><h3 class="modal-title">Delete Address</h3><p class="modal-subtitle">Are you sure you want to delete this address?</p><div class="modal-body"><p style="font-size:14px;color:#555;margin-bottom:12px;">This action cannot be undone.</p><input type="hidden" id="deleteAddressId"><span class="form-error" id="err-deleteAddress"></span></div><div class="modal-actions"><button class="btn-save btn-danger-confirm" id="btnConfirmDeleteAddress"><i class="fas fa-trash-alt"></i> Yes, Delete</button><button class="btn-cancel" id="btnDeleteAddressOverlayDismiss">Cancel</button></div></div></div>

  <!-- Rating Modal -->
  <div id="ratingModal" class="modal-overlay" style="display:none;"><div class="modal-box rating-modal-box"><button class="modal-close-btn rating-modal-close"><i class="fas fa-times"></i></button><div class="modal-icon-wrap rating-icon-wrap"><i class="fas fa-star"></i></div><h3 class="modal-title" id="ratingProductName">Rate Product</h3><p class="modal-subtitle">Share your experience with this product</p><div class="star-rating"><span data-star="1" class="star">☆</span><span data-star="2" class="star">☆</span><span data-star="3" class="star">☆</span><span data-star="4" class="star">☆</span><span data-star="5" class="star">☆</span></div><input type="hidden" id="ratingValue" value="0"><div class="modal-body"><label class="form-label" for="ratingComment">Comment <span class="optional">(optional, max 300 characters)</span></label><textarea id="ratingComment" class="form-input" rows="4" maxlength="300" placeholder="Tell others about your experience with this product..." style="resize:vertical;"></textarea><div style="text-align:right;font-size:0.75rem;color:#aaa;margin-top:4px;"><span id="charCount">0</span>/300</div></div><div class="modal-actions"><button id="submitRatingBtn" class="btn-save"><i class="fas fa-paper-plane"></i> Submit Review</button></div></div></div>

  <script src="/lookgood/userActions/profile-base.js"></script>
  <script src="/lookgood/userActions/profile-account.js"></script>
  <script src="/lookgood/userActions/profile-wishlist.js"></script>
  <script src="/lookgood/userActions/profile-purchases.js"></script>
  <script src="/lookgood/userActions/cart-standalone.js"></script>

  <section><?php include 'footer.php'; ?></section>
</body>
</html>