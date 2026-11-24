<?php

$currentPage = basename($_SERVER['PHP_SELF']);


$menuItems = [
    ["dashboard.php",   "bi-grid-1x2",        "Dashboard"],
    ["hardware.php",    "bi-boxes",           "Hardware Inventory"],
    ["categories.php",  "bi-tags",            "Categories"],
    ["locations.php",   "bi-geo-alt",         "Locations"],
    ["departments.php", "bi-building",        "Departments"],
    ["employees.php",   "bi-person-badge",    "Employees"],
    ["assignments.php", "bi-clipboard-check", "Assignments"],
    ["maintenance.php", "bi-wrench",          "Maintenance"],
    ["users.php",       "bi-people",          "User Management"],
];
if (!auth_has_role(HIMS_ROLE_ADMIN)) {
    $menuItems = array_filter($menuItems, static fn($item) => $item[0] !== 'users.php');
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-hdd-stack"></i></div>
        <div class="brand-title">
            <h2>HMS</h2>
            <span>Hardware Management</span>
        </div>
    </div>

    <p class="sidebar-label">Menu</p>

    <nav class="sidebar-nav">
        <?php foreach ($menuItems as [$file, $icon, $label]): ?>
            <?php $isActive = ($currentPage === $file); ?>
            <a href="<?= $file ?>"
               class="nav-link<?= $isActive ? ' active' : '' ?>"
               title="<?= $label ?>"
               <?= $isActive ? 'aria-current="page"' : '' ?>>
                <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        
        <form method="POST" action="../account/logout.php">
            <?= auth_csrf_field() ?>
            <button type="submit" class="nav-link" title="Logout">
                <i class="bi bi-box-arrow-left"></i><span>Logout</span>
            </button>
        </form>
    </div>
</aside>
