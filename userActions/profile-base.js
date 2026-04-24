// profile-base.js
function showToast(message, type = 'success') {
  const existing = document.querySelector('.profile-toast');
  if (existing) existing.remove();
  const iconMap = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
  const toast = document.createElement('div');
  toast.className = `profile-toast toast-${type}`;
  toast.innerHTML = `<i class="fas ${iconMap[type]}"></i> ${message}`;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 2700);
}

function initProfileTabs() {
  const tabs = document.querySelectorAll('.sidebar-tab');
  const panels = document.querySelectorAll('.profile-panel');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const targetId = tab.dataset.tab;
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      panels.forEach(p => p.classList.remove('active'));
      const panel = document.getElementById(`panel-${targetId}`);
      if (panel) panel.classList.add('active');
      sessionStorage.setItem('profileActiveTab', targetId);
    });
  });
  const saved = sessionStorage.getItem('profileActiveTab');
  if (saved) {
    const tab = document.querySelector(`.sidebar-tab[data-tab="${saved}"]`);
    if (tab) tab.click();
  }
}

function initNavbarScroll() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) navbar.classList.add('scrolled');
    else navbar.classList.remove('scrolled');
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initProfileTabs();
  initNavbarScroll();
});

window.profileUtils = { showToast };