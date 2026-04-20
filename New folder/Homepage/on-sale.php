<?php
// on-sale.php - Limited Time Offers Carousel (no dots)

$base_img = '../../New folder/Resources/Images/Frames/';

$on_sale_products = [
    [
        'id'            => 1,
        'name'          => 'Aether Aviator',
        'sale_price'    => '1,679',
        'price_raw'     => 1679.00,
        'original_price'=> '2,799',
        'image'         => $base_img.'MEN/men1/men1 front.png',
        'badge'         => 'Summer Sale',
        'link'          => 'products.php?id=1'
    ],
    [
        'id'            => 3,
        'name'          => 'Steel Nomad',
        'sale_price'    => '2,499',
        'price_raw'     => 2499.00,
        'original_price'=> '3,299',
        'image'         => $base_img.'MEN/men3/men3 front.png',
        'badge'         => 'Sale',
        'link'          => 'products.php?id=3'
    ],
    [
        'id'            => 5,
        'name'          => 'Titanium Edge',
        'sale_price'    => '1,999',
        'price_raw'     => 1999.00,
        'original_price'=> '2,899',
        'image'         => $base_img.'UNISEX/unisex1/uni1 front.png',
        'badge'         => 'Hot Deal',
        'link'          => 'products.php?id=5'
    ],
    [
        'id'            => 4,
        'name'          => 'Carbon Core',
        'sale_price'    => '1,899',
        'price_raw'     => 1899.00,
        'original_price'=> '2,499',
        'image'         => $base_img.'MEN/men5/men5 front.png',
        'badge'         => false,
        'link'          => 'products.php?id=4'
    ],
    [
        'id'            => 2,
        'name'          => 'Havana Heritage',
        'sale_price'    => '2,199',
        'price_raw'     => 2199.00,
        'original_price'=> '3,099',
        'image'         => $base_img.'WOMEN/women10/women10 front.png',
        'badge'         => 'Limited',
        'link'          => 'products.php?id=2'
    ],
];

$unique_count = count($on_sale_products);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>On Sale | Summer Deals</title>
    <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,600;0,700;1,400;1,700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .os-section {
            padding: 56px 0 64px;
            background: linear-gradient(145deg, #fef7e8 0%, #ffffff 70%);
            font-family: 'DM Sans', sans-serif;
            overflow: hidden;
        }
        .os-header { text-align: center; margin-bottom: 20px; }
        .os-eyebrow {
            display: block;
            font-family: 'Spectral', serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #c8a96e;
            margin-bottom: 10px;
        }
        .os-title {
            font-family: 'Spectral', serif;
            font-size: 36px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
            margin-bottom: 10px;
        }
        .os-title em { font-style: italic; color: #c8a96e; }
        .os-divider {
            width: 48px; height: 2px;
            background: linear-gradient(90deg, #c8a96e, #e8d5a3);
            margin: 0 auto 14px;
            border-radius: 2px;
        }
        .os-subtitle { font-size: 14px; color: #888; }

        .os-carousel-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        .os-track-container {
            overflow: visible;
            width: 100%;
            max-width: 1000px;
            position: relative;
            height: 410px;
            touch-action: pan-y;
            cursor: grab;
        }
        .os-track-container.os-dragging { cursor: grabbing; }
        .os-track { position: relative; width: 100%; height: 100%; }

        .os-card {
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

        .os-card.active { box-shadow: 0 20px 35px -12px rgba(212, 175, 55, 0.4); }
        .os-badge {
            position: absolute; top: 10px; left: 10px;
            background: #c8a96e;
            color: white;
            font-size: 12px; font-weight: 600;
            padding: 3px 10px;
            border-radius: 9999px;
            z-index: 2;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .os-img-wrap {
            height: 210px;
            background: #f5f4f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .os-img-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .os-info { padding: 12px 12px 10px; text-align: center; }
        .os-name {
            font-family: 'Spectral', serif;
            font-size: 15px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .os-price {
            font-size: 20px;
            font-weight: 800;
            color: #c0392b;
            letter-spacing: -0.2px;
        }
        .os-old-price {
            font-size: 13px;
            font-weight: 500;
            color: #888;
            text-decoration: line-through;
            margin-top: 4px;
            margin-bottom: 4px;
        }

        .os-actions {
            display: flex;
            gap: 5px;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            margin-top: 0;
            transition: max-height 0.3s ease, opacity 0.3s ease, margin-top 0.3s ease;
        }
        .os-card.active .os-actions {
            max-height: 50px;
            opacity: 1;
            margin-top: 8px;
        }
        .os-buy {
            flex: 1;
            padding: 6px;
            background: #1a1a1a;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .os-buy:hover { background: #c8a96e; }

        .os-cart-btn {
            width: 30px;
            height: 30px;
            background: white;
            border: 1px solid #1a1a1a;
            border-radius: 50%;
            cursor: pointer;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .os-cart-btn:hover { background: #1a1a1a; color: #c8a96e; border-color: #1a1a1a; }

        .os-nav-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .os-carousel-btn {
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
        }
        .os-carousel-btn:hover {
            background: #c8a96e;
            border-color: #c8a96e;
            color: white;
            transform: scale(1.05);
        }

        /* No dots styles needed */

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
</head>
<body>

<section class="os-section">
    <div class="os-header">
        <span class="os-eyebrow">Limited Time</span>
        <h2 class="os-title"><em>Specs-tacular</em> Deals</h2>
        <div class="os-divider"></div>
        <p class="os-subtitle">Grab your frames before they're gone!</p>
    </div>

    <div class="os-carousel-wrapper">
        <div class="os-track-container">
            <div class="os-track">
                <?php foreach ($on_sale_products as $idx => $item): ?>
                    <div class="os-card" data-index="<?= $idx ?>">
                        <?php if ($item['badge']): ?>
                            <span class="os-badge"><?= htmlspecialchars($item['badge']) ?></span>
                        <?php endif; ?>
                        <div class="os-img-wrap">
                            <img src="<?= htmlspecialchars($item['image']) ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <div class="os-info">
                            <div class="os-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="os-price">₱<?= htmlspecialchars($item['sale_price']) ?></div>
                            <div class="os-old-price">₱<?= htmlspecialchars($item['original_price']) ?></div>
                            <div class="os-actions">
                                <button class="os-buy"
                                    onclick="event.stopPropagation(); window.cartManager && window.cartManager.buyNow({id:<?= $item['id'] ?>, name:'<?= addslashes($item['name']) ?>', price:<?= $item['price_raw'] ?>, image:'<?= addslashes($item['image']) ?>'})">
                                    Buy Now
                                </button>
                                <button class="os-cart-btn"
                                    onclick="event.stopPropagation(); window.cartManager && window.cartManager.addToCart({id:<?= $item['id'] ?>, name:'<?= addslashes($item['name']) ?>', price:<?= $item['price_raw'] ?>, image:'<?= addslashes($item['image']) ?>'})">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Nav buttons only — no dots -->
        <div class="os-nav-row">
            <button class="os-carousel-btn os-prev">&#10094;</button>
            <button class="os-carousel-btn os-next">&#10095;</button>
        </div>
    </div>
</section>

<script>
(function () {
    const N        = <?= $unique_count ?>;
    const GAP      = 20;
    const LERP     = 0.10;
    const INTERVAL = 3500;

    const container = document.querySelector('.os-track-container');
    const cards     = Array.from(document.querySelectorAll('.os-card'));
    const prevBtn   = document.querySelector('.os-prev');
    const nextBtn   = document.querySelector('.os-next');

    let current   = 0;
    let target    = 0;
    let rafId     = null;
    let autoTimer = null;
    let isDragging = false;

    const cardW  = () => cards[0]?.clientWidth || 240;
    const contW  = () => container.clientWidth;
    const stride = () => cardW() + GAP;

    function wrapDist(d) {
        return ((d % N) + N * 1.5) % N - N * 0.5;
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
        container.classList.add('os-dragging');
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
        container.classList.remove('os-dragging');
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
            if (e.target.closest('.os-buy') || e.target.closest('.os-cart-btn')) return;
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
</body>
</html>