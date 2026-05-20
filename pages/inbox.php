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
                                    <th class="text-center">Created At</th>
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
    </div>
    <!-- ./wrapper -->

    <?php require 'footer.php'; ?>
    <script>
        let allData = [];

        let filterData = [];

        let page = 1;

        const PAGE_SIZE = 10;

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
                       LOCK STATUS
                    ===================================== */

                    let disabled = "";

                    let lockText = "";

                    if (
                        f.is_locked == true &&
                        parseInt(f.locked_by) !== parseInt(sessionStorage.getItem("uid"))
                    ) {

                        disabled = "disabled";

                        lockText = `
                            <small class="text-danger">
                                Locked by ${f.locked_by_name ?? 'Another User'}
                            </small>
                        `;
                    }

                    /* =====================================
                       VIEW BUTTON
                    ===================================== */

                    if (f.form_type === 'immovable') {

                        actionButtons = `
                            <button
                                class="btn btn-primary view-btn"
                                ${disabled}
                                onclick="openForm(${f.id}, 'viewImmovable.php')"
                            >
                                View
                            </button>

                            ${lockText}
                        `;

                    } else {

                        actionButtons = `
                            <button
                                class="btn btn-primary view-btn"
                                ${disabled}
                                onclick="openForm(${f.id}, 'viewmovable.php')"
                            >
                                View
                            </button>

                            ${lockText}
                        `;
                    }
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
                                f.workflow?.status === 'Draft' ? 'bg-gray' : ''
                            }">
                                ${f.workflow?.status ?? ''}
                            </span>
                        </td>

                        <td class="text-center">
                            ${f.timestamps?.created_at ? f.timestamps.created_at.split(" ")[0] : ''}
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

        /* ===============================
        INIT
        ================================ */

        (async function() {
            await loadAllData();
        })();
    </script>
</body>

</html>