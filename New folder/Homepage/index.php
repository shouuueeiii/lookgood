<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
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
  <link rel="stylesheet" href="../../css/User/footer.css">
  <link rel="stylesheet" href="../../css/User/cart.css">
  <link rel="stylesheet" href="../../css/User/chatbot.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&family=DM+Sans:wght@400;500;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

  <!-- NAVIGATION BAR -->
  <nav class="navbar" id="mainNav">
    <div class="navbar-container">
      <a href="#" class="navbar-logo">
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
                <span class="nav-dropdown-label">Categories</span>
                <a href="../Products/products-page.php?filter=women" class="nav-dropdown-link">Frames for Women</a>
                <a href="../Products/products-page.php?filter=men" class="nav-dropdown-link">Frames for Men</a>
                <a href="../Products/products-page.php?filter=unisex" class="nav-dropdown-link">Unisex Frames</a>
              </div>
              <div class="nav-dropdown-divider"></div>
              <div class="nav-dropdown-section">
                <span class="nav-dropdown-label">Others</span>
                <a href="#best-selling" class="nav-dropdown-link">Best Selling</a>
              </div>
            </div>
          </li>
          <li class="nav-item"><a href="#faq" class="nav-link">FAQ</a></li>
          <li class="nav-item"><a href="#team-section" class="nav-link">About</a></li>
          <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
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
        </div>
      </div>
    </div>
  </section>

  <!-- Brand Value Section -->
  <section class="brand-value">
    <div class="brand-value-container">
      <div class="brand-value-image">
        <img src="https://images.unsplash.com/photo-1574258495973-f010dfbb5371?w=900&auto=format&fit=crop&q=80"
          alt="Stylish eyeglasses on display">
      </div>
      <div class="brand-value-content">
        <span class="eyebrow">Why LookGood</span>
        <h2 class="section-title">Look Good.<br>Feel Good.<br>See Clearly.</h2>
        <div class="carousel" id="bvCarousel">
          <div class="carousel-track-container">
            <div class="carousel-track" id="bvSlides">
              <div class="carousel-slide">
                <i class="fas fa-glasses carousel-slide-icon"></i>
                <p class="carousel-slide-text">Every frame is designed to flatter every face shape — from oval to
                  square, heart to round. Style isn't one-size-fits-all.</p>
                <span class="carousel-slide-label">Designed for Every Face</span>
              </div>
              <div class="carousel-slide">
                <i class="fas fa-gem carousel-slide-icon"></i>
                <p class="carousel-slide-text">Premium acetate, titanium, and TR90 — crafted to last. We work directly
                  with artisans so you get luxury quality at fair prices.</p>
                <span class="carousel-slide-label">Premium Materials, Fair Prices</span>
              </div>
              <div class="carousel-slide">
                <i class="fas fa-feather-alt carousel-slide-icon"></i>
                <p class="carousel-slide-text">So light you forget you're wearing them. Engineered for all-day comfort
                  whether you're at the office, outdoors, or out at night.</p>
                <span class="carousel-slide-label">Lightweight & All-Day Comfort</span>
              </div>
              <div class="carousel-slide">
                <i class="fas fa-shield-alt carousel-slide-icon"></i>
                <p class="carousel-slide-text">Every pair comes with a 1-year warranty, free adjustments for life, and a
                  30-day no-questions-asked return policy.</p>
                <span class="carousel-slide-label">Backed by Our Guarantee</span>
              </div>
            </div>
          </div>
          <div class="carousel-controls">
            <button class="carousel-dot carousel-dot--active" data-index="0" aria-label="Slide 1"></button>
            <button class="carousel-dot" data-index="1" aria-label="Slide 2"></button>
            <button class="carousel-dot" data-index="2" aria-label="Slide 3"></button>
            <button class="carousel-dot" data-index="3" aria-label="Slide 4"></button>
            <div class="carousel-arrows">
              <button class="carousel-arrow" id="bvPrev" aria-label="Previous"><i
                  class="fas fa-chevron-left"></i></button>
              <button class="carousel-arrow" id="bvNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section class="about" id="about">
    <div class="about-container">
      <div class="about-image">
        <div class="about-image-wrapper">
          <img class="about-img" src="../Resources/Images/about-img.png" alt="About LookGood Frames">
          <div class="about-image-accent"></div>
        </div>
      </div>
      <div class="about-content">
        <span class="eyebrow">Our Story</span>
        <h2 class="section-title">We Believe Clarity Should Be Beautiful</h2>
        <p class="about-text">LookGood Frames was born out of a simple frustration — why should prescription glasses be
          boring? Founded in 2020 in the heart of Manila, we set out to prove that seeing clearly and looking great
          aren't mutually exclusive.</p>
        <p class="about-text">We work directly with independent craftsmen and premium material suppliers to bring you
          frames that are as durable as they are stylish — at a price that doesn't make you squint.</p>
        <div class="about-stats">
          <div class="stat">
            <span class="stat-number">10,000+</span>
            <span class="stat-label">Happy Customers</span>
          </div>
          <div class="stat">
            <span class="stat-number">40+</span>
            <span class="stat-label">Frame Styles</span>
          </div>
          <div class="stat">
            <span class="stat-number">4</span>
            <span class="stat-label">Showrooms</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="contact" id="contact">
    <div class="contact-container">
      <div class="contact-info">
        <span class="eyebrow">Get in Touch</span>
        <h2 class="section-title">We'd Love to Hear From You</h2>
        <p class="section-subtitle">Have a question about frames, orders, or prescriptions? Drop us a message and we'll
          get back to you within 24 hours.</p>
        <div class="contact-details">
          <div class="contact-detail">
            <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div class="contact-detail-content">
              <strong>Flagship Store</strong>
              <p>Greenbelt 5, Makati City, Philippines</p>
            </div>
          </div>
          <div class="contact-detail">
            <div class="contact-icon"><i class="fas fa-envelope"></i></div>
            <div class="contact-detail-content">
              <strong>Email Us</strong>
              <p>lookgoodframes@gmail.com</p>
            </div>
          </div>
          <div class="contact-detail">
            <div class="contact-icon"><i class="fas fa-phone"></i></div>
            <div class="contact-detail-content">
              <strong>Call Us</strong>
              <p>+63 917 123 4567</p>
            </div>
          </div>
          <div class="contact-detail">
            <div class="contact-icon"><i class="fas fa-clock"></i></div>
            <div class="contact-detail-content">
              <strong>Store Hours</strong>
              <p>Daily, 10:00 AM – 9:00 PM</p>
            </div>
          </div>
        </div>
      </div>
      <div class="contact-form-wrapper">
        <form class="contact-form">
          <div class="form-group">
            <label for="contactName">Full Name</label>
            <input type="text" id="contactName" class="form-input" placeholder="e.g. Sofia Reyes">
          </div>
          <div class="form-group">
            <label for="contactEmail">Email Address</label>
            <input type="email" id="contactEmail" class="form-input" placeholder="your@email.com">
          </div>
          <div class="form-group">
            <label for="contactSubject">Subject</label>
            <select id="contactSubject" class="form-select">
              <option value="" disabled selected>Select a topic</option>
              <option value="order">Order Inquiry</option>
              <option value="prescription">Prescription Help</option>
              <option value="returns">Returns & Exchanges</option>
              <option value="product">Product Information</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="contactMessage">Message</label>
            <textarea id="contactMessage" class="form-textarea" rows="5"
              placeholder="Tell us how we can help you..."></textarea>
          </div>
          <button type="button" class="btn btn--primary btn--full" onclick="handleContactSubmit()">Send Message <i
              class="fas fa-paper-plane"></i></button>
          <div class="form-success" id="contactSuccess"><i class="fas fa-check-circle"></i> Thank you! We'll get back to
            you within 24 hours.</div>
        </form>
      </div>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq" id="faq">
    <div class="faq-container">
      <div class="faq-header">
        <h2 class="section-title">Frequently Asked Questions</h2>
        <p class="section-subtitle">Everything you need to know about LookGood Frames</p>
      </div>
      <div class="faq-grid">

        <div class="faq-column">
          <div class="faq-item">
            <button class="faq-question">What is your return and exchange policy?</button>
            <div class="faq-answer">
              <p>30-day returns, free exchanges, free return shipping, and a prescription guarantee. Frames must be in
                original condition.</p>
            </div>
          </div>
          <div class="faq-item">
            <button class="faq-question faq-question--active">How long does shipping take?</button>
            <div class="faq-answer">
              <p> Standard Shipping takes 5-7 days to arrive. We offer same day delivery to areas inside Metro Manila.
              </p>
            </div>
          </div>
          <div class="faq-item">
            <button class="faq-question">What payment methods do you accept?</button>
            <div class="faq-answer">
              <p>Visa, Mastercard, GCash, PayMaya, GrabPay, major banks, installment plans, and Cash on Delivery in
                Metro Manila.</p>
            </div>
          </div>
        </div>

        <div class="faq-column">
          <div class="faq-item">
            <button class="faq-question">Do your frames come with a warranty?</button>
            <div class="faq-answer">
              <p>Yes! 1-year manufacturer warranty, 90-day lens accuracy guarantee, and free lifetime adjustments at
                partner shops.</p>
            </div>
          </div>
          <div class="faq-item">
            <button class="faq-question">What materials are your frames made from?</button>
            <div class="faq-answer">
              <p>Acetate, Titanium, Stainless Steel, and TR90. All nickel-free and hypoallergenic.</p>
            </div>
          </div>
          <div class="faq-item">
            <button class="faq-question">Do you have physical stores I can visit?</button>
            <div class="faq-answer">
              <p>Yes! Greenbelt 5 Makati, Uptown Mall BGC, SM North EDSA QC, and Ayala Center Cebu. Open daily 10AM–9PM.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="team" id="team-section">
    <div class="team-header">
      <h2 class="section-title">Meet the <em>Team</em></h2>
      <span class="eyebrow">The People Behind the Frames</span>
    </div>
    <div class="team-grid">
      <div class="team-member">
        <div class="team-member-image">
          <img src="../Resources/joe (6).jpg" alt="Aarhon Bautista">
          <div class="team-member-overlay">
            <div class="team-member-socials"><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i
                  class="fab fa-instagram"></i></a></div>
          </div>
        </div>
        <div class="team-member-info">
          <p class="team-member-role">Full Stack Developer</p>
          <h3 class="team-member-name">Aarhon Bautista</h3>
          <p class="team-member-bio">Keeps the servers running and the data flowing. If it works and you can't see it,
            that's him.</p>
        </div>
      </div>
      <div class="team-member">
        <div class="team-member-image">
          <img src="" alt="Edrian Sedrik Halili">
          <div class="team-member-overlay">
            <div class="team-member-socials"><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i
                  class="fab fa-instagram"></i></a></div>
          </div>
        </div>
        <div class="team-member-info">
          <p class="team-member-role">Back-End Developer</p>
          <h3 class="team-member-name">Edrian Sedrik Halili</h3>
          <p class="team-member-bio">The guy behind the logic. APIs, databases, and everything in between — he makes it
            scale.</p>
        </div>
      </div>
      <div class="team-member">
        <div class="team-member-image">
          <img src="" alt="Pollyne Anne Bartolome">
          <div class="team-member-overlay">
            <div class="team-member-socials"><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i
                  class="fab fa-instagram"></i></a></div>
          </div>
        </div>
        <div class="team-member-info">
          <p class="team-member-role">Front-End Developer</p>
          <h3 class="team-member-name">Pollyne Anne Bartolome</h3>
          <p class="team-member-bio">Turns designs into pixel-perfect reality. If it looks good, she made it happen.</p>
        </div>
      </div>
      <div class="team-member">
        <div class="team-member-image">
          <img src="" alt="Erica Mae Ramirez">
          <div class="team-member-overlay">
            <div class="team-member-socials"><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i
                  class="fab fa-instagram"></i></a></div>
          </div>
        </div>
        <div class="team-member-info">
          <p class="team-member-role">Front-End Developer</p>
          <h3 class="team-member-name">Erica Mae Ramirez</h3>
          <p class="team-member-bio">Great with interactions and animations. The reason everything feels smooth and
            intentional.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <img src="../Resources/Logos/lookgood-black.png" alt="LookGood" class="footer-logo">
        <p class="footer-tagline">Looking good has never been this clear.</p>
        <div class="footer-socials">
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
          <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest-p"></i></a>
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
            <li><a href="../Products/products-page.php">On Sale!</a></li>
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
            <li><a href="#">Store Location</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Press</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <!-- -->
      <div class="footer-newsletter">
        <h4 class="footer-newsletter-title">Stay in the Loop and Look Good</h4>
        <p class="footer-newsletter-text">Get exclusive deals, style tips, and new arrivals straight to your inbox.</p>
        <form class="newsletter-form">
          <input type="email" class="newsletter-input" placeholder="your@email.com">
          <button type="submit" class="newsletter-btn">Subscribe</button>
        </form>
        <p class="newsletter-note">No spam, ever. Unsubscribe anytime.</p>
        <div class="footer-contact">
          <p><i class="fas fa-map-marker-alt"></i> Greenbelt 5, Makati City</p>
          <p><i class="fas fa-envelope"></i> lookgoodframes@gmail.com</p>
          <p><i class="fas fa-phone"></i> +63 917 123 4567</p>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 LookGood Frames. All rights reserved.</p>
    </div>
  </footer>

  <script src="../../Actions/User/chatbot.js"></script>
  <script src="../../Actions/User/cart-standalone.js"></script>
  <script src="../../Actions/User/index.js"></script>
</body>

</html>