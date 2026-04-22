<?php
// on-sale.php — partial, included by index.php
// NO DOCTYPE / html / head / body tags here.
?>
<style>
.os-section {
    padding: 56px 0 64px;
    background: linear-gradient(145deg, #fef7e8 0%, #ffffff 70%);
    font-family: 'DM Sans', sans-serif;
    overflow: hidden;
    padding-top: 160px;
    
}
.os-section.os-hidden { display: none; }
.os-header { text-align: center; margin-bottom: 20px; }
.os-eyebrow {
    display: block;
    font-family: 'Spectral', serif;
    font-size: 11px; font-weight: 600;
    letter-spacing: 0.18em; text-transform: uppercase;
    color: #c8a96e; margin-bottom: 10px;
}
.os-title {
    font-family: 'Spectral', serif;
    font-size: 36px; font-weight: 700;
    color: #1a1a1a; line-height: 1.2; margin-bottom: 10px;
}
.os-title em { font-style: italic; color: #c8a96e; }
.os-divider {
    width: 48px; height: 2px;
    background: linear-gradient(90deg, #c8a96e, #e8d5a3);
    margin: 0 auto 14px; border-radius: 2px;
}
.os-subtitle { font-size: 14px; color: #888; }
.os-loading {
    display: flex; justify-content: center;
    align-items: center; gap: 16px; padding: 40px 0;
}
.os-skeleton-card {
    width: 240px; height: 340px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: os-shimmer 1.4s infinite;
    border-radius: 20px;
}
.os-skeleton-card:nth-child(2) { animation-delay: 0.1s; }
.os-skeleton-card:nth-child(3) { animation-delay: 0.2s; }
@keyframes os-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.os-carousel-wrapper {
    display: flex; flex-direction: column;
    align-items: center; gap: 24px;
}
.os-track-container {
    overflow: visible; width: 100%; max-width: 1000px;
    position: relative; height: 410px;
    touch-action: pan-y; cursor: grab;
}
.os-track-container.os-dragging { cursor: grabbing; }
.os-track { position: relative; width: 100%; height: 100%; }
.os-card {
    position: absolute; top: 50%; left: 0;
    width: 240px; background: white; border-radius: 20px;
    overflow: hidden; cursor: pointer;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    will-change: transform, opacity;
    transition: box-shadow 0.3s ease;
}
.os-card.active { box-shadow: 0 20px 35px -12px rgba(212,175,55,0.4); }

.os-img-wrap {
    height: 210px; background: #f5f4f1;
    display: flex; align-items: center; justify-content: center;
}
.os-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.os-info { padding: 12px 12px 10px; text-align: center; }
.os-name {
    font-family: 'Spectral', serif;
    font-size: 15px; font-weight: 700;
    color: #1a1a1a; margin-bottom: 6px;
}
.os-price { font-size: 20px; font-weight: 800; color: #c0392b; letter-spacing: -0.2px; }
.os-old-price {
    font-size: 13px; font-weight: 500; color: #888;
    text-decoration: line-through; margin-top: 4px; margin-bottom: 4px;
}
.os-end-date { font-size: 10px; color: #aaa; margin-top: 2px; }
.os-actions {
    display: flex; gap: 5px;
    max-height: 0; overflow: hidden; opacity: 0; margin-top: 0;
    transition: max-height 0.3s ease, opacity 0.3s ease, margin-top 0.3s ease;
}
.os-card.active .os-actions { max-height: 50px; opacity: 1; margin-top: 8px; }
.os-buy {
    flex: 1; padding: 6px;
    background: #1a1a1a; color: white;
    border: none; border-radius: 30px;
    font-size: 11px; font-weight: 600;
    cursor: pointer; transition: background 0.2s;
}
.os-buy:hover { background: #c8a96e; }
.os-cart-btn {
    width: 30px; height: 30px;
    background: white; border: 1px solid #1a1a1a;
    border-radius: 50%; cursor: pointer; color: #1a1a1a;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, color 0.2s;
}
.os-cart-btn:hover { background: #1a1a1a; color: #c8a96e; border-color: #1a1a1a; }
.os-nav-row { display: flex; align-items: center; gap: 12px; }
.os-carousel-btn {
    width: 44px; height: 44px; border-radius: 50%;
    background: white; border: 2px solid #1a1a1a; color: #1a1a1a;
    font-size: 20px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s;
    user-select: none;
}
.os-carousel-btn:hover {
    background: #c8a96e; border-color: #c8a96e;
    color: white; transform: scale(1.05);
}
@media (max-width: 768px) {
    .os-card { width: 200px; }
    .os-track-container { height: 360px; }
    .os-img-wrap { height: 175px; }
    .os-price { font-size: 18px; }
}
@media (max-width: 480px) {
    .os-card { width: 165px; }
    .os-title { font-size: 32px; }
    .os-track-container { height: 325px; }
    .os-img-wrap { height: 150px; }
    .os-price { font-size: 16px; }
    .os-old-price { font-size: 11px; }
}
</style>

<section class="os-section" id="os-section">
    <div class="os-header">
        <span class="os-eyebrow">Limited Time</span>
        <h2 class="os-title"><em>Specs-tacular</em> Deals</h2>
        <div class="os-divider"></div>
        <p class="os-subtitle">Grab your frames before they're gone!</p>
    </div>

    <div class="os-loading" id="os-loading">
        <div class="os-skeleton-card"></div>
        <div class="os-skeleton-card"></div>
        <div class="os-skeleton-card"></div>
    </div>

    <div class="os-carousel-wrapper" id="os-carousel-wrapper" style="display:none;">
        <div class="os-track-container" id="os-track-container">
            <div class="os-track" id="os-track"></div>
        </div>
        <div class="os-nav-row">
            <button class="os-carousel-btn os-prev" id="os-prev">&#10094;</button>
            <button class="os-carousel-btn os-next" id="os-next">&#10095;</button>
        </div>
    </div>
</section>

<script>
(function () {
    /* API lives at adminBack_end/sale_product.php
       index.php is at: /lookgood/New%20folder/Homepage/index.php
       so from the page:  ../../adminBack_end/sale_product.php         */
    var API_URL = '../../adminBack_end/sale_product.php';

    var section     = document.getElementById('os-section');
    var loadingEl   = document.getElementById('os-loading');
    var wrapperEl   = document.getElementById('os-carousel-wrapper');
    var trackEl     = document.getElementById('os-track');
    var containerEl = document.getElementById('os-track-container');
    var prevBtn     = document.getElementById('os-prev');
    var nextBtn     = document.getElementById('os-next');

    var GAP = 20, LERP = 0.10, INTERVAL = 3500;
    var cards = [], N = 0, current = 0, target = 0;
    var rafId = null, autoTimer = null, isDragging = false;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function fmt(n)     { return '\u20b1' + Number(n).toLocaleString('en-PH'); }
    function fmtDate(d) {
        if (!d) return '';
        var dt = new Date(d + 'T00:00:00');
        return dt.toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'});
    }
    function cardW()     { return cards[0] ? cards[0].clientWidth : 240; }
    function stride()    { return cardW() + GAP; }
    function wrapDist(d) { return ((d % N) + N * 1.5) % N - N * 0.5; }

    function buildCards(products) {
        trackEl.innerHTML = '';
        cards = [];
        N = products.length;
        products.forEach(function(p, idx) {
            var endStr     = p.saleEndDate ? 'Ends ' + fmtDate(p.saleEndDate) : '';
            var card       = document.createElement('div');
            card.className     = 'os-card';
            card.dataset.index = idx;
            card.innerHTML =
                '<div class="os-img-wrap"><img src="' + escHtml(p.image) + '" alt="' + escHtml(p.name) + '" loading="lazy"></div>' +
                '<div class="os-info">' +
                    '<div class="os-name">'      + escHtml(p.name)    + '</div>' +
                    '<div class="os-price">'     + fmt(p.salePrice)   + '</div>' +
                    '<div class="os-old-price">' + fmt(p.price)       + '</div>' +
                    (endStr ? '<div class="os-end-date"><i class="fa-regular fa-clock"></i> ' + escHtml(endStr) + '</div>' : '') +
                    '<div class="os-actions">' +
                        '<button class="os-buy"'      +
                            ' data-id="'    + escHtml(p.id)    + '"' +
                            ' data-name="'  + escHtml(p.name)  + '"' +
                            ' data-price="' + p.salePrice      + '"' +
                            ' data-image="' + escHtml(p.image) + '">Buy Now</button>' +
                        '<button class="os-cart-btn"' +
                            ' data-id="'    + escHtml(p.id)    + '"' +
                            ' data-name="'  + escHtml(p.name)  + '"' +
                            ' data-price="' + p.salePrice      + '"' +
                            ' data-image="' + escHtml(p.image) + '"' +
                            ' aria-label="Add to cart"><i class="fa-solid fa-cart-shopping"></i></button>' +
                    '</div>' +
                '</div>';

            card.querySelector('.os-buy').addEventListener('click', function(e) {
                e.stopPropagation();
                var b = e.currentTarget;
                if (window.cartManager) window.cartManager.buyNow({ id: b.dataset.id, name: b.dataset.name, price: parseFloat(b.dataset.price), image: b.dataset.image });
            });
            card.querySelector('.os-cart-btn').addEventListener('click', function(e) {
                e.stopPropagation();
                var b = e.currentTarget;
                if (window.cartManager) window.cartManager.addToCart({ id: b.dataset.id, name: b.dataset.name, price: parseFloat(b.dataset.price), image: b.dataset.image });
            });
            card.addEventListener('click', function(e) {
                if (e.target.closest('.os-buy') || e.target.closest('.os-cart-btn')) return;
                if (isDragging) return;
                var dist = wrapDist(idx - current);
                if (Math.abs(dist) > 0.35) { target = Math.round(current) + Math.round(dist); ensureRunning(); restartAuto(); }
            });
            trackEl.appendChild(card);
            cards.push(card);
        });
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
        containerEl.classList.add('os-dragging'); e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        current = dragOP - (e.clientX - dragOX) / stride(); render();
    });
    document.addEventListener('mouseup', function() {
        if (!isDragging) return;
        isDragging = false; containerEl.classList.remove('os-dragging');
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
                section.classList.add('os-hidden');
                return;
            }
            buildCards(products);
            wrapperEl.style.display = 'flex';
            render();
            startAuto();
        })
        .catch(function(err) {
            console.warn('[on-sale] Could not load sale products:', err);
            loadingEl.style.display = 'none';
            section.classList.add('os-hidden');
        });
})();
</script>