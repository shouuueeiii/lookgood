/* ── forgot-password.js ─────────────────────────────────────────────
   Handles:
   1. OTP digit-box navigation & hidden field assembly
   2. Password show/hide toggle
   3. Password strength meter
   4. Loading states for Send & Resend buttons
──────────────────────────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {

    /* ══ 1. OTP BOXES ══════════════════════════════════════════════ */
    const boxes     = document.querySelectorAll('.otp-box');
    const hidden    = document.getElementById('otpHidden');
    const submitBtn = document.getElementById('otpSubmitBtn');

    if (boxes.length) {
        const syncHidden = () => {
            const val = [...boxes].map(b => b.value).join('');
            if (hidden) hidden.value = val;
            if (submitBtn) submitBtn.disabled = val.length < 6;
        };

        boxes.forEach((box, idx) => {
            box.addEventListener('input', (e) => {
                // Allow only digits
                box.value = box.value.replace(/\D/g, '').slice(-1);
                box.classList.toggle('filled', box.value !== '');

                if (box.value && idx < boxes.length - 1) {
                    boxes[idx + 1].focus();
                }
                syncHidden();
            });

            box.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace') {
                    if (!box.value && idx > 0) {
                        boxes[idx - 1].value = '';
                        boxes[idx - 1].classList.remove('filled');
                        boxes[idx - 1].focus();
                    }
                    syncHidden();
                }
                // Allow arrow navigation
                if (e.key === 'ArrowLeft'  && idx > 0)              boxes[idx - 1].focus();
                if (e.key === 'ArrowRight' && idx < boxes.length-1) boxes[idx + 1].focus();
            });

            // Handle paste on any box
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData)
                               .getData('text').replace(/\D/g, '').slice(0, 6);
                [...pasted].forEach((char, i) => {
                    if (boxes[i]) {
                        boxes[i].value = char;
                        boxes[i].classList.add('filled');
                    }
                });
                syncHidden();
                const nextEmpty = [...boxes].findIndex(b => !b.value);
                if (nextEmpty !== -1) boxes[nextEmpty].focus();
                else boxes[boxes.length - 1].focus();
            });
        });

        // Auto-focus first box
        boxes[0]?.focus();
    }

    /* ══ 2. PASSWORD TOGGLE ════════════════════════════════════════ */
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const input    = targetId
                             ? document.getElementById(targetId)
                             : btn.previousElementSibling;
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type     = isHidden ? 'text' : 'password';
            btn.querySelector('i').className = isHidden
                ? 'fas fa-eye'
                : 'fas fa-eye-slash';
        });
    });

    /* ══ 3. PASSWORD STRENGTH BAR ══════════════════════════════════ */
    const newPassInput = document.getElementById('new_password');
    const fill         = document.getElementById('strengthFill');
    const label        = document.getElementById('strengthLabel');

    if (newPassInput && fill && label) {
        const levels = [
            { min: 0,  pct: 0,   color: '',        text: ''        },
            { min: 1,  pct: 25,  color: '#e74c3c',  text: 'Weak'    },
            { min: 6,  pct: 50,  color: '#f39c12',  text: 'Fair'    },
            { min: 10, pct: 75,  color: '#2ecc71',  text: 'Good'    },
            { min: 14, pct: 100, color: '#27ae60',  text: 'Strong'  },
        ];

        const score = (pw) => {
            let s = 0;
            if (pw.length >= 6)  s++;
            if (pw.length >= 10) s++;
            if (pw.length >= 14) s++;
            if (/[A-Z]/.test(pw)) s++;
            if (/[0-9]/.test(pw)) s++;
            if (/[^A-Za-z0-9]/.test(pw)) s++;
            return s;
        };

        newPassInput.addEventListener('input', () => {
            const pw  = newPassInput.value;
            const sc  = pw ? score(pw) : 0;
            const lvl = sc === 0 ? levels[0]
                      : sc <= 2  ? levels[1]
                      : sc <= 3  ? levels[2]
                      : sc <= 4  ? levels[3]
                      :            levels[4];

            fill.style.width           = lvl.pct + '%';
            fill.style.backgroundColor = lvl.color;
            label.textContent          = lvl.text;
            label.style.color          = lvl.color || '#999';
        });
    }

    /* ══ 4. LOADING STATES FOR SEND OTP & RESEND BUTTONS ═══════════ */
    // Send OTP form (step 1)
    const sendOtpForm = document.getElementById('sendOtpForm');
    if (sendOtpForm) {
        sendOtpForm.addEventListener('submit', () => {
            const btn = document.getElementById('sendOtpBtn');
            if (!btn) return;
            const textSpan = btn.querySelector('.btn-text');
            const loadingSpan = btn.querySelector('.btn-loading');
            const icon = btn.querySelector('.fa-paper-plane');
            
            if (textSpan) textSpan.style.display = 'none';
            if (loadingSpan) loadingSpan.style.display = 'inline';
            if (icon) icon.style.display = 'none';
            btn.disabled = true;
        });
    }

    // Resend code form (step 2)
    const resendForm = document.getElementById('resendForm');
    if (resendForm) {
        resendForm.addEventListener('submit', () => {
            const btn = document.getElementById('resendBtn');
            if (!btn) return;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            btn.disabled = true;
        });
    }
});