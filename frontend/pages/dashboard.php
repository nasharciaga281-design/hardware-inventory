
<?php
$pageTitle = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Hardware Manager</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include "../includes/topbar.php"; ?>

        <section class="dashboard-content">

            <div class="welcome-section">
                <div>
                    <h2>Inventory Overview</h2>
                    <p>Here's what's happening with your hardware today.</p>
                </div>

                <button class="primary-button" type="button">
                    + Add Hardware
                </button>
            </div>

            <div class="summary-grid">

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Total Hardware</span>
                        <span class="summary-icon blue">▤</span>
                    </div>
                    <h3>248</h3>
                    <p>Registered assets</p>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Available</span>
                        <span class="summary-icon green">✓</span>
                    </div>
                    <h3>176</h3>
                    <p>Ready for use</p>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Assigned</span>
                        <span class="summary-icon purple">⇄</span>
                    </div>
                    <h3>58</h3>
                    <p>Currently in use</p>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Under Maintenance</span>
                        <span class="summary-icon orange">⚒</span>
                    </div>
                    <h3>10</h3>
                    <p>Being serviced</p>
                </article>

            </div>

            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h3>Recent Hardware</h3>
                        <p>A quick look at your registered assets.</p>
                    </div>

                    <a href="#" class="text-link">View all</a>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Asset Tag</th>
                                <th>Hardware Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>HW-001</td>
                                <td>Dell OptiPlex 7090</td>
                                <td>Desktop Computer</td>
                                <td>Office 101</td>
                                <td><span class="status available">Available</span></td>
                            </tr>

                            <tr>
                                <td>HW-002</td>
                                <td>HP ProBook 450</td>
                                <td>Laptop</td>
                                <td>Finance Office</td>
                                <td><span class="status assigned">Assigned</span></td>
                            </tr>

                            <tr>
                                <td>HW-003</td>
                                <td>HP LaserJet Pro</td>
                                <td>Printer</td>
                                <td>Office 102</td>
                                <td><span class="status maintenance">Maintenance</span></td>
                            </tr>

                            <tr>
                                <td>HW-004</td>
                                <td>LG UltraGear 24"</td>
                                <td>Monitor</td>
                                <td>IT Department</td>
                                <td><span class="status available">Available</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </section>

    </main>

</body>
</html>
