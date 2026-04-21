<?php
require_once __DIR__ . '/../../session_bootstrap.php';
$isLoggedIn = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? 'user') !== 'admin');
$username = $isLoggedIn ? htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['email']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Terms & Conditions — LookGood Frames</title>

  <link rel="stylesheet" href="../../css/User/navbar.css">
  <link rel="stylesheet" href="../../css/User/footer.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --black:#111; --off-white:#faf9f7; --mid-gray:#6b6b6b; --light-gray:#e8e5e0; }
    body { font-family:'DM Sans',sans-serif; background:var(--off-white); color:var(--black); min-height:100vh; }

    .terms-hero { background:var(--black); color:#fff; margin-top:80px; padding:80px 24px 60px; text-align:center; }
    .terms-hero .eyebrow { font-size:11px; letter-spacing:.2em; text-transform:uppercase; color:#aaa; margin-bottom:16px; }
    .terms-hero h1 { font-family:'Spectral',serif; font-size:clamp(2rem,5vw,3.2rem); font-weight:300; margin-bottom:16px; }
    .terms-hero p { font-size:14px; color:#bbb; max-width:580px; margin:0 auto; line-height:1.7; }

    .terms-layout { display:flex; max-width:1080px; margin:0 auto; padding:60px 24px 100px; gap:60px; align-items:flex-start; }

    .terms-nav { flex:0 0 210px; position:sticky; top:100px; }
    .terms-nav p { font-size:10px; letter-spacing:.18em; text-transform:uppercase; color:var(--mid-gray); margin-bottom:14px; }
    .terms-nav ul { list-style:none; display:flex; flex-direction:column; gap:2px; }
    .terms-nav a { display:block; font-size:12.5px; color:var(--mid-gray); text-decoration:none; padding:5px 10px; border-left:2px solid transparent; transition:all .2s; line-height:1.4; }
    .terms-nav a:hover, .terms-nav a.active { color:var(--black); border-left-color:var(--black); }

    .terms-content { flex:1; min-width:0; }
    .terms-section { margin-bottom:52px; scroll-margin-top:100px; }
    .terms-section h2 { font-family:'Spectral',serif; font-size:1.45rem; font-weight:400; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--light-gray); }
    .terms-section h3 { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.1em; color:var(--black); margin:24px 0 8px; }
    .terms-section p { font-size:14.5px; color:#444; line-height:1.8; margin-bottom:14px; }
    .terms-section ul, .terms-section ol { margin:10px 0 16px 20px; display:flex; flex-direction:column; gap:8px; }
    .terms-section ul li, .terms-section ol li { font-size:14px; color:#444; line-height:1.7; }
    .terms-section strong { color:#222; }

    .policy-box { background:#fff; border:1px solid var(--light-gray); border-left:3px solid var(--black); padding:20px 24px; margin:20px 0; border-radius:2px; }
    .policy-box strong { display:block; font-size:11px; letter-spacing:.12em; text-transform:uppercase; margin-bottom:8px; color:#888; }
    .policy-box p { margin-bottom:0; font-size:14px; }

    .disclaimer-box { background:#fff8f0; border:1px solid #f0e0c8; border-left:3px solid #c8a96e; padding:16px 20px; margin:24px 0; border-radius:2px; font-size:13px; color:#666; line-height:1.7; }

    .terms-meta { display:flex; gap:24px; margin-bottom:48px; padding:16px 20px; background:#fff; border:1px solid var(--light-gray); border-radius:4px; font-size:13px; color:var(--mid-gray); flex-wrap:wrap; }
    .terms-meta span strong { color:var(--black); }

    @media(max-width:700px){
      .terms-layout{flex-direction:column;gap:32px;padding:40px 16px 80px}
      .terms-nav{position:static;width:100%}
      .terms-nav ul{flex-direction:row;flex-wrap:wrap}
      .terms-nav a{border-left:none;border-bottom:2px solid transparent;padding:4px 8px}
      .terms-nav a:hover,.terms-nav a.active{border-left-color:transparent;border-bottom-color:var(--black)}
    }
  </style>
</head>
<body>

  
<section>
  <?php include '../navbar.php'; ?>
</section>


<div class="terms-hero">
  <p class="eyebrow">LookGood Frames</p>
  <h1>Terms &amp; Conditions</h1>
  <p>Please read these terms carefully before using our website or placing an order with us.</p>
</div>

<div class="terms-layout">
  <nav class="terms-nav" aria-label="Page sections">
    <p>On this page</p>
    <ul>
      <li><a href="#overview">1. Overview</a></li>
      <li><a href="#definitions">2. Definitions</a></li>
      <li><a href="#acknowledgment">3. Acknowledgment</a></li>
      <li><a href="#user-accounts">4. User Accounts</a></li>
      <li><a href="#user-conduct">5. User Conduct</a></li>
      <li><a href="#intellectual-property">6. Intellectual Property</a></li>
      <li><a href="#return-policy">7. Return &amp; Exchange Policy</a></li>
      <li><a href="#shipping">8. Shipping</a></li>
      <li><a href="#orders">9. Orders &amp; Payment</a></li>
      <li><a href="#products">10. Products &amp; Pricing</a></li>
      <li><a href="#links">11. Links to Other Websites</a></li>
      <li><a href="#termination">12. Termination</a></li>
      <li><a href="#disclaimer">13. "AS IS" Disclaimer</a></li>
      <li><a href="#liability">14. Limitation of Liability</a></li>
      <li><a href="#governing-law">15. Governing Law</a></li>
      <li><a href="#disputes">16. Disputes Resolution</a></li>
      <li><a href="#severability">17. Severability &amp; Waiver</a></li>
      <li><a href="#privacy">18. Privacy</a></li>
      <li><a href="#changes">19. Changes to Terms</a></li>
      <li><a href="#contact">20. Contact Us</a></li>
    </ul>
  </nav>

  <main class="terms-content">
    <div class="terms-meta">
      <span><strong>Effective date:</strong> April 21, 2026</span>
      <span><strong>Last updated:</strong> April 21, 2026</span>
    </div>

    <!-- 1. Overview -->
    <section class="terms-section" id="overview">
      <h2>1. Overview</h2>
      <p>These Terms and Conditions constitute a legally binding agreement between you and <strong>LookGood Frames</strong> ("the Company," "we," "us," or "our") governing your access to and use of our website and the purchase of products through our online store.</p>
      <p>LookGood Frames is an eyewear retailer operating in the Philippines, with physical locations at Iba, Hagonoy, Bulacan and Greenbelt 5, Makati City. By accessing our website or placing an order, you confirm that you have read, understood, and agree to be bound by these Terms and Conditions in their entirety.</p>
      <p>If you do not agree with any part of these terms, you must not use our website or services.</p>
    </section>

    <!-- 2. Definitions -->
    <section class="terms-section" id="definitions">
      <h2>2. Interpretation &amp; Definitions</h2>
      <h3>Interpretation</h3>
      <p>Words with initial capital letters have specific meanings defined in this section. These definitions apply whether the terms appear in singular or plural form throughout this agreement.</p>
      <h3>Definitions</h3>
      <ul>
        <li><strong>"Account"</strong> means a unique account created for you to access our website and services.</li>
        <li><strong>"Company"</strong> (also referred to as "we," "us," or "our") refers to LookGood Frames, with offices at Iba, Hagonoy, Bulacan and Greenbelt 5, Makati, Philippines.</li>
        <li><strong>"Country"</strong> refers to the Republic of the Philippines.</li>
        <li><strong>"Device"</strong> means any device capable of accessing our website, such as a computer, smartphone, or tablet.</li>
        <li><strong>"Order"</strong> means a request by you to purchase products from LookGood Frames through our website.</li>
        <li><strong>"Products"</strong> refers to the eyewear frames and related accessories offered for sale on our website.</li>
        <li><strong>"Service"</strong> refers to our website and all related services provided by LookGood Frames.</li>
        <li><strong>"Website"</strong> refers to the LookGood Frames online store.</li>
        <li><strong>"You"</strong> means the individual accessing or using the Service, or the legal entity on whose behalf such individual is acting.</li>
      </ul>
    </section>

    <!-- 3. Acknowledgment -->
    <section class="terms-section" id="acknowledgment">
      <h2>3. Acknowledgment</h2>
      <p>These Terms and Conditions govern the use of our Service and form the agreement between you and LookGood Frames. They set out the rights and obligations of all users with respect to the use of our Service.</p>
      <p>Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms and Conditions. By accessing or using the Service, you agree to be bound by these Terms. If you disagree with any part of these Terms, you may not access the Service.</p>
      <p>You represent that you are at least 18 years of age. LookGood Frames does not knowingly permit those under 18 to register for an account or make purchases without parental consent.</p>
      <p>Your use of the Service is also conditioned on your acceptance of our Privacy Policy, which describes how we collect, use, and disclose your personal information when you use our website.</p>
    </section>

    <!-- 4. User Accounts -->
    <section class="terms-section" id="user-accounts">
      <h2>4. User Accounts</h2>
      <p>When you create an account with LookGood Frames, you must provide information that is accurate, complete, and current at all times. Failure to do so may result in immediate termination of your account.</p>
      <p>You are responsible for safeguarding your password and for all activities and actions that occur under your account. You agree not to disclose your password to any third party and to notify us immediately upon becoming aware of any unauthorized use of your account or any other security breach.</p>
      <p>You may not use as a username the name of another person or entity, a trademarked name, or a name that is otherwise offensive, vulgar, or obscene. LookGood Frames reserves the right to refuse registration or cancel accounts at its sole discretion.</p>
      <p>You may delete your account at any time by contacting us. Upon account deletion, your order history and associated data will be handled in accordance with our Privacy Policy.</p>
    </section>

    <!-- 5. User Conduct -->
    <section class="terms-section" id="user-conduct">
      <h2>5. User Conduct</h2>
      <p>By using our website, you agree not to engage in any of the following prohibited activities:</p>
      <ul>
        <li>Using the Service for any unlawful purpose or in violation of any applicable regulations.</li>
        <li>Attempting to gain unauthorized access to any part of the website, server, or network connected to the website.</li>
        <li>Transmitting any unsolicited or unauthorized advertising or promotional material (spam).</li>
        <li>Impersonating LookGood Frames, its employees, other users, or any other person or entity.</li>
        <li>Using automated tools (bots, scrapers, crawlers) to access or collect data from our website without our express written permission.</li>
        <li>Uploading or transmitting viruses or any other malicious code.</li>
        <li>Engaging in any activity that disrupts or interferes with the proper functioning of the website.</li>
        <li>Posting false, misleading, or defamatory content in reviews or communications with us.</li>
      </ul>
      <p>LookGood Frames reserves the right to terminate your access to the Service immediately, without prior notice or liability, if you breach any of the above conduct standards.</p>
    </section>

    <!-- 6. Intellectual Property -->
    <section class="terms-section" id="intellectual-property">
      <h2>6. Intellectual Property</h2>
      <p>The Service and its original content — including but not limited to the LookGood Frames name, logo, product photography, website design, written content, and all visual media — are and will remain the exclusive property of LookGood Frames and are protected by copyright, trademark, and other intellectual property laws of the Philippines and applicable international laws.</p>
      <p>Our trademarks and trade dress may not be used in connection with any product or service without the prior written consent of LookGood Frames. You may not reproduce, distribute, modify, create derivative works of, publicly display, or commercially exploit any content from our website without our express written permission.</p>
      <p>Any feedback, suggestions, or ideas you voluntarily provide to us may be used by LookGood Frames without restriction, compensation, or obligation to you.</p>
    </section>

    <!-- 7. Return Policy -->
    <section class="terms-section" id="return-policy">
      <h2>7. Return &amp; Exchange Policy</h2>

      <div class="policy-box">
        <strong>30-Day Returns &amp; Exchanges</strong>
        <p>We accept returns and exchanges within 30 days of the delivery date, free of charge, for items in their original, unused condition and original packaging.</p>
      </div>

      <h3>Eligibility</h3>
      <p>To be eligible for a return or exchange, items must meet all of the following conditions:</p>
      <ul>
        <li>The return or exchange request must be initiated within 30 days of the confirmed delivery date.</li>
        <li>The item must be unused, unworn, and in its original, unaltered condition.</li>
        <li>All original packaging, tags, cases, and accessories must be included.</li>
        <li>The item must not be classified as a final sale, promotional, or non-returnable item at the time of purchase.</li>
      </ul>

      <h3>Non-Returnable Items</h3>
      <p>The following items are not eligible for return or exchange:</p>
      <ul>
        <li>Items marked as "Final Sale" at the time of purchase.</li>
        <li>Items that have been customized, altered, or fitted with prescription lenses after delivery.</li>
        <li>Items showing visible signs of wear, scratches, damage, or misuse.</li>
        <li>Items returned without their original packaging or missing accessories.</li>
      </ul>

      <h3>How to Initiate a Return or Exchange</h3>
      <p>To start the return or exchange process, contact our support team within 30 days of receiving your order through any of the following channels:</p>
      <ul>
        <li>Email: <strong>lookgoodframes.ph@gmail.com</strong></li>
        <li>Phone: <strong>0968 660 7519</strong></li>
      </ul>
      <p>Please include your order number, the item(s) you wish to return or exchange, and the reason for the request. Once your request is reviewed and approved, we will provide you with return instructions and a designated drop-off or pickup arrangement.</p>

      <h3>Refunds</h3>
      <p>Once we receive and inspect your returned item, we will notify you of the approval or rejection of your refund. Approved refunds will be processed to your original payment method within <strong>5–10 business days</strong>. Please note that it may take additional time for the refund to reflect in your account depending on your payment provider.</p>

      <h3>Damaged or Incorrect Items</h3>
      <p>If you receive a damaged, defective, or incorrect item, please contact us within <strong>7 days</strong> of delivery with photographic evidence. We will arrange a replacement or full refund at no additional cost to you.</p>
    </section>

    <!-- 8. Shipping -->
    <section class="terms-section" id="shipping">
      <h2>8. Shipping</h2>
      <h3>Available Shipping Options</h3>
      <p>We currently offer the following shipping methods at checkout:</p>
      <ul>
        <li><strong>Free Shipping</strong> — Estimated 5–7 business days (₱0)</li>
        <li><strong>Standard Shipping</strong> — Estimated 3–5 business days (₱99)</li>
        <li><strong>Express Shipping</strong> — Estimated 1–2 business days (₱199)</li>
      </ul>
      <h3>Delivery Timeframes</h3>
      <p>Delivery estimates are calculated from the date of dispatch, not the date the order is placed. Orders are typically processed and dispatched within 1–2 business days of payment confirmation.</p>
      <p>LookGood Frames is not responsible for delays caused by third-party couriers, unforeseen circumstances, public holidays, or events beyond our reasonable control (force majeure).</p>
      <h3>Shipping Address &amp; Failed Deliveries</h3>
      <p>You are responsible for providing an accurate and complete shipping address at the time of checkout. LookGood Frames shall not be held liable for packages that are lost, delayed, or misdelivered due to an incorrect or incomplete address provided by the customer. Redelivery fees, if applicable, will be borne by the customer.</p>
      <h3>Delivery Confirmation</h3>
      <p>A delivery is considered complete when the courier marks the shipment as delivered. If you believe your package has been lost or not received despite a delivery confirmation, please contact us within 7 days so we may assist in filing a claim with the courier.</p>
    </section>

    <!-- 9. Orders & Payment -->
    <section class="terms-section" id="orders">
      <h2>9. Orders &amp; Payment</h2>
      <h3>Order Placement &amp; Confirmation</h3>
      <p>By placing an order on our website, you are making an offer to purchase products subject to these Terms and Conditions. An order confirmation email or notification is an acknowledgment that we have received your order — it does not constitute acceptance of your order.</p>
      <p>LookGood Frames reserves the right to refuse or cancel any order at any time for reasons including but not limited to: product unavailability, pricing or description errors, suspected fraudulent activity, or failure to meet eligibility requirements.</p>
      <h3>Accepted Payment Methods</h3>
      <p>We currently accept the following payment methods:</p>
      <ul>
        <li><strong>GCash</strong> — Online digital wallet payment</li>
        <li><strong>Cash </strong> — Payment in physical stores</li>
      </ul>
      <p>All prices are listed and charged in Philippine Pesos (₱) and include applicable taxes unless otherwise explicitly stated. LookGood Frames is not responsible for any currency conversion fees charged by your financial institution.</p>
      <h3>Order Cancellation</h3>
      <p>You may request to cancel an order at any time before it has been dispatched. Once an order has been shipped, it can no longer be cancelled. In that case, you may initiate a return in accordance with our Return &amp; Exchange Policy after receiving the item.</p>
      <p>LookGood Frames also reserves the right to cancel orders that cannot be fulfilled due to stock unavailability or technical errors. In such cases, you will receive a full refund.</p>
    </section>

    <!-- 10. Products & Pricing -->
    <section class="terms-section" id="products">
      <h2>10. Products &amp; Pricing</h2>
      <p>We make every effort to display our products as accurately as possible, including images, colors, and descriptions. However, the colors and appearance of products may vary slightly depending on your device's display settings. LookGood Frames does not guarantee that all product descriptions, images, or pricing are entirely free of errors.</p>
      <p>In the event of a pricing error, LookGood Frames reserves the right to cancel any affected orders and notify you accordingly, offering you the option to reorder at the correct price.</p>
      <p>We reserve the right to modify product offerings, prices, and availability at any time without prior notice. Products may be discontinued at our discretion.</p>
    </section>

    <!-- 11. Links -->
    <section class="terms-section" id="links">
      <h2>11. Links to Other Websites</h2>
      <p>Our website may contain links to third-party websites or services that are not owned or controlled by LookGood Frames. These links are provided for your convenience and informational purposes only.</p>
      <p>LookGood Frames has no control over and assumes no responsibility for the content, privacy policies, or practices of any third-party websites or services. We do not warrant or make any representations regarding the accuracy or reliability of any content on third-party sites.</p>
      <p>We strongly recommend that you read the Terms and Conditions and Privacy Policies of any third-party website you visit through links on our site.</p>
    </section>

    <!-- 12. Termination -->
    <section class="terms-section" id="termination">
      <h2>12. Termination</h2>
      <p>LookGood Frames may terminate or suspend your account and access to the Service immediately, without prior notice or liability, for any reason, including if you breach any provision of these Terms and Conditions.</p>
      <p>Upon termination, your right to use the Service will immediately cease. If you wish to terminate your account voluntarily, you may do so by contacting us at <strong>lookgoodframes.ph@gmail.com</strong>. Termination does not affect any outstanding orders or obligations between you and LookGood Frames prior to the date of termination.</p>
    </section>

    <!-- 13. AS IS Disclaimer -->
    <section class="terms-section" id="disclaimer">
      <h2>13. "AS IS" and "AS AVAILABLE" Disclaimer</h2>
      <p>The Service is provided to you on an <strong>"AS IS"</strong> and <strong>"AS AVAILABLE"</strong> basis without any warranties of any kind, whether express or implied. To the maximum extent permitted by applicable law, LookGood Frames expressly disclaims all warranties, including but not limited to implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>
      <p>LookGood Frames does not warrant that:</p>
      <ul>
        <li>The Service will be uninterrupted, timely, secure, or error-free at all times.</li>
        <li>The results obtained from using the Service will be accurate or reliable.</li>
        <li>Any errors or defects in the Service will be corrected.</li>
      </ul>
      <p>Some jurisdictions do not allow the exclusion of implied warranties, so some of the above exclusions may not apply to you.</p>
    </section>

    <!-- 14. Limitation of Liability -->
    <section class="terms-section" id="liability">
      <h2>14. Limitation of Liability</h2>
      <p>To the fullest extent permitted by applicable law, LookGood Frames and its directors, employees, partners, agents, or suppliers shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of — or inability to use — our website or products. This includes but is not limited to loss of data, loss of revenue, loss of business opportunities, or personal injury.</p>
      <p>In all cases, the total liability of LookGood Frames to you for any claim arising out of or related to your use of the Service or a specific purchase shall not exceed the amount actually paid by you for the product(s) giving rise to the claim.</p>
      <p>Nothing in these Terms and Conditions is intended to exclude or limit liability for death or personal injury caused by our negligence, or for fraud or fraudulent misrepresentation.</p>
    </section>

    <!-- 15. Governing Law -->
    <section class="terms-section" id="governing-law">
      <h2>15. Governing Law</h2>
      <p>These Terms and Conditions shall be governed by and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions.</p>
      <p>Your use of our Service may also be subject to other local, provincial, national, or international laws and regulations that apply to you based on your location.</p>
    </section>

    <!-- 16. Disputes Resolution -->
    <section class="terms-section" id="disputes">
      <h2>16. Disputes Resolution</h2>
      <p>If you have any concern or dispute about the Service, your order, or these Terms, we encourage you to first try to resolve the matter informally by contacting us directly. We are committed to working with you in good faith to reach a fair resolution as quickly as possible.</p>
      <p>In the event that a dispute cannot be resolved informally, both parties agree to submit to the exclusive jurisdiction of the appropriate courts located in the Philippines.</p>
      <p>LookGood Frames reserves the right to seek injunctive relief or other equitable remedies in any court of competent jurisdiction to prevent actual or threatened infringement, misappropriation, or violation of our intellectual property or confidential information.</p>
    </section>

    <!-- 17. Severability & Waiver -->
    <section class="terms-section" id="severability">
      <h2>17. Severability &amp; Waiver</h2>
      <h3>Severability</h3>
      <p>If any provision of these Terms and Conditions is found to be unenforceable or invalid under applicable law, that provision shall be modified to the minimum extent necessary to make it enforceable, or removed if modification is not possible. The remaining provisions shall continue in full force and effect.</p>
      <h3>Waiver</h3>
      <p>The failure of LookGood Frames to enforce any right or provision of these Terms shall not constitute a waiver of that right or provision. A waiver of any specific breach shall not be construed as a waiver of any subsequent breach of the same or any other provision.</p>
    </section>

    <!-- 18. Privacy -->
    <section class="terms-section" id="privacy">
      <h2>18. Privacy</h2>
      <p>Your use of our website is also governed by our Privacy Policy. By using LookGood Frames, you consent to the collection, use, and storage of your personal information as described in our Privacy Policy. We collect only the personal data necessary to process your orders and improve your shopping experience.</p>
      <p>LookGood Frames does not sell, rent, or share your personal data with third parties for marketing purposes without your explicit consent. Data shared with third-party service providers (e.g., payment processors and courier services) is limited to what is strictly necessary to fulfill your order.</p>
      <p>For questions about how your data is collected or used, please contact us at <strong>lookgoodframes.ph@gmail.com</strong>.</p>
    </section>

    <!-- 19. Changes -->
    <section class="terms-section" id="changes">
      <h2>19. Changes to These Terms</h2>
      <p>LookGood Frames reserves the right to modify or replace these Terms and Conditions at any time at our sole discretion. If a revision is material, we will make reasonable efforts to provide notice prior to any new terms taking effect — for example, by updating the "Last Updated" date at the top of this page or by displaying a notice on our website.</p>
      <p>What constitutes a material change will be determined at our sole discretion. By continuing to access or use our Service after any revisions become effective, you agree to be bound by the updated terms. If you do not agree to the new terms, you must stop using the Service.</p>
    </section>

    <!-- 20. Contact -->
    <section class="terms-section" id="contact">
      <h2>20. Contact Us</h2>
      <p>If you have any questions, concerns, or feedback about these Terms and Conditions, please don't hesitate to get in touch with us:</p>
      <ul>
        <li><i class="fas fa-envelope" style="width:16px;color:#888"></i> &nbsp;<strong>Email:</strong> lookgoodframes.ph@gmail.com</li>
        <li><i class="fas fa-phone-alt" style="width:16px;color:#888"></i> &nbsp;<strong>Phone:</strong> 0968 660 7519</li>
        <li><i class="fas fa-map-marker-alt" style="width:16px;color:#888"></i> &nbsp;Iba, Hagonoy, Bulacan, Philippines</li>
        <li><i class="fas fa-map-marker-alt" style="width:16px;color:#888"></i> &nbsp;Greenbelt 5, Makati, Philippines</li>
      </ul>
      <div class="disclaimer-box">
        <strong>Disclaimer:</strong> The contents of this page are provided for informational purposes only and do not constitute legal advice. LookGood Frames recommends consulting a qualified legal professional if you require advice specific to your situation.
      </div>
    </section>
  </main>
</div>

<?php include '../footer.php'; ?>

<script>
  const sections = document.querySelectorAll('.terms-section');
  const navLinks = document.querySelectorAll('.terms-nav a');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinks.forEach(l => l.classList.remove('active'));
        const active = document.querySelector(`.terms-nav a[href="#${entry.target.id}"]`);
        if (active) active.classList.add('active');
      }
    });
  }, { rootMargin: '-20% 0px -70% 0px' });
  sections.forEach(s => observer.observe(s));
</script>
</body>
</html>