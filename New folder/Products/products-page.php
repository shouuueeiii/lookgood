<?php
require_once __DIR__ . '/../../session_bootstrap.php';
$isLoggedIn = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? 'user') !== 'admin');
$username = $isLoggedIn ? htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['email']) : '';
$profileDisplay = $isLoggedIn ? '' : 'display:none;';
$guestDisplay = $isLoggedIn ? 'display:none;' : '';
?>

<!DOCTYPE html>
<html>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Products — LookGood</title>


  <link rel="stylesheet" href="../../css/User/navbar.css">
  <link rel="stylesheet" href="../../css/User/index.css">
  <link rel="stylesheet" href="../../css/User/footer.css">
  <link rel="stylesheet" href="../../css/User/cart.css">
  <link rel="stylesheet" href="../../css/User/chatbot.css">
  <link rel="stylesheet" href="../../css/User/products-page.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


</head>
<body>

  <!-- ── NAVBAR ── -->
  <nav class="navbar" id="mainNav">
    <div class="navbar-container">
      <a href="../Homepage/index.php" class="navbar-logo">
        <img src="../Resources/Logos/lookgood-black.png" alt="LookGood Frames">
      </a>
      <button class="navbar-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
        <span class="navbar-toggle-icon"></span>
        <span class="navbar-toggle-icon"></span>
        <span class="navbar-toggle-icon"></span>
      </button>
      <div class="navbar-menu" id="navMenu">
          <ul class="navbar-nav">
          <!--
          <li class="nav-item nav-item--dropdown">
            <a href="../Products/products-page.html" class="nav-link">Products</a>
            <div class="nav-dropdown">
              <div class="nav-dropdown-section">
                <span class="nav-dropdown-label">Categories</span>
                <a href="../Products/products-page.html?filter=women" class="nav-dropdown-link">Frames for Women</a>
                <a href="../Products/products-page.html?filter=men" class="nav-dropdown-link">Frames for Men</a>
                <a href="../Products/products-page.html?filter=unisex" class="nav-dropdown-link">Unisex Frames</a>
              </div>
              <div class="nav-dropdown-divider"></div>
              <div class="nav-dropdown-section">
                <span class="nav-dropdown-label">Others</span>
                <a href="#best-selling" class="nav-dropdown-link">Best Selling</a>
              </div>
            </div>
          </li>-->
          <li class="nav-item"><a href="../Homepage/index.php#home" class="nav-link">Home</a></li>
          <li class="nav-item"><a href="../Homepage/index.php#faq" class="nav-link">FAQ</a></li>
          <li class="nav-item"><a href="../Homepage/index.php#team-section" class="nav-link">About</a></li>
          <li class="nav-item"><a href="../Homepage/index.php#contact" class="nav-link">Contact</a></li>
          
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

              <!-- Guest: not logged in -->
              <div class="nav-dropdown-section" id="profileGuestLinks" style="<?= $guestDisplay ?>">
                <span class="nav-dropdown-label">User Profile</span>
                <a href="../Login/user-login.php" class="nav-dropdown-link">Log In</a>
                <a href="../Register/user-signup.php" class="nav-dropdown-link">Sign Up</a>
              </div>

              <!-- User: logged in -->
              <div class="nav-dropdown-section" id="profileUserLinks" style="<?= $profileDisplay ?>">
                <span class="nav-dropdown-label" id="profileUsername" style="color:#999; font-size:13px; padding: 8px 12px;"><?= $username ?></span>
                <a href="../Profile/myprofile.php" class="nav-dropdown-link">My Profile</a>
                <div class="nav-dropdown-divider"></div>
                <a href="../logout.php" class="nav-dropdown-link" id="signOutBtn">Sign Out</a>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- ── PRODUCTS SECTION ── -->
  <main>
    <section class="products-section" aria-labelledby="collectionHeading">
      <div class="products-container">

        <header class="products-header">
          <h1 class="collection-header" id="collectionHeading">Our Collection</h1>
          <p>Discover the perfect frames for your style</p>
        </header>

        <!-- FILTER BAR -->
        <div class="filter-bar" role="group" aria-label="Filter products by category">
          <div class="filter-track">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="men">Men</button>
            <button class="filter-btn" data-filter="women">Women</button>
            <button class="filter-btn" data-filter="unisex">Unisex</button>
            <span class="filter-indicator" aria-hidden="true"></span>
          </div>
        </div>

        <!-- PRODUCT GRID — rendered by products-page.js -->
        <div id="productsGrid" role="list"></div>

      </div>
    </section>
  </main>

  <!-- ── FOOTER ── -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <img src="../Resources/Logos/lookgood-black.png" alt="LookGood" class="footer-logo">
        <p class="footer-tagline">Looking good has never been this clear.</p>
        <div class="footer-socials">
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
          <a href="#" aria-label="TikTok"><i class="fab fa-tiktok" aria-hidden="true"></i></a>
          <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest-p" aria-hidden="true"></i></a>
        </div>
      </div>

      <div class="footer-links">
        <div class="footer-column">
          <h4 class="footer-heading">Shop</h4>
          <ul class="footer-list">
            <li><a href="../Products/products-page.php?filter=women">Frames for Women</a></li>
            <li><a href="../Products/products-page.php?filter=men">Frames for Men</a></li>
            <li><a href="../Products/products-page.php?filter=unisex">Unisex Frames</a></li>
            <li><a href="../Products/products-page.php?filter=all">Best Sellers</a></li>
            <li><a href="../Products/products-page.php">New Arrivals</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h4 class="footer-heading">Help</h4>
          <ul class="footer-list">
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Shipping Info</a></li>
            <li><a href="#">Returns & Exchanges</a></li>
            <li><a href="#">Frame Size Guide</a></li>
            <li><a href="#">Contact Us</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h4 class="footer-heading">Company</h4>
          <ul class="footer-list">
            <li><a href="#">About LookGood</a></li>
            <li><a href="#">Store Locations</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Press</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-newsletter">
        <h4 class="footer-newsletter-title">Stay in the Loop and Look Good</h4>
        <p class="footer-newsletter-text">Get exclusive deals, style tips, and new arrivals straight to your inbox.</p>
        <div class="newsletter-form">
          <input type="email" class="newsletter-input" placeholder="your@email.com" aria-label="Email for newsletter">
          <button type="button" class="newsletter-btn">Subscribe</button>
        </div>
        <p class="newsletter-note">No spam, ever. Unsubscribe anytime.</p>
        <div class="footer-contact">
          <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Greenbelt 5, Makati City, Philippines</p>
          <p><i class="fas fa-envelope" aria-hidden="true"></i> lookgoodframes@gmail.com</p>
          <p><i class="fas fa-phone" aria-hidden="true"></i> +63 917 123 4567</p>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2025 LookGood Frames. All rights reserved.</p>
    </div>
  </footer>

  <script src="../../Actions/User/products-page.js"></script>
  <script src="../../Actions/User/cart-standalone.js"></script>
  <script>
    window.LG_CHAT_USER = <?= json_encode([
      'isLoggedIn' => $isLoggedIn,
      'userId' => $_SESSION['user_id'] ?? null,
      'firstName' => $_SESSION['first_name'] ?? '',
      'lastName' => $_SESSION['last_name'] ?? '',
      'email' => $_SESSION['email'] ?? '',
      'role' => $_SESSION['role'] ?? ''
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="../../Actions/User/chatbot.js?v=20260412a"></script>
  <script src="../../../Homepage/index.js"></script>
</body>
</html>