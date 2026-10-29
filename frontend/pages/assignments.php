
<?php
$pageTitle = "Assignments";
$pageDescription = "Track hardware assigned to employees.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments | Hardware Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <section class="content-area">

            <div class="page-heading">
                <div>
                    <h2>Hardware Assignments</h2>
                    <p>Manage equipment issued to employees and track returns.</p>
                </div>

                <button class="primary-button" onclick="openAssignmentModal()">
                    + New Assignment
                </button>
            </div>

            <div class="inventory-toolbar">
                <input type="text" id="assignmentSearch"
                    placeholder="Search employee, hardware, or asset tag..."
                    oninput="filterAssignments()">

                <select id="assignmentFilter" onchange="filterAssignments()">
                    <option value="">All Assignments</option>
                    <option value="Assigned">Currently Assigned</option>
                    <option value="Returned">Returned</option>
                </select>
            </div>

            <div class="table-container">
                <table class="hardware-table" id="assignmentTable">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Hardware</th>
                            <th>Employee</th>
                            <th>Assigned Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>HW-002</td>
                            <td>HP ProBook 450</td>
                            <td>Juan Dela Cruz</td>
                            <td>2026-10-01</td>
                            <td>—</td>
                            <td><span class="status-badge assigned">Assigned</span></td>
                            <td><button type="button" class="text-button"
                                onclick="returnAssignment(this)">Mark Returned</button></td>
                        </tr>
                        <tr>
                            <td>HW-006</td>
                            <td>Logitech Keyboard</td>
                            <td>Maria Santos</td>
                            <td>2026-09-15</td>
                            <td>2026-10-10</td>
                            <td><span class="status-badge available">Returned</span></td>
                            <td><span>—</span></td>
                        </tr>
                    </tbody>
                </table>

                <p id="assignmentEmpty" class="empty-message" hidden>
                    No matching assignments found.
                </p>
            </div>

        </section>
    </main>

    <!-- New Assignment Modal -->
    <div class="modal-overlay" id="assignmentModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2>New Assignment</h2>
                <button type="button" class="close-button"
                    onclick="closeAssignmentModal()">×</button>
            </div>

            <form id="assignmentForm">
                <label class="category-form-label">
                    Asset Tag
                    <input name="assetTag" required placeholder="e.g. HW-001">
                </label>

                <label class="category-form-label">
                    Hardware Name
                    <input name="hardwareName" required placeholder="e.g. Dell Monitor">
                </label>

                <label class="category-form-label">
                    Employee Name
                    <input name="employeeName" required placeholder="e.g. Juan Dela Cruz">
                </label>

                <label class="category-form-label">
                    Assigned Date
                    <input name="assignedDate" type="date" required>
                </label>

                <div class="modal-actions">
                    <button type="button" class="secondary-button"
                        onclick="closeAssignmentModal()">Cancel</button>
                    <button type="submit" class="primary-button">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const assignmentModal = document.getElementById("assignmentModal");
        const assignmentForm = document.getElementById("assignmentForm");

        function openAssignmentModal() {
            assignmentForm.reset();
            assignmentModal.classList.add("show");
        }

        function closeAssignmentModal() {
            assignmentModal.classList.remove("show");
        }

        function filterAssignments() {
            const search = document.getElementById("assignmentSearch")
                .value.toLowerCase();
            const filter = document.getElementById("assignmentFilter")
                .value.toLowerCase();

            const rows = document.querySelectorAll("#assignmentTable tbody tr");
            let visible = 0;

            rows.forEach(row => {
                const matchesSearch = row.textContent.toLowerCase().includes(search);
                const status = row.cells[5].textContent.trim().toLowerCase();
                const matchesStatus = !filter || status === filter;

                row.hidden = !(matchesSearch && matchesStatus);
                if (!row.hidden) visible++;
            });

            document.getElementById("assignmentEmpty").hidden = visible > 0;
        }

        function returnAssignment(button) {
            const row = button.closest("tr");

            if (row.cells[5].textContent.trim() !== "Assigned") return;

            if (!confirm("Mark this hardware as returned?")) return;

            row.cells[4].textContent = new Date().toISOString().slice(0, 10);
            row.cells[5].innerHTML =
                '<span class="status-badge available">Returned</span>';
            row.cells[6].textContent = "—";

            filterAssignments();
        }

        assignmentForm.addEventListener("submit", function(event) {
            event.preventDefault();

            const data = new FormData(this);
            const assetTag = data.get("assetTag").trim();

            const rows = document.querySelectorAll("#assignmentTable tbody tr");

            for (const row of rows) {
                const existingTag = row.cells[0].textContent.trim();
                const status = row.cells[5].textContent.trim();

                if (existingTag.toLowerCase() === assetTag.toLowerCase()
                    && status === "Assigned") {
                    alert("This hardware already has an active assignment.");
                    return;
                }
            }

            const row = document.querySelector("#assignmentTable tbody").insertRow();

            [
                assetTag,
                data.get("hardwareName").trim(),
                data.get("employeeName").trim(),
                data.get("assignedDate"),
                "—"
            ].forEach(value => row.insertCell().textContent = value);

            const statusCell = row.insertCell();
            statusCell.innerHTML = '<span class="status-badge assigned">Assigned</span>';

            const actionCell = row.insertCell();
            const returnButton = document.createElement("button");
            returnButton.type = "button";
            returnButton.className = "text-button";
            returnButton.textContent = "Mark Returned";
            returnButton.addEventListener("click", () => returnAssignment(returnButton));
            actionCell.appendChild(returnButton);

            closeAssignmentModal();
            assignmentForm.reset();
            filterAssignments();
            alert("Assignment added to the temporary list.");
        });

        assignmentModal.addEventListener("click", function(event) {
            if (event.target === assignmentModal) closeAssignmentModal();
        });
    </script>

</body>
</html>
