document.addEventListener('DOMContentLoaded', function () {

    // Helper: i-reset ang Send OTP button
    function resetSendOtpButton() {
        const btn = document.getElementById('sendOtpBtn');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Send Verification Code';  // siguraduhin na ito ang tamang original text
        }
    }

    // Password toggle
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.getAttribute('data-target'));
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye-slash');
            icon.classList.toggle('fa-eye');
        });
    });

    // Refresh CAPTCHA
    function refreshCaptcha() {
        const img = document.getElementById('captchaImg');
        if (img) img.src = 'captcha.php?' + Date.now();
        const input = document.getElementById('captchaInput');
        if (input) input.value = '';
    }
    const refreshBtn = document.getElementById('refreshCaptcha');
    if (refreshBtn) refreshBtn.addEventListener('click', refreshCaptcha);

    // Feedback helpers
    let formFeedbackTimeout = null;

    function requestJson(url, options) {
        return fetch(new URL(url, window.location.href).href, options).then(async (response) => {
            const rawText = await response.text();
            let data = {};

            try {
                data = rawText ? JSON.parse(rawText) : {};
            } catch (parseError) {
                throw new Error(`Unexpected server response: ${rawText || 'empty response'}`);
            }

            if (!response.ok) {
                const details = data.error || data.message || rawText || `HTTP ${response.status}`;
                throw new Error(details);
            }

            return data;
        });
    }

    function showMsg(elementId, message, isError = true) {
        const el = document.getElementById(elementId);
        if (!el) return;
        
        if (elementId === 'formFeedback' && formFeedbackTimeout) {
            clearTimeout(formFeedbackTimeout);
        }
        
        el.textContent = message;
        el.className = 'feedback-msg ' + (isError ? 'is-error' : 'is-success');
        
        if (elementId === 'formFeedback') {
            formFeedbackTimeout = setTimeout(() => {
                const feedbackEl = document.getElementById('formFeedback');
                if (feedbackEl) {
                    feedbackEl.textContent = '';
                    feedbackEl.className = 'feedback-msg';
                }
                formFeedbackTimeout = null;
            }, 2000);
        }
    }

    function clearMsg(elementId) {
        const el = document.getElementById(elementId);
        if (el) {
            el.textContent = '';
            el.className = 'feedback-msg';
        }
        if (elementId === 'formFeedback' && formFeedbackTimeout) {
            clearTimeout(formFeedbackTimeout);
            formFeedbackTimeout = null;
        }
    }

    // Step switching
    function showOtpStep(email) {
        document.getElementById('formStep').style.display = 'none';
        document.getElementById('otpStep').style.display = 'block';
        document.getElementById('otpEmailDisplay').textContent = email;
        document.getElementById('otpInput').focus();
    }
    
    function showFormStep() {
        document.getElementById('otpStep').style.display = 'none';
        document.getElementById('formStep').style.display = 'block';
        refreshCaptcha();
        resetSendOtpButton();  // ← i-reset ang button
    }
    
    const backBtn = document.getElementById('backToForm');
    if (backBtn) backBtn.addEventListener('click', showFormStep);

    // Validate registration form
    function validateForm() {
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('passwordReg').value;
        const confirm = document.getElementById('confirmReg').value;
        const captcha = document.getElementById('captchaInput').value.trim();
        const termsChecked = document.getElementById('termsCheckbox').checked;

        if (!firstName || !lastName || !email || !phone || !username || !password || !confirm)
            return 'All fields are required.';
        if (!termsChecked)
            return 'You must agree to the Terms and Conditions.';
        if (!/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email))
            return 'Please enter a valid email address.';
        if (!/^[a-zA-Z0-9_]{3,20}$/.test(username))
            return 'Username must be 3–20 characters (letters, numbers, underscore).';
        if (password.length < 6)
            return 'Password must be at least 6 characters.';
        if (password !== confirm)
            return 'Passwords do not match.';
        if (!captcha)
            return 'Please enter the CAPTCHA code.';
        return null;
    }

    // STEP 1: Send OTP
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearMsg('formFeedback');

            const error = validateForm();
            if (error) {
                showMsg('formFeedback', error);
                return;
            }

            const btn = document.getElementById('sendOtpBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Sending code…';
            btn.disabled = true;

            requestJson('send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    firstName: document.getElementById('firstName').value.trim(),
                    lastName: document.getElementById('lastName').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    phone: document.getElementById('phone').value.trim(),
                    username: document.getElementById('username').value.trim(),
                    password: document.getElementById('passwordReg').value,
                    confirmPassword: document.getElementById('confirmReg').value,
                    captcha: document.getElementById('captchaInput').value.trim()
                })
            })
            .then(data => {
                if (data.success) {
                    showOtpStep(document.getElementById('email').value.trim());
                    // I-reset ang button agad kahit nagtagumpay
                    resetSendOtpButton();
                } else {
                    showMsg('formFeedback', data.error || 'Something went wrong.');
                    if (data.refreshCaptcha) refreshCaptcha();
                    resetSendOtpButton();
                }
            })
            .catch((error) => {
                showMsg('formFeedback', error.message || 'Network error. Please try again.');
                resetSendOtpButton();
            });
        });
    }

    // STEP 2: Verify OTP (no changes)
    const otpForm = document.getElementById('otpForm');
    if (otpForm) {
        otpForm.addEventListener('submit', function (e) {
            e.preventDefault();
            clearMsg('otpFeedback');

            const otp = document.getElementById('otpInput').value.trim();
            if (!otp || !/^\d{6}$/.test(otp)) {
                showMsg('otpFeedback', 'Please enter a valid 6‑digit code.');
                return;
            }

            const btn = document.getElementById('verifyBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Verifying…';
            btn.disabled = true;

            requestJson('create_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp: otp })
            })
            .then(data => {
                if (data.success) {
                    showMsg('otpFeedback', 'Account created! Redirecting to login…', false);
                    setTimeout(() => { window.location.href = '../Login/user-login.php'; }, 1800);
                } else {
                    showMsg('otpFeedback', data.error || 'Verification failed.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch((error) => {
                showMsg('otpFeedback', error.message || 'Network error. Please try again.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });
    }

    // Resend OTP
    const resendBtn = document.getElementById('resendOtp');
    if (resendBtn) {
        resendBtn.addEventListener('click', function () {
            clearMsg('otpFeedback');
            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = 'Sending…';

            requestJson('send_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(data => {
                if (data.success) {
                    showMsg('otpFeedback', 'A new code has been sent!', false);
                    document.getElementById('otpInput').value = '';
                } else {
                    showMsg('otpFeedback', data.error || 'Failed to resend.');
                }
                this.textContent = originalText;
                this.disabled = false;
            })
            .catch((error) => {
                showMsg('otpFeedback', error.message || 'Network error. Please try again.');
                this.textContent = originalText;
                this.disabled = false;
            });
        });
    }

    // Go back link
    const goBack = document.getElementById('goBackBtn');
    if (goBack) {
        goBack.addEventListener('click', function (e) {
            e.preventDefault();
            window.history.length > 1 ? window.history.back() : window.location.href = '../Homepage/index.php';
        });
    }

    // Siguraduhin na naka-reset ang button sa simula
    resetSendOtpButton();

    // Fix para sa browser back/forward cache
    window.addEventListener('pageshow', function(event) {
        resetSendOtpButton();
        // Kung ang OTP step ay naka-display, balik sa form step
        const otpStep = document.getElementById('otpStep');
        const formStep = document.getElementById('formStep');
        if (otpStep && formStep && otpStep.style.display === 'block') {
            showFormStep();
        }
    });
});