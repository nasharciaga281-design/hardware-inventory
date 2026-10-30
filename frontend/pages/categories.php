<?php

$pageTitle       = "Categories";
$pageDescription = "Organize hardware by type";
$pageStyles      = ["categories"];

$categories = [
    ["name" => "Laptops",     "description" => "Portable workstations issued to staff", "count" => 5],
    ["name" => "Desktops",    "description" => "Fixed office workstations",             "count" => 4],
    ["name" => "Monitors",    "description" => "External displays",                     "count" => 3],
    ["name" => "Printers",    "description" => "Printers and scanners",                 "count" => 3],
    ["name" => "Networking",  "description" => "Routers, switches and access points",   "count" => 3],
    ["name" => "Peripherals", "description" => "Projectors, webcams and accessories",   "count" => 3],
];

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

            
            <div class="summary-grid category-summary">
                <article class="summary-card">
                    <div class="summary-top">
                        <span>Categories</span>
                        <span class="summary-icon blue"><i class="bi bi-diagram-3"></i></span>
                    </div>
                    <h3 id="statCategories">0</h3>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Categorized Items</span>
                        <span class="summary-icon green"><i class="bi bi-boxes"></i></span>
                    </div>
                    <h3 id="statItems">0</h3>
                </article>

                <article class="summary-card">
                    <div class="summary-top">
                        <span>Largest Category</span>
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
                        <input type="search" id="searchInput" placeholder="Search categories" aria-label="Search categories">
                    </label>

                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Category
                    </button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Hardware Items</th>
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
                <h2 id="formTitle">Add Category</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <label class="field">Category Name
                <input name="name" required placeholder="e.g. Keyboards">
            </label>

            <label class="field">Description
                <textarea name="description" rows="3" placeholder="Describe this hardware category"></textarea>
            </label>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Save Category</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal" id="deleteDialog" aria-labelledby="deleteTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="deleteTitle">Delete category?</h2>
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
        
        const items = <?= json_encode($categories, $jsonFlags) ?>;

        const state = {
            search: "",
            editingName: null, // category being edited, or null when adding
            targetName: null   // category in the delete dialog
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
                        <button type="button" class="icon-button" data-action="edit" data-name="${esc(item.name)}" aria-label="Edit ${esc(item.name)}"><i class="bi bi-pencil"></i></button>
                        <button type="button" class="icon-button danger" data-action="delete" data-name="${esc(item.name)}" aria-label="Delete ${esc(item.name)}"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`;

        function renderSummary() {
            const largest = items.reduce((top, item) => (!top || item.count > top.count ? item : top), null);

            $("statCategories").textContent = items.length;
            $("statItems").textContent = items.reduce((sum, item) => sum + item.count, 0);
            $("statLargest").textContent = largest ? largest.name : "—";
            $("statLargestNote").textContent = plural(largest ? largest.count : 0, "item");
        }

        function render() {
            const query = state.search.trim().toLowerCase();
            const rows = items.filter((item) =>
                !query || `${item.name} ${item.description}`.toLowerCase().includes(query));

            body.innerHTML = rows.length
                ? rows.map(rowHtml).join("")
                : `<tr><td colspan="4" class="empty-message">No matching categories found.</td></tr>`;

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
            $("formTitle").textContent = name ? "Edit Category" : "Add Category";

            if (name) {
                const item = find(name);
                form.elements.name.value = item.name;
                form.elements.description.value = item.description;
            }

            formDialog.showModal();
        }

        $("addButton").addEventListener("click", () => openForm());

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const name = form.elements.name.value.trim();
            const description = form.elements.description.value.trim();

            const duplicate = items.some((item) =>
                item.name.toLowerCase() === name.toLowerCase() && item.name !== state.editingName);

            if (duplicate) {
                $("formError").textContent = "This category already exists.";
                $("formError").hidden = false;
                return;
            }

            if (state.editingName) {
                Object.assign(find(state.editingName), { name, description });
            } else {
                items.push({ name, description, count: 0 });
            }

            formDialog.close();
            render();
        });

        
        function openDelete(name) {
            const item = find(name);
            const inUse = item.count > 0;
            state.targetName = name;

            $("deleteText").textContent = inUse
                ? `${item.name} still has ${plural(item.count, "hardware item")}. Move them to another category before deleting it.`
                : `${item.name} will be removed from your categories.`;
            $("deleteConfirm").disabled = inUse;

            deleteDialog.showModal();
        }

        $("deleteConfirm").addEventListener("click", () => {
            items.splice(items.indexOf(find(state.targetName)), 1);
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