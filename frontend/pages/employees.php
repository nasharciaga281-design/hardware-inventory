<?php
require_once __DIR__ . '/../../backend/middleware/auth.php';
auth_require_user();
$canManageEmployees = auth_has_role([HIMS_ROLE_ADMIN, HIMS_ROLE_INVENTORY_MANAGER]);

$pageTitle       = "Employees";
$pageDescription = "People who use and are responsible for hardware";
$pageStyles      = ["maintenance"];

$toIntOrNull = static fn($value): ?int => $value !== null ? (int) $value : null;

$employees = array_map(static function (array $row) use ($toIntOrNull): array {
    $row['id'] = (int) $row['id'];
    $row['departmentId'] = $toIntOrNull($row['departmentId']);
    $row['userId'] = $toIntOrNull($row['userId']);
    return $row;
}, $pdo->query(
    "SELECT e.employee_id AS id, e.employee_number AS number,
            e.first_name AS firstName, COALESCE(e.middle_name, '') AS middleName,
            e.last_name AS lastName, COALESCE(e.work_email, '') AS email,
            COALESCE(e.phone, '') AS phone, COALESCE(e.position, '') AS position,
            e.employment_status AS status, e.department_id AS departmentId,
            COALESCE(d.department_name, '') AS department, e.user_id AS userId
     FROM employees e
     LEFT JOIN departments d ON d.department_id = e.department_id
     ORDER BY e.last_name, e.first_name"
)->fetchAll(PDO::FETCH_ASSOC));

$departments = array_map(static function (array $row): array {
    $row['id'] = (int) $row['id'];
    return $row;
}, $pdo->query('SELECT department_id AS id, department_name AS name FROM departments ORDER BY department_name')
    ->fetchAll(PDO::FETCH_ASSOC));

$users = array_map(static function (array $row): array {
    $row['id'] = (int) $row['id'];
    return $row;
}, $pdo->query("SELECT user_id AS id, CONCAT(first_name, ' ', last_name) AS name, email FROM users ORDER BY first_name, last_name")
    ->fetchAll(PDO::FETCH_ASSOC));

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
                        <span>Total Employees</span>
                        <span class="stat-icon blue"><i class="bi bi-people"></i></span>
                    </div>
                    <h3 id="statTotal">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Active</span>
                        <span class="stat-icon green"><i class="bi bi-person-check"></i></span>
                    </div>
                    <h3 id="statActive">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>With User Account</span>
                        <span class="stat-icon gray"><i class="bi bi-link-45deg"></i></span>
                    </div>
                    <h3 id="statLinked">0</h3>
                </article>
            </div>

            <div class="table-card">

                <div class="table-toolbar" id="toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" data-filter="search" placeholder="Search name, number, department or position" aria-label="Search employees">
                    </label>

                    <select class="filter-select" data-filter="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Inactive">Inactive</option>
                    </select>

                    <?php if ($canManageEmployees): ?>
                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Employee
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee No.</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeeBody"></tbody>
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
        <form id="employeeForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">Add Employee</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <div class="form-grid">
                <label class="field">Employee Number
                    <input name="employee_number" required placeholder="e.g. EMP-0001">
                </label>
                <label class="field">Position
                    <input name="position" placeholder="e.g. Accountant">
                </label>
                <label class="field">First Name
                    <input name="first_name" required>
                </label>
                <label class="field">Last Name
                    <input name="last_name" required>
                </label>
                <label class="field">Middle Name
                    <input name="middle_name">
                </label>
                <label class="field">Work Email
                    <input type="email" name="work_email">
                </label>
                <label class="field">Phone
                    <input name="phone">
                </label>
                <label class="field">Status
                    <select name="employment_status" required>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </label>
                <label class="field">Department
                    <select name="department_id" id="departmentSelect"></select>
                </label>
                <label class="field">Linked User Account
                    <select name="user_id" id="userSelect"></select>
                </label>
            </div>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Save Employee</button>
            </div>
        </form>
    </dialog>

    <dialog class="modal" id="viewDialog" aria-labelledby="viewTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="viewTitle">Employee Details</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <dl class="detail-list" id="detailList"></dl>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Close</button>
                <?php if ($canManageEmployees): ?>
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
                <h2 id="deleteTitle">Delete employee?</h2>
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
        const canManage = <?= json_encode($canManageEmployees) ?>;
        const items = <?= json_encode($employees, $jsonFlags) ?>;
        const DEPARTMENTS = <?= json_encode($departments, $jsonFlags) ?>;
        const USERS = <?= json_encode($users, $jsonFlags) ?>;

        const state = {
            search: "", status: "", page: 1, perPage: 10,
            editingId: null, // employee being edited, or null when adding
            targetId: null   // employee shown in the view / delete dialogs
        };

        const $ = (id) => document.getElementById(id);
        const body = $("employeeBody");
        const form = $("employeeForm");
        const dialogs = { form: $("formDialog"), view: $("viewDialog"), remove: $("deleteDialog") };

        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const byId = (id) => items.find((item) => item.id === Number(id));
        const fullName = (item) => [item.firstName, item.middleName, item.lastName].filter(Boolean).join(" ");
        const badgeClass = { "Active": "completed", "On Leave": "ongoing" };
        const badge = (item) => `<span class="badge ${badgeClass[item.status] || ""}">${esc(item.status)}</span>`;

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

        const matches = (item) => {
            const query = state.search.trim().toLowerCase();
            const haystack = [fullName(item), item.number, item.department, item.position, item.email]
                .join(" ").toLowerCase();

            return (!query || haystack.includes(query))
                && (!state.status || item.status === state.status);
        };

        const actionButton = (action, id, icon, label) =>
            `<button type="button" class="icon-button${action === "delete" ? " danger" : ""}" data-action="${action}" data-id="${id}" aria-label="${label}"><i class="bi ${icon}"></i></button>`;

        const rowHtml = (item) => `
            <tr>
                <td class="asset-tag">${esc(item.number)}</td>
                <td class="primary-cell">${esc(fullName(item))}</td>
                <td>${esc(item.department || "—")}</td>
                <td>${esc(item.position || "—")}</td>
                <td>${badge(item)}</td>
                <td>
                    <div class="row-actions">
                        ${actionButton("view", item.id, "bi-eye", `View ${esc(fullName(item))}`)}
                        ${canManage ? actionButton("edit", item.id, "bi-pencil", `Edit ${esc(fullName(item))}`) : ""}
                        ${canManage ? actionButton("delete", item.id, "bi-trash", `Delete ${esc(fullName(item))}`) : ""}
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
                : `<tr><td colspan="6" class="empty-message">No employees found.</td></tr>`;

            $("summary").textContent = rows.length
                ? `Showing ${start + 1}–${start + visible.length} of ${rows.length}`
                : "Showing 0 of 0";
            $("pageLabel").textContent = `${state.page} / ${pages}`;
            $("prevPage").disabled = state.page <= 1;
            $("nextPage").disabled = state.page >= pages;

            $("statTotal").textContent = items.length;
            $("statActive").textContent = items.filter((item) => item.status === "Active").length;
            $("statLinked").textContent = items.filter((item) => item.userId !== null).length;
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

            const { action, id } = button.dataset;
            if (action === "view")   openView(id);
            if (action === "edit")   openForm(id);
            if (action === "delete") openDelete(id);
        });

        function openForm(id = null) {
            state.editingId = id ? Number(id) : null;
            form.reset();
            $("formError").hidden = true;
            $("formTitle").textContent = id ? "Edit Employee" : "Add Employee";
            $("formSubmit").textContent = id ? "Save Changes" : "Save Employee";

            $("departmentSelect").innerHTML = `<option value="">No department</option>` +
                DEPARTMENTS.map((d) => `<option value="${d.id}">${esc(d.name)}</option>`).join("");

            // A user account can belong to only one employee, so hide accounts already taken.
            const taken = new Set(items
                .filter((item) => item.userId !== null && item.id !== state.editingId)
                .map((item) => item.userId));
            $("userSelect").innerHTML = `<option value="">No linked account</option>` +
                USERS.filter((u) => !taken.has(u.id))
                    .map((u) => `<option value="${u.id}">${esc(u.name)} · ${esc(u.email)}</option>`).join("");

            if (id) {
                const item = byId(id);
                form.elements.employee_number.value = item.number;
                form.elements.first_name.value = item.firstName;
                form.elements.middle_name.value = item.middleName;
                form.elements.last_name.value = item.lastName;
                form.elements.work_email.value = item.email;
                form.elements.phone.value = item.phone;
                form.elements.position.value = item.position;
                form.elements.employment_status.value = item.status;
                form.elements.department_id.value = item.departmentId ?? "";
                form.elements.user_id.value = item.userId ?? "";
            }

            dialogs.view.close();
            dialogs.form.showModal();
        }

        if ($("addButton")) { $("addButton").addEventListener("click", () => openForm()); }

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const data = new FormData(form);
            if (state.editingId) data.append("id", state.editingId);

            try {
                await send(state.editingId ? "update_employee.php" : "save_employee.php", data);
                dialogs.form.close();
                window.location.reload();
            } catch (error) {
                $("formError").textContent = error.message;
                $("formError").hidden = false;
            }
        });

        function openView(id) {
            const item = byId(id);
            state.targetId = item.id;
            const account = USERS.find((u) => u.id === item.userId);

            const fields = [
                ["Employee number", esc(item.number)],
                ["Name", esc(fullName(item))],
                ["Department", esc(item.department || "—")],
                ["Position", esc(item.position || "—")],
                ["Work email", esc(item.email || "—")],
                ["Phone", esc(item.phone || "—")],
                ["Status", badge(item)],
                ["User account", esc(account ? `${account.name} (${account.email})` : "—")]
            ];

            $("detailList").innerHTML = fields
                .map(([label, value]) => `<div><dt>${label}</dt><dd>${value}</dd></div>`)
                .join("");

            dialogs.view.showModal();
        }

        if ($("viewEdit")) { $("viewEdit").addEventListener("click", () => openForm(state.targetId)); }

        function openDelete(id) {
            const item = byId(id);
            state.targetId = item.id;
            $("deleteText").textContent = `${fullName(item)} will be removed from your employees.`;
            dialogs.remove.showModal();
        }

        $("deleteConfirm").addEventListener("click", async () => {
            const data = new FormData();
            data.append("id", state.targetId);

            try {
                await send("delete_employee.php", data);
                dialogs.remove.close();
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
