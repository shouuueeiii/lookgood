<!-- NAVIGATION BAR -->
<nav class="navbar" id="mainNav">
  <div class="navbar-container">
    <a href="#top" class="navbar-logo">
      <img src="../Resources/Logos/lookgood-black.png" alt="LookGood Frames">
    </a>
    <button class="navbar-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <span class="navbar-toggle-icon"></span>
      <span class="navbar-toggle-icon"></span>
      <span class="navbar-toggle-icon"></span>
    </button>
    <div class="navbar-menu" id="navMenu">

      <ul class="navbar-nav">
        <li class="nav-item nav-item--dropdown">
          <a href="../Products/products-page.php" class="nav-link">Products</a>
          <div class="nav-dropdown">
            <div class="nav-dropdown-section">
              <a href="../Homepage/index.php#on-sale" class="nav-dropdown-link">On Sale!</a>
              <a href="../Homepage/index.php#best-sellers" class="nav-dropdown-link">Best Sellers</a>
            </div>
                <div class="nav-dropdown-divider"></div>
            <div class="nav-dropdown-section">
              <span class="nav-dropdown-label">Categories</span>
              <a href="../Products/products-page.php?filter=women" class="nav-dropdown-link">Frames for Women</a>
              <a href="../Products/products-page.php?filter=men" class="nav-dropdown-link">Frames for Men</a>
              <a href="../Products/products-page.php?filter=unisex" class="nav-dropdown-link">Unisex Frames</a>
            </div>
          </div>
        </li>
        <li class="nav-item"><a href="/lookgood/New%20folder/Homepage/index.php#brand-section" class="nav-link">About</a></li>
        <li class="nav-item"><a href="/lookgood/New%20folder/Homepage/index.php#contact-section" class="nav-link">Contact</a></li>
                <li class="nav-item"><a href="/lookgood/New%20folder/Homepage/index.php#faq" class="nav-link">FAQs</a></li>
        <li class="nav-item"><a href="/lookgood/New%20folder/Homepage/index.php" class="nav-link">Home</a></li>
      </ul>
      <div class="navbar-actions">
        <div class="navbar-search">
          <input type="text" class="navbar-search-input" placeholder="Search for frames..." id="searchInput">
          <button class="navbar-search-btn" aria-label="Search"><i class="fas fa-search"></i></button>
        </div>
        <button class="navbar-icon-btn" id="cartBtn" aria-label="Shopping Cart">
          <i class="fas fa-shopping-cart"></i>
          <span class="navbar-badge" id="cartBadge">0</span>
        </button>
        <div class="navbar-profile nav-item--dropdown">
          <button class="navbar-icon-btn" aria-label="User Profile"><i class="fas fa-user-circle"></i></button>
          <div class="nav-dropdown nav-dropdown--right">
            <?php if ($isLoggedIn): ?>
              <!-- Logged-in user -->
              <div class="nav-dropdown-section">
                <span class="nav-dropdown-label" style="color:#999; font-size:13px; padding: 8px 12px;">
                  <?= $username ?>
                </span>
                <a href="../Profile/myprofile.php" class="nav-dropdown-link">My Profile</a>
                <div class="nav-dropdown-divider"></div>
                <a href="../logout.php" class="nav-dropdown-link">Sign Out</a>
              </div>
            <?php else: ?>
              <!-- Guest -->
              <div class="nav-dropdown-section">
                <span class="nav-dropdown-label">User Profile</span>
                <a href="../Login/user-login.php" class="nav-dropdown-link">Log In</a>
                <a href="../Register/user-signup.php" class="nav-dropdown-link">Sign Up</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>