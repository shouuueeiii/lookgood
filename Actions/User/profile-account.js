// profile-account.js
// Reads and saves profile data from/to the DB via profileAPI.php
// Path: New folder/Profile/ → userBack_end/
const PROFILE_API = '../../userBack_end/profileAPI.php';

let lastUsernameChange = localStorage.getItem('lastUsernameChange')
  ? parseInt(localStorage.getItem('lastUsernameChange')) : null;

let originalEmail = '';
let originalPhone = '';

// ── UI helpers ────────────────────────────────────────────────────────────────
function toggleEditMode(formId, editing) {
  const form     = document.getElementById(formId);
  const cardView = form?.closest('.profile-card')?.querySelector('.card-view');
  const editBtn  = form?.closest('.profile-card')?.querySelector('.btn-edit');
  if (!form) return;

  if (editing) {
    if (formId === 'profile-form') {
      originalEmail = document.getElementById('email').value;
      originalPhone = document.getElementById('phone').value;
    }
    form.classList.remove('hidden');
    cardView?.classList.add('hidden');
    if (editBtn) editBtn.style.display = 'none';

    if (formId === 'profile-form') {
      const usernameInput = document.getElementById('username');
      const cooldownMsg   = document.getElementById('usernameCooldownMsg');
      if (lastUsernameChange) {
        const daysSince = (Date.now() - lastUsernameChange) / (1000 * 60 * 60 * 24);
        if (daysSince < 90) {
          usernameInput.disabled = true;
          cooldownMsg.textContent = '⚠️ Can change after ' + Math.ceil(90 - daysSince) + ' days.';
        } else {
          usernameInput.disabled = false;
          cooldownMsg.textContent = '';
        }
      } else {
        usernameInput.disabled = false;
        if (cooldownMsg) cooldownMsg.textContent = '';
      }
    }
  } else {
    form.classList.add('hidden');
    cardView?.classList.remove('hidden');
    if (editBtn) editBtn.style.display = '';
    originalEmail = '';
    originalPhone = '';
  }
}

function clearFormErrors(formId) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.querySelectorAll('.form-input').forEach(i => i.classList.remove('error'));
  form.querySelectorAll('.form-error').forEach(e => e.textContent = '');
}

const validators = {
  'profile-form': {
    firstName: v => v.trim().length >= 2 ? '' : 'First name too short',
    lastName:  v => v.trim().length >= 2 ? '' : 'Last name too short',
    username:  v => /^[a-zA-Z0-9_]{3,20}$/.test(v.trim()) ? '' : '3-20 letters, numbers, underscore',
    phone:     v => v.trim() === '' || /^[\d\s\+\-\(\)]{7,15}$/.test(v.trim()) ? '' : 'Invalid phone'
  },
  'address-form': {
    addr1:    v => v.trim().length >= 5 ? '' : 'Address required',
    city:     v => v.trim().length >= 2 ? '' : 'City required',
    province: v => v.trim().length >= 2 ? '' : 'Province required',
    zip:      v => /^\d{4}$/.test(v.trim()) ? '' : 'ZIP must be 4 digits',
  }
};

const fieldIdMap = {
  firstName: 'firstName', lastName: 'lastName', username: 'username',
  phone: 'phone', addr1: 'addr1', city: 'city',
  province: 'province', zip: 'zip'
};

function validateForm(formId) {
  const rules = validators[formId];
  if (!rules) return true;
  let valid = true;
  for (const [key, rule] of Object.entries(rules)) {
    const inputId = fieldIdMap[key];
    const input   = document.getElementById(inputId);
    const errEl   = document.getElementById('err-' + inputId);
    if (!input) continue;
    const msg = rule(input.value);
    if (errEl) errEl.textContent = msg;
    input.classList.toggle('error', !!msg);
    if (msg) valid = false;
  }
  return valid;
}

function updateViewFromForm(formId) {
  if (formId === 'profile-form') {
    const first = document.getElementById('firstName').value;
    const last  = document.getElementById('lastName').value;
    const vFn = document.getElementById('view-fullname');
    const vUn = document.getElementById('view-username');
    const vEm = document.getElementById('view-email');
    const vPh = document.getElementById('view-phone');
    const hNm = document.getElementById('heroName');
    const hUn = document.getElementById('heroUsername');
    if (vFn) vFn.innerText = first + ' ' + last;
    if (vUn) vUn.innerText = '@' + document.getElementById('username').value;
    if (vEm) vEm.innerText = document.getElementById('email').value;
    if (vPh) vPh.innerText = document.getElementById('phone').value || '—';
    if (hNm) hNm.innerText = first + ' ' + last;
    if (hUn) hUn.innerText = '@' + document.getElementById('username').value;
  } else {
    const vA1 = document.getElementById('view-addr1');
    const vA2 = document.getElementById('view-addr2');
    const vCt = document.getElementById('view-city');
    const vPv = document.getElementById('view-province');
    const vZp = document.getElementById('view-zip');
    if (vA1) vA1.innerText = document.getElementById('addr1').value || '—';
    if (vA2) vA2.innerText = document.getElementById('addr2')?.value || '—';
    if (vCt) vCt.innerText = document.getElementById('city').value || '—';
    if (vPv) vPv.innerText = document.getElementById('province').value || '—';
    if (vZp) vZp.innerText = document.getElementById('zip').value || '—';
  }
}

// ── Load profile from DB ──────────────────────────────────────────────────────
async function loadProfileFromDB() {
  try {
    const res  = await fetch(PROFILE_API);
    if (!res.ok) return; // not logged in or error — leave PHP-rendered values
    const data = await res.json();
    if (data.error) return;

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
    set('firstName', data.firstName);
    set('lastName',  data.lastName);
    set('username',  data.username);
    set('email',     data.email);
    set('phone',     data.phone);
    set('addr1',     data.address);
    set('city',      data.city);
    set('province',  data.province);
    set('zip',       data.zipCode);

    updateViewFromForm('profile-form');
    updateViewFromForm('address-form');

    // Update hero
    const hNm = document.getElementById('heroName');
    const hUn = document.getElementById('heroUsername');
    const hMs = document.getElementById('heroMemberSince');
    if (hNm) hNm.innerText = (data.firstName || '') + ' ' + (data.lastName || '');
    if (hUn) hUn.innerText = '@' + (data.username || '');
    if (hMs && data.memberSince) hMs.textContent = 'Member since ' + data.memberSince;
  } catch (e) {
    console.warn('Could not load profile from DB:', e);
  }
}

// ── Save profile to DB ────────────────────────────────────────────────────────
async function saveProfileToDB(field, payload) {
  const res  = await fetch(PROFILE_API, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ field, ...payload })
  });
  return res.json();
}

// ── Init ──────────────────────────────────────────────────────────────────────
function initAccount() {
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => toggleEditMode(btn.dataset.target, true));
  });

  document.querySelectorAll('.btn-cancel').forEach(btn => {
    btn.addEventListener('click', () => {
      toggleEditMode(btn.dataset.target, false);
      clearFormErrors(btn.dataset.target);
      // Reload from DB to restore original values
      loadProfileFromDB();
    });
  });

  // Profile form submit
  document.getElementById('profile-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm('profile-form')) { showToast('Please fix errors', 'error'); return; }

    const newUsername = document.getElementById('username').value.trim();
    const oldUsername = (document.getElementById('view-username')?.innerText || '').replace('@', '');

    // 90-day username cooldown (tracked in localStorage)
    if (newUsername !== oldUsername && lastUsernameChange) {
      const daysSince = (Date.now() - lastUsernameChange) / (1000 * 60 * 60 * 24);
      if (daysSince < 90) {
        showToast('Username can change only every 90 days. ' + Math.ceil(90 - daysSince) + ' days left.', 'error');
        return;
      }
    }

    const submitBtn = e.target.querySelector('[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving…'; }

    try {
      const result = await saveProfileToDB('profile', {
        firstName: document.getElementById('firstName').value.trim(),
        lastName:  document.getElementById('lastName').value.trim(),
        username:  newUsername,
        phone:     document.getElementById('phone').value.trim(),
      });

      if (result.error) {
        showToast(result.error, 'error');
        return;
      }

      if (newUsername !== oldUsername) {
        lastUsernameChange = Date.now();
        localStorage.setItem('lastUsernameChange', lastUsernameChange);
      }

      updateViewFromForm('profile-form');
      toggleEditMode('profile-form', false);
      showToast('Profile saved', 'success');
    } catch (err) {
      showToast('Failed to save. Please try again.', 'error');
      console.error(err);
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; }
    }
  });

  // Address form submit
  document.getElementById('address-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateForm('address-form')) { showToast('Please fix errors', 'error'); return; }

    const submitBtn = e.target.querySelector('[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving…'; }

    try {
      const result = await saveProfileToDB('address', {
        address:  document.getElementById('addr1').value.trim(),
        city:     document.getElementById('city').value.trim(),
        province: document.getElementById('province').value.trim(),
        zipCode:  document.getElementById('zip').value.trim(),
      });

      if (result.error) {
        showToast(result.error, 'error');
        return;
      }

      updateViewFromForm('address-form');
      toggleEditMode('address-form', false);
      showToast('Address saved', 'success');
    } catch (err) {
      showToast('Failed to save. Please try again.', 'error');
      console.error(err);
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Save Changes'; }
    }
  });

  // Load real data on page init
  loadProfileFromDB();
}

document.addEventListener('DOMContentLoaded', initAccount);
