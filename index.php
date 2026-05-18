<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>eForms</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="bower_components/Ionicons/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="plugins/iCheck/square/blue.css">
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><b>Welcome</b> user</a>
        </div>
        <!-- /.login-logo -->
        <div class="login-box-body">
            <p class="login-box-msg">Sign in to start your session</p>

            <!-- Session timeout message -->
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Session Expired!</strong> Your session has expired due to inactivity. Please login again.
                </div>
            <?php endif; ?>

            <!-- Unauthorized access message -->
            <?php if (isset($_GET['unauthorized'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Unauthorized!</strong> Your session is invalid or has expired. Please login again.
                </div>
            <?php endif; ?>

            <form id="loginForm" novalidate>
                <div class="form-group has-feedback">
                    <input type="text" class="form-control" name="email" placeholder="username" />
                    <span
                        class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input
                        type="password"
                        class="form-control"
                        name="password"
                        placeholder="Password" />
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-8">
                    </div>
                    <!-- /.col -->
                    <div class="col-xs-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">
                            Sign In
                        </button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>
        </div>
        <!-- /.login-box-body -->
    </div>
    <!-- /.login-box -->

    <!-- jQuery 3 -->
    <script src="bower_components/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- iCheck -->
    <script src="plugins/iCheck/icheck.min.js"></script>
    <script>
        // Session timeout configuration (in milliseconds)
        const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
        const WARNING_TIME = 25 * 60 * 1000; // Warn at 25 minutes
        let logoutTimer;
        let warningTimer;

        // Initialize - clear any stale sessions
        (function init() {
            sessionStorage.clear();
            localStorage.removeItem('sessionWarned');
        })();

        // Function to reset inactivity timers
        function resetActivityTimer() {
            clearTimeout(logoutTimer);
            clearTimeout(warningTimer);
            localStorage.removeItem('sessionWarned');

            // Set warning timer
            warningTimer = setTimeout(() => {
                showSessionWarning();
            }, WARNING_TIME);

            // Set logout timer
            logoutTimer = setTimeout(() => {
                logoutUser();
            }, SESSION_TIMEOUT);
        }

        // Show session warning
        function showSessionWarning() {
            if (localStorage.getItem('sessionWarned') !== 'true') {
                const warning = confirm('⚠️ Your session will expire in 5 minutes due to inactivity.\n\nClick OK to continue working.');
                if (warning) {
                    localStorage.setItem('sessionWarned', 'true');
                    resetActivityTimer();
                } else {
                    logoutUser();
                }
            }
        }

        // Logout user and redirect
        function logoutUser() {
            sessionStorage.clear();
            fetch('api/logout.php').catch(() => {
                // Continue even if API call fails
            }).finally(() => {
                location.href = 'index.php?timeout=1';
            });
        }

        // Track user activity
        document.addEventListener('mousemove', resetActivityTimer);
        document.addEventListener('keypress', resetActivityTimer);
        document.addEventListener('click', resetActivityTimer);
        document.addEventListener('scroll', resetActivityTimer);
        document.addEventListener('touchstart', resetActivityTimer);

        // Form submission handler
        document.getElementById("loginForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            let form = new FormData(e.target);
            let data = Object.fromEntries(form);
            let res = await fetch("api/login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            });
            let json = await res.json();

            if (json.success) {
                sessionStorage.setItem("uid", json.uid);
                sessionStorage.setItem("username", json.username);
                sessionStorage.setItem("email", json.email);
                sessionStorage.setItem("designation", json.designation);
                sessionStorage.setItem("state", json.state);
                sessionStorage.setItem("address", json.address);
                sessionStorage.setItem("empCode", json.empcode);
                sessionStorage.setItem("service", json.service);
                sessionStorage.setItem("payscale", json.payscale);

                // Start session timeout tracking
                resetActivityTimer();

                setTimeout(() => {
                    location.href = "pages/dashboard.php";
                }, 800);
            } else {
                document.getElementById("msg").innerHTML =
                    `<div class='alert alert-danger'>${json.message}</div>`;
            }
        });
    </script>
</body>

</html>