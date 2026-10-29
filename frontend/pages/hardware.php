
<?php
$pageTitle = "Hardware Inventory";
$pageDescription = "Manage and monitor all hardware assets.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hardware Inventory | Hardware Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <section class="content-area">

            <div class="page-heading">
                <div>
                    <h2>All Hardware</h2>
                    <p>View, search, and manage your equipment.</p>
                </div>

                <button class="primary-button" onclick="openHardwareModal()">
                    + Add Hardware
                </button>
            </div>

            <div class="inventory-toolbar">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="Search by name, asset tag, brand..."
                    oninput="filterHardware()"
                >

                <select id="statusFilter" onchange="filterHardware()">
                    <option value="">All Statuses</option>
                    <option value="Available">Available</option>
                    <option value="Assigned">Assigned</option>
                    <option value="Under Maintenance">Under Maintenance</option>
                </select>
            </div>

            <div class="table-container">
                <table class="hardware-table" id="hardwareTable">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Hardware Name</th>
                            <th>Category</th>
                            <th>Brand / Model</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>HW-001</td>
                            <td>Dell OptiPlex 7090</td>
                            <td>Desktop</td>
                            <td>Dell / 7090</td>
                            <td>Office 101</td>
                            <td><span class="status-badge available">Available</span></td>
                            <td><button class="text-button" onclick="viewHardware(this)">View</button></td>
                        </tr>

                        <tr>
                            <td>HW-002</td>
                            <td>HP ProBook 450</td>
                            <td>Laptop</td>
                            <td>HP / 450 G8</td>
                            <td>Office 102</td>
                            <td><span class="status-badge assigned">Assigned</span></td>
                            <td><button class="text-button" onclick="viewHardware(this)">View</button></td>
                        </tr>

                        <tr>
                            <td>HW-003</td>
                            <td>Logitech C920</td>
                            <td>Webcam</td>
                            <td>Logitech / C920</td>
                            <td>Storage Room</td>
                            <td><span class="status-badge maintenance">Under Maintenance</span></td>
                            <td><button class="text-button" onclick="viewHardware(this)">View</button></td>
                        </tr>

                        <tr>
                            <td>HW-004</td>
                            <td>Lenovo ThinkCentre M70</td>
                            <td>Desktop</td>
                            <td>Lenovo / M70</td>
                            <td>Office 103</td>
                            <td><span class="status-badge available">Available</span></td>
                            <td><button class="text-button" onclick="viewHardware(this)">View</button></td>
                        </tr>
                    </tbody>
                </table>

                <p id="emptyMessage" class="empty-message" hidden>
                    No matching hardware found.
                </p>
            </div>

        </section>
    </main>

    <!-- Add Hardware Modal -->
    <div class="modal-overlay" id="hardwareModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Add Hardware</h2>
                <button type="button" class="close-button"
                    onclick="closeHardwareModal()">×</button>
            </div>

            <form id="hardwareForm">
                <div class="modal-form-grid">
                    <label>
                        Asset Tag
                        <input name="assetTag" required placeholder="e.g. HW-005">
                    </label>

                    <label>
                        Hardware Name
                        <input name="hardwareName" required placeholder="e.g. Dell Monitor">
                    </label>

                    <label>
                        Category
                        <select name="category" required>
                            <option value="">Select category</option>
                            <option>Desktop</option>
                            <option>Laptop</option>
                            <option>Monitor</option>
                            <option>Printer</option>
                            <option>Networking</option>
                            <option>Other</option>
                        </select>
                    </label>

                    <label>
                        Brand
                        <input name="brand" placeholder="e.g. Dell">
                    </label>

                    <label>
                        Model
                        <input name="model" placeholder="e.g. OptiPlex 7090">
                    </label>

                    <label>
                        Location
                        <input name="location" required placeholder="e.g. Office 101">
                    </label>

                    <label>
                        Status
                        <select name="status" required>
                            <option>Available</option>
                            <option>Assigned</option>
                            <option>Under Maintenance</option>
                        </select>
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="secondary-button"
                        onclick="closeHardwareModal()">Cancel</button>
                    <button type="submit" class="primary-button">Add to List</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("hardwareModal");

        function openHardwareModal() {
            modal.classList.add("show");
        }

        function closeHardwareModal() {
            modal.classList.remove("show");
        }

        function filterHardware() {
            const search = document.getElementById("searchInput")
                .value.toLowerCase();
            const status = document.getElementById("statusFilter")
                .value.toLowerCase();

            const rows = document.querySelectorAll("#hardwareTable tbody tr");
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowStatus = row.cells[5].textContent.trim().toLowerCase();

                const matchesSearch = text.includes(search);
                const matchesStatus = !status || rowStatus === status;

                row.hidden = !(matchesSearch && matchesStatus);

                if (!row.hidden) visibleCount++;
            });

            document.getElementById("emptyMessage").hidden = visibleCount > 0;
        }

        function viewHardware(button) {
            const row = button.closest("tr");
            const details = Array.from(row.cells)
                .slice(0, 6)
                .map(cell => cell.textContent.trim());

            alert(
                "Hardware Details\n\n" +
                "Asset Tag: " + details[0] + "\n" +
                "Name: " + details[1] + "\n" +
                "Category: " + details[2] + "\n" +
                "Brand / Model: " + details[3] + "\n" +
                "Location: " + details[4] + "\n" +
                "Status: " + details[5]
            );
        }

        document.getElementById("hardwareForm").addEventListener("submit", function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const assetTag = formData.get("assetTag").trim();
            const hardwareName = formData.get("hardwareName").trim();

            const existingRows = document.querySelectorAll("#hardwareTable tbody tr");

            for (const row of existingRows) {
                if (row.cells[0].textContent.trim().toLowerCase() === assetTag.toLowerCase()) {
                    alert("That asset tag already exists.");
                    return;
                }
            }

            const row = document.querySelector("#hardwareTable tbody").insertRow();

            const values = [
                assetTag,
                hardwareName,
                formData.get("category"),
                [formData.get("brand"), formData.get("model")].filter(Boolean).join(" / ") || "—",
                formData.get("location"),
                formData.get("status")
            ];

            values.forEach(value => {
                row.insertCell().textContent = value;
            });

            const statusCell = row.cells[5];
            const statusClass = {
                "Available": "available",
                "Assigned": "assigned",
                "Under Maintenance": "maintenance"
            }[values[5]];

            statusCell.innerHTML = "";
            const badge = document.createElement("span");
            badge.className = "status-badge " + statusClass;
            badge.textContent = values[5];
            statusCell.appendChild(badge);

            const actionCell = row.insertCell();
            const viewButton = document.createElement("button");
            viewButton.type = "button";
            viewButton.className = "text-button";
            viewButton.textContent = "View";
            viewButton.addEventListener("click", () => viewHardware(viewButton));
            actionCell.appendChild(viewButton);

            this.reset();
            closeHardwareModal();
            filterHardware();
            alert("Hardware added to the temporary list.");
        });

        modal.addEventListener("click", function(event) {
            if (event.target === modal) closeHardwareModal();
        });
    </script>

</body>
</html>
