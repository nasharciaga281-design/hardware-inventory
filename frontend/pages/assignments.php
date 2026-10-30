<?php

$pageTitle       = "Assignments";
$pageDescription = "Hardware issued to employees";
$pageStyles      = ["assignments"];

$employees = [
    ["name" => "Maria Santos",      "department" => "Finance"],
    ["name" => "Kristine Bautista", "department" => "Marketing"],
    ["name" => "Liza Mendoza",      "department" => "Finance"],
    ["name" => "Angela Reyes",      "department" => "Human Resources"],
    ["name" => "Mark Villanueva",   "department" => "Operations"],
    ["name" => "Paolo Garcia",      "department" => "IT"],
];


$catalog = [
    "LAP-0001" => "Dell Latitude 5440",
    "LAP-0002" => "Lenovo ThinkPad E14",
    "LAP-0003" => "HP ProBook 450 G10",
    "LAP-0004" => "Apple MacBook Air M2",
    "DSK-0001" => "Dell OptiPlex 7010",
    "DSK-0002" => "Dell OptiPlex 7010",
    "DSK-0003" => "HP EliteDesk 800 G6",
    "DSK-0004" => "Lenovo ThinkCentre M70q",
    "MON-0001" => "Dell P2422H Monitor",
    "MON-0002" => "LG 27UL500 Monitor",
    "MON-0003" => "Samsung S24R350 Monitor",
    "PRN-0001" => "HP LaserJet Pro M404dn",
    "PRN-0003" => "Epson EcoTank L3250",
    "NET-0001" => "Cisco Catalyst 2960 Switch",
    "NET-0002" => "Ubiquiti UniFi U6 Lite",
    "NET-0003" => "MikroTik hEX RB750Gr3",
    "PER-0001" => "Epson EB-X51 Projector",
    "PER-0002" => "Logitech C920 Webcam",
];

function assignment(string $tag, string $hardware, string $employee, string $department,
                    string $assigned, string $expected, ?string $returned = null): array {
    return compact("tag", "hardware", "employee", "department", "assigned", "expected", "returned");
}

$assignments = [
    assignment("LAP-0001", "Dell Latitude 5440",     "Maria Santos",     "Finance",         "2026-02-15", "2027-02-15"),
    assignment("LAP-0004", "Apple MacBook Air M2",   "Kristine Bautista", "Marketing",      "2026-04-03", "2026-12-31"),
    assignment("DSK-0003", "HP EliteDesk 800 G6",    "Liza Mendoza",     "Finance",         "2026-01-10", "2026-12-10"),
    assignment("MON-0002", "LG 27UL500 Monitor",     "Angela Reyes",     "Human Resources", "2026-05-21", "2026-11-21"),
    assignment("LAP-0002", "Lenovo ThinkPad E14",    "Mark Villanueva",  "Operations",      "2025-08-01", "2026-02-01", "2026-01-28"),
    assignment("PER-0002", "Logitech C920 Webcam",   "Paolo Garcia",     "IT",              "2026-06-12", "2026-09-12", "2026-09-10"),
];


function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, "UTF-8"); }

function options(array $values): string {
    return implode("", array_map(fn($v) => sprintf('<option value="%1$s">%1$s</option>', e($v)), $values));
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

            
            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-top">
                        <span>Active Assignments</span>
                        <span class="stat-icon blue"><i class="bi bi-person-check"></i></span>
                    </div>
                    <h3 id="statActive">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Returned</span>
                        <span class="stat-icon gray"><i class="bi bi-arrow-return-left"></i></span>
                    </div>
                    <h3 id="statReturned">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Overdue</span>
                        <span class="stat-icon red"><i class="bi bi-clipboard-x"></i></span>
                    </div>
                    <h3 id="statOverdue">0</h3>
                </article>
            </div>

            <div class="table-card">

                <div class="table-toolbar" id="toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" data-filter="search" placeholder="Search asset tag, hardware or employee" aria-label="Search assignments">
                    </label>

                    <select class="filter-select" data-filter="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="overdue">Overdue</option>
                        <option value="returned">Returned</option>
                    </select>

                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> New Assignment
                    </button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Asset Tag</th>
                                <th>Hardware</th>
                                <th>Employee</th>
                                <th>Assigned</th>
                                <th>Expected Return</th>
                                <th>Actual Return</th>
                                <th>Status</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assignmentBody"></tbody>
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
        <form id="assignmentForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">New Assignment</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <div class="form-grid">
                <label class="field full">Hardware
                    <select name="tag" id="hardwareSelect" required></select>
                </label>
                <label class="field full">Employee
                    <select name="employee" id="employeeSelect" required>
                        <option value="">Select employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= e($employee["name"]) ?>" data-department="<?= e($employee["department"]) ?>">
                                <?= e($employee["name"]) ?> &nbsp;&nbsp; <?= e($employee["department"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">Assigned Date
                    <input type="date" name="assigned" required>
                </label>
                <label class="field">Expected Return
                    <input type="date" name="expected" required>
                </label>
            </div>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button">Create Assignment</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal" id="viewDialog" aria-labelledby="viewTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="viewTitle">Assignment Details</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <dl class="detail-list" id="detailList"></dl>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Close</button>
            </div>
        </div>
    </dialog>

    
    <dialog class="modal" id="historyDialog" aria-labelledby="historyTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="historyTitle">Assignment History</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <ol class="history-list" id="historyList"></ol>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Close</button>
            </div>
        </div>
    </dialog>

    
    <dialog class="modal" id="returnDialog" aria-labelledby="returnTitle">
        <form id="returnForm" class="modal-body">
            <div class="modal-header">
                <h2 id="returnTitle">Mark as returned</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <p class="dialog-text" id="returnText"></p>
            <label class="field">Return Date
                <input type="date" name="returned" id="returnDate" required>
            </label>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button">Confirm Return</button>
            </div>
        </form>
    </dialog>

    <script>
    (() => {
        
        const items = <?= json_encode($assignments, $jsonFlags) ?>;
        const CATALOG = <?= json_encode($catalog, $jsonFlags) ?>;

        items.forEach((item, index) => { item.id = index + 1; });
        let nextId = items.length + 1;

        const state = { search: "", status: "", page: 1, perPage: 10, targetId: null };

        
        const $ = (id) => document.getElementById(id);
        const body = $("assignmentBody");
        const form = $("assignmentForm");
        const dialogs = {
            form: $("formDialog"), view: $("viewDialog"),
            history: $("historyDialog"), return: $("returnDialog")
        };

        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const today = () => new Date().toLocaleDateString("en-CA"); // YYYY-MM-DD, local time
        const formatDate = (iso) => iso
            ? new Date(`${iso}T00:00:00`).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" })
            : "—";

        const statusOf = (item) =>
            item.returned ? "returned" : (item.expected < today() ? "overdue" : "active");
        const STATUS_LABEL = { active: "Active", overdue: "Overdue", returned: "Returned" };
        const badge = (item) => `<span class="badge ${statusOf(item)}">${STATUS_LABEL[statusOf(item)]}</span>`;

        const byId = (id) => items.find((item) => item.id === Number(id));

        const matches = (item) => {
            const query = state.search.trim().toLowerCase();
            const haystack = [item.tag, item.hardware, item.employee, item.department].join(" ").toLowerCase();

            return (!query || haystack.includes(query))
                && (!state.status || statusOf(item) === state.status);
        };

        
        const actionButton = (action, id, icon, label) =>
            `<button type="button" class="icon-button" data-action="${action}" data-id="${id}" aria-label="${label}"><i class="bi ${icon}"></i></button>`;

        const rowHtml = (item) => `
            <tr>
                <td class="asset-tag">${esc(item.tag)}</td>
                <td class="primary-cell">${esc(item.hardware)}</td>
                <td><div class="two-line"><span class="line-main">${esc(item.employee)}</span><span class="line-sub">${esc(item.department)}</span></div></td>
                <td>${formatDate(item.assigned)}</td>
                <td>${formatDate(item.expected)}</td>
                <td>${formatDate(item.returned)}</td>
                <td>${badge(item)}</td>
                <td>
                    <div class="row-actions">
                        ${actionButton("view", item.id, "bi-eye", `View ${esc(item.tag)}`)}
                        ${actionButton("history", item.id, "bi-clock-history", `History of ${esc(item.tag)}`)}
                        ${item.returned ? "" : actionButton("return", item.id, "bi-arrow-return-left", `Mark ${esc(item.tag)} returned`)}
                    </div>
                </td>
            </tr>`;

        function renderStats() {
            const count = (status) => items.filter((item) => statusOf(item) === status).length;

            $("statActive").textContent = count("active") + count("overdue"); // overdue items are still out
            $("statReturned").textContent = count("returned");
            $("statOverdue").textContent = count("overdue");
        }

        function render() {
            const rows = items.filter(matches);
            const pages = Math.max(1, Math.ceil(rows.length / state.perPage));
            state.page = Math.min(state.page, pages);

            const start = (state.page - 1) * state.perPage;
            const visible = rows.slice(start, start + state.perPage);

            body.innerHTML = visible.length
                ? visible.map(rowHtml).join("")
                : `<tr><td colspan="8" class="empty-message">No matching assignments found.</td></tr>`;

            $("summary").textContent = rows.length
                ? `Showing ${start + 1}–${start + visible.length} of ${rows.length}`
                : "Showing 0 of 0";
            $("pageLabel").textContent = `${state.page} / ${pages}`;
            $("prevPage").disabled = state.page <= 1;
            $("nextPage").disabled = state.page >= pages;

            renderStats();
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
            if (action === "view")    openView(id);
            if (action === "history") openHistory(id);
            if (action === "return")  openReturn(id);
        });

        
        function openForm() {
            const inUse = new Set(items.filter((item) => !item.returned).map((item) => item.tag));
            const free = Object.entries(CATALOG).filter(([tag]) => !inUse.has(tag));

            $("hardwareSelect").innerHTML = free.length
                ? `<option value="">Select available hardware</option>` +
                  free.map(([tag, name]) => `<option value="${esc(tag)}">${esc(tag)} · ${esc(name)}</option>`).join("")
                : `<option value="">No hardware available</option>`;

            form.reset();
            form.elements.assigned.value = today();
            $("formError").hidden = true;
            dialogs.form.showModal();
        }

        $("addButton").addEventListener("click", openForm);

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const data = Object.fromEntries(
                Array.from(new FormData(form), ([key, value]) => [key, value.trim()])
            );

            if (data.expected < data.assigned) {
                $("formError").textContent = "Expected return can't be before the assigned date.";
                $("formError").hidden = false;
                return;
            }

            items.unshift({
                id: nextId++,
                tag: data.tag,
                hardware: CATALOG[data.tag],
                employee: data.employee,
                department: $("employeeSelect").selectedOptions[0].dataset.department,
                assigned: data.assigned,
                expected: data.expected,
                returned: null
            });

            state.page = 1;
            dialogs.form.close();
            render();
        });

        
        function openView(id) {
            const item = byId(id);

            const fields = [
                ["Asset tag", esc(item.tag)],
                ["Hardware", esc(item.hardware)],
                ["Employee", esc(item.employee)],
                ["Department", esc(item.department)],
                ["Assigned", formatDate(item.assigned)],
                ["Expected return", formatDate(item.expected)],
                ["Actual return", formatDate(item.returned)],
                ["Status", badge(item)]
            ];

            $("detailList").innerHTML = fields
                .map(([label, value]) => `<div><dt>${label}</dt><dd>${value}</dd></div>`)
                .join("");

            dialogs.view.showModal();
        }

        
        function openHistory(id) {
            const { tag, hardware } = byId(id);
            const records = items
                .filter((item) => item.tag === tag)
                .sort((a, b) => b.assigned.localeCompare(a.assigned));

            $("historyTitle").textContent = `${tag} · ${hardware}`;
            $("historyList").innerHTML = records.map((item) => `
                <li>
                    <div>
                        <strong>${esc(item.employee)}</strong>
                        <span class="period">${formatDate(item.assigned)} → ${formatDate(item.returned || item.expected)}</span>
                    </div>
                    ${badge(item)}
                </li>`).join("");

            dialogs.history.showModal();
        }

        
        function openReturn(id) {
            const item = byId(id);
            state.targetId = item.id;

            $("returnText").textContent = `${item.hardware} (${item.tag}) is currently with ${item.employee}.`;
            $("returnDate").value = today();
            $("returnDate").min = item.assigned;
            $("returnDate").max = today();

            dialogs.return.showModal();
        }

        $("returnForm").addEventListener("submit", (event) => {
            event.preventDefault();
            byId(state.targetId).returned = $("returnDate").value;
            dialogs.return.close();
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