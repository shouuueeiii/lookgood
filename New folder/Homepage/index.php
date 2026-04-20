<?php
require_once __DIR__ . '/../../session_bootstrap.php';
$isLoggedIn = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? 'user') !== 'admin');
$username = $isLoggedIn ? htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['email']) : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LookGood Frames - Homepage</title>

  <link rel="stylesheet" href="../../css/User/navbar.css"> 
  <link rel="stylesheet" href="../../css/User/index.css">
  <link rel="stylesheet" href="../../css/User/cart.css">
  <link rel="stylesheet" href="../../css/User/chatbot.css">
  <link rel="stylesheet" href="best-sellers.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&family=DM+Sans:wght@400;500;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body id="top">

  
  <section>
    <?php include '../navbar.php'; ?>
  </section>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <div class="hero-text">
        <h1 class="hero-title">Look Good in Every Frame</h1>
        <p class="hero-tagline">Looking good has never been this clear.</p>
      </div>
      <div class="hero-cta">
        <a href="../Products/products-page.php" class="btn--hero">Find your frame</a>
      </div>
      <div class="hero-carousel" id="glassesCarousel">
        <div class="hero-carousel-track">
          <div class="hero-carousel-item hero-carousel-item--active">
            <img src="../Resources/Images/glasses1.png" alt="Glasses 1">
          </div>
          <div class="hero-carousel-item">
            <img src="../Resources/Images/glasses2.png" alt="Glasses 2">
          </div>
          <div class="hero-carousel-item">
            <img src="../Resources/Images/glasses3.png" alt="Glasses 3">
          </div>
          <div class="hero-carousel-item">
            <img src="../Resources/Images/glasses4.png" alt="Glasses 3">
          </div>
          <div class="hero-carousel-item">
            <img src="../Resources/Images/glasses5.png" alt="Glasses 3">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Best-Sellers Section -->
  <section class="best-sellers" id="best-sellers">
    <?php include 'best-sellers.php'; ?>
  </section>

  <!-- On Sale Section -->
  <section class="on-sale" id="on-sale">
    <?php include 'on-sale.php'; ?>
  </section>


  <!-- FAQ Section -->
  <section class="faq" id="faq">
    <?php include 'faq.php'; ?>
  </section>

    
  <!-- Contact Form Section -->
  <section class="contact-section" id="contact-section">
    <?php include 'contact-form.php'; ?>  
  </section>
  
  <!-- About LookGood Section -->
  <section class="brand-section" id="brand-section">
    <?php include 'brand-section.php'; ?>  
  </section>

  <!-- About Team Section -->
  <section class="team" id="team-section">
    <?php include 'team.php'; ?>  
  </section>

  <!-- Footer -->
  <section>
    <?php include '../footer.php'; ?>
  </section>

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

  <script src="best-sellers.js" defer></script>
  <script src="../../Actions/User/chatbot.js?v=20260412a"></script>
  <script src="../../Actions/User/cart-standalone.js"></script>
  <script src="../../Actions/User/index.js"></script>
</body>

</html>