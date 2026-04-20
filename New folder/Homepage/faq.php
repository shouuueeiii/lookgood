<?php
// faq.php - Two columns: header left (with icon), accordion right
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ | LookGood Frames</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Spectral:ital,wght@0,400;0,600;0,700;1,400;1,700&family=DM+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #ffffff;
        }

        .faq {
            padding: 60px 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .faq-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        /* Left column: icon + header */
        .faq-header {
            position: sticky;
            top: 200px;
            text-align: left;
        }

        /* Big FAQ icon */
        .faq-icon {
            font-size: 64px;
            color: #c8a96e;
            margin-bottom: 24px;
            display: inline-block;
            background: rgba(200, 169, 110, 0.1);
            width: 100px;
            height: 100px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
        }

        .section-title {
            font-family: 'Spectral', serif;
            font-size: 42px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .section-title em {
            font-style: italic;
            color: #c8a96e;
        }

        .section-subtitle {
            font-size: 16px;
            color: #666;
            line-height: 1.5;
            margin-top: 8px;
        }

        .faq-divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, #c8a96e, #e8d5a3);
            margin: 20px 0 24px 0;
            border-radius: 2px;
        }

        /* Right column: accordion list */
        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .faq-item {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.25s ease;
            overflow: hidden;
        }

        .faq-item:hover {
            box-shadow: 0 8px 24px rgba(200, 169, 110, 0.12);
            transform: translateY(-2px);
        }

        .faq-question {
            width: 100%;
            text-align: left;
            background: #ffffff;
            border: none;
            padding: 20px 24px;
            font-size: 18px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            color: #1a1a1a;
            cursor: pointer;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            transition: background 0.2s, color 0.2s;
        }

        .faq-question::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 14px;
            color: #c8a96e;
            transition: transform 0.3s ease;
        }

        .faq-question.active {
            background: #c8a96e;
            color: #1a1a1a;
        }

        .faq-question.active::after {
            transform: rotate(180deg);
            color: #1a1a1a;
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            background: #fefcf8;
            transition: max-height 0.4s ease, padding 0.3s ease;
            padding: 0 24px;
        }

        .faq-answer.open {
            max-height: 500px;
            padding: 16px 24px 24px 24px;
        }

        .faq-answer p {
            font-size: 16px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 12px;
        }

        .faq-answer p:last-child {
            margin-bottom: 0;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .faq-container {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .faq-header {
                position: static;
                text-align: center;
            }

            .faq-icon {
                margin-left: auto;
                margin-right: auto;
            }

            .faq-divider {
                margin: 16px auto 20px auto;
            }

            .section-title {
                font-size: 36px;
            }
        }

        @media (max-width: 640px) {
            .faq {
                padding: 40px 20px;
            }

            .faq-question {
                font-size: 16px;
                padding: 16px 20px;
            }

            .faq-answer.open {
                padding: 12px 20px 20px 20px;
            }

            .faq-answer p {
                font-size: 14px;
            }

            .faq-icon {
                width: 80px;
                height: 80px;
                font-size: 48px;
            }
        }
    </style>
</head>

<body>

    <section class="faq" id="faq">
        <div class="faq-container">
            <!-- LEFT COLUMN: ICON + HEADER -->
            <div class="faq-header">
                <div class="faq-icon">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <h2 class="section-title">Frequently Asked <em>Questions</em></h2>
                <div class="faq-divider"></div>
                <p class="section-subtitle">Answers to your most asked questions.</p>
            </div>

            <!-- RIGHT COLUMN: ACCORDION LIST (unchanged) -->
            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question">What is your return and exchange policy?</button>
                    <div class="faq-answer">
                        <p>We offer a 30-day return policy for unused items in their original packaging. Exchanges are
                            free within 14 days of delivery. Simply contact our support team to initiate the process.
                        </p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">How long does shipping take?</button>
                    <div class="faq-answer">
                        <p>We offer three shipping options: Free (5–7 business days), Standard (3–5 business days), and
                            Express (1–2 business days). We also provide same-day delivery to select areas within Metro
                            Manila for orders placed before 12 PM.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">What payment methods do you accept?</button>
                    <div class="faq-answer">
                        <p>At the moment, we accept GCash as our primary online payment method. We are working on adding
                            more options in the near future.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">Do your frames come with a warranty?</button>
                    <div class="faq-answer">
                        <p>Yes! All frames come with a 1-year manufacturer warranty against defects. We also provide
                            free <em>lifetime adjustments</em> at any of our official stores.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">What materials are your frames made from?</button>
                    <div class="faq-answer">
                        <p>We use high-quality acetate, titanium, stainless steel, and TR-90. All materials are
                            nickel-free and hypoallergenic, ensuring long-lasting comfort for all skin types.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question">Do you have physical stores I can visit?</button>
                    <div class="faq-answer">
                        <p>Yes! Our main branch is located in Iba, Hagonoy, Bulacan. You can also visit us at Greenbelt
                            5, Makati. We are open daily from 10 AM to 9 PM.</p>
                    </div>
                </div>
            </div>
    </section>

    <script>
        (function () {
            const items = document.querySelectorAll('.faq-item');
            items.forEach(item => {
                const button = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const isActive = button.classList.contains('active');
                    // Close all others
                    items.forEach(other => {
                        const otherBtn = other.querySelector('.faq-question');
                        const otherAns = other.querySelector('.faq-answer');
                        if (otherBtn !== button) {
                            otherBtn.classList.remove('active');
                            otherAns.classList.remove('open');
                        }
                    });
                    // Toggle current
                    if (!isActive) {
                        button.classList.add('active');
                        answer.classList.add('open');
                    } else {
                        button.classList.remove('active');
                        answer.classList.remove('open');
                    }
                });
            });
        })();
    </script>

</body>

</html>