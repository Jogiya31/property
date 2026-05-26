<?php require '../connection/session_check.php'; ?>
<!DOCTYPE html>
<html>

<?php require 'head.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">

    <div class="wrapper">

        <?php require 'header.php'; ?>

        <?php require 'sidebar.php'; ?>

        <!-- =============================================== -->
        <!-- CONTENT -->
        <!-- =============================================== -->

        <div class="content-wrapper">

            <section class="content-header">

                <h1>
                    Outbox
                    <small>Lists</small>
                </h1>

                <ol class="breadcrumb">
                    <li>
                        <a href="dashboard.php">
                            <i class="fa fa-dashboard"></i> Home
                        </a>
                    </li>

                    <li class="active">Outbox</li>
                </ol>

            </section>

            <!-- =============================================== -->
            <!-- MAIN CONTENT -->
            <!-- =============================================== -->

            <section class="content">

                <div class="box box-primary box-solid">

                    <div class="box-header">
                        <h3 class="box-title">Outbox</h3>
                    </div>

                    <div class="box-body table-responsive">

                        <table
                            id="allData"
                            class="table table-bordered table-striped">

                            <thead>

                                <tr>

                                    <th>Reference ID</th>

                                    <th>Name</th>

                                    <th>Property Type</th>

                                    <th>Purpose</th>

                                    <th>Acquisition/Disposal</th>

                                    <th>Date of Acquisition/Disposal</th>

                                    <th class="text-center">Status</th>

                                    <th class="text-center">Currently With</th>

                                    <th class="text-center">Last Action Date</th>

                                    <th>Remarks</th>

                                    <th class="text-center action">Action</th>

                                </tr>

                            </thead>

                            <tbody></tbody>

                        </table>

                    </div>

                </div>

            </section>

        </div>

        <!-- =============================================== -->
        <!-- PULL BACK MODAL -->
        <!-- =============================================== -->

        <div class="modal fade" id="pullBackModal">

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header bg-warning">

                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal">

                            &times;

                        </button>

                        <h4 class="modal-title">
                            Pull Back Form
                        </h4>

                    </div>

                    <div class="modal-body">

                        <input
                            type="hidden"
                            id="pullback_form_id">

                        <div class="form-group">

                            <label class="required-label">
                                Select Reason
                            </label>

                            <select
                                id="pullback_reason"
                                class="form-control">

                                <option value="">
                                    Select...
                                </option>

                                <option value="Officer not available">
                                    Officer not available
                                </option>

                                <option value="Sent by mistake">
                                    Sent by mistake
                                </option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label>
                                Remarks
                            </label>

                            <textarea
                                id="pullback_remarks"
                                class="form-control"
                                rows="4"
                                placeholder="Enter remarks"></textarea>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-default"
                            data-dismiss="modal">

                            Cancel

                        </button>

                        <button
                            type="button"
                            class="btn btn-warning"
                            onclick="submitPullBack()">

                            Confirm Pull Back

                        </button>

                    </div>

                </div>

            </div>

        </div>

        <!-- =============================================== -->
        <!-- HISTORY MODAL -->
        <!-- =============================================== -->
        <div class="modal fade" id="historyModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
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

    <?php require 'footer.php'; ?>

    <script>
        let allData = [];
        let redirectUrl = '';

        /* =========================================
           LOAD DATA
        ========================================= */

        async function loadAllData() {
            try {
                const res = await fetch('../api/get_outbox.php', {
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

        /* =========================================
           RENDER TABLE
        ========================================= */

        function renderTable() {

            if ($.fn.DataTable.isDataTable('#allData')) {

                $('#allData').DataTable().destroy();
            }

            let rows = "";

            allData.forEach(f => {

                let actionBtn = '';

                /* =========================================
                   WORKFLOW
                ========================================= */

                const formOwner = f.form_owner || {};

                const workflow = f.workflow || {};

                const sender = f.sender || {};

                const receiver = f.receiver || {};

                const currentHolder = f.current_holder || {};

                const permissions = f.permissions || {};

                const lock = f.lock || {};

                const openState = f.open_state || {};

                const timestamps = f.timestamps || {};

                /* =====================================
                    VIEW BUTTON
                ===================================== */

                if (f.form_type === 'immovable') {

                    actionBtn = `
                        <button
                            class="btn btn-primary view-btn btn-sm"
                            onclick="openForm(${f.id}, 'viewImmovable.php')"
                            title="View Form"
                        >
                        View
                        </button>
                    `;

                } else {

                    actionBtn = `
                        <button
                            class="btn btn-primary view-btn btn-sm"
                            onclick="openForm(${f.id}, 'viewmovable.php')"
                            title="View Form"
                        >
                        View
                        </button>
                    `;

                }

                /* =========================================
                   PULLBACK BUTTON

                   Conditions:

                   1. Latest sender is current user
                   2. File still with receiver
                   3. File not opened
                   4. File not locked
                ========================================= */

                if (
                    permissions.can_pullback === true &&
                    lock.is_locked === false &&
                    openState.is_opened === false
                ) {

                    const viewUrl =
                        f.form_type === 'immovable' ?
                        `viewImmovable.php?id=${f.id}` :
                        `viewmovable.php?id=${f.id}`;

                    actionBtn += `
                        <button
                            class="btn btn-warning btn-sm"
                            onclick="openPullBackModal(${f.id}, '${viewUrl}')"
                            title="Pull Back Form"
                        >
                            Pull Back
                        </button>
                    `;
                }

                actionBtn += `
                    <button
                        class="btn bg-purple btn-sm"
                        onclick="openFormHistory(${f.id})"
                        title="View History"
                    >
                     History
                    </button>
                `;

                /* =========================================
                   STATUS BADGE
                ========================================= */

                let statusClass = 'bg-gray';

                if (workflow.status === 'Pending') {

                    statusClass = 'bg-yellow';

                } else if (workflow.status === 'Forwarded') {

                    statusClass = 'bg-aqua';

                } else if (workflow.status === 'Rejected') {

                    statusClass = 'bg-red';

                } else if (workflow.status === 'Approved') {

                    statusClass = 'bg-green';
                }

                /* =========================================
                   TABLE ROW
                ========================================= */

                rows += `
                    <tr>

                        <td><strong>${f.reference_no ?? ''}</strong></td>

                        <td>${f.form_owner?.username ?? ''}</td>

                        <td> ${f.form_type ?? ''} </td>

                        <td>  ${f.purpose ?? ''}</td>

                        <td>${f.acquired_disposed ?? ''}</td>

                        <td>${f.date_acquisition_disposed ?? ''}</td>

                        <td class="text-center">
                            <span class="badge ${
                                f.workflow?.status === 'Pending' ? 'bg-yellow' :
                                f.workflow?.status === 'Forwarded' ? 'bg-aqua' :
                                f.workflow?.status === 'Rejected' ? 'bg-red' :
                                f.workflow?.status === 'Draft' ? 'bg-gray' : ''
                            }">
                                ${f.workflow?.status ?? ''}
                            </span>
                        </td>

                        <td class="text-center"> ${currentHolder.username ?? ''}</td>

                        <td class="text-center">
                            ${f.timestamps?.updated_at ? f.timestamps.updated_at.split(" ")[0] : ''}
                        </td>

                        <td>${f.remarks ?? ''}</td>

                        <td class="action">${actionBtn}</td>

                    </tr>
                `;
            });

            document.querySelector(
                "#allData tbody"
            ).innerHTML = rows;

            $('#allData').DataTable({
                responsive: true,
                destroy: true
            });
        }

        /* =====================================
           OPEN FORM + LOCK FILE
        ===================================== */

        async function openForm(formId, redirectPage) {

            try {

                const form = allData.find(x => x.id == formId);

                if (form.current_holder?.uid === parseInt(sessionStorage.getItem("uid"))) {

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
                }

                window.location.href = `${redirectPage}?id=${formId}`;

            } catch (err) {

                console.error(err);

                alert("Error while locking form");
            }
        }

        /* =========================================
           OPEN PULL BACK MODAL
        ========================================= */

        function openPullBackModal(formId, viewUrl) {

            redirectUrl = viewUrl;

            $('#pullback_form_id').val(formId);

            $('#pullback_reason').val('');

            $('#pullback_remarks').val('');

            $('#pullBackModal').modal('show');
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

        /* =========================================
           SUBMIT PULL BACK
        ========================================= */

        async function submitPullBack() {
            const formId = $('#pullback_form_id').val();
            const reason = $('#pullback_reason').val();
            const remarks = $('#pullback_remarks').val();
            if (!reason) {
                alert("Please select reason");
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', formId);
                formData.append('reason', reason);
                formData.append('remarks', remarks);
                const res = await fetch('../api/pullback_form.php', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    $('#pullBackModal').modal('hide');
                    alert(json.message);
                    /* =========================================
                       REDIRECT TO VIEW PAGE
                    ========================================= */
                    window.location.href = redirectUrl;
                } else {
                    showAlert(json.error || json.message, "danger");
                }

            } catch (err) {
                showAlert("Failed to pull back form", "danger");
            }
        }

        /* =========================================
           INIT
        ========================================= */

        (async function() {
            await loadAllData();
        })();
    </script>

</body>

</html>