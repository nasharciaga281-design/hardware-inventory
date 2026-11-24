<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
auth_require_user();
$canManageDepartments = auth_has_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

$pageTitle       = "Departments";
$pageDescription = "Organize employees by department";
$pageStyles      = ["categories"];

$stmt = $pdo->query(
    "SELECT d.department_id AS id, d.department_name AS name,
            COALESCE(d.description, '') AS description,
            COUNT(e.employee_id) AS `count`
     FROM departments d
     LEFT JOIN employees e ON e.department_id = d.department_id
     GROUP BY d.department_id, d.department_name, d.description
     ORDER BY d.department_name"
);
$departments = array_map(static function (array $row): array {
    $row['id'] = (int) $row['id'];
    $row['count'] = (int) $row['count'];
    return $row;
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "../includes/head.php"; ?>
    <meta name="csrf-token" content="<?= htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="dashboard-page">

    <?php include "../includes/sidebar.php"; ?>

    <main class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <section class="content-area">

            
            <div class="summary-grid category-summary">
                <article class="summary-card">
                    <div class="summary-top">
                        <span>Departments</span>
                        <span class="summary-icon blue"><i class="bi bi-diagram-3"></i></span>
                    </div>
                    <h3 id="statDepartments">0</h3>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Employees</span>
                        <span class="summary-icon green"><i class="bi bi-boxes"></i></span>
                    </div>
                    <h3 id="statItems">0</h3>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Largest Department</span>
                        <span class="summary-icon gray"><i class="bi bi-boxes"></i></span>
                    </div>
                    <h3 id="statLargest">—</h3>
                    <p id="statLargestNote">0 items</p>
                </article>
            </div>

            <div class="table-card">

                <div class="table-toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="searchInput" placeholder="Search departments" aria-label="Search departments">
                    </label>

                    <?php if ($canManageDepartments): ?>
                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Department
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Description</th>
                                <th>Employees</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoryBody"></tbody>
                    </table>
                </div>

            </div>
        </section>
    </main>

    
    <dialog class="modal" id="formDialog" aria-labelledby="formTitle">
        <form id="categoryForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">Add Department</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <label class="field">Department Name
                <input name="name" required placeholder="e.g. Finance">
            </label>

            <label class="field">Description
                <textarea name="description" rows="3" placeholder="Describe this department"></textarea>
            </label>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Save Department</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal" id="deleteDialog" aria-labelledby="deleteTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="deleteTitle">Delete department?</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <p class="dialog-text" id="deleteText"></p>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="button" class="danger-button" id="deleteConfirm">Delete</button>
            </div>
        </div>
    </dialog>

    <script>
    (() => {
        
        const canManage = <?= json_encode($canManageDepartments) ?>;
        const items = <?= json_encode($departments, $jsonFlags) ?>;

        const state = {
            search: "",
            editingName: null, // department being edited, or null when adding
            targetName: null   // department in the delete dialog
        };

        
        const $ = (id) => document.getElementById(id);
        const body = $("categoryBody");
        const form = $("categoryForm");
        const formDialog = $("formDialog");
        const deleteDialog = $("deleteDialog");

        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const find = (name) => items.find((item) => item.name === name);
        const plural = (count, word) => `${count} ${word}${count === 1 ? "" : "s"}`;

        
        const rowHtml = (item) => `
            <tr>
                <td class="primary-cell">${esc(item.name)}</td>
                <td>${esc(item.description || "—")}</td>
                <td class="numeric-cell">${item.count}</td>
                <td>
                    <div class="row-actions">
                        ${canManage ? `<button type="button" class="icon-button" data-action="edit" data-name="${esc(item.name)}" aria-label="Edit ${esc(item.name)}"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="icon-button danger" data-action="delete" data-name="${esc(item.name)}" aria-label="Delete ${esc(item.name)}"><i class="bi bi-trash"></i></button>` : `<span class="text-muted">—</span>`}
                    </div>
                </td>
            </tr>`;

        function renderSummary() {
            const largest = items.reduce((top, item) => (!top || item.count > top.count ? item : top), null);

            $("statDepartments").textContent = items.length;
            $("statItems").textContent = items.reduce((sum, item) => sum + item.count, 0);
            $("statLargest").textContent = largest ? largest.name : "—";
            $("statLargestNote").textContent = plural(largest ? largest.count : 0, "employee");
        }

        function render() {
            const query = state.search.trim().toLowerCase();
            const rows = items.filter((item) =>
                !query || `${item.name} ${item.description}`.toLowerCase().includes(query));

            body.innerHTML = rows.length
                ? rows.map(rowHtml).join("")
                : `<tr><td colspan="4" class="empty-message">No matching departments found.</td></tr>`;

            renderSummary();
        }

        
        $("searchInput").addEventListener("input", (event) => {
            state.search = event.target.value;
            render();
        });

        
        body.addEventListener("click", (event) => {
            const button = event.target.closest("[data-action]");
            if (!button) return;

            button.dataset.action === "edit"
                ? openForm(button.dataset.name)
                : openDelete(button.dataset.name);
        });

        
        function openForm(name = null) {
            state.editingName = name;
            form.reset();
            $("formError").hidden = true;
            $("formTitle").textContent = name ? "Edit Department" : "Add Department";

            if (name) {
                const item = find(name);
                form.elements.name.value = item.name;
                form.elements.description.value = item.description;
            }

            formDialog.showModal();
        }

        if ($("addButton")) { $("addButton").addEventListener("click", () => openForm()); }

        const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

        async function send(url, data) {
            const response = await fetch(url, {
                method: "POST",
                headers: { "X-CSRF-Token": csrf() },
                body: data
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || "Something went wrong.");
            }
        }

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const data = new FormData(form);
            if (state.editingName) data.append("id", find(state.editingName).id);

            try {
                await send(state.editingName ? "update_department.php" : "save_department.php", data);
                formDialog.close();
                window.location.reload();
            } catch (error) {
                $("formError").textContent = error.message;
                $("formError").hidden = false;
            }
        });

        
        function openDelete(name) {
            const item = find(name);
            const inUse = item.count > 0;
            state.targetName = name;

            $("deleteText").textContent = inUse
                ? `${item.name} still has ${plural(item.count, "employee")}. Move them to another department before deleting it.`
                : `${item.name} will be removed from your departments.`;
            $("deleteConfirm").disabled = inUse;

            deleteDialog.showModal();
        }

        $("deleteConfirm").addEventListener("click", async () => {
            const data = new FormData();
            data.append("id", find(state.targetName).id);

            try {
                await send("delete_department.php", data);
                deleteDialog.close();
                window.location.reload();
            } catch (error) {
                $("deleteText").textContent = error.message;
            }
        });

        
        document.addEventListener("click", (event) => {
            const closer = event.target.closest("[data-close]");
            if (closer) closer.closest("dialog").close();
            if (event.target instanceof HTMLDialogElement) event.target.close();
        });

        render();
    })();
    </script>

</body>
</html>
