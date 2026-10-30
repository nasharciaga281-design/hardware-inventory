<?php

$pageTitle       = "Hardware Inventory";
$pageDescription = "Browse and manage all hardware assets";
$pageStyles      = ["hardware"];

$categories = ["Laptops", "Desktops", "Monitors", "Printers", "Networking", "Peripherals"];
$locations  = ["Computer Laboratory 1", "Conference Room", "Finance Department", "IT Office", "Server Room"];
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

$hardware = [
    hw("LAP-0001", "Dell Latitude 5440",        "Laptops",     "Dell",     "Latitude 5440",       "Finance Department",    "Good", "Assigned"),
    hw("LAP-0002", "Lenovo ThinkPad E14",       "Laptops",     "Lenovo",   "ThinkPad E14 Gen 5",  "IT Office",             "Good", "Available"),
    hw("LAP-0003", "HP ProBook 450 G10",        "Laptops",     "HP",       "ProBook 450 G10",     "IT Office",             "New",  "Available"),
    hw("LAP-0004", "Apple MacBook Air M2",      "Laptops",     "Apple",    "MacBook Air M2",      "Conference Room",       "Good", "Assigned"),
    hw("LAP-0005", "Acer TravelMate P2",        "Laptops",     "Acer",     "TravelMate P214",     "IT Office",             "Fair", "Under Maintenance"),
    hw("DSK-0001", "Dell OptiPlex 7010",        "Desktops",    "Dell",     "OptiPlex 7010",       "Computer Laboratory 1", "Good", "Available"),
    hw("DSK-0002", "Dell OptiPlex 7010",        "Desktops",    "Dell",     "OptiPlex 7010",       "Computer Laboratory 1", "Good", "Available"),
    hw("DSK-0003", "HP EliteDesk 800 G6",       "Desktops",    "HP",       "EliteDesk 800 G6",    "Finance Department",    "Fair", "Assigned"),
    hw("DSK-0004", "Lenovo ThinkCentre M70q",   "Desktops",    "Lenovo",   "ThinkCentre M70q",    "Computer Laboratory 1", "New",  "Available"),
    hw("MON-0001", "Dell P2422H Monitor",       "Monitors",    "Dell",     "P2422H",              "Computer Laboratory 1", "Good", "Available"),
    hw("MON-0002", "LG 24MK430H Monitor",       "Monitors",    "LG",       "24MK430H",            "IT Office",             "Good", "Available"),
    hw("MON-0003", "Samsung S24R350 Monitor",   "Monitors",    "Samsung",  "S24R350",             "Finance Department",    "Fair", "Available"),
    hw("PRN-0001", "HP LaserJet Pro M404dn",    "Printers",    "HP",       "LaserJet Pro M404dn", "Finance Department",    "Good", "Available"),
    hw("PRN-0002", "Brother HL-L2350DW",        "Printers",    "Brother",  "HL-L2350DW",          "IT Office",             "Poor", "Under Maintenance"),
    hw("PRN-0003", "Epson EcoTank L3250",       "Printers",    "Epson",    "EcoTank L3250",       "Computer Laboratory 1", "New",  "Available"),
    hw("NET-0001", "Cisco Catalyst 2960 Switch","Networking",  "Cisco",    "Catalyst 2960",       "Server Room",           "Good", "Available"),
    hw("NET-0002", "Ubiquiti UniFi U6 Lite",    "Networking",  "Ubiquiti", "UniFi U6 Lite",       "Conference Room",       "Good", "Available"),
    hw("NET-0003", "MikroTik hEX RB750Gr3",     "Networking",  "MikroTik", "hEX RB750Gr3",        "Server Room",           "New",  "Available"),
    hw("PER-0001", "Epson EB-X51 Projector",    "Peripherals", "Epson",    "EB-X51",              "Conference Room",       "Fair", "Available"),
    hw("PER-0002", "Logitech C920 Webcam",      "Peripherals", "Logitech", "C920",                "Conference Room",       "New",  "Available"),
    hw("PER-0003", "Logitech MX Keys Keyboard", "Peripherals", "Logitech", "MX Keys",             "Finance Department",    "Good", "Assigned"),
];


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

                    <button type="button" class="primary-button add-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Hardware
                    </button>
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
                <button type="button" class="primary-button" id="viewEdit">
                    <i class="bi bi-pencil"></i> Edit
                </button>
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
                        <button type="button" class="icon-button" data-action="edit" data-tag="${esc(item.tag)}" aria-label="Edit ${esc(item.name)}"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="icon-button danger" data-action="delete" data-tag="${esc(item.tag)}" aria-label="Delete ${esc(item.name)}"><i class="bi bi-trash"></i></button>
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
            $("formTitle").textContent = tag ? "Edit Hardware" : "Add Hardware";
            $("formSubmit").textContent = tag ? "Save Changes" : "Add to List";

            if (tag) {
                const item = find(tag);
                Object.entries(item).forEach(([key, value]) => { form.elements[key].value = value; });
            }

            viewDialog.close();
            formDialog.showModal();
        }

        $("addButton").addEventListener("click", () => openForm());

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const data = Object.fromEntries(
                Array.from(new FormData(form), ([key, value]) => [key, value.trim()])
            );
            if (state.editingTag) data.status = find(state.editingTag).status;

            const duplicate = items.some((item) =>
                item.tag.toLowerCase() === data.tag.toLowerCase() && item.tag !== state.editingTag);

            if (duplicate) {
                $("formError").textContent = "That asset tag already exists.";
                $("formError").hidden = false;
                return;
            }

            if (state.editingTag) {
                items[items.indexOf(find(state.editingTag))] = data;
            } else {
                items.unshift(data);
                state.page = 1;
            }

            formDialog.close();
            render();
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

        $("viewEdit").addEventListener("click", () => openForm(state.targetTag));

        
        function openDelete(tag) {
            const item = find(tag);
            state.targetTag = tag;
            $("deleteText").textContent = `${item.name} (${item.tag}) will be removed from the list.`;
            deleteDialog.showModal();
        }

        $("deleteConfirm").addEventListener("click", () => {
            items.splice(items.indexOf(find(state.targetTag)), 1);
            deleteDialog.close();
            render();
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