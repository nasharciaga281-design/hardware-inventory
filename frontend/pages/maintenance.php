<?php

$pageTitle       = "Maintenance";
$pageDescription = "Repairs and service records";
$pageStyles      = ["maintenance"];


$catalog = [
    "LAP-0001" => "Dell Latitude 5440",
    "LAP-0002" => "Lenovo ThinkPad E14",
    "LAP-0003" => "HP ProBook 450 G10",
    "LAP-0004" => "Apple MacBook Air M2",
    "LAP-0005" => "Acer TravelMate P2",
    "DSK-0001" => "Dell OptiPlex 7010",
    "DSK-0002" => "Dell OptiPlex 7010",
    "DSK-0003" => "HP EliteDesk 800 G6",
    "DSK-0004" => "Lenovo ThinkCentre M70q",
    "MON-0001" => "Dell P2422H Monitor",
    "MON-0002" => "LG 27UL500 Monitor",
    "MON-0003" => "Samsung S24R350",
    "PRN-0001" => "HP LaserJet Pro M404dn",
    "PRN-0002" => "Brother HL-L2350DW",
    "PRN-0003" => "Epson EcoTank L3250",
    "NET-0001" => "Cisco Catalyst 2960 Switch",
    "NET-0002" => "Ubiquiti UniFi U6 Lite",
    "NET-0003" => "MikroTik hEX RB750Gr3",
    "PER-0001" => "Epson EB-X51 Projector",
    "PER-0002" => "Logitech C920 Webcam",
    "PER-0003" => "Logitech MX Keys Keyboard",
];

function record(string $tag, string $issue, string $date, float $cost, string $status, string $action = ""): array {
    return compact("tag", "issue", "date", "cost", "status", "action");
}

$records = [
    record("LAP-0005", "Battery does not hold charge", "2026-10-20", 3200, "Ongoing"),
    record("PRN-0002", "Paper jam and roller wear",    "2026-10-24", 1450, "Ongoing"),
    record("MON-0003", "Flickering display",           "2026-08-14", 2100, "Completed"),
    record("DSK-0001", "Slow boot, failing HDD",       "2026-07-02", 2850, "Completed"),
    record("NET-0001", "Port 12 not linking",          "2026-09-18", 0,    "Completed"),
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
                        <span>Total Records</span>
                        <span class="stat-icon blue"><i class="bi bi-clipboard-data"></i></span>
                    </div>
                    <h3 id="statTotal">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Ongoing Repairs</span>
                        <span class="stat-icon amber"><i class="bi bi-wrench"></i></span>
                    </div>
                    <h3 id="statOngoing">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Completed Repairs</span>
                        <span class="stat-icon green"><i class="bi bi-check-circle"></i></span>
                    </div>
                    <h3 id="statCompleted">0</h3>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total Repair Costs</span>
                        <span class="stat-icon gray"><i class="bi bi-cash-coin"></i></span>
                    </div>
                    <h3 id="statCost">₱0.00</h3>
                </article>
            </div>

            <div class="table-card">

                <div class="table-toolbar" id="toolbar">
                    <label class="search-field">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" data-filter="search" placeholder="Search asset tag, hardware or issue" aria-label="Search maintenance records">
                    </label>

                    <select class="filter-select" data-filter="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>

                    <button type="button" class="primary-button" id="addButton">
                        <i class="bi bi-plus-lg"></i> Add Record
                    </button>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Asset Tag</th>
                                <th>Hardware</th>
                                <th>Issue Description</th>
                                <th>Date</th>
                                <th>Repair Cost</th>
                                <th>Status</th>
                                <th class="align-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recordBody"></tbody>
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
        <form id="recordForm" class="modal-body">
            <div class="modal-header">
                <h2 id="formTitle">Add Maintenance Record</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>

            <div class="form-grid">
                <label class="field full">Hardware
                    <select name="tag" id="hardwareSelect" required></select>
                </label>
                <label class="field">Maintenance Date
                    <input type="date" name="date" required>
                </label>
                <label class="field">Repair Cost (₱)
                    <input type="number" name="cost" min="0" step="0.01" value="0" required>
                </label>
                <label class="field full">Status
                    <select name="status" required>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>
                </label>
                <label class="field full">Issue Description
                    <textarea name="issue" rows="2" required placeholder="Describe the hardware problem"></textarea>
                </label>
                <label class="field full">Action Taken
                    <textarea name="action" rows="2" placeholder="Describe the repair performed"></textarea>
                </label>
            </div>

            <p class="form-error" id="formError" role="alert" hidden></p>

            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button" id="formSubmit">Save Record</button>
            </div>
        </form>
    </dialog>

    
    <dialog class="modal" id="viewDialog" aria-labelledby="viewTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="viewTitle">Maintenance Details</h2>
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

    
    <dialog class="modal" id="historyDialog" aria-labelledby="historyTitle">
        <div class="modal-body">
            <div class="modal-header">
                <h2 id="historyTitle">Maintenance History</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <ol class="history-list" id="historyList"></ol>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Close</button>
            </div>
        </div>
    </dialog>

    
    <dialog class="modal" id="completeDialog" aria-labelledby="completeTitle">
        <form id="completeForm" class="modal-body">
            <div class="modal-header">
                <h2 id="completeTitle">Mark as completed</h2>
                <button type="button" class="close-button" data-close aria-label="Close">×</button>
            </div>
            <p class="dialog-text" id="completeText"></p>
            <div class="form-grid">
                <label class="field full">Final Repair Cost (₱)
                    <input type="number" name="cost" min="0" step="0.01" required>
                </label>
                <label class="field full">Action Taken
                    <textarea name="action" rows="3" placeholder="Describe the repair performed"></textarea>
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-button" data-close>Cancel</button>
                <button type="submit" class="primary-button">Mark Completed</button>
            </div>
        </form>
    </dialog>

    <script>
    (() => {
        
        const items = <?= json_encode($records, $jsonFlags) ?>;
        const CATALOG = <?= json_encode($catalog, $jsonFlags) ?>;

        items.forEach((item, index) => { item.id = index + 1; });
        let nextId = items.length + 1;

        const state = {
            search: "", status: "", page: 1, perPage: 10,
            editingId: null, // record being edited, or null when adding
            targetId: null   // record shown in the view / complete dialogs
        };

        
        const $ = (id) => document.getElementById(id);
        const body = $("recordBody");
        const form = $("recordForm");
        const dialogs = {
            form: $("formDialog"), view: $("viewDialog"),
            history: $("historyDialog"), complete: $("completeDialog")
        };

        const esc = (value) => String(value).replace(/[&<>"']/g,
            (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

        const today = () => new Date().toLocaleDateString("en-CA"); // YYYY-MM-DD, local time
        const formatDate = (iso) => new Date(`${iso}T00:00:00`)
            .toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
        const peso = (amount) => Number(amount)
            .toLocaleString("en-PH", { style: "currency", currency: "PHP" });

        const byId = (id) => items.find((item) => item.id === Number(id));
        const badge = (item) => `<span class="badge ${item.status.toLowerCase()}">${esc(item.status)}</span>`;

        const matches = (item) => {
            const query = state.search.trim().toLowerCase();
            const haystack = [item.tag, CATALOG[item.tag], item.issue].join(" ").toLowerCase();

            return (!query || haystack.includes(query))
                && (!state.status || item.status === state.status);
        };

        
        const actionButton = (action, id, icon, label) =>
            `<button type="button" class="icon-button" data-action="${action}" data-id="${id}" aria-label="${label}"><i class="bi ${icon}"></i></button>`;

        const rowHtml = (item) => `
            <tr>
                <td class="asset-tag">${esc(item.tag)}</td>
                <td class="primary-cell">${esc(CATALOG[item.tag])}</td>
                <td>${esc(item.issue)}</td>
                <td>${formatDate(item.date)}</td>
                <td class="cost-cell">${peso(item.cost)}</td>
                <td>${badge(item)}</td>
                <td>
                    <div class="row-actions">
                        ${actionButton("view", item.id, "bi-eye", `View ${esc(item.tag)}`)}
                        ${actionButton("history", item.id, "bi-clock-history", `History of ${esc(item.tag)}`)}
                        ${actionButton("edit", item.id, "bi-pencil", `Edit ${esc(item.tag)}`)}
                        ${item.status === "Ongoing" ? actionButton("complete", item.id, "bi-check-circle", `Complete ${esc(item.tag)}`) : ""}
                    </div>
                </td>
            </tr>`;

        function renderStats() {
            const ongoing = items.filter((item) => item.status === "Ongoing").length;

            $("statTotal").textContent = items.length;
            $("statOngoing").textContent = ongoing;
            $("statCompleted").textContent = items.length - ongoing;
            $("statCost").textContent = peso(items.reduce((sum, item) => sum + item.cost, 0));
        }

        function render() {
            const rows = items.filter(matches);
            const pages = Math.max(1, Math.ceil(rows.length / state.perPage));
            state.page = Math.min(state.page, pages);

            const start = (state.page - 1) * state.perPage;
            const visible = rows.slice(start, start + state.perPage);

            body.innerHTML = visible.length
                ? visible.map(rowHtml).join("")
                : `<tr><td colspan="7" class="empty-message">No maintenance records found.</td></tr>`;

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
            if (action === "view")     openView(id);
            if (action === "history")  openHistory(id);
            if (action === "edit")     openForm(id);
            if (action === "complete") openComplete(id);
        });

        
        function openForm(id = null) {
            state.editingId = id ? Number(id) : null;
            const select = $("hardwareSelect");
            form.reset();
            $("formError").hidden = true;
            $("formTitle").textContent = id ? "Edit Maintenance Record" : "Add Maintenance Record";
            $("formSubmit").textContent = id ? "Save Changes" : "Save Record";

            if (id) {

                const item = byId(id);
                select.innerHTML = `<option value="${esc(item.tag)}">${esc(item.tag)} · ${esc(CATALOG[item.tag])}</option>`;
                select.disabled = true;
                form.elements.date.value = item.date;
                form.elements.cost.value = item.cost;
                form.elements.status.value = item.status;
                form.elements.issue.value = item.issue;
                form.elements.action.value = item.action;
            } else {

                const busy = new Set(items.filter((item) => item.status === "Ongoing").map((item) => item.tag));
                select.innerHTML = `<option value="">Select hardware</option>` +
                    Object.entries(CATALOG)
                        .filter(([tag]) => !busy.has(tag))
                        .map(([tag, name]) => `<option value="${esc(tag)}">${esc(tag)} · ${esc(name)}</option>`)
                        .join("");
                select.disabled = false;
                form.elements.date.value = today();
            }

            dialogs.view.close();
            dialogs.form.showModal();
        }

        $("addButton").addEventListener("click", () => openForm());

        form.addEventListener("submit", (event) => {
            event.preventDefault();

            const data = {
                date: form.elements.date.value,
                cost: Number(form.elements.cost.value),
                status: form.elements.status.value,
                issue: form.elements.issue.value.trim(),
                action: form.elements.action.value.trim()
            };

            if (state.editingId) {
                const item = byId(state.editingId);
                const clash = data.status === "Ongoing" &&
                    items.some((other) => other !== item && other.tag === item.tag && other.status === "Ongoing");

                if (clash) {
                    $("formError").textContent = "This hardware already has an ongoing repair.";
                    $("formError").hidden = false;
                    return;
                }
                Object.assign(item, data);
            } else {
                items.unshift({ id: nextId++, tag: form.elements.tag.value, ...data });
                state.page = 1;
            }

            dialogs.form.close();
            render();
        });

        
        function openView(id) {
            const item = byId(id);
            state.targetId = item.id;

            const fields = [
                ["Asset tag", esc(item.tag)],
                ["Hardware", esc(CATALOG[item.tag])],
                ["Date", formatDate(item.date)],
                ["Repair cost", peso(item.cost)],
                ["Issue", esc(item.issue)],
                ["Status", badge(item)],
                ["Action taken", esc(item.action || "—")]
            ];

            $("detailList").innerHTML = fields
                .map(([label, value]) => `<div><dt>${label}</dt><dd>${value}</dd></div>`)
                .join("");

            dialogs.view.showModal();
        }

        $("viewEdit").addEventListener("click", () => openForm(state.targetId));

        
        function openHistory(id) {
            const { tag } = byId(id);
            const records = items
                .filter((item) => item.tag === tag)
                .sort((a, b) => b.date.localeCompare(a.date));

            $("historyTitle").textContent = `${tag} · ${CATALOG[tag]}`;
            $("historyList").innerHTML = records.map((item) => `
                <li>
                    <div>
                        <strong>${esc(item.issue)}</strong>
                        <span class="period">${formatDate(item.date)} · ${peso(item.cost)}</span>
                    </div>
                    ${badge(item)}
                </li>`).join("");

            dialogs.history.showModal();
        }

        
        function openComplete(id) {
            const item = byId(id);
            state.targetId = item.id;

            $("completeText").textContent = `${CATALOG[item.tag]} (${item.tag}) — ${item.issue}.`;
            $("completeForm").elements.cost.value = item.cost;
            $("completeForm").elements.action.value = item.action;

            dialogs.complete.showModal();
        }

        $("completeForm").addEventListener("submit", (event) => {
            event.preventDefault();

            Object.assign(byId(state.targetId), {
                status: "Completed",
                cost: Number(event.target.elements.cost.value),
                action: event.target.elements.action.value.trim()
            });

            dialogs.complete.close();
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