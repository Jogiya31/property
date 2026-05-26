<?php require '../connection/session_check.php'; ?>
<!DOCTYPE html>
<html>

<?php require 'head.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
    <!-- Site wrapper -->
    <div class="wrapper">

        <?php require 'header.php'; ?>
        <!-- =============================================== -->

        <?php require 'sidebar.php'; ?>

        <!-- =============================================== -->

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                    Inbox
                    <small> Lists</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Inbox</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="box box-primary box-solid">
                    <div class="box-header">
                        <h3 class="box-title">Inbox</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body table-responsive">
                        <table id="allData" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Reference ID</th>
                                    <th>Name</th>
                                    <th>Property Type</th>
                                    <th>Purposes</th>
                                    <th>Acquisition/Disposal</th>
                                    <th>Date of Acquisition/disposed</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Last Action Date</th>
                                    <th>Remarks</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <div class="modal fade" id="historyModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">x</span></button>
                        <h4 class="modal-title">Form History <strong id="formHistoryTitle"></strong></h4>
                    </div>
                    <div class="modal-body">
                        <ul class="timeline" id="formHistoryTimeline"></ul>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- ./wrapper -->

    <?php require 'footer.php'; ?>
    <script>
        let allData = [];

        let filterData = [];

        async function loadAllData() {
            try {
                const res = await fetch('../api/get_inbox.php', {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    }
                });
                const json = await res.json();
                allData = json.data || [];
                renderTable();
            } catch (err) {
                console.error("Error loading data:", err);
            }
        }

        function renderTable() {
            let rows = "";

            allData.forEach(f => {

                let actionButtons = "";

                /* =====================================
                   VIEW / EDIT BUTTONS
                ===================================== */

                if (f.status === "Draft") {

                    if (f.form_type === 'immovable') {

                        actionButtons = `
                            <a href="immovableForm.php?id=${f.id}" 
                            class="btn btn-sm btn-warning edit-btn">
                                Edit
                            </a>
                        `;

                    } else {

                        actionButtons = `
                            <a href="movableForm.php?id=${f.id}" 
                            class="btn btn-sm btn-warning edit-btn">
                                Edit
                            </a>
                        `;
                    }

                } else {

                    /* =====================================
                       VIEW BUTTON
                    ===================================== */

                    if (f.form_type === 'immovable') {

                        actionButtons = `
                            <button
                                class="btn btn-primary view-btn btn-sm"
                                onclick="openForm(${f.id}, 'viewImmovable.php')"
                            >
                                View
                            </button>
                        `;

                    } else {

                        actionButtons = `
                            <button
                                class="btn btn-primary view-btn btn-sm"
                                onclick="openForm(${f.id}, 'viewmovable.php')"
                            >
                                View
                            </button>
                        `;

                    }

                    actionButtons += `
                        <button
                            class="btn bg-purple btn-sm"
                            onclick="openFormHistory(${f.id})"
                        >
                            History
                        </button>
                    `;
                }

                rows += `
                    <tr>
                        <td><strong>${f.reference_no ?? ''}</strong></td>

                        <td>${f.form_owner?.username ?? ''}</td>

                        <td>${f.form_type ?? ''}</td>

                        <td>${f.purpose ?? ''}</td>

                        <td>${f.acquired_disposed ?? ''}</td>

                        <td>${f.date_acquisition_disposed ?? ''}</td>

                        <td class="text-center">
                            <span class="badge ${
                                f.workflow?.status === 'Pending' ? 'bg-yellow' :
                                f.workflow?.status === 'Forwarded' ? 'bg-aqua' :
                                f.workflow?.status === 'Rejected' ? 'bg-red' :
                                f.workflow?.status === 'Completed' ? 'bg-green' :
                                f.workflow?.status === 'Draft' ? 'bg-gray' : ''
                            }">
                                ${f.workflow?.status ?? ''}
                            </span>
                        </td>

                        <td class="text-center">
                            ${f.timestamps?.updated_at ? f.timestamps.updated_at.split(" ")[0] : ''}
                        </td>

                        <td>${f.remarks ?? ''}</td>

                        <td class="text-center">
                            ${actionButtons}
                        </td>
                    </tr>
                `;
            });

            document.querySelector("#allData tbody").innerHTML = rows;

            $('#allData').DataTable();
        }

        /* =====================================
           OPEN FORM + LOCK FILE
        ===================================== */

        async function openForm(formId, redirectPage) {

            try {

                const formData = new FormData();

                formData.append("form_id", formId);

                const res = await fetch("../api/lock_form.php", {
                    method: "POST",
                    body: formData
                });

                const json = await res.json();

                if (!json.success) {

                    alert(json.error || "Unable to lock form");

                    return;
                }

                window.location.href = `${redirectPage}?id=${formId}`;

            } catch (err) {

                console.error(err);

                alert("Error while locking form");
            }
        }

        /* =========================================
           OPEN HISTORY MODAL
        ========================================= */

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatHistoryDate(value) {
            if (!value) return "";

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;

            return date.toLocaleString("en-IN", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit"
            });
        }

        function statusClass(actionType) {
            const action = String(actionType ?? "").toLowerCase();

            if (action.includes("reject")) return "red";
            if (action.includes("forward")) return "blue";
            if (action.includes("submit") || action.includes("created")) return "green";
            if (action.includes("pull")) return "yellow";
            if (action.includes("draft")) return "gray";

            return "aqua";
        }

        function getTimelineIcon(actionType) {

            const action = String(actionType || '')
                .toLowerCase();

            if (action.includes('submit') || action.includes('create')) {
                return 'fa-check';
            }

            if (action.includes('forward')) {
                return 'fa-arrow-right';
            }

            if (action.includes('pull')) {
                return 'fa-reply';
            }

            if (action.includes('reject')) {
                return 'fa-times';
            }

            if (action.includes('approve')) {
                return 'fa-thumbs-up';
            }

            if (action.includes('unlock')) {
                return 'fa-unlock';
            }

            if (action.includes('lock')) {
                return 'fa-lock';
            }

            if (action.includes('draft')) {
                return 'fa-file-text-o';
            }

            if (action.includes('update')) {
                return 'fa-pencil';
            }

            return 'fa-clock-o';
        }

        function renderHistoryTimeline(items) {
            const timeline = document.getElementById("formHistoryTimeline");
            if (!timeline) return;

            if (!items || items.length === 0) {
                timeline.innerHTML = `<li class="form-history-empty">No history found for this form.</li>`;
                return;
            }

            timeline.innerHTML = items.slice()
                .reverse().map(item => {
                    const action = escapeHtml(item.action_type || "Updated");
                    const by = escapeHtml(item.action_by_name || item.action_by || "Unknown");
                    const byRole = escapeHtml(item.action_by_role || "");
                    const to = escapeHtml(item.action_to_name || item.action_to || "");
                    const toRole = escapeHtml(item.action_to_role || "");
                    const remarks = escapeHtml(item.remarks || "");
                    const date = escapeHtml(formatHistoryDate(item.created_at).split(",")[0] || "");
                    const time = escapeHtml(formatHistoryDate(item.created_at).split(",")[1]?.trim() || "");
                    const oldValue = escapeHtml(item.old_value || "");
                    const newValue = escapeHtml(item.new_value || "");
                    const fieldName = escapeHtml(item.field_name || "");
                    const badgeClass = statusClass(item.action_type);
                    const iconClass = getTimelineIcon(item.action_type);

                    return `
                        <!-- timeline time label -->
                        <li class="time-label">
                            <span class="bg-${badgeClass}">
                                ${date}
                            </span>
                        </li>
                        <!-- /.timeline-label -->

                        <!-- timeline item -->
                        <li>
                            <!-- timeline icon -->
                            <i class="fa ${iconClass} bg-${badgeClass}"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="fa fa-clock-o"></i> ${time}</span>

                                ${action === 'Pull Back' ?  
                                    `<h3 class="timeline-header"><a href="#">${action}</a>  from  ${to}${toRole ? ` (${toRole})` : ""}
                                    ${to ? ` <i class="fa fa-long-arrow-right"></i> ${by}${byRole ? ` (${byRole})` : ""}` : ""}</h3>`
                                    :
                                        `<h3 class="timeline-header"><a href="#">${action}</a>  by  ${by}${byRole ? ` (${byRole})` : ""}
                                    ${to ? ` <i class="fa fa-long-arrow-right"></i> ${to}${toRole ? ` (${toRole})` : ""}` : ""}</h3>`
                                }
                               

                                <div class="timeline-body">
                                    ${fieldName || oldValue || newValue ? `
                                        <div class="form-history-change">
                                            ${fieldName ? `<span class="label label-default">${fieldName}</span>` : ""}
                                            ${oldValue || newValue ? `<span>${oldValue || ""} ${fieldName !=='form' ? 'to' : ''} ${newValue || "-"}</span>` : ""}
                                        </div>
                                        <br>
                                    ` : ""}
                                    ${remarks ? `<div class="form-history-remarks"><strong>Remarks:</strong> ${remarks}</div>` : ""}
                                </div>
                            </div>
                        </li>
                        <!-- END timeline item -->
                `
            }).join("");
        }
        
        async function openFormHistory(formId) {
            if (!formId) {
                showAlert("Form ID not found", "danger");
                return;
            }

            const timeline = document.getElementById("formHistoryTimeline");
            if (timeline) {
                timeline.innerHTML = `<li class="form-history-empty">Loading history...</li>`;
            }

            $("#historyModal").modal("show");

            try {
                const res = await fetch("../api/get_form_history.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "Application/json"
                    },
                    body: JSON.stringify({
                        id: formId
                    })
                });

                const json = await res.json();

                if (json.success) {
                    renderHistoryTimeline(json.data);
                    document.getElementById('formHistoryTitle').innerHTML = json.formData?.reference_no;

                } else {
                    renderHistoryTimeline([]);
                    showAlert(json.error || "Unable to load form history", "danger");

                }
            } catch (err) {
                renderHistoryTimeline([]);
                showAlert("Server error while loading history", "danger");
            }
        }

        /* ===============================
        INIT
        ================================ */

        (async function() {
            await loadAllData();
        })();
    </script>
</body>

</html>