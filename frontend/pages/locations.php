<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
auth_require_user();
$canManageLocations = auth_has_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

$pageTitle       = "Locations";
$pageDescription = "Rooms and departments holding hardware";
$pageStyles      = ["locations"];

$stmt = $pdo->query(
    "SELECT l.location_id AS id, l.location_name AS name,
            COALESCE(l.room, '') AS room,
            COALESCE(l.description, '') AS description,
            COUNT(h.asset_tag) AS `count`
     FROM locations l
     LEFT JOIN hardware h ON h.location_id = l.location_id
     GROUP BY l.location_id, l.location_name, l.room, l.description
     ORDER BY l.location_name"
);
$locations = array_map(static function (array $row): array {
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

            
            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-top">
                        <span>Locations</span>
                        <span class="stat-icon blue"><i class="bi bi-geo-alt"></i></span>
                    </div>
                    <h3 id="statLocations">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Occupied</span>
                        <span class="stat-icon green"><i class="bi bi-boxes"></i></span>
                    </div>
                    <h3 id="statOccupied">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Empty</span>
                        <span class="stat-icon gray"><i class="bi bi-geo-alt"></i></span>
                    </div>
                    <h3 id="statEmpty">0</h3>
                </article>
            </div>

            <div class="table-card">

                <div class="table-toolbar" id="toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" data-filter="search" placeholder="Search name or room" aria-label="Search locations">
                    </label>

                    <select class="filter-select" data-filter="occupancy" aria-label="Filter locations">
                        <option value="">All locations</option>
                        <option value="occupied">Occupied</option>
                        <option value="empty">Empty</option>
                    </select>

                    <?php if ($canManageLocations): ?>
                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Location
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Room</th>
                                <th>Description</th>
                                <th>Hardware Items</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="locationBody"></tbody>
                    </table>
                </div>

            </div>
        </section>
    </main>

    
    <dialog class="modal" id="formDialog" aria-labelledby="formTitle">
        <form id="locationForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">Add Location</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <label class="field">Location Name
                <input name="name" required placeholder="e.g. Computer Laboratory 2">
            </label>

            <label class="field">Room
                <input name="room" placeholder="e.g. C-302">
            </label>

            <label class="field">Description
                <textarea name="description" rows="3" placeholder="Describe this location"></textarea>
            </label>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Save Location</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal" id="viewDialog" aria-labelledby="viewTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="viewTitle">Location Details</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <dl class="detail-list" id="detailList"></dl>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Close</button>
                <?php if ($canManageLocations): ?>
                <button type="button" class="primary-button" id="viewEdit">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <?php endif; ?>
            </div>
        </div>
    </dialog>

    
    <dialog class="modal" id="deleteDialog" aria-labelledby="deleteTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="deleteTitle">Delete location?</h2>
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
        
        const canManage = <?= json_encode($canManageLocations) ?>;
        const items = <?= json_encode($locations, $jsonFlags) ?>;

        const state = {
            search: "",
            occupancy: "",     // "", "occupied" or "empty"
            editingName: null, // location being edited, or null when adding
            targetName: null   // location shown in the view / delete dialogs
        };

        
        const $ = (id) => document.getElementById(id);
        const body = $("locationBody");
        const form = $("locationForm");
        const formDialog = $("formDialog");
        const viewDialog = $("viewDialog");
        const deleteDialog = $("deleteDialog");

        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const find = (name) => items.find((item) => item.name === name);
        const plural = (count, word) => `${count} ${word}${count === 1 ? "" : "s"}`;

        const matches = (item) => {
            const query = state.search.trim().toLowerCase();
            const haystack = `${item.name} ${item.room} ${item.description}`.toLowerCase();

            return (!query || haystack.includes(query))
                && (state.occupancy !== "occupied" || item.count > 0)
                && (state.occupancy !== "empty" || item.count === 0);
        };

        
        const rowHtml = (item) => `
            <tr>
                <td class="primary-cell">${esc(item.name)}</td>
                <td class="room-cell">${esc(item.room || "—")}</td>
                <td>${esc(item.description || "—")}</td>
                <td class="numeric-cell">${item.count}</td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="icon-button" data-action="view" data-name="${esc(item.name)}" aria-label="View ${esc(item.name)}"><i class="bi bi-eye"></i></button>
                        ${canManage ? `<button type="button" class="icon-button" data-action="edit" data-name="${esc(item.name)}" aria-label="Edit ${esc(item.name)}"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="icon-button danger" data-action="delete" data-name="${esc(item.name)}" aria-label="Delete ${esc(item.name)}"><i class="bi bi-trash"></i></button>` : ""}
                    </div>
                </td>
            </tr>`;

        function renderStats() {
            const occupied = items.filter((item) => item.count > 0).length;

            $("statLocations").textContent = items.length;
            $("statOccupied").textContent = occupied;
            $("statEmpty").textContent = items.length - occupied;
        }

        function render() {
            const rows = items.filter(matches);

            body.innerHTML = rows.length
                ? rows.map(rowHtml).join("")
                : `<tr><td colspan="5" class="empty-message">No matching locations found.</td></tr>`;

            renderStats();
        }

        
        $("toolbar").addEventListener("input", (event) => {
            const key = event.target.dataset.filter;
            if (!key) return;
            state[key] = event.target.value;
            render();
        });

        
        body.addEventListener("click", (event) => {
            const button = event.target.closest("[data-action]");
            if (!button) return;

            const { action, name } = button.dataset;
            if (action === "view")   openView(name);
            if (action === "edit")   openForm(name);
            if (action === "delete") openDelete(name);
        });

        
        function openForm(name = null) {
            state.editingName = name;
            form.reset();
            $("formError").hidden = true;
            $("formTitle").textContent = name ? "Edit Location" : "Add Location";

            if (name) {
                const item = find(name);
                form.elements.name.value = item.name;
                form.elements.room.value = item.room;
                form.elements.description.value = item.description;
            }

            viewDialog.close();
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
                await send(state.editingName ? "update_location.php" : "save_location.php", data);
                formDialog.close();
                window.location.reload();
            } catch (error) {
                $("formError").textContent = error.message;
                $("formError").hidden = false;
            }
        });

        
        function openView(name) {
            const item = find(name);
            state.targetName = name;

            const fields = [
                ["Location", item.name],
                ["Room", item.room || "—"],
                ["Description", item.description || "—"],
                ["Hardware items", item.count]
            ];

            $("detailList").innerHTML = fields
                .map(([label, value]) => `<div><dt>${label}</dt><dd>${esc(value)}</dd></div>`)
                .join("");

            viewDialog.showModal();
        }

        if ($("viewEdit")) { $("viewEdit").addEventListener("click", () => openForm(state.targetName)); }

        
        function openDelete(name) {
            const item = find(name);
            const inUse = item.count > 0;
            state.targetName = name;

            $("deleteText").textContent = inUse
                ? `${item.name} still holds ${plural(item.count, "hardware item")}. Move them to another location before deleting it.`
                : `${item.name} will be removed from your locations.`;
            $("deleteConfirm").disabled = inUse;

            deleteDialog.showModal();
        }

        $("deleteConfirm").addEventListener("click", async () => {
            const data = new FormData();
            data.append("id", find(state.targetName).id);

            try {
                await send("delete_location.php", data);
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
