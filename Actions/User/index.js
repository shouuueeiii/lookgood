// ============================================================
// LOOKGOOD FRAMES - REFACTORED JAVASCRIPT
// Vanilla JS - No Bootstrap Dependencies
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
  
  // ========================
  // NAVIGATION
  // ========================
  
  // Mobile Menu Toggle
  const navToggle = document.getElementById('navToggle');
  const navMenu = document.getElementById('navMenu');
  
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', function() {
      const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', !isExpanded);
      navMenu.classList.toggle('active');
    });
  }
  
  // Mobile Dropdown Toggle
  const dropdownItems = document.querySelectorAll('.nav-item--dropdown');
  
  if (window.innerWidth <= 991) {
    dropdownItems.forEach(item => {
      const link = item.querySelector('.nav-link');
      link.addEventListener('click', function(e) {
        if (window.innerWidth <= 991) {
          e.preventDefault();
          item.classList.toggle('active');
        }
      });
    });
  }
  
  
  // ========================
  // HERO CAROUSEL
  // ========================
  
  const heroCarousel = document.getElementById('glassesCarousel');
  if (heroCarousel) {
    const items = heroCarousel.querySelectorAll('.hero-carousel-item');
    let currentIndex = 0;
    
    function showNextSlide() {
      items[currentIndex].classList.remove('hero-carousel-item--active');
      currentIndex = (currentIndex + 1) % items.length;
      items[currentIndex].classList.add('hero-carousel-item--active');
    }
    
    // Auto-advance every 3 seconds
    setInterval(showNextSlide, 3000);
    
    // Fade in hero content on load
    setTimeout(() => {
      const heroContent = document.querySelector('.hero-content');
      if (heroContent) {
        heroContent.classList.add('visible');
      }
    }, 100);
  }
  
  
  // ========================
  // BRAND VALUE CAROUSEL
  // ========================
  
  const bvCarousel = document.getElementById('bvCarousel');
  if (bvCarousel) {
    const track = document.getElementById('bvSlides');
    const dots = bvCarousel.querySelectorAll('.carousel-dot');
    const prevBtn = document.getElementById('bvPrev');
    const nextBtn = document.getElementById('bvNext');
    const slides = track.querySelectorAll('.carousel-slide');
    
    let currentSlide = 0;
    
    function updateCarousel() {
      const slideWidth = slides[0].offsetWidth;
      track.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
      
      dots.forEach((dot, index) => {
        dot.classList.toggle('carousel-dot--active', index === currentSlide);
      });
    }
    
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        currentSlide = index;
        updateCarousel();
      });
    });
    
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        currentSlide = currentSlide > 0 ? currentSlide - 1 : slides.length - 1;
        updateCarousel();
      });
    }
    
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateCarousel();
      });
    }
  }
  
  
  // ========================
  // 3D FEATURED CAROUSEL
  // ========================
  
  const carousel3d = document.getElementById('carousel3d');
  if (carousel3d) {
    const items = carousel3d.querySelectorAll('.carousel-3d-item');
    const prevBtn = document.getElementById('carouselPrev');
    const nextBtn = document.getElementById('carouselNext');
    
    let currentIndex = 0;
    
    function updateCarousel3D() {
      items.forEach((item, index) => {
        const position = index - currentIndex;
        
        if (Math.abs(position) <= 2) {
          item.setAttribute('data-pos', position);
        } else {
          item.removeAttribute('data-pos');
        }
      });
    }
    
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        currentIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
        updateCarousel3D();
      });
    }
    
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % items.length;
        updateCarousel3D();
      });
    }
    
    // Initialize
    updateCarousel3D();
  }
  
  // ========================
  // FAQ ACCORDION
  // ========================
  
  const faqQuestions = document.querySelectorAll('.faq-question');
  
  faqQuestions.forEach(question => {
    question.addEventListener('click', function() {
      const faqItem = this.parentElement;
      const answer = faqItem.querySelector('.faq-answer');
      const isActive = this.classList.contains('faq-question--active');
      
      // Close all other FAQ items
      faqQuestions.forEach(q => {
        if (q !== this) {
          q.classList.remove('faq-question--active');
          q.parentElement.querySelector('.faq-answer').classList.remove('faq-answer--open');
        }
      });
      
      // Toggle current item
      this.classList.toggle('faq-question--active');
      answer.classList.toggle('faq-answer--open');
    });
  });
  
  
  // ========================
  // TEAM SCROLL ANIMATION
  // ========================
  
  const teamMembers = document.querySelectorAll('.team-member');
  
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);
  
  teamMembers.forEach(member => {
    observer.observe(member);
  });
  
  
  // ========================
  // CART FUNCTIONALITY
  // ========================
  
  const cartBtn = document.getElementById('cartBtn');
  const cartSidebar = document.getElementById('cartSidebar');
  const cartOverlay = document.getElementById('cartOverlay');
  const closeCartBtn = document.getElementById('closeCart');
  const continueShoppingBtn = document.getElementById('continueShopping');
  const cartBadge = document.getElementById('cartBadge');
  const cartItems = document.getElementById('cartItems');
  const emptyCart = document.getElementById('emptyCart');
  
  let cart = [];
  
  function openCart() {
    cartSidebar.classList.add('active');
    cartOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  
  function closeCart() {
    cartSidebar.classList.remove('active');
    cartOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }
  
  if (cartBtn) {
    cartBtn.addEventListener('click', openCart);
  }
  
  if (closeCartBtn) {
    closeCartBtn.addEventListener('click', closeCart);
  }
  
  if (continueShoppingBtn) {
    continueShoppingBtn.addEventListener('click', closeCart);
  }
  
  if (cartOverlay) {
    cartOverlay.addEventListener('click', closeCart);
  }
  
  // Add to Cart
  const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
  
  addToCartButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      const name = this.dataset.name;
      const price = parseFloat(this.dataset.price);
      const img = this.dataset.img;
      
      const existingItem = cart.find(item => item.name === name);
      
      if (existingItem) {
        existingItem.quantity += 1;
      } else {
        cart.push({ name, price, img, quantity: 1 });
      }
      
      updateCart();
      openCart();
    });
  });
  
  function updateCart() {
    // Update badge
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartBadge) {
      cartBadge.textContent = totalItems;
      cartBadge.style.display = totalItems > 0 ? 'flex' : 'none';
    }
    
    // Update cart display
    if (cart.length === 0) {
      if (emptyCart) emptyCart.style.display = 'block';
      if (cartItems) cartItems.classList.remove('has-items');
      updateTotal();
      return;
    }
    
    if (emptyCart) emptyCart.style.display = 'none';
    if (cartItems) {
      cartItems.classList.add('has-items');
      cartItems.innerHTML = cart.map((item, index) => `
        <div class="cart-item" data-index="${index}">
          <img src="${item.img}" alt="${item.name}">
          <div class="cart-item-details">
            <h4>${item.name}</h4>
            <p class="cart-item-price">₱${item.price.toFixed(2)}</p>
            <div class="cart-item-quantity">
              <button class="qty-btn qty-minus" data-index="${index}">-</button>
              <span class="qty-display">${item.quantity}</span>
              <button class="qty-btn qty-plus" data-index="${index}">+</button>
            </div>
          </div>
          <button class="remove-item" data-index="${index}">
            <i class="fas fa-times"></i>
          </button>
        </div>
      `).join('');
      
      // Attach event listeners to cart item buttons
      attachCartItemListeners();
    }
    
    updateTotal();
  }
  
  function attachCartItemListeners() {
    // Quantity buttons
    document.querySelectorAll('.qty-plus').forEach(btn => {
      btn.addEventListener('click', function() {
        const index = parseInt(this.dataset.index);
        cart[index].quantity += 1;
        updateCart();
      });
    });
    
    document.querySelectorAll('.qty-minus').forEach(btn => {
      btn.addEventListener('click', function() {
        const index = parseInt(this.dataset.index);
        if (cart[index].quantity > 1) {
          cart[index].quantity -= 1;
        } else {
          cart.splice(index, 1);
        }
        updateCart();
      });
    });
    
    // Remove buttons
    document.querySelectorAll('.remove-item').forEach(btn => {
      btn.addEventListener('click', function() {
        const index = parseInt(this.dataset.index);
        cart.splice(index, 1);
        updateCart();
      });
    });
  }
  
  function updateTotal() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const totalElement = document.querySelector('.cart-total-price');
    if (totalElement) {
      totalElement.textContent = `₱${total.toFixed(2)}`;
    }
  }
  
  
  // ========================
  // SEARCH FUNCTIONALITY
  // ========================
  
  const searchInput = document.getElementById('searchInput');
  
  if (searchInput) {
    searchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        const query = this.value.trim();
        if (query) {
          // Redirect to products page with search query
          window.location.href = `../Products/products-page.php?search=${encodeURIComponent(query)}`;
        }
      }
    });
  }
  
  
  // ========================
  // CONTACT FORM
  // ========================
  
  window.handleContactSubmit = function() {
    const name = document.getElementById('contactName').value;
    const email = document.getElementById('contactEmail').value;
    const subject = document.getElementById('contactSubject').value;
    const message = document.getElementById('contactMessage').value;
    
    if (!name || !email || !subject || !message) {
      alert('Please fill in all fields');
      return;
    }
    
    // Show success message
    const successMessage = document.getElementById('contactSuccess');
    if (successMessage) {
      successMessage.classList.add('show');
      
      // Clear form
      document.getElementById('contactName').value = '';
      document.getElementById('contactEmail').value = '';
      document.getElementById('contactSubject').value = '';
      document.getElementById('contactMessage').value = '';
      
      // Hide success message after 5 seconds
      setTimeout(() => {
        successMessage.classList.remove('show');
      }, 5000);
    }
  };
  
  
  // ========================
  // SMOOTH SCROLL FOR ANCHOR LINKS
  // ========================
  
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href !== '#' && href !== '#cart') {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          const navbarHeight = document.querySelector('.navbar').offsetHeight;
          const targetPosition = target.offsetTop - navbarHeight;
          
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
          
          // Close mobile menu if open
          if (navMenu && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
          }
        }
      }
    });
  });
  
});

