// profile-account.js
const PROFILE_API = '/lookgood/userBack_end/profileAPI.php';
const ADDRESS_API = '/lookgood/userBack_end/addressAPI.php';

let lastUsernameChange = localStorage.getItem('lastUsernameChange') ? parseInt(localStorage.getItem('lastUsernameChange')) : null;
let addressList = [];
let pendingDeleteAddressId = null;

// ── Helper ────────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
  if (window.profileUtils?.showToast) return window.profileUtils.showToast(message, type);
  const existing = document.querySelector('.profile-toast');
  if (existing) existing.remove();
  const iconMap = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
  const toast = document.createElement('div');
  toast.className = `profile-toast toast-${type}`;
  toast.innerHTML = `<i class="fas ${iconMap[type]}"></i> ${message}`;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2700);
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
}

// ── Profile Load / Save ──────────────────────────────────────────────────
async function loadProfileFromDB() {
  try {
    const res = await fetch(PROFILE_API);
    if (!res.ok) throw new Error();
    const data = await res.json();
    if (data.error) throw new Error();
    document.getElementById('firstName').value = data.firstName || '';
    document.getElementById('lastName').value = data.lastName || '';
    document.getElementById('username').value = data.username || '';
    document.getElementById('email').value = data.email || '';
    document.getElementById('phone').value = data.phone || '';
    updateViewFromForm();
    if (data.avatar) setAvatarImage(data.avatar);
    // Member since badge
    const badge = document.querySelector('.profile-member-badge');
    if (badge && data.memberSince) badge.innerHTML = '<i class="fas fa-star"></i> Member since ' + data.memberSince;
  } catch (e) {
    showToast('Failed to load profile. Please refresh the page.', 'error');
  }
}

function updateViewFromForm() {
  const first = document.getElementById('firstName').value;
  const last = document.getElementById('lastName').value;
  document.getElementById('view-fullname').innerText = first + ' ' + last;
  document.getElementById('view-username').innerText = '@' + document.getElementById('username').value;
  document.getElementById('view-email').innerText = document.getElementById('email').value;
  document.getElementById('view-phone').innerText = document.getElementById('phone').value || '—';
  document.getElementById('heroName').innerText = first + ' ' + last;
  document.getElementById('heroUsername').innerText = '@' + document.getElementById('username').value;
}

async function saveProfileToDB(field, payload) {
  const res = await fetch(PROFILE_API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ field, ...payload }) });
  return res.json();
}

// ── Avatar Upload ────────────────────────────────────────────────────────
function setAvatarImage(url) {
  const avatarImg = document.getElementById('avatarImg');
  const placeholderIcon = document.getElementById('avatarPlaceholderIcon');
  const previewImg = document.getElementById('avatarPreviewImg');
  const previewIcon = document.getElementById('avatarPreviewIcon');
  if (url && url !== '') {
    avatarImg.src = url;
    avatarImg.style.display = 'block';
    placeholderIcon.style.display = 'none';
    previewImg.src = url;
    previewImg.style.display = 'block';
    previewIcon.style.display = 'none';
    document.getElementById('btnRemoveAvatar').style.display = 'flex';
  } else {
    avatarImg.style.display = 'none';
    placeholderIcon.style.display = 'flex';
    previewImg.style.display = 'none';
    previewIcon.style.display = 'flex';
    document.getElementById('btnRemoveAvatar').style.display = 'none';
  }
}

async function uploadAvatar(file) {
  // Validate file on client side before sending
  const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
  if (!allowed.includes(file.type)) {
    showToast('Only JPG and PNG files are allowed.', 'error');
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    showToast('File must be under 2 MB.', 'error');
    return;
  }

  const btnUpload = document.getElementById('btnUploadAvatar');
  const originalLabel = btnUpload ? btnUpload.innerHTML : '';
  if (btnUpload) { btnUpload.disabled = true; btnUpload.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...'; }

  const formData = new FormData();
  formData.append('avatar', file);
  // Reset the input so the same file can be re-selected if needed
  const input = document.getElementById('avatarInput');
  if (input) input.value = '';

  try {
    const res = await fetch(PROFILE_API + '?action=upload_avatar', { method: 'POST', body: formData });
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(data.error || 'Upload failed');
    if (!data.avatar_url) throw new Error('No URL returned from server');
    setAvatarImage(data.avatar_url);
    showToast('Profile picture updated!', 'success');
  } catch (e) {
    showToast(e.message || 'Failed to upload profile picture. Please try again.', 'error');
  } finally {
    if (btnUpload) { btnUpload.disabled = false; btnUpload.innerHTML = originalLabel; }
  }
}

// ── Change Password ──────────────────────────────────────────────────────
async function changePassword(current, newPwd) {
  const res = await fetch(PROFILE_API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ field: 'change_password', current_password: current, new_password: newPwd }) });
  return res.json();
}

// ── Address Management (without recipient name/phone) ────────────────────
async function loadAddresses() {
  try {
    const res = await fetch(ADDRESS_API);
    if (!res.ok) throw new Error();
    addressList = await res.json();
    if (!Array.isArray(addressList)) throw new Error();
  } catch (e) {
    showToast('Failed to load addresses. Please refresh the page.', 'error');
    addressList = [];
  }
  renderAddressList();
}

function renderAddressList() {
  const container = document.getElementById('addressList');
  if (!container) return;
  if (!addressList.length) {
    container.innerHTML = `<div class="empty-state"><i class="fas fa-map-marker-alt empty-state-icon"></i><p class="empty-state-text">No addresses saved yet.</p></div>`;
    return;
  }
  container.innerHTML = addressList.map(addr => {
    const isDefault = !!addr.is_default;
    const icon = (addr.label || '').toLowerCase().includes('work') ? 'fa-briefcase' : (addr.label || '').toLowerCase().includes('home') ? 'fa-home' : 'fa-tag';
    const lines = [addr.address_line1, addr.address_line2, `${addr.city}${addr.province ? ', ' + addr.province : ''}`, `${addr.zip_code} ${addr.region || ''}`].filter(Boolean);
    return `
      <div class="address-item-card ${isDefault ? 'is-default' : ''}" data-addr-id="${addr.id}">
        <div class="address-item-header">
          <div class="address-item-label-wrap">
            <span class="address-label-tag"><i class="fas ${icon}"></i> ${escapeHtml(addr.label || 'Address')}</span>
            ${isDefault ? '<span class="default-badge"><i class="fas fa-check"></i> Default</span>' : ''}
          </div>
          <div class="address-item-controls">
            <button class="btn-addr-edit" data-id="${addr.id}"><i class="fas fa-pen"></i> Edit</button>
            ${!isDefault ? `<button class="btn-addr-setdefault" data-id="${addr.id}"><i class="fas fa-check-circle"></i> Set Default</button>` : ''}
            <button class="btn-addr-delete" data-id="${addr.id}"><i class="fas fa-trash-alt"></i> Delete</button>
          </div>
        </div>
        <div class="address-item-body">
          <div>${lines.map(l => escapeHtml(l)).join('<br>')}</div>
          ${addr.delivery_notes ? `<div style="margin-top:6px;color:#888;font-size:13px;"><i class="fas fa-sticky-note"></i> ${escapeHtml(addr.delivery_notes)}</div>` : ''}
        </div>
      </div>`;
  }).join('');
  attachAddressCardEvents();
}

function attachAddressCardEvents() {
  document.querySelectorAll('.btn-addr-edit').forEach(btn => 
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openAddressForm(btn.dataset.id);
    })
  );
  document.querySelectorAll('.btn-addr-setdefault').forEach(btn => 
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      setDefaultAddress(btn.dataset.id);
    })
  );
  document.querySelectorAll('.btn-addr-delete').forEach(btn => 
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openDeleteAddressOverlay(btn.dataset.id);
    })
  );
}

function openDeleteAddressOverlay(id) {
  pendingDeleteAddressId = id;
  const overlay = document.getElementById('deleteAddressOverlay');
  if (overlay) overlay.style.display = 'flex';
}

function closeDeleteAddressOverlay() { document.getElementById('deleteAddressOverlay').style.display = 'none'; pendingDeleteAddressId = null; }

async function confirmDeleteAddress() {
  if (!pendingDeleteAddressId) return;
  try {
    const res = await fetch(ADDRESS_API, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: pendingDeleteAddressId }) });
    const data = await res.json();
    if (data.error) throw new Error(data.error);
  } catch (e) {
    showToast('Failed to delete address. Please try again.', 'error');
    closeDeleteAddressOverlay();
    return;
  }
  await loadAddresses();
  closeDeleteAddressOverlay();
  showToast('Address deleted', 'success');
}

async function setDefaultAddress(id) {
  try {
    const res = await fetch(ADDRESS_API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'set_default', id }) });
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    await loadAddresses();
    showToast('Default address updated', 'success');
  } catch (e) {
    showToast(e.message || 'Failed to update default address.', 'error');
  }
}

async function saveAddress(payload) {
  const res = await fetch(ADDRESS_API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
  return res.json();
}

function openAddressForm(editId = null) {
  const formCard = document.getElementById('card-address-form');
  const form = document.getElementById('address-form');
  form.reset();
  document.getElementById('editAddressId').value = '';
  document.getElementById('addrIsDefault').checked = false;
  if (editId) {
    const addr = addressList.find(a => String(a.id) === String(editId));
    if (addr) {
      document.getElementById('addressFormTitle').innerText = 'Edit Address';
      document.getElementById('editAddressId').value = addr.id;
      document.getElementById('addrLabel').value = addr.label || '';
      document.getElementById('addr1').value = addr.address_line1 || '';
      document.getElementById('addr2').value = addr.address_line2 || '';
      document.getElementById('city').value = addr.city || '';
      document.getElementById('province').value = addr.province || '';
      document.getElementById('zip').value = addr.zip_code || '';
      document.getElementById('region').value = addr.region || '';
      document.getElementById('addrNotes').value = addr.delivery_notes || '';
      document.getElementById('addrIsDefault').checked = !!addr.is_default;
    }
  } else {
    document.getElementById('addressFormTitle').innerText = 'Add New Address';
    if (addressList.length === 0) document.getElementById('addrIsDefault').checked = true;
  }
  formCard.classList.remove('hidden');
  formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function closeAddressForm() {
  document.getElementById('card-address-form').classList.add('hidden');
  document.getElementById('address-form').reset();
}

// ── Delete Account Overlay ── (unchanged, kept from original)
function initDeleteAccount() {
  const btnOpen    = document.getElementById('btnDeleteAccount');
  const overlay    = document.getElementById('deleteAccountOverlay');
  const btnClose   = document.getElementById('deleteOverlayClose');
  const btnDismiss = document.getElementById('btnDeleteOverlayDismiss');
  const btnConfirm = document.getElementById('btnConfirmDelete');
  const input      = document.getElementById('deleteConfirmInput');
  const errSpan    = document.getElementById('err-deleteConfirm');

  const openOverlay  = () => { if (overlay) { overlay.style.display = 'flex'; if (input) input.value = ''; if (errSpan) errSpan.innerText = ''; } };
  const closeOverlay = () => { if (overlay) overlay.style.display = 'none'; };

  btnOpen?.addEventListener('click', openOverlay);
  btnClose?.addEventListener('click', closeOverlay);
  btnDismiss?.addEventListener('click', closeOverlay);
  overlay?.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(); });

  btnConfirm?.addEventListener('click', async () => {
    if (!input || input.value.trim() !== 'DELETE') {
      if (errSpan) errSpan.innerText = 'Please type DELETE to confirm';
      return;
    }
    btnConfirm.disabled = true;
    btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    try {
      const res = await fetch(PROFILE_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ field: 'delete_account' })
      });
      const data = await res.json();
      if (data.error) throw new Error(data.error);
      showToast('Account deleted. Redirecting...', 'success');
      setTimeout(() => { window.location.href = '/lookgood/home/index.php'; }, 1500);
    } catch (e) {
      if (errSpan) errSpan.innerText = e.message || 'Failed to delete account. Please try again.';
      btnConfirm.disabled = false;
      btnConfirm.innerHTML = '<i class="fas fa-trash-alt"></i> Permanently Delete';
    }
  });
}

// ── Validation ───────────────────────────────────────────────────────────
function validateForm(formId) {
  if (formId === 'address-form') {
    let valid = true;
    const addr1 = document.getElementById('addr1');
    const city = document.getElementById('city');
    const province = document.getElementById('province');
    const zip = document.getElementById('zip');
    const region = document.getElementById('region');
    if (!addr1.value.trim()) { document.getElementById('err-addr1').innerText = 'Address required'; addr1.classList.add('error'); valid = false; } else document.getElementById('err-addr1').innerText = '';
    if (!city.value.trim()) { document.getElementById('err-city').innerText = 'City required'; city.classList.add('error'); valid = false; } else document.getElementById('err-city').innerText = '';
    if (!province.value.trim()) { document.getElementById('err-province').innerText = 'Province required'; province.classList.add('error'); valid = false; } else document.getElementById('err-province').innerText = '';
    if (!/^\d{4}$/.test(zip.value.trim())) { document.getElementById('err-zip').innerText = 'ZIP must be 4 digits'; zip.classList.add('error'); valid = false; } else document.getElementById('err-zip').innerText = '';
    if (!region.value) { document.getElementById('err-region').innerText = 'Select region'; region.classList.add('error'); valid = false; } else document.getElementById('err-region').innerText = '';
    return valid;
  }
  if (formId === 'profile-form') {
    let valid = true;
    const first = document.getElementById('firstName');
    const last = document.getElementById('lastName');
    const username = document.getElementById('username');
    if (first.value.trim().length < 2) { document.getElementById('err-firstName').innerText = 'First name too short'; first.classList.add('error'); valid = false; } else document.getElementById('err-firstName').innerText = '';
    if (last.value.trim().length < 2) { document.getElementById('err-lastName').innerText = 'Last name too short'; last.classList.add('error'); valid = false; } else document.getElementById('err-lastName').innerText = '';
    if (!/^[a-zA-Z0-9_]{3,20}$/.test(username.value.trim())) { document.getElementById('err-username').innerText = '3-20 letters, numbers, underscore'; username.classList.add('error'); valid = false; } else document.getElementById('err-username').innerText = '';
    return valid;
  }
  return true;
}

// ── Initialization ───────────────────────────────────────────────────────
function initAccount() {
  // Toggle edit mode
  document.querySelectorAll('.btn-edit').forEach(btn => btn.addEventListener('click', () => {
    const form = document.getElementById(btn.dataset.target);
    const cardView = form?.closest('.profile-card')?.querySelector('.card-view');
    const editBtn = form?.closest('.profile-card')?.querySelector('.btn-edit');
    form.classList.remove('hidden');
    cardView?.classList.add('hidden');
    if (editBtn) editBtn.style.display = 'none';
  }));
  document.querySelectorAll('.btn-cancel').forEach(btn => btn.addEventListener('click', () => {
    const form = document.getElementById(btn.dataset.target);
    const cardView = form?.closest('.profile-card')?.querySelector('.card-view');
    const editBtn = form?.closest('.profile-card')?.querySelector('.btn-edit');
    form.classList.add('hidden');
    cardView?.classList.remove('hidden');
    if (editBtn) editBtn.style.display = '';
    loadProfileFromDB();
  }));

  // Profile form submit
  document.getElementById('profile-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm('profile-form')) return;
    const newUsername = document.getElementById('username').value.trim();
    const oldUsername = document.getElementById('view-username').innerText.replace('@', '');
    if (newUsername !== oldUsername && lastUsernameChange && (Date.now() - lastUsernameChange) / (1000*60*60*24) < 90) {
      showToast('Username can change only every 90 days.', 'error');
      return;
    }
    const result = await saveProfileToDB('profile', {
      firstName: document.getElementById('firstName').value.trim(),
      lastName: document.getElementById('lastName').value.trim(),
      username: newUsername,
      phone: document.getElementById('phone').value.trim(),
    });
    if (result.error) showToast(result.error, 'error');
    else {
      if (newUsername !== oldUsername) localStorage.setItem('lastUsernameChange', Date.now());
      updateViewFromForm();
      document.querySelector('#profile-form').classList.add('hidden');
      document.querySelector('#profile-view').classList.remove('hidden');
      document.querySelector('.btn-edit').style.display = '';
      showToast('Profile saved', 'success');
    }
  });

  // Address form submit
  document.getElementById('address-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm('address-form')) return;
    const editId = document.getElementById('editAddressId').value;
    const payload = {
      action: editId ? 'update' : 'create',
      id: editId || undefined,
      label: document.getElementById('addrLabel').value.trim(),
      address_line1: document.getElementById('addr1').value.trim(),
      address_line2: document.getElementById('addr2').value.trim(),
      city: document.getElementById('city').value.trim(),
      province: document.getElementById('province').value.trim(),
      zip_code: document.getElementById('zip').value.trim(),
      region: document.getElementById('region').value,
      delivery_notes: document.getElementById('addrNotes').value.trim(),
      is_default: document.getElementById('addrIsDefault').checked ? 1 : 0,
    };
    try {
      await saveAddress(payload);
      await loadAddresses();
      closeAddressForm();
      showToast(editId ? 'Address updated' : 'Address added', 'success');
    } catch (err) { showToast('Failed to save address', 'error'); }
  });

  // Add address button
  document.getElementById('btnAddAddress')?.addEventListener('click', () => openAddressForm(null));
  document.getElementById('btnCancelAddressForm')?.addEventListener('click', closeAddressForm);

  // Avatar upload
  document.getElementById('btnUploadAvatar')?.addEventListener('click', () => document.getElementById('avatarInput').click());
  document.getElementById('avatarInput')?.addEventListener('change', (e) => { if (e.target.files[0]) uploadAvatar(e.target.files[0]); });
  document.getElementById('btnRemoveAvatar')?.addEventListener('click', async () => {
    try { await fetch(PROFILE_API + '?action=remove_avatar', { method: 'POST' }); } catch(e) {}
    setAvatarImage('');
    showToast('Avatar removed', 'success');
  });

  // Change password
  document.getElementById('changePasswordForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const current = document.getElementById('currentPassword').value;
    const newPwd = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    if (!current || !newPwd || !confirm) { showToast('Please fill all fields', 'error'); return; }
    if (newPwd.length < 8) { showToast('New password must be at least 8 characters', 'error'); return; }
    if (newPwd !== confirm) { showToast('Passwords do not match', 'error'); return; }
    const result = await changePassword(current, newPwd);
    if (result.error) showToast(result.error, 'error');
    else { showToast('Password changed successfully', 'success'); document.getElementById('changePasswordForm').reset(); }
  });

  // Delete account overlay (original implementation assumed)
  initDeleteAccount();

  // Address delete overlay
  document.getElementById('btnConfirmDeleteAddress')?.addEventListener('click', confirmDeleteAddress);
  document.getElementById('deleteAddressOverlayClose')?.addEventListener('click', closeDeleteAddressOverlay);
  document.getElementById('btnDeleteAddressOverlayDismiss')?.addEventListener('click', closeDeleteAddressOverlay);
  document.getElementById('deleteAddressOverlay')?.addEventListener('click', (e) => { if (e.target === document.getElementById('deleteAddressOverlay')) closeDeleteAddressOverlay(); });

  // Load data
  loadProfileFromDB();
  loadAddresses();


}

document.addEventListener('DOMContentLoaded', initAccount);