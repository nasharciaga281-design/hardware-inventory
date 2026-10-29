
<?php
$pageTitle = "Maintenance";
include "../includes/sidebar.php";
include "../includes/topbar.php";
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
    
<main class="content-area">

    <div class="page-heading">
        <div>
            <h2>Maintenance Records</h2>
            <p>Track hardware issues, repairs, and maintenance history.</p>
        </div>

        <button class="primary-button" onclick="openMaintenanceModal()">
            + Add Maintenance
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <p>Total Records</p>
            <h2 id="totalRecords">4</h2>
        </div>

        <div class="dashboard-card">
            <p>Ongoing</p>
            <h2 id="ongoingRecords">2</h2>
        </div>

        <div class="dashboard-card">
            <p>Completed</p>
            <h2 id="completedRecords">2</h2>
        </div>

        <div class="dashboard-card">
            <p>Total Repair Cost</p>
            <h2 id="totalRepairCost">₱3,500.00</h2>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="inventory-toolbar">
        <input
            type="text"
            id="maintenanceSearch"
            placeholder="Search asset tag, hardware, or issue..."
            oninput="filterMaintenance()"
        >

        <select id="maintenanceStatusFilter" onchange="filterMaintenance()">
            <option value="all">All Statuses</option>
            <option value="Ongoing">Ongoing</option>
            <option value="Completed">Completed</option>
        </select>
    </div>

    <!-- Maintenance Table -->
    <div class="table-container">
        <table class="hardware-table">
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Hardware</th>
                    <th>Issue</th>
                    <th>Maintenance Date</th>
                    <th>Repair Cost</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="maintenanceTableBody">
                <tr>
                    <td>HW-001</td>
                    <td>Desktop Computer</td>
                    <td>Overheating</td>
                    <td>2026-10-20</td>
                    <td data-cost="1500">₱1,500.00</td>
                    <td><span class="status-badge maintenance">Ongoing</span></td>
                    <td>
                        <button class="text-button" onclick="completeMaintenance(this)">
                            Complete
                        </button>
                    </td>
                </tr>

                <tr>
                    <td>HW-002</td>
                    <td>HP Printer</td>
                    <td>Paper jam</td>
                    <td>2026-10-21</td>
                    <td data-cost="500">₱500.00</td>
                    <td><span class="status-badge maintenance">Ongoing</span></td>
                    <td>
                        <button class="text-button" onclick="completeMaintenance(this)">
                            Complete
                        </button>
                    </td>
                </tr>

                <tr>
                    <td>HW-003</td>
                    <td>Network Switch</td>
                    <td>Faulty power supply</td>
                    <td>2026-10-15</td>
                    <td data-cost="1200">₱1,200.00</td>
                    <td><span class="status-badge available">Completed</span></td>
                    <td><span>—</span></td>
                </tr>

                <tr>
                    <td>HW-004</td>
                    <td>Office Monitor</td>
                    <td>Display flickering</td>
                    <td>2026-10-18</td>
                    <td data-cost="300">₱300.00</td>
                    <td><span class="status-badge available">Completed</span></td>
                    <td><span>—</span></td>
                </tr>
            </tbody>
        </table>

        <p id="maintenanceEmptyMessage" class="empty-message" style="display:none;">
            No maintenance records found.
        </p>
    </div>

</main>

<!-- Add Maintenance Modal -->
<div class="modal-overlay" id="maintenanceModal" style="display:none;">
    <div class="modal-card">

        <div class="modal-header">
            <div>
                <h2>Add Maintenance Record</h2>
                <p>Enter the hardware issue and repair details.</p>
            </div>

            <button class="close-button" onclick="closeMaintenanceModal()">
                &times;
            </button>
        </div>

        <form id="maintenanceForm">

            <div class="modal-form-grid">

                <label class="category-form-label">
                    Asset Tag
                    <input
                        type="text"
                        id="maintenanceAssetTag"
                        placeholder="e.g. HW-005"
                        required
                    >
                </label>

                <label class="category-form-label">
                    Hardware Name
                    <input
                        type="text"
                        id="maintenanceHardware"
                        placeholder="e.g. Laptop"
                        required
                    >
                </label>

                <label class="category-form-label">
                    Maintenance Date
                    <input
                        type="date"
                        id="maintenanceDate"
                        required
                    >
                </label>

                <label class="category-form-label">
                    Repair Cost (₱)
                    <input
                        type="number"
                        id="maintenanceCost"
                        min="0"
                        step="0.01"
                        value="0"
                        required
                    >
                </label>

                <label class="category-form-label">
                    Maintenance Status
                    <select id="maintenanceStatus" required>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>
                </label>

                <label class="category-form-label">
                    Issue Description
                    <textarea
                        id="maintenanceIssue"
                        rows="3"
                        placeholder="Describe the hardware problem..."
                        required
                    ></textarea>
                </label>

                <label class="category-form-label">
                    Action Taken
                    <textarea
                        id="maintenanceAction"
                        rows="3"
                        placeholder="Describe the repair performed..."
                    ></textarea>
                </label>

            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="secondary-button"
                    onclick="closeMaintenanceModal()"
                >
                    Cancel
                </button>

                <button type="submit" class="primary-button">
                    Save Record
                </button>
            </div>

        </form>
    </div>
</div>

<script>
const maintenanceBody = document.getElementById("maintenanceTableBody");
const maintenanceModal = document.getElementById("maintenanceModal");
const maintenanceForm = document.getElementById("maintenanceForm");

function openMaintenanceModal() {
    maintenanceForm.reset();
    maintenanceModal.style.display = "flex";
}

function closeMaintenanceModal() {
    maintenanceModal.style.display = "none";
}

function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, function(character) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[character];
    });
}

function formatPeso(amount) {
    return Number(amount).toLocaleString("en-PH", {
        style: "currency",
        currency: "PHP"
    });
}

function completeMaintenance(button) {
    const row = button.closest("tr");

    if (!confirm("Mark this maintenance record as completed?")) {
        return;
    }

    row.cells[5].innerHTML =
        '<span class="status-badge available">Completed</span>';

    row.cells[6].innerHTML = "<span>—</span>";

    updateMaintenanceSummary();
    filterMaintenance();
}

maintenanceForm.addEventListener("submit", function(event) {
    event.preventDefault();

    const assetTag = document.getElementById("maintenanceAssetTag").value.trim();
    const hardware = document.getElementById("maintenanceHardware").value.trim();
    const issue = document.getElementById("maintenanceIssue").value.trim();
    const date = document.getElementById("maintenanceDate").value;
    const cost = Number(document.getElementById("maintenanceCost").value);
    const status = document.getElementById("maintenanceStatus").value;
    const action = document.getElementById("maintenanceAction").value.trim();

    if (!assetTag || !hardware || !issue || !date || cost < 0 || !Number.isFinite(cost)) {
        alert("Please enter valid maintenance details.");
        return;
    }

    const rows = Array.from(maintenanceBody.querySelectorAll("tr"));

    const duplicate = rows.some(row =>
        row.cells[0].textContent.trim().toLowerCase() === assetTag.toLowerCase() &&
        row.cells[5].textContent.trim() === "Ongoing"
    );

    if (duplicate) {
        alert("This hardware already has an ongoing maintenance record.");
        return;
    }

    const row = document.createElement("tr");

    row.innerHTML = `
        <td>${escapeHTML(assetTag)}</td>
        <td>${escapeHTML(hardware)}</td>
        <td title="${escapeHTML(action)}">${escapeHTML(issue)}</td>
        <td>${escapeHTML(date)}</td>
        <td data-cost="${cost}">${formatPeso(cost)}</td>
        <td>
            <span class="status-badge ${status === "Ongoing" ? "maintenance" : "available"}">
                ${status}
            </span>
        </td>
        <td>
            ${status === "Ongoing"
                ? '<button class="text-button" onclick="completeMaintenance(this)">Complete</button>'
                : '<span>—</span>'}
        </td>
    `;

    maintenanceBody.prepend(row);

    maintenanceForm.reset();
    closeMaintenanceModal();
    updateMaintenanceSummary();
    filterMaintenance();

    alert("Maintenance record added for this session.");
});

function filterMaintenance() {
    const search = document.getElementById("maintenanceSearch").value.toLowerCase();
    const statusFilter = document.getElementById("maintenanceStatusFilter").value;
    const rows = maintenanceBody.querySelectorAll("tr");

    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const status = row.cells[5].textContent.trim();

        const matchesSearch = text.includes(search);
        const matchesStatus = statusFilter === "all" || status === statusFilter;
        const show = matchesSearch && matchesStatus;

        row.style.display = show ? "" : "none";

        if (show) visibleCount++;
    });

    document.getElementById("maintenanceEmptyMessage").style.display =
        visibleCount === 0 ? "block" : "none";
}

function updateMaintenanceSummary() {
    const rows = Array.from(maintenanceBody.querySelectorAll("tr"));

    let ongoing = 0;
    let completed = 0;
    let totalCost = 0;

    rows.forEach(row => {
        const status = row.cells[5].textContent.trim();

        if (status === "Ongoing") ongoing++;
        if (status === "Completed") completed++;

        totalCost += Number(row.cells[4].dataset.cost || 0);
    });

    document.getElementById("totalRecords").textContent = rows.length;
    document.getElementById("ongoingRecords").textContent = ongoing;
    document.getElementById("completedRecords").textContent = completed;
    document.getElementById("totalRepairCost").textContent = formatPeso(totalCost);
}

maintenanceModal.addEventListener("click", function(event) {
    if (event.target === maintenanceModal) {
        closeMaintenanceModal();
    }
});

updateMaintenanceSummary();
</script>
</body>
</html>
