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

            <div id="msg" style="position: fixed; top:20px; right:20px; z-index:9999; width:320px;"></div>
            
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                    Dashboard
                    <small> Control panel</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Dashboard</li>
                </ol>
            </section>

            <!-- Main content -->
            <section class="content">



            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
    </div>
    <!-- ./wrapper -->

    <?php require 'footer.php'; ?>
</body>

</html>