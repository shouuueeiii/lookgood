<?php
$team = [
  [
    'name' => 'Aarhon Bautista',
    'role' => 'Front-End Developer',
    'bio' => 'Keeps the brand identity alive. ',
    'photo' => '../Resources/Images/team/aarhon.png',
  ],
  [
    'name' => 'Erica Mae Ramirez',
    'role' => 'Full Stack Developer',
    'bio' => 'The reason everything feels smooth and intentional.',
    'photo' => '../Resources/Images/team/erica.png',
  ],
  [
    'name' => 'Pollyne Anne Bartolome',
    'role' => 'Front-End Developer',
    'bio' => 'Turns designs into identity.',
    'photo' => '../Resources/Images/team/pollyne.png',
  ],
  [
    'name' => 'Edrian Sedrik Halili',
    'role' => 'Back-End Developer',
    'bio' => 'APIs, databases, and everything in between — he makes it.',
    'photo' => '../Resources/Images/team/eds.png',
  ],

];

?>

<!-- Team Section -->
<section class="team" id="team-section">

  <style>
    .team {
      background: #0a0a0a;
      padding: 35px 32px;
      font-family: 'DM Sans', sans-serif;
      padding-top: 100px;
      min-height: 700px;
    }

    .team-header {
      max-width: 960px;
      margin: 0 auto 48px;
    }

    .team-header .eyebrow {
      font-family: 'Spectral', serif;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #c8a96e;
      display: block;
      margin-bottom: 8px;
    }

    .team-header .section-title {
      font-family: 'Spectral', serif;
      font-size: 40px;
      font-weight: 700;
      color: #ffffff;
      margin: 0;
      line-height: 1.2;
    }

    .team-header .section-title em {
      font-style: italic;
      color: #c8a96e;
    }

    .team-grid {
      max-width: 960px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .team-card {
      border-radius: 16px;
      overflow: hidden;
      background: #141414;
      border: 0.5px solid rgba(255, 255, 255, 0.07);
      transition: transform 0.35s cubic-bezier(0.2, 0.9, 0.4, 1.1),
        box-shadow 0.35s ease,
        border-color 0.35s ease;
      cursor: default;
      position: relative;
      z-index: 1;
    }

    .team-card:hover {
      transform: scale(1.06);
      box-shadow: 0 20px 48px rgba(0, 0, 0, 0.6);
      border-color: rgba(200, 169, 110, 0.35);
      z-index: 2;
    }

    .team-card-img {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      display: block;
      filter: grayscale(0%);
      transition: filter 0.35s ease;
    }

    .team-card:hover .team-card-img {
      filter: grayscale(0%);
    }

    .team-card-body {
      padding: 0;
      position: relative;
      height: 72px;
      overflow: hidden;
    }

    .team-card-default,
    .team-card-hover {
      position: absolute;
      inset: 0;
      padding: 14px 16px 18px;
      transition: opacity 0.25s ease, transform 0.25s ease;
    }

    .team-card-default {
      opacity: 1;
      transform: translateY(0);
    }

    .team-card-hover {
      opacity: 0;
      transform: translateY(6px);
    }

    .team-card:hover .team-card-default {
      opacity: 0;
      transform: translateY(-6px);
    }

    .team-card:hover .team-card-hover {
      opacity: 1;
      transform: translateY(0);
    }

    .team-card-role {
      font-size: 10px;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: #c8a96e;
      margin: 0 0 4px;
    }

    .team-card-name {
      font-family: 'Spectral', serif;
      font-size: 16px;
      font-weight: 700;
      color: #ffffff;
      margin: 0;
      line-height: 1.2;
    }

    .team-card-bio {
      font-size: 11px;
      line-height: 1.55;
      color: rgba(255, 255, 255, 0.55);
      margin: 0;
    }

    @media (max-width: 768px) {
      .team-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 480px) {
      .team {
        padding: 48px 20px;
      }

      .team-header .section-title {
        font-size: 28px;
      }

      .team-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }
    }
  </style>

  <div class="team-header">
    <span class="eyebrow">The People Behind the Frames</span>
    <h2 class="section-title">Meet the <em>Team</em></h2>
  </div>

  <div class="team-grid">
    <?php foreach ($team as $i => $member): ?>
      <?php

      $photo = file_exists(__DIR__ . '/' . $member['photo'])
        ? htmlspecialchars($member['photo'])
        : $placeholder_base . $placeholder_seeds[$i] . '/400/533';
      ?>
      <div class="team-card">
        <img class="team-card-img" src="<?= $photo ?>" alt="<?= htmlspecialchars($member['name']) ?>" loading="lazy">
        <div class="team-card-body">
          <div class="team-card-default">
            <p class="team-card-role"><?= htmlspecialchars($member['role']) ?></p>
            <h3 class="team-card-name"><?= htmlspecialchars($member['name']) ?></h3>
          </div>
          <div class="team-card-hover">
            <p class="team-card-bio"><?= htmlspecialchars($member['bio']) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</section>
