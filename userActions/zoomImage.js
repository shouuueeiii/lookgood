/**
 * modal.js – Image Modal Lightbox
 * Automatically opens any clicked image in a fullscreen modal with a close button.
 * Dependencies: none (pure JavaScript, works with any CSS framework or vanilla HTML)
 */

(function() {
  // Create modal elements
  const modal = document.createElement('div');
  modal.id = 'imageModal';
  modal.style.cssText = `
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    z-index: 19999;
    cursor: pointer;
    justify-content: center;
    align-items: center;
    text-align: center;
  `;

  const modalImg = document.createElement('img');
  modalImg.style.cssText = `
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    cursor: default;
  `;

  const closeBtn = document.createElement('span');
  closeBtn.innerHTML = '&times;';
  closeBtn.style.cssText = `
    position: absolute;
    top: 20px;
    right: 35px;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    z-index: 10000;
  `;
  closeBtn.onmouseover = () => closeBtn.style.color = '#ccc';
  closeBtn.onmouseout = () => closeBtn.style.color = '#fff';

  modal.appendChild(closeBtn);
  modal.appendChild(modalImg);
  document.body.appendChild(modal);

  // Function to open modal with specific image source
  function openModal(imgSrc) {
    modalImg.src = imgSrc;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // prevent scrolling behind modal
  }

  // Function to close modal
  function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }

  // Event: click on the modal background closes it
  modal.addEventListener('click', function(e) {
    // If the click is on the modal itself (background) or the close button, close.
    if (e.target === modal || e.target === closeBtn) {
      closeModal();
    }
  });

  // Event: ESC key closes modal
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      closeModal();
    }
  });

  // Attach click listeners to all images on the page
function bindImageClick() {
  const images = document.querySelectorAll('img:not(.no-modal)');  // ← exclude .no-modal
  images.forEach(img => {
    if (img.closest('.thumbnail-strip')) return;
    img.removeEventListener('click', imageClickHandler);
    img.addEventListener('click', imageClickHandler);
  });
}


function imageClickHandler(e) {
  if (this.classList.contains('no-modal')) return;  // ← skip kung may class na 'no-modal'
  e.stopPropagation();
  const src = this.getAttribute('src');
  if (src) openModal(src);
}

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindImageClick);
  } else {
    bindImageClick();
  }

  // Optional: If new images are added dynamically (e.g., via AJAX), you can call bindImageClick() again.
  // For simplicity, we also watch for DOM changes (optional, can be removed if not needed)
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.addedNodes.length) {
        bindImageClick();
      }
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });
})();