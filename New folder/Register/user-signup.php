<?php
session_start();
// Walang POST processing dito; lahat ng gagawin ay sa AJAX (send_otp.php at create_account.php)
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Create Account</title>
  <link rel="stylesheet" href="../../css/User/register.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Optional inline style for checkbox alignment – you can move to register.css */
    .terms-group {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 15px 0 20px;
      font-size: 14px;
    }

    .terms-group input {
      width: 18px;
      height: 18px;
      margin: 0;
      cursor: pointer;
    }

    .terms-group label {
      color: #555;
      cursor: pointer;
      margin: 0;
    }

    .terms-group a {
      color: #111;
      font-weight: 600;
      text-decoration: none;
    }

    .terms-group a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="page">
    <div class="login-section">
      <div class="login-box">
        <h2 class="login-title">Create account</h2>
        <p class="login-subtitle">Join LookGood & find your perfect frame</p>

        <!-- STEP 1: Registration form + CAPTCHA -->
        <div id="formStep">
          <form id="signupForm" novalidate>
            <div class="name-fields">
              <div class="float-group">
                <input type="text" class="float-input" id="firstName" name="firstName" placeholder=" " required
                  autocomplete="given-name">
                <label class="float-label">First name</label>
              </div>
              <div class="float-group">
                <input type="text" class="float-input" id="lastName" name="lastName" placeholder=" " required
                  autocomplete="family-name">
                <label class="float-label">Last name</label>
              </div>
            </div>

            <div class="float-group">
              <input type="email" class="float-input" id="email" name="email" placeholder=" " required
                autocomplete="email">
              <label class="float-label">Email address</label>
            </div>

            <!-- Phone Number field -->
            <div class="float-group">
              <input type="tel" class="float-input" id="phone" name="phone" placeholder=" " required autocomplete="tel"
                pattern="[0-9+\-\s]+" title="Please enter a valid phone number">
              <label class="float-label">Phone number</label>
            </div>

            <div class="float-group">
              <input type="text" class="float-input" id="username" name="username" placeholder=" " required
                autocomplete="username">
              <label class="float-label">Username</label>
            </div>

            <div class="float-group">
              <input type="password" class="float-input" id="passwordReg" name="password" placeholder=" " required
                autocomplete="new-password">
              <label class="float-label">Password</label>
              <button type="button" class="toggle-pw" data-target="passwordReg" aria-label="Show password">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>

            <div class="float-group">
              <input type="password" class="float-input" id="confirmReg" name="confirmPassword" placeholder=" "
                required>
              <label class="float-label">Confirm password</label>
              <button type="button" class="toggle-pw" data-target="confirmReg" aria-label="Show password">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>

            <!-- CAPTCHA row -->
            <div class="captcha-group">
              <div class="float-group captcha-input-group">
                <input type="text" class="float-input" id="captchaInput" name="captcha" placeholder=" " required>
                <label class="float-label">Enter code </label>
              </div>
              <div class="captcha-image-wrap">
                <img src="captcha.php" id="captchaImg" class="captcha-img">
                <button type="button" id="refreshCaptcha" class="captcha-refresh">⟳</button>
              </div>
            </div>

            <!-- Terms & Conditions checkbox -->
            <div class="terms-group" style="padding: 0px 0; margin: 0px 0;">
              <input type="checkbox" id="termsCheckbox" required>
              <label for="termsCheckbox" style="font-size: 11px;">
                I agree to the <a href="#" target="_blank">Terms and Conditions</a>.
              </label>
            </div>

            <div id="formFeedback" class="feedback-msg"></div>

            <button type="submit" id="sendOtpBtn" class="loginbtn">Send OTP</button>
          </form>

          <div class="separator" style="margin: 10px 0;">
            <hr><span>Already have an account?</span>
            <hr>
          </div>
          <a href="../Login/user-login.php" class="signup-link-btn">Log In</a>
        </div>

        <!-- STEP 2: OTP verification (hidden initially) -->
        <div id="otpStep" style="display:none;">
          <div class="otp-icon"><i class="fas fa-envelope-open-text"></i></div>
          <p class="otp-hint">
            Enter the 6-digit code sent to <br>
            <strong id="otpEmailDisplay"></strong>
          </p>
          <form id="otpForm">
            <div class="float-group">
              <input type="text" class="float-input" id="otpInput" name="otp" placeholder=" " maxlength="6"
                autocomplete="one-time-code">
              <label class="float-label">OTP Code</label>
            </div>
            <div id="otpFeedback" class="feedback-msg"></div>
            <button type="submit" id="verifyBtn" class="loginbtn">Create Account</button>
          </form>
          <button type="button" id="resendOtp" class="resend-btn">Resend OTP</button>
          <button type="button" id="backToForm" class="back-to-form-btn"><i class="fas fa-arrow-left"></i> Go
            Back</button>
        </div>
      </div>
    </div>
    <div class="brand-section">
      <div class="brand-content">
        <a href="../Homepage/index.php"><img src="../Resources/Logos/lookgood-black.png" class="brand-logo"
            alt="LookGood logo"></a>
        <p class="brand-tagline">Looking good has never been this clear.</p>
      </div>
    </div>
  </div>
  <script src="../../Actions/User/user-signup.js?v=20260412a"></script>
</body>
</html>