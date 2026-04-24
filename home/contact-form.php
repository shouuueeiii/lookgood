<style>
  .contact-module {
    --gold: #c8a96e;
    --dark-bg: #0a0a0a;
    --card-bg: #141414;
    --border-light: rgba(255, 255, 255, 0.07);
    
  }

  .contact-module * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  .contact-module {
    display: block;
    background: var(--dark-bg);
    padding: 200px 32px;
    font-family: 'DM Sans', sans-serif;
    width: 100%;
  }

  .contact-module .contact-inner {
    max-width: 1280px;
    margin: 0 auto;
    width: 100%;
  }

  .contact-module .contact-card {
    display: grid;
    grid-template-columns: 1fr 1.6fr;
    background: var(--card-bg);
    border: 0.5px solid var(--border-light);
    border-radius: 24px;
    overflow: hidden;
  }

  .contact-module .contact-left {
    padding: 48px 40px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 0.5px solid var(--border-light);
    background: #0f0f0f;
  }

  .contact-module .left-eyebrow {
    font-family: 'Spectral', serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--gold);
    display: block;
    margin-bottom: 12px;
  }

  .contact-module .left-title {
    font-family: 'Spectral', serif;
    font-size: 42px;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.2;
    margin: 0 0 16px 0;
  }

  .contact-module .left-title em {
    font-style: italic;
    color: var(--gold);
  }

  .contact-module .response-time {
    margin-top: auto;
    padding-top: 32px;
  }

  .contact-module .response-time span {
    display: block;
    font-size: 9px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 6px;
  }

  .contact-module .response-time p {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
    margin: 0;
    line-height: 1.4;
  }

  .contact-module .contact-right {
    padding: 48px 48px;
    display: flex;
    flex-direction: column;
    gap: 22px;
  }

  .contact-module .contact-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .contact-module .contact-field label {
    font-size: 9px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.4);
    font-weight: 500;
  }

  .contact-module .contact-field input,
  .contact-module .contact-field textarea {
    width: 100%;
    background: rgba(255, 255, 255, 0.04);
    border: 0.5px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    padding: 14px 18px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: #fff;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
    resize: none;
  }

  .contact-module .contact-field input::placeholder,
  .contact-module .contact-field textarea::placeholder {
    color: rgba(255, 255, 255, 0.25);
  }

  .contact-module .contact-field textarea {
    resize: vertical;
    min-height: 110px;
  }

  .contact-module .contact-field input:focus,
  .contact-module .contact-field textarea:focus {
    border-color: rgba(200, 169, 110, 0.6);
    background: rgba(255, 255, 255, 0.08);
  }

  .contact-module .contact-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  /* Bottom row: email + button side by side */
  .contact-module .contact-bottom {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    margin-top: 4px;
  }

  .contact-module .contact-bottom .contact-field {
    flex: 1;
  }

  .contact-module .contact-submit {
    flex-shrink: 0;
    background: var(--gold);
    color: #0a0a0a;
    border: none;
    border-radius: 40px;
    padding: 14px 34px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 700;
    letter-spacing: 0.03em;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s, opacity 0.2s;
    white-space: nowrap;
    align-self: flex-end;
  }

  .contact-module .contact-submit:hover {
    background: #d9bb85;
    transform: translateY(-2px);
  }

  .contact-module .contact-submit:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
  }

  .contact-module .cf-error-msg {
    background: rgba(255, 80, 80, 0.08);
    border: 0.5px solid rgba(255, 80, 80, 0.3);
    border-radius: 14px;
    padding: 13px 18px;
    font-size: 13px;
    color: #ff8888;
    display: none;
    align-items: center;
    gap: 10px;
  }

  .contact-module .cf-error-msg.visible { display: flex; }

  .contact-module .cf-error-msg::before {
    content: '!';
    font-weight: 800;
    font-size: 15px;
    background: rgba(255, 80, 80, 0.2);
    width: 24px; height: 24px; min-width: 24px;
    display: inline-flex;
    align-items: center; justify-content: center;
    border-radius: 50%;
  }

  #cf-toast {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(16px);
    background: #1a1a1a;
    color: #fff;
    padding: 13px 22px;
    border-radius: 9999px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 6px 24px rgba(0,0,0,.3);
    z-index: 99999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease, transform 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
  }

  #cf-toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }

  #cf-toast .cf-toast-check {
    background: #c8a96e;
    width: 22px; height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    color: #fff; flex-shrink: 0;
  }

  @media (max-width: 900px) {
    .contact-module .contact-card { grid-template-columns: 1fr; }
    .contact-module .contact-left {
      border-right: none;
      border-bottom: 0.5px solid var(--border-light);
      padding: 40px 36px;
      text-align: center;
    }
    .contact-module .left-title { font-size: 36px; }
    .contact-module .response-time { text-align: center; padding-top: 24px; }
    .contact-module .contact-right { padding: 40px 36px; }
    .contact-module .contact-bottom { flex-direction: column; }
    .contact-module .contact-submit { width: 100%; text-align: center; }
  }

  @media (max-width: 600px) {
    .contact-module { padding: 48px 20px; }
    .contact-module .left-title { font-size: 30px; }
    .contact-module .contact-right { padding: 32px 24px; }
    .contact-module .contact-row { grid-template-columns: 1fr; }
    #cf-toast { font-size: 13px; padding: 12px 18px; white-space: normal; max-width: 88vw; }
  }
</style>

<!-- Toast -->
<div id="cf-toast">
  <span class="cf-toast-check">✓</span>
  <span>Message sent! We'll get back to you soon.</span>
</div>

<div class="contact-module" id="contact-module">
  <div class="contact-inner">
    <div class="contact-card">

      <!-- LEFT -->
      <div class="contact-left">
        <div>
          <span class="left-eyebrow">Get in Touch</span>
          <h2 class="left-title">Send a <em>Message</em></h2>
          <p style="font-size:13px;color:rgba(255,255,255,0.45);margin-top:12px;line-height:1.5;">
            Have a question, feedback, or just want to say hi?<br>
            Fill out the form and we'll get back to you.
          </p>
        </div>
        <div class="response-time">
          <span>Response Time</span>
          <p>Within 1–2 business days</p>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="contact-right">

        <div class="cf-error-msg" id="cfErrorMsg"></div>

        <form id="contactForm" novalidate>

          <!-- Name + Subject side by side -->
          <div class="contact-row" style="margin-bottom:22px;">
            <div class="contact-field">
              <label for="cf_name">Full Name</label>
              <input type="text" id="cf_name" name="name"
                     placeholder="e.g. Maria Santos" required>
            </div>
            <div class="contact-field">
              <label for="cf_subject">Subject</label>
              <input type="text" id="cf_subject" name="subject"
                     placeholder="What's this about?" required>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;gap:22px;">
            <div class="contact-field">
              <label for="cf_content">Message</label>
              <textarea id="cf_content" name="content"
                        placeholder="Write your message here..."
                        required></textarea>
            </div>

            <!-- Email + Send Button side by side -->
            <div class="contact-bottom">
              <div class="contact-field">
                <label for="cf_email">Email Address</label>
                <input type="email" id="cf_email" name="email"
                       placeholder="Enter your email so we can reply back to you" required>
              </div>
              <button type="submit" class="contact-submit" id="cfSubmitBtn">
                Send Message
              </button>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var form     = document.getElementById('contactForm');
  var btn      = document.getElementById('cfSubmitBtn');
  var errorMsg = document.getElementById('cfErrorMsg');
  var toast    = document.getElementById('cf-toast');

  function showToast() {
    toast.classList.add('show');
    setTimeout(function () { toast.classList.remove('show'); }, 3500);
  }

  function showError(msg) {
    errorMsg.textContent = msg;
    errorMsg.classList.add('visible');
    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hideError() {
    errorMsg.classList.remove('visible');
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    e.stopPropagation();
    hideError();

    var name    = document.getElementById('cf_name').value.trim();
    var email   = document.getElementById('cf_email').value.trim();
    var subject = document.getElementById('cf_subject').value.trim();
    var content = document.getElementById('cf_content').value.trim();

    if (!name || !email || !subject || !content) {
      showError('Please fill in all fields.');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError('Please enter a valid email address.');
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Sending…';

    fetch('/lookgood/home/contact-send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: name, email: email, subject: subject, content: content })
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      if (data.success) {
        form.reset();
        showToast();
      } else {
        showError(data.error || 'Something went wrong. Please try again.');
      }
    })
    .catch(function () {
      showError('Network error. Please check your connection and try again.');
    })
    .finally(function () {
      btn.disabled = false;
      btn.textContent = 'Send Message';
    });
  });
});
</script>