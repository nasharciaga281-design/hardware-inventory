
<?php
$pageTitle = "Locations";
$pageDescription = "Manage the places where hardware is stored or assigned.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locations | Hardware Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <section class="content-area">

            <div class="page-heading">
                <div>
                    <h2>Hardware Locations</h2>
                    <p>Track the buildings, rooms, and storage areas where hardware is kept.</p>
                </div>

                <button class="primary-button" onclick="openLocationModal()">
                    + Add Location
                </button>
            </div>

            <div class="inventory-toolbar">
                <input
                    type="text"
                    id="locationSearch"
                    placeholder="Search location, building, or room..."
                    oninput="filterLocations()"
                >
            </div>

            <div class="table-container">
                <table class="hardware-table" id="locationTable">
                    <thead>
                        <tr>
                            <th>Location Name</th>
                            <th>Building</th>
                            <th>Room Number</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Main Office</td>
                            <td>Administration Building</td>
                            <td>101</td>
                            <td>Main office equipment</td>
                            <td><button type="button" class="text-button" onclick="editLocation(this)">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Computer Lab</td>
                            <td>Technology Building</td>
                            <td>202</td>
                            <td>Student computers and peripherals</td>
                            <td><button type="button" class="text-button" onclick="editLocation(this)">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Storage Room</td>
                            <td>Administration Building</td>
                            <td>105</td>
                            <td>Unused and spare hardware</td>
                            <td><button type="button" class="text-button" onclick="editLocation(this)">Edit</button></td>
                        </tr>
                    </tbody>
                </table>

                <p id="locationEmpty" class="empty-message" hidden>
                    No matching locations found.
                </p>
            </div>

        </section>
    </main>

    <!-- Add/Edit Location Modal -->
    <div class="modal-overlay" id="locationModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="locationModalTitle">Add Location</h2>
                <button type="button" class="close-button"
                    onclick="closeLocationModal()">×</button>
            </div>

            <form id="locationForm">
                <label class="category-form-label">
                    Location Name
                    <input type="text" name="locationName" id="locationName"
                        required placeholder="e.g. Computer Laboratory">
                </label>

                <label class="category-form-label">
                    Building
                    <input type="text" name="building" id="building"
                        placeholder="e.g. Technology Building">
                </label>

                <label class="category-form-label">
                    Room Number
                    <input type="text" name="roomNumber" id="roomNumber"
                        placeholder="e.g. 204">
                </label>

                <label class="category-form-label">
                    Description
                    <textarea name="description" id="locationDescription"
                        rows="3" placeholder="Describe this location"></textarea>
                </label>

                <div class="modal-actions">
                    <button type="button" class="secondary-button"
                        onclick="closeLocationModal()">Cancel</button>
                    <button type="submit" class="primary-button">Save Location</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const locationModal = document.getElementById("locationModal");
        const locationForm = document.getElementById("locationForm");
        let editingLocationRow = null;

        function openLocationModal() {
            editingLocationRow = null;
            locationForm.reset();
            document.getElementById("locationModalTitle").textContent = "Add Location";
            locationModal.classList.add("show");
        }

        function closeLocationModal() {
            locationModal.classList.remove("show");
        }

        function editLocation(button) {
            editingLocationRow = button.closest("tr");

            document.getElementById("locationName").value =
                editingLocationRow.cells[0].textContent.trim();
            document.getElementById("building").value =
                editingLocationRow.cells[1].textContent.trim();
            document.getElementById("roomNumber").value =
                editingLocationRow.cells[2].textContent.trim();
            document.getElementById("locationDescription").value =
                editingLocationRow.cells[3].textContent.trim();

            document.getElementById("locationModalTitle").textContent = "Edit Location";
            locationModal.classList.add("show");
        }

        function filterLocations() {
            const search = document.getElementById("locationSearch")
                .value.toLowerCase();
            const rows = document.querySelectorAll("#locationTable tbody tr");
            let visibleCount = 0;

            rows.forEach(row => {
                row.hidden = !row.textContent.toLowerCase().includes(search);
                if (!row.hidden) visibleCount++;
            });

            document.getElementById("locationEmpty").hidden = visibleCount > 0;
        }

        locationForm.addEventListener("submit", function(event) {
            event.preventDefault();

            const name = document.getElementById("locationName").value.trim();
            const building = document.getElementById("building").value.trim();
            const room = document.getElementById("roomNumber").value.trim();
            const description =
                document.getElementById("locationDescription").value.trim();

            if (!name) return;

            if (editingLocationRow) {
                editingLocationRow.cells[0].textContent = name;
                editingLocationRow.cells[1].textContent = building || "—";
                editingLocationRow.cells[2].textContent = room || "—";
                editingLocationRow.cells[3].textContent = description || "—";
            } else {
                const row = document.querySelector("#locationTable tbody").insertRow();

                [name, building || "—", room || "—", description || "—"]
                    .forEach(value => row.insertCell().textContent = value);

                const actionCell = row.insertCell();
                const editButton = document.createElement("button");
                editButton.type = "button";
                editButton.className = "text-button";
                editButton.textContent = "Edit";
                editButton.addEventListener("click", () => editLocation(editButton));
                actionCell.appendChild(editButton);
            }

            closeLocationModal();
            locationForm.reset();
            filterLocations();
        });

        locationModal.addEventListener("click", function(event) {
            if (event.target === locationModal) closeLocationModal();
        });
    </script>

</body>
</html>
