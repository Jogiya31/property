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

                                    <th>Date</th>

                                    <th class="text-center">Status</th>

                                    <th class="text-center">Forwarded To</th>

                                    <th class="text-center">Created At</th>

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

                            <label>
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

        <div class="modal fade" id="timeLine">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"></div>
                    <div class="modal-body">
                        <ul class="timeline">

                            <!-- timeline time label -->
                            <li class="time-label">
                                <span class="bg-red">
                                    10 Feb. 2014
                                </span>
                            </li>
                            <!-- /.timeline-label -->

                            <!-- timeline item -->
                            <li>
                                <!-- timeline icon -->
                                <i class="fa fa-envelope bg-blue"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> 12:05</span>

                                    <h3 class="timeline-header"><a href="#">Support Team</a> ...</h3>

                                    <div class="timeline-body">
                                        ...
                                        Content goes here
                                    </div>

                                    <div class="timeline-footer">
                                        <a class="btn btn-primary btn-xs">...</a>
                                    </div>
                                </div>
                            </li>
                            <!-- END timeline item -->

                            ...

                        </ul>
                    </div>
                    <div class="modal-footer"></div>
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
                   VIEW BUTTON
                ========================================= */

                const viewUrl =
                    f.form_type === 'immovable' ?
                    `viewImmovable.php?id=${f.id}` :
                    `viewmovable.php?id=${f.id}`;

                actionBtn += `
                    <a href="${viewUrl}"
                        class="btn btn-primary">
                        View
                    </a>
                `;

                /* =========================================
                   LOCK STATUS
                ========================================= */

                if (f.is_locked == true) {

                    actionBtn += `
                        <span class="label label-danger">
                            Locked
                        </span>
                    `;
                }

                /* =========================================
                   PULL BACK BUTTON
                ========================================= */

                if (
                    f.status === 'Forwarded' &&
                    f.can_pullback == true &&
                    f.is_opened == false &&
                    f.is_locked == false
                ) {

                    actionBtn += `
                        <button
                            class="btn btn-warning"
                            onclick="openPullBackModal(${f.id})"
                        >
                            Revert
                        </button>
                    `;
                } else if (
                    String(f.user?.uid) === sessionStorage.getItem('uid')
                ) {

                    actionBtn = '';
                }

                rows += `
                    <tr>

                        <td>
                            <strong>${f.reference_no ?? ''}</strong>
                        </td>

                        <td>${f.user?.username ?? ''}</td>

                        <td>${f.form_type ?? ''}</td>

                        <td>${f.purpose ?? ''}</td>

                        <td>${f.acquired_disposed ?? ''}</td>

                        <td>${f.date_acquisition_disposed ?? ''}</td>

                        <td class="text-center">

                            <span class="badge ${
                                f.status === 'Pending' ? 'bg-yellow' :
                                f.status === 'Forwarded' ? 'bg-aqua' :
                                f.status === 'Rejected' ? 'bg-red' :
                                f.status === 'Draft' ? 'bg-gray' : ''
                            }">

                                ${f.status ?? ''}

                            </span>

                        </td>

                        <td class="text-center">
                            ${f.forward_to?.username ?? ''}
                        </td>

                        <td class="text-center">
                            ${f.created_at
                                ? f.created_at.split(" ")[0]
                                : ''}
                        </td>

                        <td>${f.remarks ?? ''}</td>

                        <td class="text-center action">
                            ${actionBtn}
                        </td>

                    </tr>
                `;
            });

            document.querySelector("#allData tbody").innerHTML = rows;

            $('#allData').DataTable();
        }
        /* =========================================
           OPEN MODAL
        ========================================= */

        function openPullBackModal(formId, viewUrl) {

            redirectUrl = viewUrl;

            $('#pullback_form_id').val(formId);

            $('#pullback_reason').val('');

            $('#pullback_remarks').val('');

            $('#pullBackModal').modal('show');
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
                    alert(json.error || json.message);
                }

            } catch (err) {
                console.error(err);
                alert("Failed to pull back form");
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