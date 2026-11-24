<?php

date_default_timezone_set('Asia/Manila');

$topbarFirstName = (string) ($_SESSION['first_name'] ?? '');
$topbarLastName = (string) ($_SESSION['last_name'] ?? '');
$topbarName = trim($topbarFirstName . ' ' . $topbarLastName);
if ($topbarName === '') {
    $topbarName = 'User';
}
$topbarRole = (string) ($_SESSION['role'] ?? 'Unassigned');
?>
<header class="topbar">
    <div class="topbar-left">
        <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        <p><?= htmlspecialchars($pageDescription ?? "Manage and monitor your organization's hardware.") ?></p>
    </div>

    <div class="topbar-right">
        <span class="topbar-date"><i class="bi bi-calendar3"></i><?= date('M d, Y') ?></span>

        
        <div class="topbar-user">
            <div class="user-avatar"><?= htmlspecialchars(mb_substr($topbarFirstName, 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="topbar-user-info">
                <strong><?= htmlspecialchars($topbarName, ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars($topbarRole, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
</header>