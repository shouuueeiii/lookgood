<?php
$base_img = '../../New folder/Resources/Images/Frames/';
$best_sellers = [
    ['name'=>'Havana Heritage',   'price'=>'3,099.00','image'=>$base_img.'WOMEN/women10/women10 front.png','badge'=>true, 'link'=>'products.php?id=2','rank'=>1],
    ['name'=>'Steel Nomad',       'price'=>'3,299.00','image'=>$base_img.'MEN/men3/men3 front.png',        'badge'=>false,'link'=>'products.php?id=3','rank'=>2],
    ['name'=>'Aether Aviator',    'price'=>'2,799.00','image'=>$base_img.'MEN/men1/men1 front.png',        'badge'=>false,'link'=>'products.php?id=1','rank'=>3],
    ['name'=>'Titanium Browline', 'price'=>'2,899.00','image'=>$base_img.'UNISEX/unisex1/uni1 front.png','badge'=>false,'link'=>'products.php?id=5','rank'=>4],
    ['name'=>'Carbon Core',       'price'=>'2,499.00','image'=>$base_img.'MEN/men5/men5 front.png',        'badge'=>false,'link'=>'products.php?id=4','rank'=>5],
];
$unique_count = count($best_sellers);
?>

<style>
.bs-section {
    padding: 56px 0 64px;
    background: linear-gradient(160deg, #f9f8f6 0%, #ffffff 60%);
    font-family: 'DM Sans', sans-serif;
    overflow: hidden;
}

/* HEADER — reduced margin-bottom */
.bs-header { text-align: center; margin-bottom: 35px; }
.bs-eyebrow {
    display: block;
    font-family: 'Spectral', serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #c8a96e;
    margin-bottom: 10px;
}
.bs-title {
    font-family: 'Spectral', serif;
    font-size: 36px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
    margin-bottom: 10px;
}
.bs-title em { font-style: italic; color: #c8a96e; }
.bs-divider {
    width: 48px; height: 2px;
    background: linear-gradient(90deg, #c8a96e, #e8d5a3);
    margin: 0 auto 14px;
    border-radius: 2px;
}
.bs-subtitle { font-size: 14px; color: #888; }

/* CAROUSEL WRAPPER — column layout so buttons never overlap cards */
.bs-carousel-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
}

.bs-track-container {
    overflow: visible;
    width: 100%;
    max-width: 1000px;
    position: relative;
    height: 390px;
    touch-action: pan-y;
    cursor: grab;
}
.bs-track-container.bs-dragging { cursor: grabbing; }
.bs-track { position: relative; width: 100%; height: 100%; }

/* CARDS — wider (260px) + taller (220px image) proportionally */
.bs-card {
    position: absolute;
    top: 50%;
    left: 0;
    width: 240px;
    background: white;
    border-radius: 20px;
    overflow: hidden;
    cursor: pointer;
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
    border: 1px solid #c8a96e;
    border-radius: 50%;
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
}
.bs-price { font-size: 13px; font-weight: 700; color: #007bff; }

/* ORIGINAL button styles — untouched */
.bs-actions {
    display: flex; gap: 5px;
    max-height: 0; overflow: hidden; opacity: 0; margin-top: 0;
    transition: max-height 0.3s ease, opacity 0.3s ease, margin-top 0.3s ease;
}
.bs-card.active .bs-actions { max-height: 50px; opacity: 1; margin-top: 8px; }
.bs-buy {
    flex: 1; padding: 6px;
    background: #1a1a1a; color: white;
    border: none; border-radius: 30px; font-size: 11px; cursor: pointer;
    transition: background 0.2s;
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

/* NAV BUTTONS — below the track, never overlapping cards */
.bs-nav-row {
    display: flex;
    align-items: center;
    gap: 12px;
}
.bs-carousel-btn {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: white;
    border: 2px solid #1a1a1a;
    color: #1a1a1a;
    font-size: 20px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s, border-color 0.2s, color 0.2s, transform 0.2s;
    user-select: none;
    -webkit-user-select: none;
}
.bs-carousel-btn:hover {
    background: #c8a96e;
    border-color: #c8a96e;
    color: white;
    transform: scale(1.05);
}

/* dot indicators */
.bs-dots { display: flex; gap: 6px; align-items: center; }
.bs-dot {
    width: 6px; height: 6px;
    border-radius: 50%; background: #ddd;
    cursor: pointer;
    transition: background 0.3s, transform 0.3s;
}
.bs-dot.active { background: #c8a96e; transform: scale(1.4); }

.bs-cta-wrap { text-align: center; margin-top: 32px;}

.bs-cta {
    display: inline-block; padding: 12px 38px;
    background: #1a1a1a; color: white;
    font-family: 'DM Sans', serif; font-size: 14px; font-weight: 600;
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
    .bs-title { font-size:35px; }
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

    <div class="bs-carousel-wrapper">
        <div class="bs-track-container">
            <div class="bs-track">
                <?php foreach ($best_sellers as $item): ?>
                <div class="bs-card">
                    <?php if ($item['badge']): ?>
                        <span class="bs-badge">&#9733; Best Seller</span>
                    <?php endif; ?>
                    <span class="bs-rank"><?= $item['rank'] ?></span>
                    <div class="bs-img-wrap">
                        <img src="<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="bs-info">
                        <div class="bs-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="bs-price">&#8369;<?= htmlspecialchars($item['price']) ?></div>
                        <div class="bs-actions">
                            <button class="bs-buy"
                                onclick="event.stopPropagation(); window.location='<?= $item['link'] ?>'">
                                Buy Now
                            </button>
                            <button class="bs-cart-btn"
                                onclick="event.stopPropagation(); addToCart(<?= $item['rank'] - 1 ?>)">
                                <i class="fa-solid fa-cart-shopping"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Nav buttons BELOW the track — never overlap cards -->
        <div class="bs-nav-row">
            <button class="bs-carousel-btn bs-prev">&#10094;</button>
            <div class="bs-dots">
                <?php for ($i = 0; $i < $unique_count; $i++): ?>
                    <div class="bs-dot <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>"></div>
                <?php endfor; ?>
            </div>
            <button class="bs-carousel-btn bs-next">&#10095;</button>
        </div>
    </div>

</section>

<script>
(function () {
    const N        = <?= $unique_count ?>;
    const GAP      = 20;
    const LERP     = 0.10;
    const INTERVAL = 3000;

    const container = document.querySelector('.bs-track-container');
    const cards     = Array.from(document.querySelectorAll('.bs-card'));
    const dots      = Array.from(document.querySelectorAll('.bs-dot'));
    const prevBtn   = document.querySelector('.bs-prev');
    const nextBtn   = document.querySelector('.bs-next');

    let current   = 0;
    let target    = 0;
    let rafId     = null;
    let autoTimer = null;
    let isDragging = false;

    const cardW  = () => cards[0]?.clientWidth || 260;
    const contW  = () => container.clientWidth;
    const stride = () => cardW() + GAP;

    function wrapDist(d) {
        return ((d % N) + N * 1.5) % N - N * 0.5;
    }

    function updateDots(idx) {
        const active = ((Math.round(idx) % N) + N) % N;
        dots.forEach((d, i) => d.classList.toggle('active', i === active));
    }

    function render() {
        const st = stride();
        const cw = contW();
        const w  = cardW();
        const cx = cw * 0.5 - w * 0.5;

        cards.forEach((card, i) => {
            const dist = wrapDist(i - current);
            const abs  = Math.abs(dist);

            card.style.transform     = `translateX(${cx + dist * st}px) translateY(-50%) scale(${Math.max(0.78, 1 - abs * 0.08)})`;
            card.style.opacity       = Math.max(0, 1 - abs * 0.40).toFixed(3);
            card.style.zIndex        = Math.round(20 - abs * 4);
            card.style.pointerEvents = abs > 1.8 ? 'none' : 'auto';

            card.classList.toggle('active', abs < 0.35);
        });
        updateDots(current);
    }

    function tick() {
        const diff = wrapDist(target - current);

        if (Math.abs(diff) < 0.001) {
            current = Math.round(current);
            target  = current;
            const shift = Math.round(current / N) * N;
            current -= shift;
            target  -= shift;
            render();
            rafId = null;
            return;
        }

        current += diff * LERP;
        render();
        rafId = requestAnimationFrame(tick);
    }

    function ensureRunning() {
        if (!rafId) rafId = requestAnimationFrame(tick);
    }

    function move(dir) {
        target = Math.round(current) + dir;
        ensureRunning();
        restartAuto();
    }

    prevBtn.addEventListener('click', () => move(-1));
    nextBtn.addEventListener('click', () => move(1));

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            const dist = wrapDist(i - current);
            target = Math.round(current) + Math.round(dist);
            ensureRunning();
            restartAuto();
        });
    });

    function startAuto()   { autoTimer = setInterval(() => move(1), INTERVAL); }
    function stopAuto()    { clearInterval(autoTimer); autoTimer = null; }
    function restartAuto() { stopAuto(); startAuto(); }

    container.addEventListener('mouseenter', stopAuto);
    container.addEventListener('mouseleave', () => { if (!isDragging) startAuto(); });

    let dragOriginX   = 0;
    let dragOriginPos = 0;

    container.addEventListener('mousedown', e => {
        if (e.button !== 0) return;
        isDragging    = true;
        dragOriginX   = e.clientX;
        dragOriginPos = current;
        cancelAnimationFrame(rafId); rafId = null;
        stopAuto();
        container.classList.add('bs-dragging');
        e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
        if (!isDragging) return;
        current = dragOriginPos - (e.clientX - dragOriginX) / stride();
        render();
    });

    document.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        container.classList.remove('bs-dragging');
        target = Math.round(current);
        ensureRunning();
        restartAuto();
    });

    let touchOriginX   = 0;
    let touchOriginY   = 0;
    let touchOriginPos = 0;
    let touchIsH       = false;

    container.addEventListener('touchstart', e => {
        touchOriginX   = e.touches[0].clientX;
        touchOriginY   = e.touches[0].clientY;
        touchOriginPos = current;
        touchIsH       = false;
        cancelAnimationFrame(rafId); rafId = null;
        stopAuto();
    }, { passive: true });

    container.addEventListener('touchmove', e => {
        const dx = e.touches[0].clientX - touchOriginX;
        const dy = e.touches[0].clientY - touchOriginY;

        if (!touchIsH && Math.abs(dx) > 8 && Math.abs(dx) > Math.abs(dy)) {
            touchIsH = true;
        }
        if (touchIsH) {
            e.preventDefault();
            current = touchOriginPos - dx / stride();
            render();
        }
    }, { passive: false });

    container.addEventListener('touchend', () => {
        target = Math.round(current);
        ensureRunning();
        restartAuto();
    });

    cards.forEach((card, i) => {
        card.addEventListener('click', e => {
            if (e.target.closest('.bs-buy') || e.target.closest('.bs-cart-btn')) return;
            if (isDragging) return;
            const dist = wrapDist(i - current);
            if (Math.abs(dist) > 0.35) {
                target = Math.round(current) + Math.round(dist);
                ensureRunning();
                restartAuto();
            }
        });
    });

    render();
    startAuto();

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(render, 80);
    });
})();
</script>