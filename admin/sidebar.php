<?php
if (!function_exists('canAccess')) {
    require_once __DIR__ . '/../auth_admin.php';
}
$activePage = $activePage ?? '';

$navItems = [
    'dashboard'     => ['icon' => 'fa-chart-line',    'label' => 'Dashboard',      'href' => 'dashboard.php'],
    'product'       => ['icon' => 'fa-box',            'label' => 'Product',        'href' => 'product.php'],
    'orders'        => ['icon' => 'fa-shopping-cart',  'label' => 'Order',          'href' => 'orders.php'],
    'users'         => ['icon' => 'fa-users',          'label' => 'User',           'href' => 'users.php'],
    'messages'      => ['icon' => 'fa-envelope',       'label' => 'Message',        'href' => 'messages.php'],
    'feedback'      => ['icon' => 'fa-star',           'label' => 'Feedback',       'href' => 'feedback.php'],
    'report'        => ['icon' => 'fa-file-alt',       'label' => 'Report',         'href' => 'report.php'],
    'notifications' => ['icon' => 'fa-bell',           'label' => 'Notifications',  'href' => 'notifications.php'],
    'settings'      => ['icon' => 'fa-cog',            'label' => 'Setting',        'href' => 'settings.php'],
];
?>
<aside class="sidebar">
    <ul class="nav-links">
        <?php foreach ($navItems as $slug => $item): ?>
            <?php if (canAccess($slug)): ?>
                <li class="nav-item">
                    <a href="<?= $item['href'] ?>" class="nav-link<?= ($activePage === $slug) ? ' active' : '' ?>">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-chevron-left"></i>
    </div>
</aside>
