<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
auth_require_user();
$canManageHardware = auth_has_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);
$pageTitle       = "Hardware Inventory";
$pageDescription = "Browse and manage all hardware assets";
$pageStyles      = ["hardware"];

$categories = $pdo->query("SELECT category_name FROM categories ORDER BY category_name")->fetchAll(PDO::FETCH_COLUMN);
$locations  = $pdo->query("SELECT location_name FROM locations ORDER BY location_name")->fetchAll(PDO::FETCH_COLUMN);
$conditions = ["New", "Good", "Fair", "Poor"];


$statuses = [
    "Available"         => "available",
    "Assigned"          => "assigned",
    "Under Maintenance" => "maintenance",
];

function hw(string $tag, string $name, string $category, string $brand, string $model,
            string $location, string $condition, string $status, string $serial = "",
            string $purchaseDate = "", string $purchaseCost = "", string $description = ""): array {
    return compact("tag", "name", "category", "brand", "model", "location", "serial",
                   "purchaseDate", "purchaseCost", "description", "condition", "status");
}

$sql = "SELECT
            h.asset_tag AS tag,
            h.hardware_name AS name,
            c.category_name AS category,
            h.brand,
            h.model,
            l.location_name AS location,
            h.condition_status AS `condition`,
            h.status,
            h.serial_number AS serial,
            h.purchase_date AS purchaseDate,
            h.purchase_cost AS purchaseCost,
            h.description
        FROM hardware h
        INNER JOIN categories c
            ON h.category_id = c.category_id
        INNER JOIN locations l
            ON h.location_id = l.location_id";

$stmt = $pdo->query($sql);

$hardware = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, "UTF-8"); }

function options(array $values, string $selected = ""): string {
    $html = "";
    foreach ($values as $value) {
        $html .= sprintf('<option value="%1$s"%2$s>%1$s</option>', e($value), $value === $selected ? " selected" : "");
    }
    return $html;
}

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
            <div class="inventory-card">

                
                <div class="inventory-toolbar" id="toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" data-filter="search" placeholder="Search tag, name, brand or model" aria-label="Search hardware">
                    </label>

                    <select class="filter-select" data-filter="category" aria-label="Filter by category">
                        <option value="">All categories</option>
                        <?= options($categories) ?>
                    </select>

                    <select class="filter-select" data-filter="location" aria-label="Filter by location">
                        <option value="">All locations</option>
                        <?= options($locations) ?>
                    </select>

                    <select class="filter-select" data-filter="condition" aria-label="Filter by condition">
                        <option value="">All conditions</option>
                        <?= options($conditions) ?>
                    </select>

                    <select class="filter-select" data-filter="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <?= options(array_keys($statuses)) ?>
                    </select>

                    <?php if ($canManageHardware): ?>
                    <button type="button" class="primary-button add-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Hardware
                    </button>
                    <?php endif; ?>
                </div>

                
                <div class="table-container">
                    <table class="hardware-table">
                        <thead>
                            <tr>
                                <th>Asset Tag</th>
                                <th>Hardware Name</th>
                                <th>Category</th>
                                <th>Brand / Model</th>
                                <th>Location</th>
                                <th>Condition</th>
                                <th>Status</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="hardwareBody"></tbody>
                    </table>
                </div>

                
                <div class="table-footer">
                    <p id="summary">Showing 0 of 0</p>

                    <div class="pagination">
                        <select class="page-size" id="pageSize" aria-label="Rows per page">
                            <option value="10">10 / page</option>
                            <option value="20">20 / page</option>
                            <option value="50">50 / page</option>
                        </select>
                        <button type="button" class="page-button" id="prevPage" aria-label="Previous page">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="page-label" id="pageLabel">1 / 1</span>
                        <button type="button" class="page-button" id="nextPage" aria-label="Next page">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>

            </div>
        </section>
    </main>

    
    <dialog class="modal" id="formDialog" aria-labelledby="formTitle">
        <form id="hardwareForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">Add Hardware</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <div class="modal-form-grid">
                <label>Asset Tag
                    <input name="tag" required placeholder="e.g. LAP-0006">
                </label>
                <label>Hardware Name
                    <input name="name" required placeholder="e.g. Dell Latitude 5440">
                </label>
                <label>Category
                    <select name="category" required>
                        <option value="">Select category</option>
                        <?= options($categories) ?>
                    </select>
                </label>
                <label>Location
                    <select name="location" required>
                        <option value="">Select location</option>
                        <?= options($locations) ?>
                    </select>
                </label>
                <label>Brand
                    <input name="brand" placeholder="e.g. Dell">
                </label>
                <label>Model
                    <input name="model" placeholder="e.g. Latitude 5440">
                </label>
                <label>Serial Number
                    <input name="serial" placeholder="Enter serial number">
                </label>
                <label>Purchase Date
                    <input type="date" name="purchaseDate">
                </label>
                <label>Purchase Cost
                    <input type="number" name="purchaseCost" min="0" step="0.01" placeholder="0.00">
                </label>
                <label>Condition
                    <select name="condition" required><?= options($conditions, "Good") ?></select>
                </label>
                <label class="full-field">Description
                    <textarea name="description" rows="3" placeholder="Additional hardware details"></textarea>
                </label>
                <input type="hidden" name="status" value="Available">
            </div>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Add to List</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal modal-small" id="viewDialog" aria-labelledby="viewTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="viewTitle">Hardware Details</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <dl class="detail-list" id="detailList"></dl>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Close</button>
                <?php if ($canManageHardware): ?>
                <button type="button" class="primary-button" id="viewEdit">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <?php endif; ?>
            </div>
        </div>
    </dialog>

    
    <dialog class="modal modal-small" id="deleteDialog" aria-labelledby="deleteTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="deleteTitle">Delete hardware?</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <p class="delete-text" id="deleteText"></p>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="button" class="danger-button" id="deleteConfirm">Delete</button>
            </div>
        </div>
    </dialog>

    <script>
    (() => {
        
        const canManage = <?= json_encode($canManageHardware) ?>;
        const items = <?= json_encode($hardware, $jsonFlags) ?>;
        const STATUS_CLASS = <?= json_encode($statuses, $jsonFlags) ?>;

        const state = {
            search: "", category: "", location: "", condition: "", status: "",
            page: 1, perPage: 10,
            editingTag: null,  // tag being edited, or null when adding
            targetTag: null    // tag shown in the view / delete dialogs
        };

        
        const $ = (id) => document.getElementById(id);
        const body = $("hardwareBody");
        const form = $("hardwareForm");
        const formDialog = $("formDialog");
        const viewDialog = $("viewDialog");
        const deleteDialog = $("deleteDialog");

        
        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const badge = (label, modifier) => `<span class="status-badge ${modifier}">${esc(label)}</span>`;
        const conditionBadge = (item) => badge(item.condition, item.condition.toLowerCase());
        const statusBadge = (item) => badge(item.status, STATUS_CLASS[item.status]);
        const brandModel = (item) => [item.brand, item.model].filter(Boolean).join(" · ") || "—";
        const find = (tag) => items.find((item) => item.tag === tag);

        const matches = (item) => {
            const query = state.search.trim().toLowerCase();
            const haystack = [item.tag, item.name, item.brand, item.model].join(" ").toLowerCase();

            return (!query || haystack.includes(query))
                && (!state.category  || item.category  === state.category)
                && (!state.location  || item.location  === state.location)
                && (!state.condition || item.condition === state.condition)
                && (!state.status    || item.status    === state.status);
        };

        
        const rowHtml = (item) => `
            <tr>
                <td class="asset-tag">${esc(item.tag)}</td>
                <td class="hardware-name">${esc(item.name)}</td>
                <td>${esc(item.category)}</td>
                <td>${esc(brandModel(item))}</td>
                <td>${esc(item.location)}</td>
                <td>${conditionBadge(item)}</td>
                <td>${statusBadge(item)}</td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="icon-button" data-action="view" data-tag="${esc(item.tag)}" aria-label="View ${esc(item.name)}"><i class="bi bi-eye"></i></button>
                        ${canManage ? `<button type="button" class="icon-button" data-action="edit" data-tag="${esc(item.tag)}" aria-label="Edit ${esc(item.name)}"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="icon-button danger" data-action="delete" data-tag="${esc(item.tag)}" aria-label="Delete ${esc(item.name)}"><i class="bi bi-trash"></i></button>` : ""}
                    </div>
                </td>
            </tr>`;

        function render() {
            const rows = items.filter(matches);
            const pages = Math.max(1, Math.ceil(rows.length / state.perPage));
            state.page = Math.min(state.page, pages);

            const start = (state.page - 1) * state.perPage;
            const visible = rows.slice(start, start + state.perPage);

            body.innerHTML = visible.length
                ? visible.map(rowHtml).join("")
                : `<tr><td colspan="8" class="empty-message">No matching hardware found.</td></tr>`;

            $("summary").textContent = rows.length
                ? `Showing ${start + 1}–${start + visible.length} of ${rows.length}`
                : "Showing 0 of 0";
            $("pageLabel").textContent = `${state.page} / ${pages}`;
            $("prevPage").disabled = state.page <= 1;
            $("nextPage").disabled = state.page >= pages;
        }

        
        $("toolbar").addEventListener("input", (event) => {
            const key = event.target.dataset.filter;
            if (!key) return;
            state[key] = event.target.value;
            state.page = 1;
            render();
        });

        $("pageSize").addEventListener("change", (event) => {
            state.perPage = Number(event.target.value);
            state.page = 1;
            render();
        });

        $("prevPage").addEventListener("click", () => { state.page--; render(); });
        $("nextPage").addEventListener("click", () => { state.page++; render(); });

        
        body.addEventListener("click", (event) => {
            const button = event.target.closest("[data-action]");
            if (!button) return;

            const { action, tag } = button.dataset;
            if (action === "view")   openView(tag);
            if (action === "edit")   openForm(tag);
            if (action === "delete") openDelete(tag);
        });

        
        function openForm(tag = null) {
            state.editingTag = tag;
            form.reset();
            $("formError").hidden = true;
            form.elements.tag.readOnly = Boolean(tag);
            $("formTitle").textContent = tag ? "Edit Hardware" : "Add Hardware";
            $("formSubmit").textContent = tag ? "Save Changes" : "Add to List";

            if (tag) {
                const item = find(tag);
                Object.entries(item).forEach(([key, value]) => { form.elements[key].value = value; });
            }

            viewDialog.close();
            formDialog.showModal();
        }

        if ($("addButton")) { $("addButton").addEventListener("click", () => openForm()); }

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const endpoint = state.editingTag ? "update_hardware.php" : "save_hardware.php";

            try {
                const response = await fetch(endpoint, {
                    method: "POST",
                    headers: { "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Unable to save hardware.");
                }

                // Close the form and reload database records
                formDialog.close();
                window.location.reload();

            } catch (error) {
                $("formError").textContent = error.message;
                $("formError").hidden = false;
            }
        });

        
        function openView(tag) {
            const item = find(tag);
            state.targetTag = tag;

            const fields = [
                ["Asset tag", esc(item.tag)],
                ["Hardware name", esc(item.name)],
                ["Category", esc(item.category)],
                ["Brand / Model", esc(brandModel(item))],
                ["Location", esc(item.location)],
                ["Serial number", esc(item.serial || "—")],
                ["Purchase date", esc(item.purchaseDate || "—")],
                ["Purchase cost", esc(item.purchaseCost ? `₱${Number(item.purchaseCost).toLocaleString("en-PH", { minimumFractionDigits: 2 })}` : "—")],
                ["Condition", conditionBadge(item)],
                ["Status", statusBadge(item)],
                ["Description", esc(item.description || "—")]
            ];

            $("detailList").innerHTML = fields
                .map(([label, value]) => `<div><dt>${label}</dt><dd>${value}</dd></div>`)
                .join("");

            viewDialog.showModal();
        }

        if ($("viewEdit")) { $("viewEdit").addEventListener("click", () => openForm(state.targetTag)); }

        
        function openDelete(tag) {
            const item = find(tag);
            state.targetTag = tag;
            $("deleteText").textContent = `${item.name} (${item.tag}) will be removed from the list.`;
            deleteDialog.showModal();
        }

        $("deleteConfirm").addEventListener("click", async () => {
            try {
                const response = await fetch("delete_hardware.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                        "X-CSRF-Token": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: new URLSearchParams({ tag: state.targetTag })
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Unable to delete hardware.");
                }

                deleteDialog.close();
                window.location.reload();
            } catch (error) {
                alert(error.message);
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
