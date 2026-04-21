<?php
session_start();
// Get ban reason from URL (passed from login)
$reason = isset($_GET['reason']) ? htmlspecialchars(urldecode($_GET['reason'])) : 'violation of our Terms of Service';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Account Banned - LookGood</title>
  <!-- Use the same CSS as login -->
  <link rel="stylesheet" href="../../css/User/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Additional styles for banned page - keeps the same card look */
    .ban-icon {
      background: #fee2e2;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.2rem;
    }
    .ban-icon i {
      font-size: 3rem;
      color: #d32f2f;
    }
    .ban-badge {
      display: inline-block;
      background: #ffebee;
      color: #b71c1c;
      font-weight: 600;
      font-size: 0.7rem;
      padding: 0.3rem 1rem;
      border-radius: 40px;
      margin-bottom: 1rem;
      letter-spacing: 0.5px;
    }
    .message-card {
      background: #f8fafc;
      border-radius: 20px;
      padding: 1.2rem;
      margin: 1.5rem 0;
      text-align: left;
      border-left: 4px solid #d32f2f;
      font-size: 0.9rem;
    }
    .message-card p {
      margin-bottom: 0.75rem;
      color: #1e293b;
    }
    .contact-support {
      background: #fef9e6;
      border-radius: 16px;
      padding: 0.8rem;
      margin: 1rem 0;
      display: flex;
      justify-content: center;
      gap: 0.6rem;
      flex-wrap: wrap;
      align-items: center;
    }
    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 1.5rem;
    }
    .btn-ban-primary, .btn-ban-outline {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.7rem 1.4rem;
      border-radius: 50px;
      font-weight: 600;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.2s ease;
    }
    .btn-ban-primary {
      background: #1e2a3e;
      color: white;
    }
    .btn-ban-primary:hover {
      background: #0f1a2a;
      transform: translateY(-1px);
    }
    .btn-ban-outline {
      border: 1.5px solid #cbd5e1;
      color: #2c3e50;
      background: transparent;
    }
    .btn-ban-outline:hover {
      background: #f1f5f9;
    }
    .footer-note {
      font-size: 0.7rem;
      color: #94a3b8;
      margin-top: 1.8rem;
      text-align: center;
    }
  </style>
</head>
<body class="top">
<div class="page">
  <!-- LEFT SIDE: Brand section (same as login) -->
  <div class="brand-section">
    <div class="brand-content">
      <a href="../Homepage/index.php">
        <img src="../Resources/Logos/lookgood-black.png" class="brand-logo" alt="LookGood logo"
             onerror="this.src='https://placehold.co/400x120?text=LookGood'">
      </a>
      <p class="brand-tagline">Looking good has never been this clear.</p>
    </div>
  </div>

  <!-- RIGHT SIDE: Ban message (styled like login-box) -->
  <div class="login-section">
    <div class="login-box" style="text-align: center;">
      <div class="ban-icon">
        <i class="fas fa-ban"></i>
      </div>
      <div class="ban-badge">
        <i class="fas fa-exclamation-triangle"></i> PERMANENT SUSPENSION
      </div>
      <h2 class="login-title" style="color: #1e2a3e;">Account banned</h2>
      <p class="login-subtitle" style="margin-bottom: 0.5rem;">You can no longer access LookGood</p>

      <div class="message-card">
        <p><strong>Why this happened?</strong><br>
        Your account was banned due to <?= $reason ?>.</p>
        <p>If you believe this is an error, please contact our support team.</p>
      </div>

      <div class="contact-support">
        <i class="fas fa-envelope" style="color:#e67e22;"></i>
        <span>Support: </span>
        <a href="mailto:support@lookgood.com" style="color:#d32f2f; text-decoration:none; font-weight:500;">lookgoodframes.ph@gmail.com</a>
      </div>

      <div class="action-buttons">
        <a href="user-login.php" class="btn-ban-primary"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
        <a href="../Homepage/index.php" class="btn-ban-outline"><i class="fas fa-home"></i> Homepage</a>
      </div>
      <div class="footer-note">
        <i class="fas fa-lock"></i> All active sessions have been terminated.
      </div>
    </div>
  </div>
</div>
</body>
</html>