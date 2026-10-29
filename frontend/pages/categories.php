
<?php
$pageTitle = "Categories";
$pageDescription = "Organize hardware assets by category.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | Hardware Management System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <section class="content-area">

            <div class="page-heading">
                <div>
                    <h2>Hardware Categories</h2>
                    <p>Group your hardware to keep inventory organized.</p>
                </div>

                <button class="primary-button" onclick="openCategoryModal()">
                    + Add Category
                </button>
            </div>

            <div class="inventory-toolbar">
                <input
                    type="text"
                    id="categorySearch"
                    placeholder="Search categories..."
                    oninput="filterCategories()"
                >
            </div>

            <div class="table-container">
                <table class="hardware-table" id="categoryTable">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Desktop</td>
                            <td>Desktop computers and workstations</td>
                            <td><button class="text-button" onclick="editCategory(this)">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Laptop</td>
                            <td>Portable computers</td>
                            <td><button class="text-button" onclick="editCategory(this)">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Monitor</td>
                            <td>Computer displays</td>
                            <td><button class="text-button" onclick="editCategory(this)">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Printer</td>
                            <td>Printers and scanning equipment</td>
                            <td><button class="text-button" onclick="editCategory(this)">Edit</button></td>
                        </tr>
                        <tr>
                            <td>Networking</td>
                            <td>Routers, switches, and network devices</td>
                            <td><button class="text-button" onclick="editCategory(this)">Edit</button></td>
                        </tr>
                    </tbody>
                </table>

                <p id="categoryEmpty" class="empty-message" hidden>
                    No matching categories found.
                </p>
            </div>

        </section>
    </main>

    <!-- Add/Edit Category Modal -->
    <div class="modal-overlay" id="categoryModal">
        <div class="modal-card">
            <div class="modal-header">
                <h2 id="categoryModalTitle">Add Category</h2>
                <button type="button" class="close-button"
                    onclick="closeCategoryModal()">×</button>
            </div>

            <form id="categoryForm">
                <label class="category-form-label">
                    Category Name
                    <input type="text" name="categoryName" id="categoryName"
                        required placeholder="e.g. Keyboard">
                </label>

                <label class="category-form-label">
                    Description
                    <textarea name="categoryDescription" id="categoryDescription"
                        rows="3" placeholder="Describe this hardware category"></textarea>
                </label>

                <div class="modal-actions">
                    <button type="button" class="secondary-button"
                        onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="primary-button">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const categoryModal = document.getElementById("categoryModal");
        const categoryForm = document.getElementById("categoryForm");
        let editingCategoryRow = null;

        function openCategoryModal() {
            editingCategoryRow = null;
            categoryForm.reset();
            document.getElementById("categoryModalTitle").textContent = "Add Category";
            categoryModal.classList.add("show");
        }

        function closeCategoryModal() {
            categoryModal.classList.remove("show");
        }

        function editCategory(button) {
            editingCategoryRow = button.closest("tr");

            document.getElementById("categoryName").value =
                editingCategoryRow.cells[0].textContent.trim();

            document.getElementById("categoryDescription").value =
                editingCategoryRow.cells[1].textContent.trim();

            document.getElementById("categoryModalTitle").textContent = "Edit Category";
            categoryModal.classList.add("show");
        }

        function filterCategories() {
            const search = document.getElementById("categorySearch")
                .value.toLowerCase();

            const rows = document.querySelectorAll("#categoryTable tbody tr");
            let visibleCount = 0;

            rows.forEach(row => {
                row.hidden = !row.textContent.toLowerCase().includes(search);
                if (!row.hidden) visibleCount++;
            });

            document.getElementById("categoryEmpty").hidden = visibleCount > 0;
        }

        categoryForm.addEventListener("submit", function(event) {
            event.preventDefault();

            const name = document.getElementById("categoryName").value.trim();
            const description =
                document.getElementById("categoryDescription").value.trim();

            if (!name) return;

            const rows = document.querySelectorAll("#categoryTable tbody tr");

            for (const row of rows) {
                if (row !== editingCategoryRow &&
                    row.cells[0].textContent.trim().toLowerCase() === name.toLowerCase()) {
                    alert("This category already exists.");
                    return;
                }
            }

            if (editingCategoryRow) {
                editingCategoryRow.cells[0].textContent = name;
                editingCategoryRow.cells[1].textContent = description || "—";
            } else {
                const row = document.querySelector("#categoryTable tbody").insertRow();
                row.insertCell().textContent = name;
                row.insertCell().textContent = description || "—";

                const actionCell = row.insertCell();
                const editButton = document.createElement("button");
                editButton.type = "button";
                editButton.className = "text-button";
                editButton.textContent = "Edit";
                editButton.addEventListener("click", () => editCategory(editButton));
                actionCell.appendChild(editButton);
            }

            closeCategoryModal();
            categoryForm.reset();
            filterCategories();
        });

        categoryModal.addEventListener("click", function(event) {
            if (event.target === categoryModal) closeCategoryModal();
        });
    </script>

</body>
</html>
