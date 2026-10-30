<?php

$pageTitle       = "User Management";
$pageDescription = "System accounts and roles";
$pageStyles      = ["users"];

$currentUsername = "admin";

$users = [
    ["name" => "Juan Dela Cruz",  "username" => "admin",       "email" => "", "role" => "Admin", "status" => "Enabled"],
    ["name" => "Paolo Garcia",    "username" => "pgarcia",     "email" => "", "role" => "Admin", "status" => "Enabled"],
    ["name" => "Angela Reyes",    "username" => "staff",       "email" => "", "role" => "Staff", "status" => "Enabled"],
    ["name" => "Mark Villanueva", "username" => "mvillanueva", "email" => "", "role" => "Staff", "status" => "Disabled"],
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

            
            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total Users</span>
                        <span class="stat-icon blue"><i class="bi bi-people"></i></span>
                    </div>
                    <h3 id="statTotal">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Administrators</span>
                        <span class="stat-icon blue"><i class="bi bi-shield-check"></i></span>
                    </div>
                    <h3 id="statAdmins">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Active Accounts</span>
                        <span class="stat-icon green"><i class="bi bi-person-check"></i></span>
                    </div>
                    <h3 id="statActive">0</h3>
                </article>
            </div>

            <div class="table-card">

                <div class="table-toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" id="searchInput" placeholder="Search name or username" aria-label="Search users">
                    </label>

                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add User
                    </button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userBody"></tbody>
                    </table>
                </div>

            </div>
        </section>
    </main>

    
    <dialog class="modal" id="formDialog" aria-labelledby="formTitle">
        <form id="userForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">Add User</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <div class="form-grid">
                <label class="field full">Full Name
                    <input name="name" maxlength="100" required placeholder="e.g. Maria Santos">
                </label>
                <label class="field">Username
                    <input name="username" maxlength="50" required
                           pattern="[A-Za-z0-9._\-]+" title="Letters, numbers, dots, dashes and underscores only"
                           placeholder="e.g. msantos">
                </label>
                <label class="field">Email (optional)
                    <input name="email" type="email" maxlength="150" placeholder="name@company.com">
                </label>
                <label class="field">Role
                    <select name="role">
                        <option>Staff</option>
                        <option>Admin</option>
                    </select>
                </label>
                <label class="field">Status
                    <select name="status">
                        <option>Enabled</option>
                        <option>Disabled</option>
                    </select>
                </label>
            </div>

            <p class="field-hint" id="selfHint" hidden>You can't change your own role or status.</p>
            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Save User</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal modal-small" id="toggleDialog" aria-labelledby="toggleTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="toggleTitle">Disable account?</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <p class="dialog-text" id="toggleText"></p>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="button" id="toggleConfirm">Disable</button>
            </div>
        </div>
    </dialog>

    <script>
    (() => {
        
        const items = <?= json_encode($users, $jsonFlags) ?>;
        const CURRENT = <?= json_encode($currentUsername, $jsonFlags) ?>;

        items.forEach((item, index) => { item.id = index + 1; });
        let nextId = items.length + 1;

        const state = {
            search: "",
            editingId: null, // user being edited, or null when adding
            targetId: null   // user in the enable / disable dialog
        };

        
        const $ = (id) => document.getElementById(id);
        const body = $("userBody");
        const form = $("userForm");
        const formDialog = $("formDialog");
        const toggleDialog = $("toggleDialog");

        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const byId = (id) => items.find((item) => item.id === Number(id));
        const isYou = (item) => item.username === CURRENT;
        const badge = (label) => `<span class="badge ${label.toLowerCase()}">${esc(label)}</span>`;

        const matches = (item) => {
            const query = state.search.trim().toLowerCase();
            return !query || `${item.name} ${item.username} ${item.email}`.toLowerCase().includes(query);
        };

        
        const rowHtml = (item) => `
            <tr>
                <td class="primary-cell">${esc(item.name)}${isYou(item) ? '<span class="you-tag">(you)</span>' : ""}</td>
                <td><span class="username">@${esc(item.username)}</span></td>
                <td>${badge(item.role)}</td>
                <td>${badge(item.status)}</td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="icon-button" data-action="edit" data-id="${item.id}" aria-label="Edit ${esc(item.name)}"><i class="bi bi-pencil"></i></button>
                        ${isYou(item) ? "" : `<button type="button" class="icon-button ${item.status === "Enabled" ? "danger" : ""}" data-action="toggle" data-id="${item.id}" aria-label="${item.status === "Enabled" ? "Disable" : "Enable"} ${esc(item.name)}"><i class="bi bi-power"></i></button>`}
                    </div>
                </td>
            </tr>`;

        function render() {
            const rows = items.filter(matches);

            body.innerHTML = rows.length
                ? rows.map(rowHtml).join("")
                : `<tr><td colspan="5" class="empty-message">No matching users found.</td></tr>`;

            $("statTotal").textContent = items.length;
            $("statAdmins").textContent = items.filter((item) => item.role === "Admin").length;
            $("statActive").textContent = items.filter((item) => item.status === "Enabled").length;
        }

        
        $("searchInput").addEventListener("input", (event) => {
            state.search = event.target.value;
            render();
        });

        
        body.addEventListener("click", (event) => {
            const button = event.target.closest("[data-action]");
            if (!button) return;

            button.dataset.action === "edit"
                ? openForm(button.dataset.id)
                : openToggle(button.dataset.id);
        });

        
        function openForm(id = null) {
            state.editingId = id ? Number(id) : null;
            form.reset();
            $("formError").hidden = true;
            $("formTitle").textContent = id ? "Edit User" : "Add User";
            $("formSubmit").textContent = id ? "Save Changes" : "Add User";

            const item = id ? byId(id) : null;
            const locked = Boolean(item && isYou(item)); // you can't demote or disable yourself

            if (item) {
                ["name", "username", "email", "role", "status"].forEach((key) => {
                    form.elements[key].value = item[key];
                });
            }

            form.elements.role.disabled = locked;
            form.elements.status.disabled = locked;
            $("selfHint").hidden = !locked;

            formDialog.showModal();
        }

        $("addButton").addEventListener("click", () => openForm());

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const data = {
                name: form.elements.name.value.trim(),
                username: form.elements.username.value.trim(),
                email: form.elements.email.value.trim(),
                role: form.elements.role.value,
                status: form.elements.status.value
            };

            const duplicate = items.some((item) =>
                item.username.toLowerCase() === data.username.toLowerCase() && item.id !== state.editingId);

            if (duplicate) {
                $("formError").textContent = "That username is already taken.";
                $("formError").hidden = false;
                return;
            }

            if (state.editingId) {
                Object.assign(byId(state.editingId), data);
            } else {
                items.push({ id: nextId++, ...data });
            }

            formDialog.close();
            render();
        });

        
        function openToggle(id) {
            const item = byId(id);
            const disabling = item.status === "Enabled";
            state.targetId = item.id;

            $("toggleTitle").textContent = disabling ? "Disable account?" : "Enable account?";
            $("toggleText").textContent = disabling
                ? `${item.name} (@${item.username}) will no longer be able to sign in.`
                : `${item.name} (@${item.username}) will be able to sign in again.`;

            const confirm = $("toggleConfirm");
            confirm.textContent = disabling ? "Disable" : "Enable";
            confirm.className = disabling ? "danger-button" : "primary-button";

            toggleDialog.showModal();
        }

        $("toggleConfirm").addEventListener("click", () => {
            const item = byId(state.targetId);
            item.status = item.status === "Enabled" ? "Disabled" : "Enabled";
            toggleDialog.close();
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