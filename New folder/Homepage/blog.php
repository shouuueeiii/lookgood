<!DOCTYPE html>
<html lang="en">
<head:
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Clear Perspectives — LookGood Frames</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../../css/User/navbar.css">
  <link rel="stylesheet" href="../../css/User/footer.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: #ffffff;
      font-family: 'DM Sans', sans-serif;
      color: #1e1e1e;
    }

    .blog-section {
      background: #ffffff;
      padding-bottom: 80px;
    }

    /* ── BLOG INTRO (original) ── */
    .blog-intro {
      text-align: center;
      padding: 60px 24px 40px;
      margin-top: 80px;
    }

    .blog-intro .label {
      font-size: 10px;
      letter-spacing: 4px;
      text-transform: uppercase;
      color: #a7a7a7;
      margin-bottom: 12px;
      font-weight: 500;
    }

    .blog-intro h2 {
      font-family: 'Spectral', serif;
      font-size: clamp(36px, 6vw, 56px);
      font-weight: 700;
      letter-spacing: -1.2px;
      line-height: 1.1;
      color: #111;
    }

    .blog-intro h2 em {
      font-style: italic;
      color: #c8a96e;
    }

    .blog-intro .sub {
      margin-top: 16px;
      font-size: 15px;
      color: #6f6f6f;
      max-width: 700px;
      margin-left: auto;
      margin-right: auto;
    }

    .blog-divider {
      width: 80px;
      height: 2px;
      background: #c8a96e;
      margin: 0 auto 48px auto;
      opacity: 0.7;
    }

    .blog-wrap {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 28px;
    }

    /* ── LAYOUT ── */
    .b-layout {
      display: flex;
      gap: 40px;
      align-items: flex-start;
    }

    /* LEFT: sticky panel — contains sticky intro + article cards */
    .b-list {
      flex: 0 0 400px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      position: sticky;
      top: 100px;
    }

    /* RIGHT: article panel */
    .b-article-wrap {
      flex: 1;
      min-width: 0;
    }

    .b-article {
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid #efebe5;
      overflow: hidden;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.02);
    }

    .b-article-inner {
      padding: 32px 36px 44px;
    }

    /* ── CARDS (unchanged) ── */
    .b-card {
      background: #ffffff;
      border: 1px solid #eae7e0;
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      flex-direction: row;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
    }

    .b-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 14px 26px -8px rgba(0, 0, 0, 0.10);
      border-color: #dad3c8;
    }

    .b-card.active {
      border-left: 4px solid #c8a96e;
      background: #fefcf8;
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.05);
    }

    .b-card-img {
      width: 140px;
      flex-shrink: 0;
      align-self: stretch;
    }

    .b-card-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.35s ease;
    }

    .b-card:hover .b-card-img img {
      transform: scale(1.05);
    }

    .b-card-body {
      padding: 18px 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }

    .b-card-date {
      font-size: 10px;
      letter-spacing: 1.8px;
      text-transform: uppercase;
      color: #c8a96e;
      font-weight: 600;
    }

    .b-card-title {
      font-weight: 700;
      font-size: 14.5px;
      line-height: 1.45;
      color: #1e1e1e;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .b-card-author {
      font-size: 11.5px;
      color: #a09585;
      margin-top: auto;
      padding-top: 8px;
    }

    /* ── STICKY INTRO STYLES (inside left column) ── */
    .sticky-intro-wrapper {
      display: none;
      margin-bottom: 6px;
    }
    
    /* make cloned intro look like a cohesive card inside sticky panel */
    .b-list .blog-intro {
      margin: 0 0 12px 0 !important;
      padding: 24px 20px 26px 20px !important;
      background: #ffffff !important;
      border: 1px solid #eae7e0 !important;
      border-radius: 20px !important;
      box-shadow: 0 6px 12px -6px rgba(0, 0, 0, 0.04) !important;
      text-align: center !important;
      width: 100% !important;
    }
    
    .b-list .blog-intro .label {
      font-size: 10px;
      letter-spacing: 3px;
      color: #b09a6b;
    }
    
    .b-list .blog-intro h2 {
      font-family: 'Spectral', serif;
      font-size: 26px !important;
      font-weight: 700 !important;
      letter-spacing: -0.6px;
      margin-bottom: 10px;
      color: #1c1c1c;
    }
    
    .b-list .blog-intro h2 em {
      color: #c8a96e;
    }
    
    .b-list .blog-intro .sub {
      font-size: 12.5px;
      line-height: 1.45;
      color: #6f6f6f;
      margin-top: 8px;
      max-width: 100%;
    }
    
    /* optional mini divider for elegance */
    .sticky-intro-divider {
      width: 48px;
      height: 2px;
      background: #e2d7ca;
      margin: 16px auto 8px auto;
      border-radius: 2px;
    }

    /* ── ARTICLE TYPOGRAPHY ── */
    .b-article-top {
      text-align: center;
      margin-bottom: 24px;
    }

    .b-article-lbl {
      font-size: 10px;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: #b9af9c;
      margin-bottom: 8px;
    }

    .b-article-cat {
      font-family: 'Spectral', serif;
      font-size: 28px;
      font-weight: 700;
      color: #26211a;
      letter-spacing: -0.2px;
    }

    .b-pic {
      width: 100%;
      height: 480px;
      object-fit: cover;
      border-radius: 20px;
      margin-bottom: 28px;
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.03);
    }

    .b-article-title {
      font-family: 'DM Sans', sans-serif;
      font-size: 26px;
      font-weight: 700;
      color: #1c1c1c;
      line-height: 1.3;
      margin-bottom: 12px;
    }

    .b-ameta {
      font-size: 12px;
      color: #aa9f8b;
      border-bottom: 1px solid #f0e9df;
      padding-bottom: 18px;
      margin-bottom: 24px;
    }

    .b-article-inner p {
      font-size: 16px;
      line-height: 1.75;
      color: #3a352e;
      margin-bottom: 20px;
    }

    .b-article-inner p b {
      color: #c8a96e;
      font-weight: 600;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 960px) {
      .b-list {
        flex: 0 0 320px;
      }
      .b-card-img {
        width: 110px;
      }
    }

    @media (max-width: 880px) {
      .b-layout {
        flex-direction: column;
        gap: 28px;
      }
      .b-list {
        flex: auto;
        width: 100%;
        position: static;
      }
      .b-article-inner {
        padding: 24px 22px 32px;
      }
      .b-article-title {
        font-size: 22px;
      }
      .b-pic {
        height: 210px;
      }
      /* sticky intro still appears but left column not sticky - fine */
    }

    @media (max-width: 560px) {
      .blog-wrap {
        padding: 0 20px;
      }
      .b-card-img {
        width: 95px;
      }
      .b-card-title {
        font-size: 13px;
      }
      .b-list .blog-intro h2 {
        font-size: 22px !important;
      }
    }
  </style>
</head>
<body>

<?php include '../navbar.php'; ?>

<section class="blog-section" id="blog">
  <div class="blog-intro" id="originalBlogIntro">
    <p class="label">Read About</p>
    <h2>Clear <em>Perspectives</em></h2>
    <p class="sub">Expert tips, eyewear guides, and everything you need to find your perfect pair.</p>
  </div>
  <div class="blog-divider"></div>

  <div class="blog-wrap">
    <div class="b-layout" id="bLayout">
      <div class="b-list" id="bList"></div>

      <div class="b-article-wrap" id="bArticleWrap">
        <div class="b-article">
          <div class="b-article-inner" id="bArticleInner"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../footer.php'; ?>

<script>
  const articles = [
    {
      lbl: 'Frame Guide',
      cat: 'Face Shape',
      img: '../Resources/blog-img/image1.png',
      title: 'What Is the Perfect Frame for Your Face Shape?',
      date: 'April 9, 2026',
      author: 'By Amari Ysaiah',
      body: `
        <p>Choosing the right eyeglass frame isn't just about picking a color you love — it's about finding a shape that works in harmony with your face. The good news? Every face shape has a flattering match, and once you know yours, the decision becomes a lot easier.</p>
        <p><b>Oval Face</b> — You're in luck. The oval face is considered the most versatile, and almost any frame style suits it. From oversized square frames to delicate round ones, you have the most freedom. Just avoid frames that are too large or too wide, as they can overwhelm your naturally balanced proportions.</p>
        <p><b>Round Face</b> — Go angular. Rectangle and square frames add structure and definition, making your face appear slimmer and longer. Frames with a strong bridge and clear angles work especially well. Avoid circular or small rounded frames, which can emphasize the roundness of your face rather than complement it.</p>
        <p><b>Square Face</b> — Strong jawlines deserve a softer counterpoint. Round or oval frames are your best friends here, as they soften the angularity and bring balance to your features. Rimless and semi-rimless styles also work beautifully by drawing less visual attention to the jaw area.</p>
        <p><b>Heart-Shaped Face</b> — With a broader forehead and narrower chin, the goal is to add width at the bottom and minimize the top. Opt for frames that are wider at the bottom than the top. Aviators, oval frames, and bottom-heavy styles all help balance a wider forehead with a narrower chin.</p>
        <p><b>Diamond Face</b> — Your cheekbones are your best feature — so play them up. Cat-eye and oval frames work beautifully by highlighting the eyes while softening the width of the cheekbones. Rimless designs and frames with distinctive brow lines also complement this face shape well.</p>
        <p><b>Oblong Face</b> — For faces that are longer than they are wide, the goal is to add width. Oversized frames, decorative temples, and frames with a low bridge all help create the illusion of a shorter, wider face.</p>
        <p>The key takeaway: knowing your face shape is the first step, but nothing replaces actually trying frames on. Visit us at LookGood Frames and try on multiple styles — our team is always here to help guide you toward your perfect pair.</p>
      `
    },
    {
      lbl: 'Style Guide',
      cat: 'Skin Tone',
      img: '../Resources/blog-img/image2.png',
      title: 'How to Choose Eyeglass Frames That Complement Your Skin Tone',
      date: 'April 7, 2026',
      author: 'By Yvo Mercadejas',
      body: `
        <p>Your skin tone plays a huge role in how a frame color looks on your face. Just like clothing, the right frame color can make you glow — and the wrong one can wash you out or clash with your natural complexion. Understanding your undertone is the key to making a confident choice.</p>
        <p><b>How to Identify Your Undertone</b> — Look at the veins on the inside of your wrist. If they appear greenish, you likely have warm undertones. Bluish or purple veins suggest cool undertones. If you see a mix of both, you're probably neutral.</p>
        <p><b>Warm Skin Tones</b> — Earth tones are your best friend. Think tortoise shell, warm browns, honey, caramel, olive green, and gold. These shades echo the natural warmth in your complexion.</p>
        <p><b>Cool Skin Tones</b> — Reach for jewel tones: navy, plum, deep green, silver, and black. These colors create a beautiful contrast against cool undertones and make your features pop.</p>
        <p><b>Neutral Skin Tones</b> — Lucky you — you can pull off virtually any color. Experiment with bold hues like coral, teal, or terracotta, or stick with classics like black, tortoise, or clear acetate.</p>
        <p><b>A Quick Trick at the Store</b> — Hold the frame next to your face in natural light before deciding. If it makes your eyes brighter and your skin look vibrant, it's the one.</p>
        <p>At LookGood Frames, we carry hundreds of colors and finishes. Our team is trained to help you find the perfect color match — don't hesitate to ask for guidance on your next visit.</p>
      `
    },
    {
      lbl: 'Lens Guide',
      cat: 'Prescription Lenses',
      img: '../Resources/blog-img/image3.png',
      title: 'The Difference Between Prescription Lenses: Which One Is Right for You?',
      date: 'April 5, 2026',
      author: 'By Annaliese Juarez',
      body: `
        <p>Picking a frame is only half the journey. What goes inside the frame matters just as much — if not more. With so many lens options available today, it can be overwhelming to know which type fits your lifestyle and vision needs.</p>
        <p><b>Single Vision Lenses</b> — The most common type. They correct one field of vision, either near or far. If you're nearsighted, farsighted, or have astigmatism, single vision lenses are likely what your optometrist has prescribed.</p>
        <p><b>Bifocal Lenses</b> — Split into two zones: upper for distance, lower for near. A visible line separates the two sections. Practical for people who need correction for both near and far.</p>
        <p><b>Progressive Lenses</b> — The modern, seamless upgrade to bifocals. A gradual transition between near, intermediate, and distance vision — with no visible line. Ideal for people over 40 developing presbyopia.</p>
        <p><b>Blue Light Blocking Lenses</b> — For long hours in front of screens. They filter high-energy blue light, reducing eye strain, headaches, and sleep disruption. Can be added as a coating to almost any lens type.</p>
        <p><b>Photochromic (Transition) Lenses</b> — Darken automatically outdoors and return to clear indoors. One pair does the job of both regular glasses and sunglasses.</p>
        <p><b>Anti-Reflective (AR) Coating</b> — Reduces glare from screens, headlights, and bright lights — making your lenses cleaner-looking and your vision sharper, especially at night.</p>
        <p>Always consult your optometrist before choosing a lens type. At LookGood Frames, our optical team can help match your prescription and lifestyle to the best lens option available.</p>
      `
    },
    {
      lbl: 'Trends',
      cat: '2026 Eyewear Trends',
      img: '../Resources/blog-img/image4.png',
      title: '5 Eyewear Trends You Need to Know in 2026',
      date: 'April 3, 2026',
      author: 'By Vincentius Hidalgo',
      body: `
        <p>Fashion moves fast, and eyewear is no exception. Frames have evolved from a purely functional accessory to one of the most expressive parts of personal style. Here are the top five eyewear trends dominating 2026.</p>
        <p><b>1. Oversized Retro Frames</b> — Big is back. Think 70s-inspired wide frames in tortoiseshell, deep amber, or translucent acetate. These dramatic silhouettes make an instant statement and work across a wide range of face shapes.</p>
        <p><b>2. Geometric Shapes</b> — Hexagons, octagons, and irregular polygons are having a serious moment. They're especially popular in thin metal finishes — gold, gunmetal, and matte black — that keep the look refined.</p>
        <p><b>3. Tinted Lenses in Prescription Frames</b> — Light amber, rose, sage, and warm yellow-tinted lenses are everywhere. They give off effortless vintage energy and make even the most basic outfit feel curated.</p>
        <p><b>4. Thin Metal Wireframes</b> — Ultra-thin gold and silver wireframes trend strongly for their minimalist elegance. They're the kind of frames that look good on virtually everyone and pair with almost any outfit.</p>
        <p><b>5. Transparent and Pastel Acetate</b> — Clear acetate frames remain a crowd-pleaser, joined by soft pastels — blush pink, pale lavender, mint green, and baby blue. Subtle yet modern, they work on every face shape and skin tone.</p>
        <p>Stop by LookGood Frames to explore our 2026 collection — pieces that blend these trends with timeless wearability.</p>
      `
    },
    {
      lbl: 'Care Tips',
      cat: 'Eyeglass Care',
      img: '../Resources/blog-img/image5.png',
      title: 'How to Properly Care for Your Eyeglasses (And Make Them Last Longer)',
      date: 'April 1, 2026',
      author: 'By Percival Riego',
      body: `
        <p>A quality pair of frames is an investment — and like any investment, it rewards those who take care of it. With proper maintenance, your glasses can look and perform like new for years.</p>
        <p><b>Clean Them the Right Way</b> — Always use a clean microfiber cloth and a lens-safe cleaning solution. Never use paper towels, tissues, or the hem of your shirt — these are microscopically abrasive and will leave fine scratches over time.</p>
        <p><b>Rinse Before Wiping</b> — Rinse lenses gently under lukewarm water first. This removes dust and grit that could scratch the surface when rubbed. Avoid hot water — it can damage lens coatings.</p>
        <p><b>Store Them in a Hard Case</b> — Keep glasses in a hard-shell case when not in use. Tossing them loose in a bag or leaving them face-down leads to scratches, bent frames, and broken hinges.</p>
        <p><b>Handle with Both Hands</b> — Always use both hands when putting on or removing your glasses. One-handed removal repeatedly stresses the frame unevenly, causing temples to loosen and bend over time.</p>
        <p><b>Avoid Heat and Chemicals</b> — Never leave glasses in a hot car or under direct sunlight for long periods. Avoid contact with household cleaners, hairspray, and perfume — these chemicals cloud lenses and break down frame finishes.</p>
        <p><b>Get Regular Professional Adjustments</b> — Frames shift naturally with daily use. Visit us at LookGood Frames for a free adjustment — it takes minutes and ensures your glasses sit correctly for both comfort and optical clarity.</p>
        <p>A little daily care goes a long way. Treat your frames well, and they'll keep your vision sharp and your style on point.</p>
      `
    }
  ];

  const bList = document.getElementById('bList');
  let stickyWrapper = null;
  let originalIntro = document.getElementById('originalBlogIntro');
  let cloneAttached = false;     // to store cloned content reference
  let cloneIntroElement = null;   // hold cloned node

  // render article cards
  function renderCards() {
    bList.innerHTML = '';
    articles.forEach((art, idx) => {
      const card = document.createElement('div');
      card.className = 'b-card';
      card.setAttribute('data-index', idx);
      card.onclick = () => openArticle(idx);
      card.innerHTML = `
        <div class="b-card-img">
          <img src="${art.img}" alt="${art.title}">
        </div>
        <div class="b-card-body">
          <p class="b-card-date">${art.date}</p>
          <p class="b-card-title">${art.title}</p>
          <p class="b-card-author">${art.author}</p>
        </div>
      `;
      bList.appendChild(card);
    });
  }

  // create sticky container (empty) and prepend to left column
  function createStickyContainer() {
    if (stickyWrapper) return stickyWrapper;
    const wrapper = document.createElement('div');
    wrapper.className = 'sticky-intro-wrapper';
    wrapper.id = 'stickyIntroWrapper';
    bList.prepend(wrapper);
    stickyWrapper = wrapper;
    return stickyWrapper;
  }

  // clone original blog-intro content (once) and prepare it inside sticky wrapper
  function prepareStickyIntroClone() {
    if (!originalIntro) return null;
    // deep clone the whole .blog-intro node
    const clone = originalIntro.cloneNode(true);
    // remove any id to avoid duplicate id in DOM
    clone.removeAttribute('id');
    // remove any extraneous inline styles if needed (none present)
    // optional: add subtle extra divider for aesthetic separation inside sticky card
    const extraDivider = document.createElement('div');
    extraDivider.className = 'sticky-intro-divider';
    clone.appendChild(extraDivider);
    return clone;
  }

  // initialize scroll detection: show/hide sticky intro when user scrolls past original intro
  function initStickyIntroScroll() {
    if (!originalIntro) return;
    
    // ensure wrapper exists
    const wrapper = createStickyContainer();
    
    // function to handle scroll logic
    const handleScroll = () => {
      if (!originalIntro) return;
      const rect = originalIntro.getBoundingClientRect();
      // completely scrolled past original intro? (its bottom is above viewport)
      const isScrolledPast = rect.bottom <= 0;
      
      if (isScrolledPast) {
        // need to show sticky intro content if not already shown or not yet cloned
        if (!cloneAttendedAndReady()) {
          // clone the intro content only once and store inside wrapper
          const clonedIntro = prepareStickyIntroClone();
          if (clonedIntro && wrapper) {
            // clear wrapper and append the clone
            wrapper.innerHTML = '';
            wrapper.appendChild(clonedIntro);
            cloneIntroElement = clonedIntro;
            cloneAttached = true;
          }
        }
        // make wrapper visible
        if (wrapper) wrapper.style.display = 'block';
      } else {
        // user scrolled back, hide the sticky version
        if (wrapper) wrapper.style.display = 'none';
      }
    };
    
    // helper: check if clone is prepared and visible condition ready
    function cloneAttendedAndReady() {
      return cloneAttached && cloneIntroElement && wrapper && wrapper.contains(cloneIntroElement);
    }
    
    // run once on load to set initial state (hidden)
    handleScroll();
    
    // attach scroll event with passive flag for performance
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', () => {
      // re-evaluate on resize to avoid layout mismatch (e.g., orientation change)
      handleScroll();
    });
  }

  // render article content
  function renderArticleContent(index) {
    const art = articles[index];
    const container = document.getElementById('bArticleInner');
    container.innerHTML = `
      <div class="b-article-top">
        <p class="b-article-lbl">LookGood Frames &middot; ${art.lbl}</p>
        <p class="b-article-cat">${art.cat}</p>
      </div>
      <img src="${art.img}" class="b-pic" alt="${art.title}">
      <h3 class="b-article-title">${art.title}</h3>
      <div class="b-ameta">${art.date} &middot; ${art.author}</div>
      ${art.body}
    `;
    document.querySelectorAll('.b-card').forEach((card, i) => {
      card.classList.toggle('active', i === index);
    });
  }

  function openArticle(index) {
    renderArticleContent(index);
    if (window.innerWidth < 880) {
      document.querySelector('.b-article-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  // 1) render cards & default first article
  renderCards();
  openArticle(0);
  
  // 2) build sticky container and initialize scroll behavior (inserts empty wrapper)
  createStickyContainer();   // creates empty wrapper prepended to bList
  initStickyIntroScroll();    // sets up clone on scroll-past + show/hide logic
  
  // 3) edge case: if dynamic content changes (no) but ensure that after openArticle we keep active state
  // also we keep card active highlight
</script>
</body>
</html>