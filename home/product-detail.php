<?php
require_once __DIR__ . '/../session_bootstrap.php';
$isLoggedIn = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? 'user') !== 'admin');
$username = $isLoggedIn ? htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['email']) : '';
$profileDisplay = $isLoggedIn ? '' : 'display:none;';
$guestDisplay = $isLoggedIn ? 'display:none;' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Details — LookGood</title>


  <link rel="stylesheet" href="/lookgood/css/User/navbar.css">
  <link rel="stylesheet" href="/lookgood/css/User/index.css">
  <link rel="stylesheet" href="/lookgood/css/User/footer.css">
  <link rel="stylesheet" href="/lookgood/css/User/cart.css">
  <link rel="stylesheet" href="/lookgood/css/User/products-page.css">
  <link rel="stylesheet" href="/lookgood/css/User/product-detail.css">


  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=DM+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

  <!-- ── NAVBAR ── -->
  <nav class="navbar" id="mainNav">
    <div class="navbar-container">
      <a href="/lookgood/home/index.php" class="navbar-logo">
        <img src="/lookgood/home/Resources/Logos/lookgood-black.png" alt="LookGood Frames">
      </a>
      <button class="navbar-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
        <span class="navbar-toggle-icon"></span>
        <span class="navbar-toggle-icon"></span>
        <span class="navbar-toggle-icon"></span>
      </button>
      <div class="navbar-menu" id="navMenu">
          <ul class="navbar-nav">
          <li class="nav-item"><a href="/lookgood/home/index.php#home" class="nav-link">Home</a></li>
          <li class="nav-item"><a href="/lookgood/home/index.php#faq" class="nav-link">FAQ</a></li>
          <li class="nav-item"><a href="/lookgood/home/index.php#brand-section" class="nav-link">About</a></li>
          <li class="nav-item"><a href="/lookgood/home/index.php#contact-section" class="nav-link">Contact</a></li>
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
                <a href="user-login.php" class="nav-dropdown-link">Log In</a>
                <a href="user-signup.php" class="nav-dropdown-link">Sign Up</a>
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

  <!-- ── BREADCRUMB ── -->
  <div class="breadcrumb-container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <ol>
        <li><a href="index.php">Home</a></li>
        <li><a href="products-page.php">Products</a></li>
        <li> <span id="breadcrumbCategory">Category</span></li>
        <li aria-current="page"><span id="breadcrumbProduct">Product Name</span></li>
      </ol>
    </nav>
  </div>

  <main>
    <!-- PRODUCT DETAIL SECTION -->
    <section class="product-detail-section">
      <div class="product-detail-container">

        <div class="product-gallery">
          <!-- Thumbnail strip (walang buttons) -->
          <div class="thumbnail-strip no-zoomImage" id="thumbnailStrip"></div>
          <!-- Main image (walang navigation buttons) -->
          <div class="main-image-wrapper">
            <img id="mainProductImage" src="" alt="" class="main-product-image">
            <span class="image-counter" id="imageCounter">1 / 3</span>
          </div>
        </div>

        <!-- Product Info -->
        <div class="product-detail-info">
          <div class="product-badges">
            <span class="product-badge product-badge--category" id="productCategory"></span>
            <span class="product-badge product-badge--stock"><i class="fas fa-box-open"></i> <span
                id="stockCount">0</span> in stock</span>
          </div>

          <div class="product-name-row">
            <h1 class="product-detail-name" id="productName"></h1>
            <div class="name-row-actions">
              <span class="sold-count" id="soldCount"><i class="fas fa-fire"></i> <span id="soldNumber">0</span>
                sold</span>
              <button class="btn-wishlist" id="wishlistBtn" aria-label="Add to Wishlist"><i
                  class="far fa-heart"></i></button>
            </div>
          </div>
          <div class="product-detail-price" id="productPrice"></div>
          <div class="product-detail-description" id="productDescription"></div>

          <div class="product-detail-features">
            <div class="feature-item"><i class="fas fa-ruler-combined"></i><span><strong>Frame Width:</strong> <span
                  id="frameWidth">-</span> mm</span></div>
            <div class="feature-item"><i class="fas fa-arrows-alt-v"></i><span><strong>Frame Height:</strong> <span
                  id="frameHeight">-</span> mm</span></div>
            <div class="feature-item"><i class="fas fa-arrow-right"></i><span><strong>Temple Length:</strong> <span
                  id="templeLength">-</span> mm</span></div>
            <div class="feature-item"><i class="fas fa-circle"></i><span><strong>Lens Width:</strong> <span
                  id="lensWidth">-</span> mm</span></div>
            <div class="feature-item"><i class="fas fa-weight-hanging"></i><span><strong>Material:</strong> <span
                  id="frameMaterial">-</span></span></div>
            <div class="feature-item"><i class="fas fa-palette"></i><span><strong>Frame Color:</strong> <span
              id="frameColor">-</span></span></div>
          </div>

          <!-- Qty with stock info -->
          <div class="product-detail-qty">
            <span class="qty-label">Quantity:</span>
            <div class="qty-selector">
              <button class="qty-btn qty-btn--large" id="qtyDecrease">−</button>
              <input type="number" id="qtyInput" value="1" min="1" max="100" class="qty-input">
              <button class="qty-btn qty-btn--large" id="qtyIncrease">+</button>
            </div>
            <span class="stock-remaining" id="stockRemainingMsg"></span>
          </div>

          <div class="product-detail-actions">
            <button class="btn btn--secondary btn--large" id="addToCartBtn"><i class="fas fa-shopping-cart"></i> Add to
              Cart</button>
            <button class="btn btn--primary btn--large" id="buyNowBtn"><i class="fas fa-bolt"></i> Buy Now</button>
          </div>

          <div class="shipping-info">
            <div class="shipping-item"><i class="fas fa-undo-alt"></i><span><strong>30-Day Returns</strong> — Easy &
                Free</span></div>
            <div class="shipping-item"><i class="fas fa-shield-alt"></i><span><strong>1-Year Warranty</strong> on all
                frames</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- CUSTOMER REVIEWS (moved before related products) -->
    <section class="reviews-section">
      <div class="reviews-container">
        <div class="reviews-header-row">
          <div class="reviews-title-block">
            <h2 class="reviews-title">Customer Reviews</h2>
            <div class="reviews-summary">
              <span class="reviews-avg-score" id="reviewsAvgScore">4.7</span>
              <div class="reviews-avg-right">
                <div class="stars-display" id="reviewsStars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                    class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                <span class="reviews-count-label" id="reviewsCountLabel">Based on 128 reviews</span>
              </div>
            </div>
          </div>
          <div class="rating-breakdown" id="ratingBreakdown"></div>
        </div>
        <div class="reviews-grid" id="reviewsGrid"></div>
        <div class="reviews-load-more"><button class="btn-load-more" id="loadMoreReviews">Load More Reviews</button>
        </div>
      </div>
    </section>

    <!-- YOU MAY ALSO LIKE (after reviews) -->
    <section class="related-products-section">
      <div class="related-container">
        <div class="section-header-row">
          <h2 class="related-title">You May Also Like</h2>
        </div>
        <div class="related-grid" id="relatedProductsGrid"></div>
      </div>
    </section>
  </main>

    <?php include 'footer.php'; ?>
 

  <!-- Toast Notification -->
  <div id="toastMessage" class="toast-message">
    <span id="toastText"></span>
  </div>

  <script src="/lookgood/userActions/zoomImage.js"></script>
  <script src="/lookgood/userActions/index.js"></script>
  <script>
    window.LG_CHAT_USER = <?= json_encode([
      'isLoggedIn' => $isLoggedIn,
      'userId'     => $_SESSION['user_id'] ?? null,
      'firstName'  => $_SESSION['first_name'] ?? '',
      'lastName'   => $_SESSION['last_name'] ?? '',
      'email'      => $_SESSION['email'] ?? '',
      'role'       => $_SESSION['role'] ?? ''
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="/lookgood/userActions/cart-standalone.js"></script>
  <script src="/lookgood/userActions/product-detail.js"></script>
</body>

</html>