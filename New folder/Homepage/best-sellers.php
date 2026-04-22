<?php
// best-sellers.php — partial, included by index.php
// NO DOCTYPE / html / head / body tags here.
?>
<style>
.bs-section {
    padding: 56px 0 64px;
    background: linear-gradient(160deg, #f9f8f6 0%, #ffffff 60%);
    font-family: 'DM Sans', sans-serif;
    overflow: hidden;
}
.bs-header { text-align: center; margin-top: 60px; margin-bottom: 35px; }
.bs-eyebrow {
    display: block;
    font-family: 'Spectral', serif;
    font-size: 11px; font-weight: 600;
    letter-spacing: 0.18em; text-transform: uppercase;
    color: #c8a96e; margin-bottom: 10px;
}
.bs-title {
    font-family: 'Spectral', serif;
    font-size: 36px; font-weight: 700;
    color: #1a1a1a; line-height: 1.2; margin-bottom: 10px;
}
.bs-title em { font-style: italic; color: #c8a96e; }
.bs-divider {
    width: 48px; height: 2px;
    background: linear-gradient(90deg, #c8a96e, #e8d5a3);
    margin: 0 auto 14px; border-radius: 2px;
}
.bs-subtitle { font-size: 14px; color: #888; }

/* skeleton */
.bs-loading {
    display: flex; justify-content: center;
    align-items: center; gap: 16px; padding: 40px 0;
}
.bs-skeleton-card {
    width: 240px; height: 360px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: bs-shimmer 1.4s infinite;
    border-radius: 20px;
}
.bs-skeleton-card:nth-child(2) { animation-delay: 0.1s; }
.bs-skeleton-card:nth-child(3) { animation-delay: 0.2s; }
@keyframes bs-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.bs-carousel-wrapper {
    display: flex; flex-direction: column;
    align-items: center; gap: 24px;
}
.bs-track-container {
    overflow: visible; width: 100%; max-width: 1000px;
    position: relative; height: 390px;
    touch-action: pan-y; cursor: grab;
}
.bs-track-container.bs-dragging { cursor: grabbing; }
.bs-track { position: relative; width: 100%; height: 100%; }

.bs-card {
    position: absolute; top: 50%; left: 0;
    width: 240px; background: white; border-radius: 20px;
    overflow: hidden; cursor: pointer;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    will-change: transform, opacity;
    transition: box-shadow 0.3s ease;
}
.bs-card.active { box-shadow: 0 20px 35px -12px rgba(200,169,110,0.4); }

.bs-badge {
    position: absolute; top: 10px; left: 10px;
    background: #1a1a1a; color: #c8a96e;
    font-size: 12px; padding: 3px 8px;
    border-radius: 9999px; z-index: 2;
}
.bs-rank {
    position: absolute; top: 10px; right: 10px;
    width: 22px; height: 22px;
    background: rgba(200,169,110,0.15);
    border: 1px solid #c8a96e; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: bold; color: #c8a96e; z-index: 2;
}
.bs-img-wrap {
    height: 220px; background: #f5f4f1;
    display: flex; align-items: center; justify-content: center;
}
.bs-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.bs-info { padding: 12px; text-align: center; }
.bs-name {
    font-family: 'Spectral', serif;
    font-size: 15px; font-weight: 700; color: #1a1a1a;
    margin-bottom: 4px;
}
.bs-price-row { display: flex; align-items: center; justify-content: center; gap: 6px; }
.bs-price      { font-size: 15px; font-weight: 700; color: #c8a96e; }
.bs-price.sale { color: #c0392b; }
.bs-old-price  { font-size: 12px; color: #aaa; text-decoration: line-through; }
.bs-units      { font-size: 11px; color: #bbb; margin-top: 3px; }

.bs-actions {
    display: flex; gap: 5px;
    max-height: 0; overflow: hidden; opacity: 0; margin-top: 0;
    transition: max-height 0.3s ease, opacity 0.3s ease, margin-top 0.3s ease;
}
.bs-card.active .bs-actions { max-height: 50px; opacity: 1; margin-top: 8px; }
.bs-buy {
    flex: 1; padding: 6px;
    background: #1a1a1a; color: white;
    border: none; border-radius: 30px;
    font-size: 11px; font-weight: 600;
    cursor: pointer; transition: background 0.2s;
}
.bs-buy:hover { background: #c8a96e; }
.bs-cart-btn {
    width: 30px; height: 30px;
    background: white; border: 1px solid #1a1a1a;
    border-radius: 50%; cursor: pointer; color: #1a1a1a;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, color 0.2s;
}
.bs-cart-btn:hover { background: #1a1a1a; color: white; }

.bs-nav-row { display: flex; align-items: center; gap: 12px; }
.bs-carousel-btn {
    width: 44px; height: 44px; border-radius: 50%;
    background: white; border: 2px solid #1a1a1a; color: #1a1a1a;
    font-size: 20px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s;
    user-select: none;
}
.bs-carousel-btn:hover {
    background: #c8a96e; border-color: #c8a96e;
    color: white; transform: scale(1.05);
}
.bs-dots { display: flex; gap: 6px; align-items: center; }
.bs-dot {
    width: 6px; height: 6px; border-radius: 50%; background: #ddd;
    cursor: pointer; transition: background 0.3s, transform 0.3s;
}
.bs-dot.active { background: #c8a96e; transform: scale(1.4); }

.bs-cta-wrap { text-align: center; margin-top: 32px; }
.bs-cta {
    display: inline-block; padding: 12px 38px;
    background: #1a1a1a; color: white;
    font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
    border-radius: 9999px; text-decoration: none;
    transition: background 0.2s, transform 0.2s;
}
.bs-cta:hover { background: #c8a96e; transform: translateY(-2px); }

@media (max-width: 768px) {
    .bs-card { width: 200px; }
    .bs-track-container { height: 330px; }
    .bs-img-wrap { height: 175px; }
}
@media (max-width: 480px) {
    .bs-card { width: 165px; }
    .bs-title { font-size: 32px; }
    .bs-track-container { height: 295px; }
    .bs-img-wrap { height: 155px; }
}
</style>

<section class="bs-section">
    <div class="bs-header">
        <span class="bs-eyebrow">Top 5 Best Selling</span>
        <h2 class="bs-title">Hall of <em>Frames</em></h2>
        <div class="bs-divider"></div>
        <p class="bs-subtitle">Frames our customers can't stop wearing</p>
    </div>

    <div class="bs-loading" id="bs-loading">
        <div class="bs-skeleton-card"></div>
        <div class="bs-skeleton-card"></div>
        <div class="bs-skeleton-card"></div>
    </div>

    <div class="bs-carousel-wrapper" id="bs-carousel-wrapper" style="display:none;">
        <div class="bs-track-container" id="bs-track-container">
            <div class="bs-track" id="bs-track"></div>
        </div>
        <div class="bs-nav-row">
            <button class="bs-carousel-btn bs-prev" id="bs-prev">&#10094;</button>
            <div class="bs-dots" id="bs-dots"></div>
            <button class="bs-carousel-btn bs-next" id="bs-next">&#10095;</button>
        </div>
    </div>

    <div class="bs-cta-wrap">
        <a href="../Products/products-page.php" class="bs-cta">Shop All Frames</a>
    </div>
</section>

<script>
(function () {
    var API_URL = '../../adminBack_end/bestSellerAPI.php';

    var loadingEl   = document.getElementById('bs-loading');
    var wrapperEl   = document.getElementById('bs-carousel-wrapper');
    var trackEl     = document.getElementById('bs-track');
    var containerEl = document.getElementById('bs-track-container');
    var dotsEl      = document.getElementById('bs-dots');
    var prevBtn     = document.getElementById('bs-prev');
    var nextBtn     = document.getElementById('bs-next');

    var GAP = 20, LERP = 0.10, INTERVAL = 3000;
    var cards = [], dots = [], N = 0, current = 0, target = 0;
    var rafId = null, autoTimer = null, isDragging = false;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function fmt(n)  { return '\u20b1' + Number(n).toLocaleString('en-PH'); }
    function cardW() { return cards[0] ? cards[0].clientWidth : 240; }
    function stride(){ return cardW() + GAP; }
    function wrapDist(d) { return ((d % N) + N * 1.5) % N - N * 0.5; }

    function buildCards(products) {
        trackEl.innerHTML = '';
        dotsEl.innerHTML  = '';
        cards = []; dots = [];
        N = products.length;

        products.forEach(function(p, idx) {

            var badgeHtml = '<span class="bs-badge">&#9733; Best Seller</span>';

            var displayPrice  = p.onSale ? p.salePrice : p.price;
            var priceHtml = p.onSale
                ? '<div class="bs-price-row">' +
                    '<span class="bs-price sale">'  + fmt(p.salePrice) + '</span>' +
                    '<span class="bs-old-price">'   + fmt(p.price)     + '</span>' +
                '</div>'
                : '<div class="bs-price-row"><span class="bs-price">' + fmt(p.price) + '</span></div>';

            var card = document.createElement('div');
            card.className     = 'bs-card';
            card.dataset.index = idx;
            card.innerHTML =
                badgeHtml +
                '<span class="bs-rank">' + p.rank + '</span>' +
                '<div class="bs-img-wrap"><img src="' + escHtml(p.image) + '" alt="' + escHtml(p.name) + '" loading="lazy"></div>' +
                '<div class="bs-info">' +
                    '<div class="bs-name">' + escHtml(p.name) + '</div>' +
                    priceHtml +
                    '<div class="bs-units">' + Number(p.unitsSold).toLocaleString() + ' sold</div>' +
                    '<div class="bs-actions">' +
                        '<button class="bs-buy"' +
                            ' data-id="'    + escHtml(p.id)              + '"' +
                            ' data-name="'  + escHtml(p.name)            + '"' +
                            ' data-price="' + displayPrice               + '"' +
                            ' data-image="' + escHtml(p.image)           + '">Buy Now</button>' +
                        '<button class="bs-cart-btn"' +
                            ' data-id="'    + escHtml(p.id)              + '"' +
                            ' data-name="'  + escHtml(p.name)            + '"' +
                            ' data-price="' + displayPrice               + '"' +
                            ' data-image="' + escHtml(p.image)           + '"' +
                            ' aria-label="Add to cart"><i class="fa-solid fa-cart-shopping"></i></button>' +
                    '</div>' +
                '</div>';

            card.querySelector('.bs-buy').addEventListener('click', function(e) {
                e.stopPropagation();
                var b = e.currentTarget;
                if (window.cartManager) window.cartManager.buyNow({ id: b.dataset.id, name: b.dataset.name, price: parseFloat(b.dataset.price), image: b.dataset.image });
            });
            card.querySelector('.bs-cart-btn').addEventListener('click', function(e) {
                e.stopPropagation();
                var b = e.currentTarget;
                if (window.cartManager) window.cartManager.addToCart({ id: b.dataset.id, name: b.dataset.name, price: parseFloat(b.dataset.price), image: b.dataset.image });
            });
            card.addEventListener('click', function(e) {
                if (e.target.closest('.bs-buy') || e.target.closest('.bs-cart-btn')) return;
                if (isDragging) return;
                var dist = wrapDist(idx - current);
                if (Math.abs(dist) > 0.35) { target = Math.round(current) + Math.round(dist); ensureRunning(); restartAuto(); }
            });

            trackEl.appendChild(card);
            cards.push(card);

            /* dot */
            var dot = document.createElement('div');
            dot.className = 'bs-dot' + (idx === 0 ? ' active' : '');
            dot.dataset.i = idx;
            dot.addEventListener('click', function() {
                var d = wrapDist(parseInt(this.dataset.i) - current);
                target = Math.round(current) + Math.round(d);
                ensureRunning(); restartAuto();
            });
            dotsEl.appendChild(dot);
            dots.push(dot);
        });
    }

    function updateDots() {
        var active = ((Math.round(current) % N) + N) % N;
        dots.forEach(function(d, i) { d.classList.toggle('active', i === active); });
    }

    function render() {
        var st = stride(), cw = containerEl.clientWidth, w = cardW(), cx = cw * 0.5 - w * 0.5;
        cards.forEach(function(card, i) {
            var dist = wrapDist(i - current), abs = Math.abs(dist);
            card.style.transform     = 'translateX(' + (cx + dist * st) + 'px) translateY(-50%) scale(' + Math.max(0.78, 1 - abs * 0.08) + ')';
            card.style.opacity       = Math.max(0, 1 - abs * 0.40).toFixed(3);
            card.style.zIndex        = Math.round(20 - abs * 4);
            card.style.pointerEvents = abs > 1.8 ? 'none' : 'auto';
            card.classList.toggle('active', abs < 0.35);
        });
        updateDots();
    }

    function tick() {
        var diff = wrapDist(target - current);
        if (Math.abs(diff) < 0.001) {
            current = Math.round(current); target = current;
            var shift = Math.round(current / N) * N; current -= shift; target -= shift;
            render(); rafId = null; return;
        }
        current += diff * LERP; render();
        rafId = requestAnimationFrame(tick);
    }

    function ensureRunning() { if (!rafId) rafId = requestAnimationFrame(tick); }
    function move(dir)       { target = Math.round(current) + dir; ensureRunning(); restartAuto(); }
    function startAuto()     { autoTimer = setInterval(function(){ move(1); }, INTERVAL); }
    function stopAuto()      { clearInterval(autoTimer); autoTimer = null; }
    function restartAuto()   { stopAuto(); startAuto(); }

    prevBtn.addEventListener('click', function(){ move(-1); });
    nextBtn.addEventListener('click', function(){ move(1); });
    containerEl.addEventListener('mouseenter', stopAuto);
    containerEl.addEventListener('mouseleave', function(){ if (!isDragging) startAuto(); });

    var dragOX = 0, dragOP = 0;
    containerEl.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return;
        isDragging = true; dragOX = e.clientX; dragOP = current;
        cancelAnimationFrame(rafId); rafId = null; stopAuto();
        containerEl.classList.add('bs-dragging'); e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        current = dragOP - (e.clientX - dragOX) / stride(); render();
    });
    document.addEventListener('mouseup', function() {
        if (!isDragging) return;
        isDragging = false; containerEl.classList.remove('bs-dragging');
        target = Math.round(current); ensureRunning(); restartAuto();
    });

    var tOX = 0, tOY = 0, tOP = 0, tIsH = false;
    containerEl.addEventListener('touchstart', function(e) {
        tOX = e.touches[0].clientX; tOY = e.touches[0].clientY; tOP = current; tIsH = false;
        cancelAnimationFrame(rafId); rafId = null; stopAuto();
    }, { passive: true });
    containerEl.addEventListener('touchmove', function(e) {
        var dx = e.touches[0].clientX - tOX, dy = e.touches[0].clientY - tOY;
        if (!tIsH && Math.abs(dx) > 8 && Math.abs(dx) > Math.abs(dy)) tIsH = true;
        if (tIsH) { e.preventDefault(); current = tOP - dx / stride(); render(); }
    }, { passive: false });
    containerEl.addEventListener('touchend', function() {
        target = Math.round(current); ensureRunning(); restartAuto();
    });

    var resizeTimer;
    window.addEventListener('resize', function() { clearTimeout(resizeTimer); resizeTimer = setTimeout(render, 80); });

    fetch(API_URL)
        .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function(products) {
            loadingEl.style.display = 'none';
            if (!Array.isArray(products) || products.length === 0) {
                /* no sales data yet — hide section silently */
                document.querySelector('.bs-section').style.display = 'none';
                return;
            }
            buildCards(products);
            wrapperEl.style.display = 'flex';
            render();
            startAuto();
        })
        .catch(function(err) {
            console.warn('[best-sellers] Could not load products:', err);
            loadingEl.style.display = 'none';
        });
})();
</script>