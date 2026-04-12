<?php
require_once __DIR__ . '/../../session_bootstrap.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
  $_SESSION['redirect_after_login'] = '../Profile/myprofile.php';
  header('Location: ../Login/user-login.php');
  exit();
}
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

  
  <link rel="stylesheet" href="../../css/User/navbar.css">
  <link rel="stylesheet" href="../../css/User/footer.css">
    <link rel="stylesheet" href="../../css/User/cart.css">
  <link rel="stylesheet" href="../../css/User/profile-base.css">
  <link rel="stylesheet" href="../../css/User/profile-account.css">
  <link rel="stylesheet" href="../../css/User/profile-purchases.css">
  <link rel="stylesheet" href="../../css/User/profile-wishlist.css">
</head>
<body>

  <!-- Navbar (same as original) -->
  <nav class="navbar" id="mainNav">
    <div class="navbar-container">
      <a href="../Homepage/index.php" class="navbar-logo">
        <img src="../Resources/Logos/lookgood-black.png" alt="LookGood Frames">
      </a>
      <button class="navbar-toggle" id="navToggle" aria-label="Toggle navigation">
        <span class="navbar-toggle-icon"></span><span class="navbar-toggle-icon"></span><span class="navbar-toggle-icon"></span>
      </button>
      <div class="navbar-menu" id="navMenu">
        <ul class="navbar-nav">
          <li class="nav-item nav-item--dropdown">
            <a href="../Homepage/index.php" class="nav-link">Home</a>
          </li>
        </ul>
        <div class="navbar-actions">
          <div class="navbar-search">
            <input type="text" class="navbar-search-input" placeholder="Search for frames..." id="searchInput">
            <button class="navbar-search-btn"><i class="fas fa-search"></i></button>
          </div>
          <button class="navbar-icon-btn" id="cartBtn"><i class="fas fa-shopping-cart"></i><span class="navbar-badge" id="cartBadge">0</span></button>
          <div class="navbar-profile nav-item--dropdown">
            <button class="navbar-icon-btn"><i class="fas fa-user-circle"></i></button>
            <div class="nav-dropdown nav-dropdown--right">
              <div class="nav-dropdown-section">
                <span class="nav-dropdown-label">My Account</span>
                <a href="myprofile.php" class="nav-dropdown-link">My Profile</a>
                <a href="../logout.php" class="nav-dropdown-link">Sign Out</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <main class="profile-main">
    <!-- Profile Header -->
    <div class="profile-hero">
      <div class="profile-hero-inner">
        <div class="profile-avatar" id="profileAvatar">
          <i class="fas fa-glasses" style="font-size: 2.5rem;"></i>
        </div>
        <div class="profile-hero-info">
          <h1 class="profile-hero-name" id="heroName">Juan dela Cruz</h1>
          <p class="profile-hero-username" id="heroUsername">@onedelacruz</p>
          <span class="profile-member-badge"><i class="fas fa-star"></i> Member since Feb 2026</span>
        </div>
      </div>
    </div>

    <!-- Tab Navigation -->
    <div class="profile-tabs-wrapper">
      <nav class="profile-tabs" role="tablist">
        <button class="profile-tab active" data-tab="account"><i class="fas fa-user"></i> My Account</button>
        <button class="profile-tab" data-tab="wishlist"><i class="fas fa-heart"></i> My Wishlist <span class="tab-badge" id="wishlistTabCount">0</span></button>
        <button class="profile-tab" data-tab="purchases"><i class="fas fa-box"></i> My Purchases</button>
      </nav>
    </div>

    <div class="profile-content">
      <!-- ACCOUNT PANEL -->
      <section id="panel-account" class="profile-panel active">
        <div class="panel-grid">
          <!-- Profile Card -->
          <div class="profile-card" id="card-profile">
            <div class="card-header">
              <div class="card-header-left"><i class="fas fa-id-card card-icon"></i><h2 class="card-title">My Profile</h2></div>
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
              <div class="form-actions"><button type="submit" class="btn-save"><i class="fas fa-check"></i> Save Changes</button><button type="button" class="btn-cancel" data-target="profile-form">Cancel</button></div>
            </form>
          </div>

          <!-- Address Card -->
          <div class="profile-card" id="card-address">

            <div class="card-header">
              <div class="card-header-left"><i class="fas fa-map-marker-alt card-icon"></i><h2 class="card-title">Shipping Address</h2></div>
              <button class="btn-edit" data-target="address-form"><i class="fas fa-pen"></i> Edit</button>
            </div>

            <div class="card-view" id="address-view">

              <div class="info-row"><span class="info-label">Address Line 1</span><span class="info-value" id="view-addr1">123 Rizal Street</span></div>
              <div class="info-row"><span class="info-label">Address Line 2</span><span class="info-value" id="view-addr2">Barangay Poblacion</span></div>
              <div class="info-row"><span class="info-label">City</span><span class="info-value" id="view-city">Makati City</span></div>
              <div class="info-row"><span class="info-label">Province</span><span class="info-value" id="view-province">Metro Manila</span></div>
              <div class="info-row"><span class="info-label">ZIP Code</span><span class="info-value" id="view-zip">1210</span></div>
              <div class="info-row"><span class="info-label">Region</span><span class="info-value" id="view-region">NCR</span></div>
            
            </div>
            
            <form class="card-form hidden" id="address-form" novalidate>

              <div class="form-group"><label class="form-label">Address Line 1</label><input class="form-input" type="text" id="addr1" value="123 Rizal Street" placeholder="House/Unit No., Street"><span class="form-error" id="err-addr1"></span></div>
              <div class="form-group"><label class="form-label">Address Line 2 (optional)</label><input class="form-input" type="text" id="addr2" value="Barangay Poblacion" placeholder="Barangay, Subdivision, Landmark"></div>
              <div class="form-row two-col"><div class="form-group"><label class="form-label">City</label><input class="form-input" type="text" id="city" value="Makati City" placeholder="City/Municipality"><span class="form-error" id="err-city"></span></div><div class="form-group"><label class="form-label">Province</label><input class="form-input" type="text" id="province" value="Metro Manila" placeholder="Province"><span class="form-error" id="err-province"></span></div></div>
              <div class="form-row two-col">

                <div class="form-group">
                  <label class="form-label">ZIP Code</label>
                  <input class="form-input" type="text" id="zip" value="1210" maxlength="4" placeholder="e.g., 1210" required>
                  <span class="form-error" id="err-zip"></span>
                </div>

                <div class="form-group">
                  <label class="form-label">Region</label>
                  <select class="form-input" id="region" name="region" required>
                    <option value="">Select Region</option>
                    <option value="NCR">NCR — National Capital Region</option>
                    <option value="CAR">CAR — Cordillera Administrative Region</option>
                    <option value="I">Region I — Ilocos Region</option>
                    <option value="II">Region II — Cagayan Valley</option>
                    <option value="III">Region III — Central Luzon</option>
                    <option value="IV-A">Region IV-A — CALABARZON</option>
                    <option value="IV-B">Region IV-B — MIMAROPA</option>
                    <option value="V">Region V — Bicol Region</option>
                    <option value="VI">Region VI — Western Visayas</option>
                    <option value="VII">Region VII — Central Visayas</option>
                    <option value="VIII">Region VIII — Eastern Visayas</option>
                    <option value="IX">Region IX — Zamboanga Peninsula</option>
                    <option value="X">Region X — Northern Mindanao</option>
                    <option value="XI">Region XI — Davao Region</option>
                    <option value="XII">Region XII — SOCCSKSARGEN</option>
                    <option value="XIII">Region XIII — Caraga</option>
                    <option value="BARMM">BARMM — Bangsamoro Autonomous Region</option>
                  </select>
                  <span class="form-error" id="err-region"></span>
                </div>
              </div>
              <div class="form-actions"><button type="submit" class="btn-save"><i class="fas fa-check"></i> Save Address</button><button type="button" class="btn-cancel" data-target="address-form">Cancel</button></div>
            </form>
          </div>
        </div>
      </section>

      <!-- WISHLIST PANEL -->
      <section id="panel-wishlist" class="profile-panel">
        <div class="panel-section-header"><h2 class="panel-section-title">My Wishlist</h2><p class="panel-section-sub">Items you've saved from the store</p></div>
        <div class="empty-state" id="wishlist-empty" style="display: none;"><i class="fas fa-heart-broken empty-state-icon"></i><p class="empty-state-text">Your wishlist is empty.</p><a href="../Products/products-page.html" class="btn-browse"><i class="fas fa-glasses"></i> Browse Frames</a></div>
        <div class="wishlist-grid" id="wishlistGrid"></div>
      </section>

      <!-- PURCHASES PANEL -->
      <section id="panel-purchases" class="profile-panel">

        <div class="panel-section-header"><h2 class="panel-section-title">My Purchases</h2><p class="panel-section-sub">Track all your orders here</p></div>
        <div class="purchase-tabs-wrapper">
          <nav class="purchase-tabs" role="tablist">
            <button class="purchase-tab active" data-ptab="paid">Paid <span class="ptab-badge" id="count-paid">0</span></button>
            <button class="purchase-tab" data-ptab="to-ship">To Ship <span class="ptab-badge" id="count-to-ship">0</span></button>
            <button class="purchase-tab" data-ptab="to-receive">To Receive <span class="ptab-badge" id="count-to-receive">0</span></button>
            <button class="purchase-tab" data-ptab="delivered">Delivered <span class="ptab-badge" id="count-delivered">0</span></button>
            <button class="purchase-tab" data-ptab="completed">Completed <span class="ptab-badge" id="count-completed">0</span></button>
            <button class="purchase-tab" data-ptab="cancelled">Cancelled <span class="ptab-badge" id="count-cancelled">0</span></button>
          </nav>
        </div>

        <div class="purchase-panel active" id="ppanel-paid"><div class="empty-state" id="empty-paid"><i class="fas fa-check-circle"></i><p>No confirmed orders.</p></div><div id="list-paid" class="orders-list"></div></div>
        <div class="purchase-panel" id="ppanel-to-ship"><div class="empty-state" id="empty-to-ship"><i class="fas fa-box"></i><p>No orders to ship.</p></div><div id="list-to-ship" class="orders-list"></div></div>
        <div class="purchase-panel" id="ppanel-to-receive"><div class="empty-state" id="empty-to-receive"><i class="fas fa-truck"></i><p>No orders out for delivery.</p></div><div id="list-to-receive" class="orders-list"></div></div>
        <div class="purchase-panel" id="ppanel-delivered"><div class="empty-state" id="empty-delivered"><i class="fas fa-check-circle"></i><p>No delivered orders.</p></div><div id="list-delivered" class="orders-list"></div></div>
        <div class="purchase-panel" id="ppanel-completed"><div class="empty-state" id="empty-completed"><i class="fas fa-history"></i><p>No completed orders.</p></div><div id="list-completed" class="orders-list"></div></div>
        <div class="purchase-panel" id="ppanel-cancelled"><div class="empty-state" id="empty-cancelled"><i class="fas fa-times-circle"></i><p>No cancelled orders.</p></div><div id="list-cancelled" class="orders-list"></div></div>
      
      </section>
    </div>
  </main>

  <footer class="footer"><!-- footer content --></footer>

  <!-- Scripts -->
  <script src="../../Actions/User/profile-base.js"></script>
  <script src="../../Actions/User/profile-account.js"></script>
  <script src="../../Actions/User/profile-wishlist.js"></script>
  <script src="../../Actions/User/profile-purchases.js"></script>
  <script src="../../Actions/User/cart-standalone.js"></script>

  <!-- Rating Modal (overlay - hidden until called) -->
<div id="ratingModal" class="rating-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; justify-content: center; align-items: center;">
  <div class="rating-modal-content" style="background: white; border-radius: 20px; max-width: 500px; width: 90%; padding: 2rem; position: relative;">
    <span class="rating-modal-close" style="position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; cursor: pointer;">&times;</span>
    <h3 id="ratingProductName" style="margin-bottom: 1rem;">Rate Product</h3>
    
    <!-- Star rating -->
    <div class="star-rating" style="display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; font-size: 2rem;">
      <span data-star="1" class="star">☆</span>
      <span data-star="2" class="star">☆</span>
      <span data-star="3" class="star">☆</span>
      <span data-star="4" class="star">☆</span>
      <span data-star="5" class="star">☆</span>
    </div>
    <input type="hidden" id="ratingValue" value="0">
    
    <!-- Comment -->
    <label for="ratingComment">Comment (optional, max 600 characters)</label>
    <textarea id="ratingComment" rows="4" maxlength="600" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid #ccc; margin-top: 0.5rem;"></textarea>
    <div style="text-align: right; font-size: 0.75rem; color: #888;"><span id="charCount">0</span>/600</div>
    
    <button id="submitRatingBtn" style="background: #2ecc71; color: white; border: none; padding: 0.75rem; width: 100%; border-radius: 8px; margin-top: 1rem; cursor: pointer;">Submit Review</button>
  </div>
</div>
</body>
</html>