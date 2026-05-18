<!-- jQuery 3 -->
<script src="../bower_components/jquery/dist/jquery.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="../bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<!-- SlimScroll -->
<script src="../bower_components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="../bower_components/fastclick/lib/fastclick.js"></script>
<!-- bootstrap datepicker -->
<script src="../bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<!-- DataTables -->
<script src="../bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="../bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="../dist/js/demo.js"></script>


<!-- Session Manager - Auto-Logout Handler -->
<script src="../js/session-manager.js"></script>

<script>
    $(document).ready(function() {
        $('.sidebar-menu').tree()
    })
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const msg = sessionStorage.getItem("alertMsg");
        if (msg) {
            showAlert(msg, "success");
            sessionStorage.removeItem("alertMsg");
        }

        const currentPage = window.location.pathname.split("/").pop();

        // Remove all active/menu-open first
        document.querySelectorAll(".sidebar-menu li").forEach(li => {
            li.classList.remove("active", "menu-open");
        });

        document.querySelectorAll(".treeview-menu").forEach(ul => {
            ul.style.display = "none";
        });

        // Find matching link
        const links = document.querySelectorAll(".sidebar-menu a");

        links.forEach(link => {
            const href = link.getAttribute("href");

            if (href && href === currentPage) {
                const li = link.closest("li");
                li.classList.add("active");

                // Open all parent treeviews
                let parent = li.parentElement;
                while (parent && parent !== document) {
                    if (parent.classList.contains("treeview-menu")) {
                        parent.style.display = "block";
                    }

                    if (parent.classList.contains("treeview")) {
                        parent.classList.add("active", "menu-open");
                    }

                    parent = parent.parentElement;
                }
            }
        });

        var designation = sessionStorage.getItem('designation');

        if (designation === 'DDG' || designation === 'SO' || designation === 'DH' || designation === 'JD' || designation === 'DG') {
            $('.property-menu').hide();
        } else {
            $('.property-menu').show();
        }

        //Date picker
        $('.datepicker').datepicker({
            autoclose: true,
            format: 'dd/mm/yyyy'
        });

        var user = sessionStorage.getItem('username');
        $('.userName').text(user)

    });

    function showAlert(message, type = 'success') {
        const msgDiv = document.getElementById('msg');
        if (!msgDiv) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible`;

        alert.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    `;

        msgDiv.appendChild(alert);

        setTimeout(() => {
            $(alert).fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
</script>