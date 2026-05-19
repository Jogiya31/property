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
                    Outbox
                    <small> Lists</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Outbox</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="box box-primary box-solid">
                    <div class="box-header">
                        <h3 class="box-title">Outbox</h3>
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
                                    <th class="text-center">Forwarded To</th>
                                    <th class="text-center">Created At</th>
                                    <th>Remarks</th>
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

        function renderTable() {
            let rows = "";
            allData.forEach(f => {
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
                        <td class="text-center">${f.forward_to?.username ?? ''}</td>
                        <td class="text-center">${f.created_at ? f.created_at.split(" ")[0] : ''}</td>
                        <td>${f.remarks ?? ''}</td>
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