<?php

date_default_timezone_set('Asia/Manila');
?>
<header class="topbar">
    <div class="topbar-left">
        <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
        <p><?= htmlspecialchars($pageDescription ?? "Manage and monitor your organization's hardware.") ?></p>
    </div>

    <div class="topbar-right">
        <span class="topbar-date"><i class="bi bi-calendar3"></i><?= date('M d, Y') ?></span>

        
        <div class="topbar-user">
            <div class="user-avatar">A</div>
            <div class="topbar-user-info">
                <strong>Admin User</strong>
                <span>Administrator</span>
            </div>
        </div>
    </div>
</header>