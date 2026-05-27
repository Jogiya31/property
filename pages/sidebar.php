        <!-- Left side column. contains the sidebar -->
        <aside class="main-sidebar">
            <!-- sidebar: style can be found in sidebar.less -->
            <section class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel">
                    <div class="pull-left image">
                        <img src="../dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
                    </div>
                    <div class="pull-left info">
                        <p class="userName"></p>
                        <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
                    </div>
                </div>
                <!-- sidebar menu: : style can be found in sidebar.less -->
                <ul class="sidebar-menu">
                    <li class="header">MAIN NAVIGATION</li>
                    <li class="active">
                        <a href="dashboard.php">
                            <i class="fa fa-dashboard"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="inbox.php">
                            <?php

                            if (!isset($conn)) {
                                require_once __DIR__ . '/../connection/db.php';
                            }

                            $stmt = $conn->prepare("
                                SELECT COUNT(*) AS total
                                FROM forms
                                WHERE
                                    current_holder::INTEGER = :uid
                                    AND status IN (
                                        'Pending',
                                        'Forwarded',
                                        'Pull Back'
                                    )
                            ");
                            $stmt->execute([
                                ':uid' => $_SESSION['uid']
                            ]);

                            $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                            ?>
                            <i class="fa fa-inbox"></i>
                            <span>Inbox</span>
                            <?php if ($count > 0): ?>
                                <span class="pull-right-container">
                                    <small class="label pull-right bg-green">
                                        <?php echo $count; ?>
                                    </small>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="outbox.php">
                            <i class="fa fa-envelope-open"></i> <span>Sent</span>
                        </a>
                    </li>
                    <li class="treeview property-menu">
                        <a href="#">
                            <i class="fa fa-briefcase"></i> <span>Property</span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li class="treeview" id="propertyRequest">
                                <a href="#"><i class="fa fa-circle-o"></i> New Request
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li><a href="immovableForm.php"><i class="fa fa-circle-o"></i> Immovable Property</a></li>
                                    <li><a href="movableForm.php"><i class="fa fa-circle-o"></i> Movable Property</a></li>
                                </ul>
                            </li>

                            <li><a href="requestLists.php"><i class="fa fa-list"></i>Request Lists</a></li>
                        </ul>
                    </li>
                </ul>
            </section>
            <!-- /.sidebar -->
        </aside>

        <!-- =============================================== -->