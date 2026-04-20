        // ============================================================
        // product-detail.js
        // Fetches a single product from the DB via productsAPI.php
        // All gallery, reviews, cart, wishlist logic kept intact.
        // ============================================================

        // Path: New folder/Products/ → userBack_end/
        const PRODUCTS_API_DETAIL = '../../userBack_end/productsAPI.php';

        const REVIEWS_DATA = {
          reviews: []
        };


        function resolveReviewKey(productId, category) {
          if (REVIEWS_DATA[productId]) return productId;
          const normalizedCategory = String(category || '').toLowerCase();
          if (normalizedCategory === 'unisex') return 'LGF-U-001-26';
          if (normalizedCategory === 'female' || normalizedCategory === 'women') return 'LGF-W-001-26';
          if (normalizedCategory === 'male' || normalizedCategory === 'men') return 'LGF-M-001-26';
          return 'default';
        }

        // ============================================================
        // GLOBALS
        // ============================================================
        let currentProduct = null;
        let currentImageIndex = 0;
        let reviewsVisible = 3;

        // ============================================================
        // HELPER: generate mock sold count (stable per product)
        // ============================================================
        function getSoldCount(productId) {
          // deterministic hash from product id
          let hash = 0;
          for (let i = 0; i < productId.length; i++) {
            hash = ((hash << 5) - hash) + productId.charCodeAt(i);
            hash |= 0;
          }
          const base = Math.abs(hash) % 400 + 80; // between 80 and 479
          return base;
        }

        // ============================================================
        // LOAD PRODUCT — fetches from DB
        // ============================================================
        async function loadProduct(productId) {
          // Show a simple loading state
          const nameEl = document.getElementById('productName');
          if (nameEl) nameEl.textContent = 'Loading…';

          let product;
          try {
            const res = await fetch(PRODUCTS_API_DETAIL + '?id=' + encodeURIComponent(productId));
            if (!res.ok) throw new Error('Product not found (HTTP ' + res.status + ')');
            product = await res.json();
            if (product.error) throw new Error(product.error);
          } catch (err) {
            console.error('loadProduct error:', err);
            if (nameEl) nameEl.textContent = 'Product not found.';
            return;
          }

          currentProduct = product;

          document.title = currentProduct.name + ' — LookGood';
          const categoryLink = document.getElementById('breadcrumbCategory');
          if (categoryLink) categoryLink.innerHTML = '<a href="../Products/products-page.php?filter=' + currentProduct.category + '">' + currentProduct.category.charAt(0).toUpperCase() + currentProduct.category.slice(1) + '</a>';
          const breadcrumbProduct = document.getElementById('breadcrumbProduct');
          if (breadcrumbProduct) breadcrumbProduct.textContent = currentProduct.name;
          const productCategory = document.getElementById('productCategory');
          if (productCategory) productCategory.textContent = currentProduct.category.toUpperCase();
          const stockCount = document.getElementById('stockCount');
          if (stockCount) stockCount.textContent = currentProduct.stock;
          if (nameEl) nameEl.textContent = currentProduct.name;
          const priceEl = document.getElementById('productPrice');
          if (priceEl) priceEl.innerHTML = '&#8369;' + currentProduct.price.toLocaleString('en-PH', { minimumFractionDigits: 2 });
          const descEl = document.getElementById('productDescription');
          if (descEl) descEl.textContent = currentProduct.description || '';
          const fwEl = document.getElementById('frameWidth');
          if (fwEl) fwEl.textContent = currentProduct.frameWidth || '—';
          const fhEl = document.getElementById('frameHeight');
          if (fhEl) fhEl.textContent = currentProduct.frameHeight || '—';
          const tlEl = document.getElementById('templeLength');
          if (tlEl) tlEl.textContent = currentProduct.templeLength || '—';
          const lwEl = document.getElementById('lensWidth');
          if (lwEl) lwEl.textContent = currentProduct.lensWidth || '—';
          const matEl = document.getElementById('frameMaterial');
          if (matEl) matEl.textContent = currentProduct.material || '—';
          const colEl = document.getElementById('frameColor');
          if (colEl) colEl.textContent = currentProduct.color || '—';

          // Sold count (deterministic hash — no DB query needed)
          const soldEl = document.getElementById('soldNumber');
          if (soldEl) soldEl.textContent = getSoldCount(productId);

          updateStockRemainingMsg(currentProduct.stock);

          loadGallery(currentProduct.images || [currentProduct.image]);
          loadRelatedProducts(currentProduct.category, currentProduct.id);
          loadReviews(currentProduct.id, currentProduct.category);
        }

        function updateStockRemainingMsg(stock) {
          const msgSpan = document.getElementById('stockRemainingMsg');
          if (stock <= 5) {
            msgSpan.innerHTML = `<i class="fas fa-exclamation-circle"></i> Only ${stock} left!`;
            msgSpan.style.color = '#e67e22';
            msgSpan.style.background = '#fff3e0';
          } else if (stock <= 20) {
            msgSpan.innerHTML = `Hurry! Only ${stock} remaining`;
            msgSpan.style.color = '#f39c12';
            msgSpan.style.background = '#fef9e7';
          } else {
            msgSpan.innerHTML = `${stock} in stock`;
            msgSpan.style.color = '#2e7d32';
            msgSpan.style.background = '#e8f5e9';
          }
        }

        // ============================================================
        // GALLERY with vertical thumbnails + up/down buttons
        // ============================================================
        function loadGallery(images) {
          if (!images || images.length === 0) return;
          currentImageIndex = 0;
          const mainImage = document.getElementById('mainProductImage');
          mainImage.src = images[0];
          mainImage.alt = currentProduct.name;
          updateImageCounter(images);

          const strip = document.getElementById('thumbnailStrip');
          strip.innerHTML = images.map((img, i) => `
            <div class="thumbnail ${i === 0 ? 'active' : ''}" data-index="${i}">
              <img src="${img}" alt="${currentProduct.name} view ${i + 1}" loading="lazy">
            </div>
          `).join('');

          // Click sa thumbnail -> palitan ang main image
          strip.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', () => setActiveImage(parseInt(thumb.dataset.index)));
          });
        }

        function setActiveImage(index) {
          const images = currentProduct.images;
          if (!images || index < 0 || index >= images.length) return;
          currentImageIndex = index;
          document.getElementById('mainProductImage').src = images[index];
          updateImageCounter(images);

          document.querySelectorAll('#thumbnailStrip .thumbnail').forEach((t, i) => {
            t.classList.toggle('active', i === index);
          });
        }

        function updateImageCounter(images) {
          const counter = document.getElementById('imageCounter');
          if (counter) {
            counter.textContent = `${currentImageIndex + 1} / ${images.length}`;
          }
        }

        // ============================================================
        // RELATED PRODUCTS (unchanged)
        // ============================================================
        async function loadRelatedProducts(category, currentId) {
          const grid = document.getElementById('relatedProductsGrid');
          if (!grid) return;
          let allProducts = [];
          try {
            const res = await fetch(PRODUCTS_API_DETAIL + '?category=' + encodeURIComponent(category));
            if (res.ok) allProducts = await res.json();
          } catch (e) { /* silently fall through */ }
          const related = allProducts.filter(p => String(p.id) !== String(currentId)).slice(0, 4);
          if (!related.length) { grid.innerHTML = '<p class="no-related">No related products found.</p>'; return; }
          grid.innerHTML = related.map(p => `
            <article class="product-col" data-category="${p.category}" data-product-id="${p.id}">
              <div class="product-card" tabindex="0" role="button" aria-label="View details for ${p.name}">
                <div class="product-image-wrapper">
                  <img src="${p.image}" alt="${p.name} frames" class="product-image" loading="lazy" onerror="this.onerror=null;this.src='/lookgood/New%20folder/Resources/Images/glasses1.png';">
                  <span class="stock-badge"><i class="fas fa-box-open"></i> ${p.stock} in stock</span>
                </div>
                <div class="product-info">
                  <h2 class="product-name">${p.name}</h2>
                  <p class="product-price">&#8369;${p.price.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</p>
                  <div class="product-actions">
                    <button class="btn-buy-now" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-image="${p.image}">Buy Now</button>
                    <button class="btn-add-to-cart" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}" data-image="${p.image}"><i class="fas fa-cart-plus"></i></button>
                  </div>
                </div>
              </div>
            </article>
          `).join('');
          // attach event listeners (same as original)
          grid.querySelectorAll('.product-col').forEach(col => {
            col.addEventListener('click', (e) => {
              if (e.target.closest('.btn-add-to-cart') || e.target.closest('.btn-buy-now')) return;
              window.location.href = `product-detail.php?id=${col.dataset.productId}`;
            });
          });
          grid.querySelectorAll('.btn-add-to-cart').forEach(btn => {
            btn.addEventListener('click', (e) => {
              e.stopPropagation();
              window.cartManager?.addToCart({ id: btn.dataset.id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), image: btn.dataset.image });
            });
          });
          grid.querySelectorAll('.btn-buy-now').forEach(btn => {
            btn.addEventListener('click', (e) => {
              e.stopPropagation();
              window.cartManager?.buyNow({ id: btn.dataset.id, name: btn.dataset.name, price: parseFloat(btn.dataset.price), image: btn.dataset.image });
            });
          });
        }

        // ============================================================
        // REVIEWS (unchanged)
        // ============================================================
        function starsHTML(rating, size = 13) {
          // same as original
          return [5, 4, 3, 2, 1].reverse().map((_, i) => {
            const val = i + 1;
            const cls = val <= Math.floor(rating) ? 'fas fa-star' : (val - 0.5 <= rating ? 'fas fa-star-half-alt' : 'fas fa-star empty');
            return `<i class="${cls}" style="font-size:${size}px"></i>`;
          }).join('');
        }

        // FIX: Load real reviews from DB, fall back to static data if none exist
        let _cachedReviews = null;

        async function loadReviews(productId, category) {
          const grid        = document.getElementById('reviewsGrid');
          const loadMoreBtn = document.getElementById('loadMoreReviews');

          // Show loading state
          grid.innerHTML = '<p style="color:#999;padding:1rem;"><i class="fas fa-spinner fa-spin"></i> Loading reviews…</p>';
          loadMoreBtn.style.display = 'none';

          let data = null;
          try {
            const apiBase = '../../userBack_end/productFeedbackAPI.php';
            const res = await fetch(`${apiBase}?product_id=${encodeURIComponent(productId)}`);
            if (res.ok) {
              const json = await res.json();
              // Only use DB data if there are actual reviews
              if (json && json.count > 0) {
                data = json;
              }
            }
          } catch(e) {
            console.warn('Could not load reviews from DB:', e);
          }

          // Fall back to static data if no DB reviews yet
          if (!data) {
            data = REVIEWS_DATA[resolveReviewKey(productId, category)] || REVIEWS_DATA['default'];
          }

          _cachedReviews = data;

          document.getElementById('reviewsAvgScore').textContent = data.avg > 0 ? data.avg.toFixed(1) : '—';
          document.getElementById('reviewsStars').innerHTML = starsHTML(data.avg, 16);
          document.getElementById('reviewsCountLabel').textContent = `Based on ${data.count} review${data.count !== 1 ? 's' : ''}`;

          const total = Object.values(data.breakdown).reduce((a, b) => a + b, 0);
          const breakdownEl = document.getElementById('ratingBreakdown');
          breakdownEl.innerHTML = [5, 4, 3, 2, 1].map(star => {
            const count = data.breakdown[star] || 0;
            const pct = total > 0 ? Math.round((count / total) * 100) : 0;
            return `<div class="rating-bar-row"><span class="rating-bar-label">${star} <i class="fas fa-star"></i></span><div class="rating-bar-track"><div class="rating-bar-fill" style="width: ${pct}%"></div></div><span class="rating-bar-count">${count}</span></div>`;
          }).join('');

          reviewsVisible = 3;
          renderReviewCards(data.reviews, 0, reviewsVisible);

          if (data.reviews.length <= reviewsVisible) {
            loadMoreBtn.style.display = 'none';
          } else {
            loadMoreBtn.style.display = '';
            loadMoreBtn.onclick = () => {
              reviewsVisible += 3;
              renderReviewCards(data.reviews, 0, reviewsVisible);
              if (reviewsVisible >= data.reviews.length) loadMoreBtn.style.display = 'none';
            };
          }
        }

        function renderReviewCards(reviews, start, end) {
          const grid = document.getElementById('reviewsGrid');
          const visible = reviews.slice(start, end);
          grid.innerHTML = visible.map(r => `
            <div class="review-card">
              <div class="review-card-header">
                <div class="reviewer-avatar" style="background: ${r.color};">${r.name.charAt(0)}</div>
                <div class="reviewer-info"><div class="reviewer-name">${r.name}</div><div class="reviewer-meta">${r.date}</div></div>
                <div class="review-stars">${starsHTML(r.rating, 12)}</div>
              </div>
              <p class="review-text">${r.text}</p>
              <div class="review-footer"><span class="review-product-tag"><i class="fas fa-glasses"></i> Verified Purchase</span>${r.verified ? `<span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>` : ''}</div>
              ${r.response ? `<div class="lookgood-response"><div class="lookgood-response-header"><div class="lookgood-response-logo"><i class="fas fa-store"></i></div><span class="lookgood-response-label">LookGood Response </span><span class="lookgood-response-date">${r.response.date}</span></div><p class="lookgood-response-text">${r.response.text}</p></div>` : ''}
            </div>
          `).join('');
        }

        // ============================================================
        // EVENT LISTENERS
        // ============================================================
        function setupEventListeners() {
          const qtyInput = document.getElementById('qtyInput');
          document.getElementById('qtyIncrease').addEventListener('click', () => {
            let newVal = parseInt(qtyInput.value) + 1;
            if (newVal <= currentProduct.stock) qtyInput.value = newVal;
            else qtyInput.value = currentProduct.stock;
          });
          document.getElementById('qtyDecrease').addEventListener('click', () => {
            qtyInput.value = Math.max(parseInt(qtyInput.value) - 1, 1);
          });

          document.getElementById('addToCartBtn').addEventListener('click', () => {
            if (!currentProduct || !window.cartManager) return;
            let qty = parseInt(document.getElementById('qtyInput').value);
            if (qty > currentProduct.stock) qty = currentProduct.stock;
            for (let i = 0; i < qty; i++) {
              window.cartManager.addToCart({
                id: currentProduct.id, name: currentProduct.name, price: currentProduct.price, image: currentProduct.images[0]
              });
            }
          });

          document.getElementById('buyNowBtn').addEventListener('click', () => {
            if (!currentProduct || !window.cartManager) return;
            window.cartManager.buyNow({
              id: currentProduct.id, name: currentProduct.name, price: currentProduct.price, image: currentProduct.images[0]
            });
          });

          document.getElementById('wishlistBtn')?.addEventListener('click', async function () {
            if (!currentProduct) return;
            const icon = this.querySelector('i');
            const isWishlisted = icon.classList.contains('fas');

            // Optimistic UI update
            if (isWishlisted) {
              icon.classList.replace('fas', 'far');
              this.style.color = '';
              this.style.borderColor = '';
            } else {
              icon.classList.replace('far', 'fas');
              this.style.color = '#e53935';
              this.style.borderColor = '#e53935';
            }

            // Sync with DB
            try {
              const res = await fetch('../../userBack_end/wishlistAPI.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ product_id: currentProduct.id }),
              });
              const data = await res.json();
              if (!data.success) throw new Error('Toggle failed');
              const action = data.action; // 'added' or 'removed'
              showToast(action === 'added'
                ? 'Added ' + currentProduct.name + ' to wishlist'
                : 'Removed ' + currentProduct.name + ' from wishlist',
                action === 'added' ? 'success' : 'info');
            } catch (e) {
              // Revert on failure
              if (isWishlisted) {
                icon.classList.replace('far', 'fas');
                this.style.color = '#e53935';
                this.style.borderColor = '#e53935';
              } else {
                icon.classList.replace('fas', 'far');
                this.style.color = '';
                this.style.borderColor = '';
              }
              showToast('Could not update wishlist. Are you logged in?', 'error');
            }
          });
        }

        function initNavbarScroll() {
          const navbar = document.querySelector('.navbar');
          if (!navbar) return;
          window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            const atBottom = scrollY + window.innerHeight >= document.documentElement.scrollHeight - 100;
            if (atBottom) { navbar.classList.add('navbar-hidden'); navbar.classList.remove('scrolled'); }
            else if (scrollY > 50) { navbar.classList.add('scrolled'); navbar.classList.remove('navbar-hidden'); }
            else { navbar.classList.remove('scrolled', 'navbar-hidden'); }
          });
        }

        document.addEventListener('DOMContentLoaded', async function () {
          const params = new URLSearchParams(window.location.search);
          const productId = params.get('id') || '';
          if (!productId) {
            const nameEl = document.getElementById('productName');
            if (nameEl) nameEl.textContent = 'No product specified.';
          } else {
            await loadProduct(productId);
          }
          setupEventListeners();
          initNavbarScroll();
        });


        function showToast(message, type = 'info') {
          const toast = document.getElementById('toastMessage');
          const toastText = document.getElementById('toastText');
          if (!toast || !toastText) return;

          toastText.textContent = message;
          toast.className = `toast-message ${type}`;
          toast.classList.add('show');

          setTimeout(() => {
            toast.classList.remove('show');
          }, 3000);
        }