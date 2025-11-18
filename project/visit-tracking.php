        <?php
            require_once 'template.php';
            require_once 'visit-tracker.php';
            session_name('ResponsePortal');
            session_start();
            
            if (!isset($_SESSION["user_id"])) {
                header("Location:page-login.php");
                exit();
            }
            
            $role = $_SESSION['role'];

                if($role != 'Administrator')
            {
                header("Location:page-error-403.php");
                exit();
            }


            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
            ini_set('error_log', __DIR__.'/php_errors.log');

            $query = "
                SELECT vu.user_id, u.username, p.url, v.timestamp
                FROM Visit v
                LEFT JOIN VisitUser vu ON vu.visit_id = v.visit_id
                LEFT JOIN User u ON vu.user_id = u.user_id
                JOIN Page p ON v.page_id = p.page_id
                ORDER BY v.timestamp DESC;
            ";

            $result = $mysqli->query($query);

        ?>
        <!DOCTYPE html>
        <html lang="en">


        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title>Visit Tracking</title>
            <!-- Favicon icon -->
            <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
            <!-- Custom Stylesheet -->
            <link href="css/style.css" rel="stylesheet">

            <!-- jqueries + datatables.net -->
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
            <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.css" />
            <script src="https://cdn.datatables.net/2.3.0/js/dataTables.js"></script>

            <script>
            document.addEventListener("DOMContentLoaded", function() {
                let table = new DataTable('#visitsTable', {
                    order: [
                        [3, 'desc']
                    ]
                });

            });
            </script>

        </head>

        <body>

            <!--*******************
                Preloader start
            ********************-->
            <div id="preloader">
                <div class="loader">
                    <svg class="circular" viewBox="25 25 50 50">
                        <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="3"
                            stroke-miterlimit="10" />
                    </svg>
                </div>
            </div>
            <!--*******************
                Preloader end
            ********************-->


            <!--**********************************
                Main wrapper start
            ***********************************-->
            <div id="main-wrapper">

                <!--**********************************
                Nav header start
            ***********************************-->
                <div class="nav-header">
                    <div class="brand-logo">
                        <a href="index.php">
                            <b class="logo-abbr"><img src="images/irp.png" alt=""> </b>
                            <span class="logo-compact"><img src="./images/irp.png" alt=""></span>
                            <span class="brand-title">
                                <img src="images/irp.png" alt="">
                            </span>
                        </a>
                    </div>
                </div>
                <!--**********************************
                Nav header end
            ***********************************-->

                <!--**********************************
            Header start
            ***********************************-->
                <div class="header">
                    <div class="header-content clearfix">

                        <div class="nav-control">
                            <div class="hamburger">
                                <span class="toggle-icon"><i class="icon-menu"></i></span>
                            </div>
                        </div>
                        <div class="header-left">
                            <div class="welcome-message">
                                <span>
                                    Welcome,
                                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                                    Role:
                                    <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>
                                </span>
                            </div>
                        </div>
                        <div class="header-right">
                            <div class="c-pointer position-relative mt-3 mr-3">
                                <a href="logout.php" class="btn btn-outline-danger w-100"
                                    onclick="return confirm('Are you sure you want to logout?');">
                                    <i class="icon-key"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!--**********************************
                Header end ti-comment-alt
            ***********************************-->

                <!--**********************************
                    Sidebar start
                ***********************************-->
                <div class="nk-sidebar">
                    <div class="nk-nav-scroll">
                        <ul class="metismenu" id="menu">
                            <li class="nav-label">Dashboard</li>
                            <li>
                                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                                    <i class="icon-speedometer menu-icon"></i><span class="nav-text">Dashboard</span>
                                </a>
                                <ul aria-expanded="false">
                                    <li><a href="./index.php">Home</a></li>
                                </ul>
                            </li>

                            <li class="nav-label">Incident Reporting</li>
                            <li>
                                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                                    <i class="icon-note menu-icon"></i><span class="nav-text">Incident Reporting</span>
                                </a>
                                <ul aria-expanded="false">
                                    <li><a href="./create-report.php">Create a report</a></li>
                                </ul>
                            </li>

                            <li class="nav-label">Incident Management</li>
                            <li>
                                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                                    <i class="icon-note menu-icon"></i><span class="nav-text">Incident Tracking</span>
                                </a>
                                <ul aria-expanded="false">
                                    <li><a href="./your-reports.php">See your reports</a></li>
                                    <li><a href="./all-reports.php">See all reports</a></li>
                                    <!-- For responders and admins -->
                                </ul>

                                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                                    <i class="icon-graph menu-icon"></i><span class="nav-text">Incident Analytics</span>
                                </a>
                                <ul aria-expanded="false">
                                    <li><a href="./reports-chart.php">See reports chart</a></li>
                                </ul>
                            </li>


                            <li class="nav-label">Page visit tracking</li> <!-- Admin ONLY -->
                            <li>
                                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                                    <i class="icon-note menu-icon"></i><span class="nav-text">Page visit tracking</span>
                                </a>
                                <ul aria-expanded="false">
                                    <li><a href="./visit-tracking.php">Visit tracking</a></li>
                                </ul>
                            </li>

                            <li class="nav-label">User management</li> <!-- Admin ONLY -->
                            <li>
                                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                                    <i class="icon-note menu-icon"></i><span class="nav-text">User management</span>
                                </a>
                                <ul aria-expanded="false">
                                    <li><a href="./create-user.php">Add new user</a></li>
                                    <li><a href="./all-users.php">All users</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
                <!--**********************************
                    Sidebar end
                ***********************************-->

                <!--**********************************
                    Content body start
                ***********************************-->
                <div class="content-body">

                    <div class="container-fluid">
                        <div class="row justify-content-center">
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="card-title">User Visit Tracking</h4>
                                        <div class="table-responsive">
                                            <table id="visitsTable" class="table table-striped table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>User ID</th>
                                                        <th>Username</th>
                                                        <th>Page Visited</th>
                                                        <th>Timestamp</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if ($result && $result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo "<tr>";
                                                            echo "<td>" . (!is_null($row['user_id']) ? htmlspecialchars($row['user_id']) : "-") . "</td>";
                                                            echo "<td>" . (!is_null($row['username']) ? htmlspecialchars($row['username']) : "-") . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['url']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($row['timestamp']) . "</td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4'>No visit records found.</td></tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- #/ container -->
                </div>
                <!--**********************************
                    Content body end
                ***********************************-->


                <!--**********************************
                    Footer start
                ***********************************-->
                <div class="footer">
                    <div class="copyright">
                        <p>Copyright &copy; Designed & Developed by <a
                                href="https://themeforest.net/user/quixlab">Quixlab</a>
                            2018</p>
                    </div>
                </div>
                <!--**********************************
                    Footer end
                ***********************************-->
            </div>
            <!--**********************************
                Main wrapper end
            ***********************************-->

            <!--**********************************
                Scripts
            ***********************************-->
            <script src="plugins/common/common.min.js"></script>
            <script src="js/custom.min.js"></script>
            <script src="js/settings.js"></script>
            <script src="js/gleek.js"></script>
            <script src="js/styleSwitcher.js"></script>

            <script src="./plugins/validation/jquery.validate.min.js"></script>
            <script src="./plugins/validation/jquery.validate-init.js"></script>

        </body>

        </html>