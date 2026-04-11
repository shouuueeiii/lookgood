// Page trackers
let currentProductsPage  = 1;
let currentInventoryPage = 1;
let currentDiscountsPage = 1;
const itemsPerPage = 5;
let pendingInventoryHighlightId = null;
let pendingDiscountHighlightCode = null;

// Selection trackers
let selectedProductId   = null;
let deleteTargetId      = null;
let currentInventoryId  = null;
let deleteDiscountId    = null;
let viewModalImageList  = [];
let currentViewImageIndex = 0;
const PRODUCT_ID_PATTERN = /^LGF-\d{3}$/;

// Mock data
const mockData = {
    products: []
};

function formatProductId(value) {
    const raw = String(value || '').trim().toUpperCase();
    if (!raw) return '';

    if (PRODUCT_ID_PATTERN.test(raw)) return raw;

    const match = raw.match(/^(?:LGF-)?(\d+)$/);
    if (!match) return raw;

    return `LGF-${match[1].padStart(3, '0')}`;
}

function normalizeExistingProductIds() {
    mockData.products.forEach((product) => {
        product.id = formatProductId(product.id);
    });
}

normalizeExistingProductIds();

// Helpers
function openModal(modalId)  { document.getElementById(modalId).style.display = 'flex'; }
function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }

document.querySelectorAll('.modal .close-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const modal = e.target.closest('.modal');
        if (modal) modal.style.display = 'none';
    });
});

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Discount status logic
function getDiscountStatus(discount) {
    const now   = new Date();
    const start = new Date(discount.startDate);
    const end   = new Date(discount.endDate);

    if (!discount.active)                         return { label: 'Inactive',      cls: 'badge-muted'   };
    if (discount.usageCount >= discount.usageLimit) return { label: 'Limit Reached', cls: 'badge-purple'  };
    if (now < start)                              return { label: 'Scheduled',     cls: 'badge-info'    };
    if (now > end)                                return { label: 'Expired',       cls: 'badge-danger'  };

    const daysLeft = Math.ceil((end - now) / (1000 * 60 * 60 * 24));
    if (daysLeft <= 7)                            return { label: 'Active',        cls: 'badge-warning' };
    return                                               { label: 'Active',        cls: 'badge-success' };
}

// Discount stats
function updateDiscountStats() {
    const now      = new Date();
    const list     = mockData.discounts;
    const total    = list.length;
    const active   = list.filter(d => {
        const s = getDiscountStatus(d);
        return s.label === 'Active';
    }).length;
    const expiring = list.filter(d => {
        if (!d.active) return false;
        const end = new Date(d.endDate);
        const days = Math.ceil((end - now) / (1000 * 60 * 60 * 24));
        return days >= 0 && days <= 7;
    }).length;
    const expired  = list.filter(d => new Date(d.endDate) < now).length;

    document.getElementById('totalDiscounts').textContent   = total;
    document.getElementById('activeDiscounts').textContent  = active;
    document.getElementById('expiringSoon').textContent     = expiring;
    document.getElementById('expiredDiscounts').textContent = expired;
}

// Render discounts table
function renderDiscounts() {
    const tbody       = document.querySelector('#discountsTable tbody');
    const search      = document.getElementById('discountSearch').value.toLowerCase();
    const statusFilter = document.getElementById('discountStatusFilter').value;

    const filtered = mockData.discounts.filter(d => {
        const matchSearch = d.code.toLowerCase().includes(search) ||
                            d.description.toLowerCase().includes(search);
        const status = getDiscountStatus(d).label;
        const matchStatus = !statusFilter || status === statusFilter;
        return matchSearch && matchStatus;
    });

    const totalPages  = Math.ceil(filtered.length / itemsPerPage);
    const start       = (currentDiscountsPage - 1) * itemsPerPage;
    const paginated   = filtered.slice(start, start + itemsPerPage);

    tbody.innerHTML = paginated.map(d => {
        const status    = getDiscountStatus(d);
        const typeLabel = d.type === 'percentage' ? `${d.value}%` : `₱${d.value}`;
        const typeText  = d.type === 'percentage' ? 'Percentage' : 'Fixed';
        return `
        <tr data-discount-code="${d.code}">
            <td><span class="discount-code">${d.code}</span></td>
            <td>${d.description || '—'}</td>
            <td>${typeText}</td>
            <td><strong>${typeLabel}</strong></td>
            <td>${d.usageCount} / ${d.usageLimit}</td>
            <td>${formatDate(d.endDate)}</td>
            <td><span class="badge ${status.cls}">${status.label}</span></td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-secondary btn-sm" onclick="openEditDiscountModal('${d.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" style="color:var(--danger);" onclick="openDeleteDiscountModal('${d.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    if (pendingDiscountHighlightCode) {
        highlightDiscountRow(pendingDiscountHighlightCode);
        pendingDiscountHighlightCode = null;
    }

    renderPagination('discountsPagination', currentDiscountsPage, totalPages, 'discounts');
    updateDiscountStats();
}

// Add discount
function addDiscount() {
    const code         = document.getElementById('discountCode').value.trim().toUpperCase();
    const description  = document.getElementById('discountDescription').value.trim();
    const type         = document.getElementById('discountType').value;
    const value        = parseFloat(document.getElementById('discountValue').value);
    const minPurchase  = parseFloat(document.getElementById('discountMinPurchase').value) || 0;
    const maxAmount    = parseFloat(document.getElementById('discountMaxAmount').value) || null;
    const startDate    = document.getElementById('discountStartDate').value;
    const endDate      = document.getElementById('discountEndDate').value;
    const usageLimit   = parseInt(document.getElementById('discountUsageLimit').value);
    const perUserLimit = parseInt(document.getElementById('discountPerUserLimit').value) || 1;
    const applicableTo = document.getElementById('discountApplicableTo').value;

    if (!code || isNaN(value) || !startDate || !endDate || isNaN(usageLimit)) {
        alert('Please fill out all required fields.');
        return;
    }
    if (new Date(startDate) >= new Date(endDate)) {
        alert('End date must be after start date.');
        return;
    }
    if (mockData.discounts.find(d => d.code === code)) {
        alert(`Discount code "${code}" already exists.`);
        return;
    }

    const newId = 'd' + Date.now();
    mockData.discounts.push({
        id: newId, code, description, type, value, minPurchase, maxAmount,
        startDate, endDate, usageLimit, usageCount: 0, perUserLimit, applicableTo, active: true
    });

    closeModal('addDiscountModal');
    document.getElementById('addDiscountModal').querySelector('form') &&
        document.getElementById('addDiscountModal').querySelector('form').reset();
    // manually clear fields
    ['discountCode','discountDescription','discountValue','discountMinPurchase',
     'discountMaxAmount','discountStartDate','discountEndDate','discountUsageLimit',
     'discountPerUserLimit'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    renderDiscounts();
    alert('Discount created successfully!');
}

// Open edit discount modal
function openEditDiscountModal(discountId) {
    const d = mockData.discounts.find(x => x.id === discountId);
    if (!d) return;

    document.getElementById('editDiscountId').value           = d.id;
    document.getElementById('editDiscountCode').value         = d.code;
    document.getElementById('editDiscountDescription').value  = d.description || '';
    document.getElementById('editDiscountType').value         = d.type;
    document.getElementById('editDiscountValue').value        = d.value;
    document.getElementById('editDiscountMinPurchase').value  = d.minPurchase || 0;
    document.getElementById('editDiscountMaxAmount').value    = d.maxAmount || '';
    document.getElementById('editDiscountStartDate').value    = d.startDate;
    document.getElementById('editDiscountEndDate').value      = d.endDate;
    document.getElementById('editDiscountUsageLimit').value   = d.usageLimit;
    document.getElementById('editDiscountPerUserLimit').value = d.perUserLimit || 1;
    document.getElementById('editDiscountApplicableTo').value = d.applicableTo || 'all';

    const toggle = document.getElementById('editDiscountActive');
    toggle.checked = d.active;
    document.getElementById('editDiscountStatusText').textContent = d.active ? 'Active' : 'Inactive';

    toggle.onchange = () => {
        document.getElementById('editDiscountStatusText').textContent =
            toggle.checked ? 'Active' : 'Inactive';
    };

    openModal('editDiscountModal');
}

// Save edited discount
function saveDiscount() {
    const id           = document.getElementById('editDiscountId').value;
    const d            = mockData.discounts.find(x => x.id === id);
    if (!d) return;

    const code         = document.getElementById('editDiscountCode').value.trim().toUpperCase();
    const startDate    = document.getElementById('editDiscountStartDate').value;
    const endDate      = document.getElementById('editDiscountEndDate').value;

    if (!code || !startDate || !endDate) {
        alert('Please fill out all required fields.');
        return;
    }
    if (new Date(startDate) >= new Date(endDate)) {
        alert('End date must be after start date.');
        return;
    }
    const duplicate = mockData.discounts.find(x => x.code === code && x.id !== id);
    if (duplicate) {
        alert(`Discount code "${code}" already exists.`);
        return;
    }

    d.code         = code;
    d.description  = document.getElementById('editDiscountDescription').value.trim();
    d.type         = document.getElementById('editDiscountType').value;
    d.value        = parseFloat(document.getElementById('editDiscountValue').value);
    d.minPurchase  = parseFloat(document.getElementById('editDiscountMinPurchase').value) || 0;
    d.maxAmount    = parseFloat(document.getElementById('editDiscountMaxAmount').value) || null;
    d.startDate    = startDate;
    d.endDate      = endDate;
    d.usageLimit   = parseInt(document.getElementById('editDiscountUsageLimit').value);
    d.perUserLimit = parseInt(document.getElementById('editDiscountPerUserLimit').value) || 1;
    d.applicableTo = document.getElementById('editDiscountApplicableTo').value;
    d.active       = document.getElementById('editDiscountActive').checked;

    closeModal('editDiscountModal');
    renderDiscounts();
    alert('Discount updated successfully!');
}

// Delete discount
function openDeleteDiscountModal(discountId) {
    deleteDiscountId = discountId;
    openModal('deleteDiscountModal');
}

function confirmDeleteDiscount() {
    if (!deleteDiscountId) return;
    mockData.discounts = mockData.discounts.filter(d => d.id !== deleteDiscountId);
    deleteDiscountId = null;
    closeModal('deleteDiscountModal');
    renderDiscounts();
    alert('Discount deleted successfully!');
}

// Image upload handlers
document.addEventListener('DOMContentLoaded', function() {
    // Add product image uploads (4 images)
    for (let i = 1; i <= 4; i++) {
        const addImageArea = document.getElementById(`addImagePreview${i}`);
        const addImageInput = document.getElementById(`addImage${i}`);

        if (addImageArea && addImageInput) {
            addImageArea.addEventListener('click', () => addImageInput.click());
            addImageInput.addEventListener('change', (e) => handleImagePreview(e, `addImagePreview${i}`));
        }
    }

    // Edit product image uploads (4 images)
    for (let i = 1; i <= 4; i++) {
        const editImageArea = document.getElementById(`editImagePreview${i}`);
        const editImageInput = document.getElementById(`editImage${i}`);

        if (editImageArea && editImageInput) {
            editImageArea.addEventListener('click', () => editImageInput.click());
            editImageInput.addEventListener('change', (e) => handleImagePreview(e, `editImagePreview${i}`));
        }
    }
});

function handleImagePreview(event, previewId) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file.');
        return;
    }

    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        alert('Image size must be less than 5MB.');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById(previewId);
        preview.innerHTML = `<img src="${e.target.result}" alt="Product image">`;
    };
    reader.readAsDataURL(file);
}
function addProduct() {
    const warning = document.getElementById('productIdWarning');
    if (warning && warning.style.display === 'flex') {
        alert('Please use a unique Product ID before submitting.');
        return;
    }

    // ✅ FORMAT + VALIDATE
    const rawId = document.getElementById('addProductId').value.trim();
    const id = formatProductId(rawId);

    const name = document.getElementById('addProductName').value.trim();
    const description = document.getElementById('addDescription').value.trim();
    const category = document.getElementById('addCategory').value;
    const stock = parseInt(document.getElementById('addStock').value) || 0;
    const price = parseFloat(document.getElementById('addPrice').value) || 0;
    const frameWidth = parseFloat(document.getElementById('addFrameWidth').value) || 0;
    const templeLength = parseFloat(document.getElementById('addTempleLength').value) || 0;
    const material = document.getElementById('addMaterial').value.trim();

    if (!id || !name || !category || price <= 0) {
        alert('Please fill in all required fields correctly.');
        return;
    }

    if (!PRODUCT_ID_PATTERN.test(id)) {
        alert('Product ID must use the LGF-001 format.');
        return;
    }

    const duplicateProductId = mockData.products.some(p => formatProductId(p.id) === id);
    if (duplicateProductId) {
        alert(`Product ID "${id}" already exists.`);
        return;
    }

    // ✅ PREPARE FORM DATA (FOR PHP)
    const formData = new FormData();
    formData.append('addProductID', id);
    formData.append('addProductName', name);
    formData.append('addProductDescription', description);
    formData.append('addProductCategory', category);
    formData.append('addProductPrice', price);
    formData.append('addProductStock', stock);

    // ✅ EXTRA FIELDS (NEW)
    formData.append('addProductFrame', frameWidth);
    formData.append('addProductTemple', templeLength);
    formData.append('addProductMaterial', material);

    // ✅ HANDLE IMAGES
    const images = [];
    for (let i = 1; i <= 4; i++) {
        const input = document.getElementById(`addImage${i}`);
        const file = input?.files?.[0];

        if (file) {
            formData.append(`addProductImage${i}`, file);
            images.push(URL.createObjectURL(file)); // for preview
        }
    }

    if (images.length === 0) {
        images.push('/global/jin.jpg');
    }

    // ✅ SEND TO SERVER
    fetch('../adminBack_end/add_products.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        console.log("ADD RESPONSE:", data);

        if (data.trim().toLowerCase() === 'success') {

            // ✅ ALSO UPDATE LOCAL STATE (instant UI)
            mockData.products.push({
                id,
                name,
                description,
                category,
                stock,
                price,
                images,
                frameWidth,
                templeLength,
                material,
                onSale: false,
                salePrice: null,
                saleStartDate: null,
                saleEndDate: null,
                saleLabel: null
            });

            // ✅ CLEAR FORM
            [
                'addProductId','addProductName','addDescription','addStock',
                'addPrice','addFrameWidth','addTempleLength','addMaterial'
            ].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });

            document.getElementById('addCategory').value = '';

            // ✅ RESET IMAGES
            for (let i = 1; i <= 4; i++) {
                const preview = document.getElementById(`addImagePreview${i}`);
                const input   = document.getElementById(`addImage${i}`);

                if (preview) {
                    preview.innerHTML = `
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload image</p>
                        <small>PNG, JPG up to 5MB</small>
                    `;
                }
                if (input) input.value = '';
            }

            if (warning) warning.style.display = 'none';
            document.getElementById('addProductId').style.borderColor = '';

            // ✅ UPDATE UI
            updateStats();
            renderProducts();

            alert('Product added successfully!');
            closeModal('addProductModal');

            // OPTIONAL: sync with DB again
            loadProducts();

        } else {
            alert('Add failed: ' + data);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Request failed.');
    });
}
function updateProduct() {
    const id = document.getElementById('editProductId').value;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('name', document.getElementById('editProductName').value.trim());
    formData.append('description', document.getElementById('editDescription').value.trim());
    formData.append('category', document.getElementById('editCategory').value);
    formData.append('price', document.getElementById('editPrice').value);
    formData.append('stock', document.getElementById('editStock').value);
    formData.append('frameWidth', document.getElementById('editFrameWidth').value);
    formData.append('templeLength', document.getElementById('editTempleLength').value);
    formData.append('material', document.getElementById('editMaterial').value);

    // ✅ HANDLE MULTIPLE IMAGES (1–4)
    for (let i = 1; i <= 4; i++) {
        const file = document.getElementById(`editImage${i}`).files[0];
        if (file) {
            formData.append(`image${i}`, file);
        }
    }

    fetch('../adminBack_end/edit_products.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        console.log("EDIT RESPONSE:", data);

        if (data.trim().toLowerCase() === 'success') {
            alert('Product updated successfully!');
            closeModal('editProductModal');
            loadProducts(); // ✅ reload from DB
        } else {
            alert('Update failed: ' + data);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Request failed.');
    });
}
function loadProducts() {
    fetch('../adminBack_end/get_products.php')
        .then(res => res.text()) // 👈 CHANGE THIS
        .then(data => {
            console.log("RAW RESPONSE:", data); // 👈 DEBUG

            try {
                const json = JSON.parse(data);
                mockData.products = json;

                renderProducts();
                renderInventory();
                updateStats();
                updateInvStats();

            } catch (e) {
                console.error("JSON ERROR:", e);
                alert("Invalid JSON from server");
            }
        })
        .catch(err => console.error("FETCH ERROR:", err));
}
function openViewModal(productId) {
    const product = mockData.products.find(p => p.id === productId);
    if (!product) return;

    // Populate view modal with product details
    document.getElementById('viewProductId').value = product.id;
    document.getElementById('viewProductName').value = product.name;
    document.getElementById('viewProductCategory').value = product.category;
    document.getElementById('viewProductPrice').value = product.price.toLocaleString();
    document.getElementById('viewProductStock').value = product.stock;
    document.getElementById('viewProductDescription').value = product.description || 'No description available';
    document.getElementById('viewFrameWidth').value = product.frameWidth || 'N/A';
    document.getElementById('viewTempleLength').value = product.templeLength || 'N/A';
    document.getElementById('viewMaterial').value = product.material || 'N/A';

    // Show sale info if on sale
    const saleInfoGroup = document.getElementById('saleInfoGroup');
    if (product.onSale) {
        document.getElementById('viewSalePrice').textContent = product.salePrice.toLocaleString();
        document.getElementById('viewSaleStartDate').textContent = product.saleStartDate || 'Not set';
        document.getElementById('viewSaleEndDate').textContent = product.saleEndDate || 'Not set';
        document.getElementById('viewSaleLabel').textContent = product.saleLabel || 'No label';
        saleInfoGroup.style.display = 'block';
    } else {
        saleInfoGroup.style.display = 'none';
    }

    // Handle images - similar to edit modal structure
    const images = product.images && product.images.length ? product.images : ['/global/jin.jpg'];
    viewModalImageList = images.slice(0, 4);
    currentViewImageIndex = 0;
    renderViewImages(viewModalImageList, currentViewImageIndex);

    openModal('viewProductModal');
}

function renderViewImages(images, selectedIndex) {
    const imageContainer = document.getElementById('viewProductImages');
    const mainImage = images[selectedIndex] || '/global/jin.jpg';
    const mainImageHtml = `
        <div id="viewMainImage" class="image-upload-area" style="background-image: url('${mainImage}'); background-size: cover; background-position: center; cursor: default;"></div>
    `;

    const thumbnailImages = images
        .map((img, index) => ({ img, index }))
        .filter(item => item.index !== selectedIndex);

    const thumbnailsHtml = `
        <div class="additional-images-row">
            ${thumbnailImages.map((item) => `
                <div class="image-upload-area thumbnail"
                    style="background-image: url('${item.img}'); background-size: cover; background-position: center;"
                    onclick="setViewMainImage(${item.index})"></div>
            `).join('')}
        </div>
    `;

    imageContainer.innerHTML = mainImageHtml + thumbnailsHtml;
}

function setViewMainImage(index) {
    if (!viewModalImageList || index < 0 || index >= viewModalImageList.length) return;
    currentViewImageIndex = index;
    renderViewImages(viewModalImageList, currentViewImageIndex);
}

function openEditModal(productId) {
    const product = mockData.products.find(p => p.id === productId);
    if (!product) return;

    document.getElementById('editProductId').value    = product.id;
    document.getElementById('editProductName').value  = product.name;
    document.getElementById('editDescription').value  = product.description || '';
    document.getElementById('editCategory').value     = product.category;
    document.getElementById('editStock').value        = product.stock;
    document.getElementById('editPrice').value        = product.price;
    document.getElementById('editFrameWidth').value   = product.frameWidth || '';
    document.getElementById('editTempleLength').value = product.templeLength || '';
    document.getElementById('editMaterial').value     = product.material || '';

    const images = product.images || ['/global/jin.jpg'];

    for (let i = 1; i <= 4; i++) {
        const preview = document.getElementById(`editImagePreview${i}`);
        const imageSrc = images[i - 1] || '/global/jin.jpg';
        if (preview) {
            preview.innerHTML = `<img src="${imageSrc}" alt="Product image">`;
        }
        const input = document.getElementById(`editImage${i}`);
        if (input) input.value = '';
    }

    openModal('editProductModal');
}

function openSaleModal(productId) {
    const product = mockData.products.find(p => p.id === productId);
    if (!product) return;

    document.getElementById('saleProductName').value = product.name;
    document.getElementById('saleOriginalPrice').value = product.price.toLocaleString();
    document.getElementById('salePrice').value = product.salePrice || '';
    document.getElementById('saleStartDate').value = product.saleStartDate || '';
    document.getElementById('saleEndDate').value = product.saleEndDate || '';
    document.getElementById('saleLabel').value = product.saleLabel || '';

    const removeBtn = document.getElementById('removeSaleBtn');
    if (product.onSale) {
        removeBtn.style.display = 'inline-block';
    } else {
        removeBtn.style.display = 'none';
    }

    selectedProductId = productId;
    openModal('saleProductModal');
}

function applySale() {
    const product = mockData.products.find(p => p.id === selectedProductId);
    if (!product) return;

    const salePrice = parseFloat(document.getElementById('salePrice').value);
    const startDate = document.getElementById('saleStartDate').value;
    const endDate = document.getElementById('saleEndDate').value;
    const label = document.getElementById('saleLabel').value.trim();

    if (!salePrice || salePrice <= 0) {
        alert('Please enter a valid sale price.');
        return;
    }

    if (salePrice >= product.price) {
        alert('Sale price must be less than the original price.');
        return;
    }

    if (startDate && endDate && new Date(startDate) >= new Date(endDate)) {
        alert('End date must be after start date.');
        return;
    }

    product.onSale = true;
    product.salePrice = salePrice;
    product.saleStartDate = startDate || null;
    product.saleEndDate = endDate || null;
    product.saleLabel = label || null;

    updateStats();
    renderProducts();
    alert('Sale applied successfully!');
    closeModal('saleProductModal');
}

function removeSale() {
    const product = mockData.products.find(p => p.id === selectedProductId);
    if (!product) return;

    product.onSale = false;
    product.salePrice = null;
    product.saleStartDate = null;
    product.saleEndDate = null;
    product.saleLabel = null;

    updateStats();
    renderProducts();
    alert('Sale removed successfully!');
    closeModal('saleProductModal');
}

function openDeleteModal(productId) {
    deleteTargetId = productId;
    openModal('deleteProductModal');
}

function confirmDelete() {
    if (!deleteTargetId) return;

    fetch(`../adminBack_end/delete_products.php?id=${encodeURIComponent(deleteTargetId)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mockData.products = mockData.products.filter(p => p.id !== deleteTargetId);
                deleteTargetId = null;
                closeModal('deleteProductModal');
                renderProducts();
                updateStats();
                updateInvStats();
            } else {
                alert('Delete failed: ' + (data.error || 'Unknown error'));
                deleteTargetId = null;
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            alert('Request failed.');
            deleteTargetId = null;
        });
}

// Inventory
function openInventoryModal(productId) {
    const product = mockData.products.find(p => p.id === productId);
    if (!product) {
        console.error('Product not found:', productId);
        alert('Product not found!');
        return;
    }
    currentInventoryId = productId;

    // Check if modal elements exist
    const productNameEl = document.getElementById('inventoryProductName');
    const stockDisplayEl = document.getElementById('currentStockDisplay');
    const priceDisplayEl = document.getElementById('currentPriceDisplay');
    const stockInputEl = document.getElementById('inventoryStock');
    const priceInputEl = document.getElementById('inventoryPrice');

    if (!productNameEl || !stockDisplayEl || !priceDisplayEl || !stockInputEl || !priceInputEl) {
        console.error('Modal elements not found');
        alert('Modal elements not found. Please refresh the page.');
        return;
    }

    // Reset modal content first
    productNameEl.textContent = 'Loading...';
    stockDisplayEl.textContent = '0';
    priceDisplayEl.textContent = '₱0.00';
    stockInputEl.value = '';
    priceInputEl.value = '';

    // Populate product info
    productNameEl.textContent = product.name;
    stockDisplayEl.textContent = product.stock.toString();
    priceDisplayEl.textContent = `₱${product.price.toFixed(2)}`;

    // Set form values
    stockInputEl.value = product.stock;
    priceInputEl.value = product.price.toFixed(2);

    openModal('updateInventoryModal');
}

function getStockStatus(stock) {
    if (stock === 0)   return { label: 'Out of Stock', class: 'badge-danger'  };
    if (stock < 15)    return { label: 'Low Stock',    class: 'badge-warning' };
    return                    { label: 'In Stock',     class: 'badge-success' };
}

// Stats
function updateStats() {
    const p = mockData.products;
    document.getElementById('totalProducts').textContent = p.length;
    document.getElementById('inStock').textContent       = p.filter(x => x.stock >= 15).length;
    document.getElementById('lowStock').textContent      = p.filter(x => x.stock > 0 && x.stock < 15).length;
    document.getElementById('outOfStock').textContent    = p.filter(x => x.stock === 0).length;
}

function updateInvStats() {
    const p = mockData.products;
    document.getElementById('totalItems').textContent      = p.length;
    document.getElementById('lowStockInv').textContent     = p.filter(x => x.stock > 0 && x.stock < 15).length;
    document.getElementById('outOfStockInv').textContent   = p.filter(x => x.stock === 0).length;
    document.getElementById('overstocked').textContent     = p.filter(x => x.stock > 100).length;
}

// Render products
function renderProducts() {
    const tableBody = document.querySelector('#productsTable tbody');
    const search    = document.getElementById('searchInput').value.toLowerCase();
    const category  = document.getElementById('categoryFilter').value;

    const filtered = mockData.products.filter(p => {
        const matchesSearch   = p.name.toLowerCase().includes(search) || p.id.toLowerCase().includes(search);
        const matchesCategory = !category || p.category === category;
        return matchesSearch && matchesCategory;
    });

    const totalPages  = Math.ceil(filtered.length / itemsPerPage);
    const start       = (currentProductsPage - 1) * itemsPerPage;
    const paginated   = filtered.slice(start, start + itemsPerPage);

    tableBody.innerHTML = paginated.map(p => `
        <tr>
            <td><img src="${p.images && p.images[0] ? p.images[0] : '/global/jin.jpg'}" alt="${p.name}" class="avatar" style="width:80px;height:80px;border-radius:8px;object-fit:cover;"></td>
            <td><strong>${p.id}</strong></td>
            <td><strong>${p.name}</strong></td>
            <td>${p.category}</td>
            <td><span class="badge ${p.onSale ? 'badge-purple' : 'badge-success'}">${p.onSale ? 'On Sale' : 'Regular'}</span></td>
            <td><strong>${p.onSale ? `<span style="text-decoration:line-through;color:#888;">₱${p.price}</span> <span style="color:#e74c3c;">₱${p.salePrice}</span>` : `₱${p.price}`}</strong></td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-secondary btn-sm" onclick="openViewModal('${p.id}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="openEditModal('${p.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="openSaleModal('${p.id}')" title="Put on Sale">
                        <i class="fas fa-tags"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm" style="color:var(--danger);" onclick="openDeleteModal('${p.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    renderPagination('productsPagination', currentProductsPage, totalPages, 'products');
}

// Render inventory
function renderInventory() {
    const tableBody  = document.querySelector('#inventoryTable tbody');
    const search     = document.getElementById('invSearchInput').value.toLowerCase();
    const stockFilter = document.getElementById('stockFilter').value;

    const filtered = mockData.products.filter(p => {
        const matchesSearch = p.name.toLowerCase().includes(search);
        const statusInfo    = getStockStatus(p.stock);
        const matchesStock  = !stockFilter || statusInfo.label === stockFilter;
        return matchesSearch && matchesStock;
    });

    const totalPages = Math.ceil(filtered.length / itemsPerPage);
    const start      = (currentInventoryPage - 1) * itemsPerPage;
    const paginated  = filtered.slice(start, start + itemsPerPage);

    tableBody.innerHTML = paginated.map(p => {
        const statusInfo = getStockStatus(p.stock);
        return `
        <tr data-product-id="${p.id}">
            <td><img src="${p.images && p.images[0] ? p.images[0] : '/global/jin.jpg'}" alt="${p.name}" class="avatar" style="width:80px;height:80px;border-radius:8px;object-fit:cover;"></td>
            <td><strong>${p.product_id}</strong></td>
            <td><strong>${p.name}</strong></td>
            <td>${p.category}</td>
            <td><strong>${p.stock}</strong></td>
            <td><span class="badge ${statusInfo.class}">${statusInfo.label}</span></td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-secondary btn-sm" onclick="openInventoryModal('${p.product_id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    renderPagination('inventoryPagination', currentInventoryPage, totalPages, 'inventory');

    if (pendingInventoryHighlightId) {
        highlightInventoryRow(pendingInventoryHighlightId);
        pendingInventoryHighlightId = null;
    }
}

function highlightInventoryRow(productId) {
    const row = document.querySelector(`#inventoryTable tbody tr[data-product-id="${productId}"]`);
    if (!row) return;

    row.classList.remove('notification-target-highlight');
    void row.offsetWidth;
    row.classList.add('notification-target-highlight');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function highlightDiscountRow(discountCode) {
    const row = document.querySelector(`#discountsTable tbody tr[data-discount-code="${discountCode}"]`);
    if (!row) return;

    row.classList.remove('notification-target-highlight');
    void row.offsetWidth;
    row.classList.add('notification-target-highlight');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function updateProductSectionHeading(tabId) {
    const heading = document.getElementById('productSectionHeading');
    if (!heading) return;

    const headings = {
        productsTab: 'Product Overview',
        inventoryTab: 'Inventory Management',
        discountsTab: 'Discounts & Vouchers'
    };

    heading.textContent = headings[tabId] || 'Product Overview';
}

function activateProductTab(tabId) {
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(t => t.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));

    const tabBtn = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
    const tabContent = document.getElementById(tabId);
    if (tabBtn) tabBtn.classList.add('active');
    if (tabContent) tabContent.classList.add('active');
    updateProductSectionHeading(tabId);
}

function handleNotificationDeepLink() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('source') !== 'notification') return;

    const tab = params.get('tab');
    const productId = params.get('productId');
    const discountCode = params.get('discountCode');

    if (tab === 'inventoryTab' || tab === 'inventory') {
        activateProductTab('inventoryTab');

        document.getElementById('invSearchInput').value = '';
        document.getElementById('stockFilter').value = '';

        if (productId) {
            const index = mockData.products.findIndex((p) => String(p.id) === String(productId));
            if (index >= 0) {
                currentInventoryPage = Math.floor(index / itemsPerPage) + 1;
                pendingInventoryHighlightId = String(productId);
            }
        }

        renderInventory();
        return;
    }

    if (tab === 'discountsTab' || tab === 'discounts') {
        activateProductTab('discountsTab');

        document.getElementById('discountSearch').value = '';
        document.getElementById('discountStatusFilter').value = '';

        if (discountCode) {
            const discountIndex = mockData.discounts.findIndex((d) => d.code === discountCode);
            if (discountIndex >= 0) {
                currentDiscountsPage = Math.floor(discountIndex / itemsPerPage) + 1;
                pendingDiscountHighlightCode = discountCode;
            }
        }

        renderDiscounts();
        return;
    }

    if (tab === 'productsTab' || tab === 'products') {
        activateProductTab('productsTab');
        renderProducts();
    }
}

// Pagination
function renderPagination(containerId, currentPage, totalPages, type) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';

    const createBtn = (text, page, disabled = false) => {
        const btn = document.createElement('button');
        btn.className  = 'pagination-btn';
        btn.innerHTML  = text;
        btn.disabled   = disabled;
        if (!disabled) btn.onclick = () => changePage(page, type);
        if (page === currentPage) btn.classList.add('active');
        return btn;
    };

    container.appendChild(createBtn('<', currentPage - 1, currentPage === 1));

    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage   = Math.min(totalPages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1)
        startPage = Math.max(1, endPage - maxVisible + 1);

    for (let i = startPage; i <= endPage; i++) {
        container.appendChild(createBtn(i, i));
    }

    container.appendChild(createBtn('>', currentPage + 1, currentPage === totalPages));
}

function changePage(page, type) {
    if (type === 'products')  { currentProductsPage  = page; renderProducts();  }
    if (type === 'inventory') { currentInventoryPage = page; renderInventory(); }
    if (type === 'discounts') { currentDiscountsPage = page; renderDiscounts(); }
}

// ── DOMContentLoaded ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const tabs        = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');

    // Initialize tabs
    tabs.forEach(t => t.classList.remove('active'));
    tabContents.forEach(c => c.classList.remove('active'));

    // Set products tab as active initially
    const productsTab = document.querySelector('[data-tab="productsTab"]');
    const productsContent = document.getElementById('productsTab');
    if (productsTab && productsContent) {
        productsTab.classList.add('active');
        productsContent.classList.add('active');
        updateProductSectionHeading('productsTab');
        // Render products after a short delay to ensure CSS is loaded
        setTimeout(() => renderProducts(), 10);
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            activateProductTab(tab.dataset.tab);

            if (tab.dataset.tab === 'productsTab')  renderProducts();
            if (tab.dataset.tab === 'inventoryTab') renderInventory();
            if (tab.dataset.tab === 'discountsTab') renderDiscounts();
        });
    });

    // Discount search + filter live update
    document.getElementById('discountSearch').addEventListener('input', () => {
        currentDiscountsPage = 1;
        renderDiscounts();
    });
    document.getElementById('discountStatusFilter').addEventListener('change', () => {
        currentDiscountsPage = 1;
        renderDiscounts();
    });

    // Product search + filter live update
    document.getElementById('searchInput').addEventListener('input', () => {
        currentProductsPage = 1;
        renderProducts();
    });
    document.getElementById('categoryFilter').addEventListener('change', () => {
        currentProductsPage = 1;
        renderProducts();
    });

    // Inventory search + filter live update
    document.getElementById('invSearchInput').addEventListener('input', () => {
        currentInventoryPage = 1;
        renderInventory();
    });
    document.getElementById('stockFilter').addEventListener('change', () => {
        currentInventoryPage = 1;
        renderInventory();
    });
    document.getElementById('clearInvFilters').addEventListener('click', () => {
        document.getElementById('invSearchInput').value   = '';
        document.getElementById('stockFilter').value      = '';
        currentInventoryPage = 1;
        renderInventory();
    });

    // Auto-uppercase discount code inputs
    ['discountCode', 'editDiscountCode'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => { el.value = el.value.toUpperCase(); });
    });

    updateStats();
    updateInvStats();
    renderInventory();
    renderDiscounts();
    initNotifications();
    handleNotificationDeepLink();
    loadProducts();
});