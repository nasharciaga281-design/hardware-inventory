<?php

$pageTitle       = "Dashboard";
$pageDescription = "Overview of your organization's hardware.";
$pageStyles      = ["dashboard"];

$inventoryValue = 621640;

$statusCounts = [
    "available"   => 15,
    "assigned"    => 4,
    "maintenance" => 2,
];

$categories = [
    ["label" => "Laptops",     "count" => 5, "color" => "blue"],
    ["label" => "Desktops",    "count" => 4, "color" => "green"],
    ["label" => "Monitors",    "count" => 3, "color" => "amber"],
    ["label" => "Printers",    "count" => 3, "color" => "teal"],
    ["label" => "Networking",  "count" => 3, "color" => "purple"],
    ["label" => "Peripherals", "count" => 3, "color" => "red"],
];

$conditions = [
    ["label" => "New",  "count" => 5,  "color" => "green"],
    ["label" => "Good", "count" => 11, "color" => "blue"],
    ["label" => "Fair", "count" => 4,  "color" => "amber"],
    ["label" => "Poor", "count" => 1,  "color" => "red"],
];

$recentHardware = [
    ["tag" => "PER-0002", "name" => "Logitech C920 Webcam",     "category" => "Peripherals", "location" => "Conference Room",        "status" => "Available"],
    ["tag" => "MON-0001", "name" => "Dell P2422H Monitor",      "category" => "Monitors",    "location" => "Computer Laboratory 1",  "status" => "Available"],
    ["tag" => "PER-0001", "name" => "Epson EB-X51 Projector",   "category" => "Peripherals", "location" => "Conference Room",        "status" => "Available"],
    ["tag" => "DSK-0004", "name" => "Lenovo ThinkCentre M70q",  "category" => "Desktops",    "location" => "Computer Laboratory 1",  "status" => "Available"],
    ["tag" => "NET-0003", "name" => "MikroTik hEX RB750Gr3",    "category" => "Networking",  "location" => "Server Room",            "status" => "Available"],
    ["tag" => "DSK-0003", "name" => "HP EliteDesk 800 G6",      "category" => "Desktops",    "location" => "Finance Department",     "status" => "Assigned"],
];

$maintenance = [
    ["issue" => "Paper jam and roller wear",   "tag" => "PRN-0002", "item" => "Brother HL-L2350DW",    "category" => "Printer",    "date" => "Oct 24, 2026", "cost" => 1450, "status" => "Ongoing"],
    ["issue" => "Battery does not hold charge", "tag" => "LAP-0005", "item" => "Acer TravelMate P2",    "category" => "Laptop",     "date" => "Oct 20, 2026", "cost" => 3200, "status" => "Ongoing"],
    ["issue" => "Port 12 not linking",          "tag" => "NET-0001", "item" => "Cisco Catalyst 2960 Switch", "category" => "Network", "date" => "Sep 18, 2026", "cost" => 0,    "status" => "Completed"],
    ["issue" => "Flickering display",           "tag" => "MON-0003", "item" => "Samsung S24R350",       "category" => "Monitor",    "date" => "Aug 14, 2026", "cost" => 2100, "status" => "Completed"],
    ["issue" => "Slow boot, failing HDD",       "tag" => "DSK-0001", "item" => "Dell OptiPlex 7010",    "category" => "Desktop",    "date" => "Jul 2, 2026",  "cost" => 2850, "status" => "Completed"],
];

$recentAssignments = [
    ["id" => "ASG-006", "tag" => "LAP-0001", "hardware" => "Dell Latitude 5440", "employee" => "Maria Santos", "date" => "Oct 28, 2026", "status" => "Active"],
    ["id" => "ASG-005", "tag" => "MON-0001", "hardware" => "Dell P2422H Monitor", "employee" => "Angela Reyes", "date" => "Oct 25, 2026", "status" => "Active"],
    ["id" => "ASG-004", "tag" => "LAP-0004", "hardware" => "Apple MacBook Air M2", "employee" => "Kristine Bautista", "date" => "Oct 23, 2026", "status" => "Active"],
];

$assetAge = [
    ["category" => "Laptops", "label" => "< 1 year", "count" => 8, "color" => "green"],
    ["category" => "Desktops", "label" => "1-3 years", "count" => 7, "color" => "blue"],
    ["category" => "Monitors", "label" => "3-5 years", "count" => 4, "color" => "amber"],
    ["category" => "Printers", "label" => "> 5 years", "count" => 2, "color" => "red"],
];

$categoryValues = [
    ["label" => "Laptops", "value" => 280000, "color" => "blue"],
    ["label" => "Desktops", "value" => 155000, "color" => "green"],
    ["label" => "Monitors", "value" => 72000, "color" => "amber"],
    ["label" => "Printers", "value" => 54640, "color" => "teal"],
    ["label" => "Networking", "value" => 42000, "color" => "purple"],
    ["label" => "Peripherals", "value" => 18000, "color" => "red"],
];

$recentCategories = [
    ["id" => "CAT-006", "name" => "Peripherals", "description" => "Input and output devices", "date" => "Oct 27, 2026"],
    ["id" => "CAT-005", "name" => "Networking", "description" => "Network equipment", "date" => "Oct 20, 2026"],
    ["id" => "CAT-004", "name" => "Printers", "description" => "Printing devices", "date" => "Oct 18, 2026"],
];

$recentLocations = [
    ["id" => "LOC-005", "name" => "Server Room", "address" => "Main Building, 2nd Floor", "date" => "Oct 26, 2026"],
    ["id" => "LOC-004", "name" => "IT Office", "address" => "Main Building, 1st Floor", "date" => "Oct 21, 2026"],
    ["id" => "LOC-003", "name" => "Conference Room", "address" => "Main Building, Ground Floor", "date" => "Oct 15, 2026"],
];

$recentUsers = [
    ["id" => "USR-006", "name" => "Maria Santos", "role" => "Administrator", "date" => "Oct 29, 2026"],
    ["id" => "USR-005", "name" => "Angela Reyes", "role" => "Staff", "date" => "Oct 24, 2026"],
    ["id" => "USR-004", "name" => "Mark Villanueva", "role" => "Staff", "date" => "Oct 19, 2026"],
];


$totalHardware = array_sum($statusCounts);
$repairSpend   = array_sum(array_column($maintenance, "cost"));

$summaryCards = [
    ["label" => "Total Hardware",     "value" => $totalHardware,                "note" => count($categories) . " categories", "icon" => "bi-boxes",        "tone" => "blue"],
    ["label" => "Available",          "value" => $statusCounts["available"],    "note" => "Ready to assign",                  "icon" => "bi-check-circle", "tone" => "green"],
    ["label" => "Assigned",           "value" => $statusCounts["assigned"],     "note" => "In use by employees",              "icon" => "bi-person-check", "tone" => "gray"],
    ["label" => "Under Maintenance",  "value" => $statusCounts["maintenance"],  "note" => "Ongoing repairs",                  "icon" => "bi-wrench",       "tone" => "amber"],
];


function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, "UTF-8"); }
function peso(float $amount): string { return "₱" . number_format($amount, 2); }
function percent(int $part, int $whole): int { return $whole > 0 ? (int) round($part / $whole * 100) : 0; }


$barAxisMax   = (int) (ceil((max(array_column($categories, "count")) + 2) / 2) * 2);
$barAxisTicks = range(0, $barAxisMax, 2);
$assetAgeMax = max(array_column($assetAge, "count"));
$categoryValueMax = max(array_column($categoryValues, "value"));


$donutOuter = 90;
$donutInner = 56;
$conditionTotal = array_sum(array_column($conditions, "count"));

function polar(float $radius, float $degrees): array {
    $rad = deg2rad($degrees - 90);
    return [100 + $radius * cos($rad), 100 + $radius * sin($rad)];
}

function slicePath(float $start, float $end, float $outer, float $inner): string {
    [$x1, $y1] = polar($outer, $start);
    [$x2, $y2] = polar($outer, $end);
    [$x3, $y3] = polar($inner, $end);
    [$x4, $y4] = polar($inner, $start);
    $large = ($end - $start) > 180 ? 1 : 0;

    return sprintf(
        "M%.2f %.2fA%d %d 0 %d 1 %.2f %.2fL%.2f %.2fA%d %d 0 %d 0 %.2f %.2fZ",
        $x1, $y1, $outer, $outer, $large, $x2, $y2, $x3, $y3, $inner, $inner, $large, $x4, $y4
    );
}

$angle = 0;
foreach ($conditions as $i => $slice) {
    $sweep = $conditionTotal > 0 ? $slice["count"] / $conditionTotal * 360 : 0;
    $sweep = min($sweep, 359.99); // a full 360° arc collapses to nothing in SVG
    $conditions[$i]["percent"] = percent($slice["count"], $conditionTotal);
    $conditions[$i]["path"]    = slicePath($angle, $angle + $sweep, $donutOuter, $donutInner);
    $angle += $sweep;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "../includes/head.php"; ?>
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <section class="dashboard-content">

            
            <div class="dashboard-toolbar">
                <p class="inventory-value">Inventory value: <strong><?= peso($inventoryValue) ?></strong></p>

                <div class="toolbar-actions">
                    <a href="hardware.php" class="primary-button">
                        <i class="bi bi-plus-lg"></i> Add Hardware
                    </a>
                    <a href="assignments.php" class="secondary-button">
                        <i class="bi bi-clipboard-check"></i> New Assignment
                    </a>
                </div>
            </div>

            
            <div class="summary-grid">
                <?php foreach ($summaryCards as $card): ?>
                    <article class="summary-card">
                        <div class="summary-top">
                            <span><?= e($card["label"]) ?></span>
                            <span class="summary-icon <?= e($card["tone"]) ?>"><i class="bi <?= e($card["icon"]) ?>"></i></span>
                        </div>
                        <h3><?= $card["value"] ?></h3>
                        <p><?= e($card["note"]) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            
            <div class="dashboard-grid inventory-grid">
                <section class="panel">
                    <div class="panel-heading">
                        <h3>Recently Added Hardware</h3>
                        <a href="hardware.php" class="text-link">View all</a>
                    </div>

                    <div class="table-wrapper">
                        <table class="hardware-table dashboard-hardware-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hardware</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentHardware as $item): ?>
                                    <tr>
                                        <td class="asset-tag"><?= e($item["tag"]) ?></td>
                                        <td class="hardware-name"><?= e($item["name"]) ?></td>
                                        <td><?= e($item["category"]) ?></td>
                                        <td><?= e($item["location"]) ?></td>
                                        <td><span class="status-badge <?= e(strtolower($item["status"])) ?>"><?= e($item["status"]) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="dashboard-grid chart-grid">
                <section class="panel">
                    <div class="panel-heading"><h3>Inventory Category, Value vs. Count</h3></div>
                    <div class="panel-body">
                        <div class="category-value-chart">
                            <?php foreach ($categories as $index => $row): ?>
                                <div class="category-value-row">
                                    <span class="category-value-label"><?= e($row["label"]) ?></span>
                                    <div class="category-value-track">
                                        <span class="bar bar-<?= e($row["color"]) ?>" style="width: <?= $row["count"] / $barAxisMax * 100 ?>%"></span>
                                        <strong><?= $row["count"] ?></strong>
                                    </div>
                                    <strong class="category-value-total"><?= peso($categoryValues[$index]["value"]) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="category-value-axis">
                            <?php foreach ($barAxisTicks as $tick): ?>
                                <span style="left: <?= $tick / $barAxisMax * 100 ?>%"><?= $tick ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading"><h3>Asset Age Distribution, Count by Category</h3></div>
                    <div class="panel-body">
                        <div class="metric-chart">
                            <?php foreach ($assetAge as $row): ?>
                                <div class="metric-row">
                                    <span><strong><?= e($row["category"]) ?></strong><small><?= e($row["label"]) ?></small></span>
                                    <div class="metric-track"><div class="metric-bar bar-<?= e($row["color"]) ?>" style="width: <?= $row["count"] / $assetAgeMax * 100 ?>%"></div></div>
                                    <strong><?= $row["count"] ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>

            
            <div class="dashboard-grid">

                <section class="panel">
                    <div class="panel-heading">
                        <h3>Recent Maintenance</h3>
                        <a href="maintenance.php" class="text-link">View all</a>
                    </div>

                    <div class="table-wrapper">
                        <table class="hardware-table dashboard-data-table">
                            <thead><tr><th>ID</th><th>Asset Name</th><th>Category</th><th>Issue</th><th>Date</th><th>Cost</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($maintenance as $job): ?>
                                    <tr>
                                        <td class="asset-tag"><?= e($job["tag"]) ?></td>
                                        <td class="hardware-name"><?= e($job["item"]) ?></td>
                                        <td><?= e($job["category"]) ?></td>
                                        <td><?= e($job["issue"]) ?></td>
                                        <td><?= e($job["date"]) ?></td>
                                        <td><?= peso($job["cost"]) ?></td>
                                        <td><span class="status-badge <?= $job["status"] === "Completed" ? "available" : "maintenance" ?>"><?= e($job["status"]) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <h3>Hardware Condition</h3>
                    </div>

                    <div class="panel-body">
                        <div class="condition-chart">

                            <div class="donut">
                                <svg viewBox="0 0 200 200" role="img" aria-label="Pie chart of hardware condition">
                                    <?php foreach ($conditions as $slice): ?>
                                        <path class="donut-slice bar-<?= e($slice["color"]) ?>"
                                              d="<?= $slice["path"] ?>"
                                              tabindex="0"
                                              data-tip-title="<?= e($slice["label"]) ?>"
                                              data-tip-value="<?= $slice["count"] ?> items · <?= $slice["percent"] ?>%"/>
                                    <?php endforeach; ?>
                                </svg>
                                <?php if ($donutInner > 0): ?>
                                    <div class="donut-center">
                                        <strong><?= $conditionTotal ?></strong>
                                        <span>Total</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <ul class="legend">
                                <?php foreach ($conditions as $slice): ?>
                                    <li>
                                        <span class="legend-dot bar-<?= e($slice["color"]) ?>"></span>
                                        <span class="legend-label"><?= e($slice["label"]) ?></span>
                                        <span class="legend-value"><?= $slice["count"] ?> · <?= $slice["percent"] ?>%</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        </div>

                        <dl class="condition-stats">
                            <div>
                                <dt>Active assignments</dt>
                                <dd><?= $statusCounts["assigned"] ?></dd>
                            </div>
                            <div>
                                <dt>Repair spend</dt>
                                <dd><?= peso($repairSpend) ?></dd>
                            </div>
                        </dl>
                    </div>
                </section>

            </div>

            <div class="dashboard-grid recent-grid">
                <section class="panel">
                    <div class="panel-heading">
                        <h3>Recent Assignments</h3>
                        <a href="assignments.php" class="text-link">View all</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="hardware-table dashboard-data-table">
                            <thead><tr><th>ID</th><th>Asset ID</th><th>Asset Name</th><th>Employee</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentAssignments as $item): ?>
                                    <tr>
                                        <td class="asset-tag"><?= e($item["id"]) ?></td>
                                        <td class="asset-tag"><?= e($item["tag"]) ?></td>
                                        <td class="hardware-name"><?= e($item["hardware"]) ?></td>
                                        <td><?= e($item["employee"]) ?></td>
                                        <td><?= e($item["date"]) ?></td>
                                        <td><span class="status-badge assigned"><?= e($item["status"]) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <h3>Recently Created Categories</h3>
                        <a href="categories.php" class="text-link">View all</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="hardware-table dashboard-data-table">
                            <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Date Created</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentCategories as $item): ?>
                                    <tr><td class="asset-tag"><?= e($item["id"]) ?></td><td class="hardware-name"><?= e($item["name"]) ?></td><td><?= e($item["description"] ?? "") ?: "—" ?></td><td><?= e($item["date"]) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <h3>Recently Added Locations</h3>
                        <a href="locations.php" class="text-link">View all</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="hardware-table dashboard-data-table">
                            <thead><tr><th>ID</th><th>Name</th><th>Address</th><th>Date Added</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentLocations as $item): ?>
                                    <tr><td class="asset-tag"><?= e($item["id"]) ?></td><td class="hardware-name"><?= e($item["name"]) ?></td><td><?= e($item["address"] ?? "") ?: "—" ?></td><td><?= e($item["date"]) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <h3>Recently Added Users</h3>
                        <a href="users.php" class="text-link">View all</a>
                    </div>
                    <div class="table-wrapper">
                        <table class="hardware-table dashboard-data-table">
                            <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Date Added</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentUsers as $item): ?>
                                    <tr><td class="asset-tag"><?= e($item["id"]) ?></td><td class="hardware-name"><?= e($item["name"]) ?></td><td><?= e($item["role"] ?? "") ?: "—" ?></td><td><?= e($item["date"]) ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </section>
    </main>

    
    <script>
    (() => {
        const tip = document.createElement("div");
        tip.className = "chart-tooltip";
        tip.setAttribute("role", "tooltip");
        document.body.append(tip);

        const place = (x, y) => {
            const gap = 14;
            const { offsetWidth: w, offsetHeight: h } = tip;
            tip.style.left = Math.min(x + gap, window.innerWidth - w - 8) + "px";
            tip.style.top  = Math.min(y + gap, window.innerHeight - h - 8) + "px";
        };

        const show = (target, x, y) => {
            tip.replaceChildren();
            const title = document.createElement("strong");
            const value = document.createElement("span");
            title.textContent = target.dataset.tipTitle;
            value.textContent = target.dataset.tipValue;
            tip.append(title, value);
            tip.classList.add("visible");
            place(x, y);
        };

        const hide = () => tip.classList.remove("visible");

        document.addEventListener("pointermove", (event) => {
            const target = event.target.closest("[data-tip-title]");
            target ? show(target, event.clientX, event.clientY) : hide();
        });

        document.addEventListener("focusin", (event) => {
            const target = event.target.closest("[data-tip-title]");
            if (!target) return;
            const box = target.getBoundingClientRect();
            show(target, box.left + box.width / 2, box.top + box.height / 2);
        });

        document.addEventListener("focusout", hide);
        document.addEventListener("pointerleave", hide);
    })();
    </script>

</body>
</html>
