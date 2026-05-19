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
                                    <th></th>
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
                if (f.status === "Draft") {
                    // Only show Edit button if status is Draft
                    if (f.form_type === 'immovable') {
                        actionButtons = `<a href="immovableForm.php?id=${f.id}" class="btn btn-sm btn-warning edit-btn">Edit</a>`;
                    } else {
                        actionButtons = `<a href="movableForm.php?id=${f.id}" class="btn btn-sm btn-warning edit-btn">Edit</a>`;
                    }
                } else {
                    // Show only the View button if not Draft
                    if (f.form_type === 'immovable') {
                        actionButtons = `<a href="viewImmovable.php?id=${f.id}" class="btn btn-sm btn-primary view-btn">View</a>`;
                    } else {
                        actionButtons = `<a href="viewmovable.php?id=${f.id}" class="btn btn-sm btn-primary view-btn">View</a>`;
                    }
                }

                rows += `
                    <tr>
                        <td><strong>${f.reference_no ?? ''}</strong></td>
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
                        <td class="text-center">${f.created_at ? f.created_at.split(" ")[0] : ''}</td>
                        <td>${f.remarks ?? ''}</td>
                        <td class="text-center">${actionButtons}</td>
                    </tr>
                `;
            });

            document.querySelector("#allData tbody").innerHTML = rows;

            $('#allData').DataTable();
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