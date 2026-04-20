
  (function() {
    // Toggle password visibility
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (toggleBtn && passwordInput) {
      toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        const icon = this.querySelector('i');
        if (icon) {
          icon.className = isHidden ? 'fas fa-eye' : 'fas fa-eye-slash';
        }
        passwordInput.focus();
      });
    }

    // Auto-hide error message after 2 seconds (mas maikli sa 5)
      // Auto‑hide error message after 5 seconds
      const errorDiv = document.getElementById('formFeedback');
      if (errorDiv && errorDiv.classList.contains('show')) {
        setTimeout(() => {
          errorDiv.classList.remove('show');
        }, 1500);
      }
    })();
