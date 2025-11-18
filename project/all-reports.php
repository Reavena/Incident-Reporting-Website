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
    
    if($role !== 'Administrator' && $role !== 'Responder') 
    {
        header("Location: page-error-403.php");
        exit();
    }

    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__.'/php_errors.log');

    $query = "
        SELECT 
        i.incident_id, 
        i.description, 
        it.incident_type,
        MIN(s.updated_at) AS first_updated_date,
        MAX(s.updated_at) AS last_updated_date, 
        se.severity,
        GROUP_CONCAT(DISTINCT ass.asset_name ORDER BY ass.asset_name SEPARATOR ', ') AS assets,
        st.status
        
        FROM Incident i
        JOIN Severity se ON i.severity = se.severity_id
        JOIN IncidentType it ON it.incident_type_id = i.type_id
        JOIN IncidentStatus s ON i.incident_id = s.incident_id
        JOIN Status st ON st.status_id = s.status_id
        LEFT JOIN AffectedAsset a ON a.incident_status_id = s.incident_status_id
        LEFT JOIN Asset ass ON ass.asset_id = a.asset_id

        GROUP BY 
            i.incident_id, i.description, it.incident_type, se.severity, st.status
        ORDER BY incident_id DESC, last_updated_date DESC;
    ";

    $result = $mysqli->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>All Reports</title>
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
        let table = new DataTable('#allReportsTable', {
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
                <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="3" stroke-miterlimit="10" />
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
                            <li><a href="./index.php">Home 1</a></li>
                            <!-- <li><a href="./index-2.html">Home 2</a></li> -->
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
                            <?php if ($role == 'Responder' || $role == 'Administrator') : ?>
                            <li><a href="./all-reports.php">See all reports</a></li>
                            <?php endif; ?>
                        </ul>

                        <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                            <i class="icon-graph menu-icon"></i><span class="nav-text">Incident Analytics</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="./reports-chart.php">See reports chart</a></li>
                        </ul>
                    </li>


                    <?php if ($role == 'Administrator') : ?>
                    <li class="nav-label">Page visit tracking</li>
                    <li>
                        <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                            <i class="icon-note menu-icon"></i><span class="nav-text">Page visit tracking</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="./visit-tracking.php">Visit tracking</a></li>
                        </ul>
                    </li>

                    <li class="nav-label">User management</li>
                    <li>
                        <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                            <i class="icon-note menu-icon"></i><span class="nav-text">User management</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="./create-user.php">Add new user</a></li>
                            <li><a href="./all-users.php">All users</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>



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

            <div class="row page-titles mx-0">
                <div class="col p-md-0">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">User management</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Add new user</a></li>
                    </ol>
                </div>
            </div>
            <!-- row -->

            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">All Reports</h4>
                                <div class="table-responsive">
                                    <table id="allReportsTable"
                                        class="table table-bordered table-striped verticle-middle">
                                        <thead>
                                            <tr>
                                                <th scope="col">Incident ID</th>
                                                <th scope="col">Description</th>
                                                <th scope="col">Type</th>
                                                <th scope="col">Status Created</th>
                                                <th scope="col">Last Updated</th>
                                                <th scope="col">Severity</th>
                                                <th scope="col">Affected Assets</th>
                                                <th scope="col" style="width: 120px;">Status</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result && $result->num_rows > 0): ?>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['incident_id']) ?></td>
                                                <td><?= htmlspecialchars($row['description']) ?></td>
                                                <td><?= htmlspecialchars($row['incident_type']) ?></td>
                                                <td>
                                                    <?= date("Y,", strtotime($row['first_updated_date'])) ?><br>
                                                    <?= date("M d", strtotime($row['first_updated_date'])) ?><br>
                                                    <?= date("H:i", strtotime($row['first_updated_date'])) ?>
                                                </td>
                                                <td>
                                                    <?= date("Y,", strtotime($row['last_updated_date'])) ?><br>
                                                    <?= date("M d", strtotime($row['last_updated_date'])) ?><br>
                                                    <?= date("H:i", strtotime($row['last_updated_date'])) ?>
                                                </td>
                                                <td><span
                                                        class="label gradient-9 btn-rounded"><?= htmlspecialchars($row['severity']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($row['assets']) ?></td>
                                                <td><span
                                                        class="label gradient-1 btn-rounded"><?= htmlspecialchars($row['status']) ?></span>
                                                </td>
                                                <td>
                                                    <span>

                                                        <a href="view-files.php?incident_id=<?= $row['incident_id'] ?>"
                                                            data-toggle="tooltip" title="View Files and Comments">
                                                            <i class="fa fa-folder-open color-info m-l-5"></i>
                                                        </a>

                                                        <a href="update-report.php?incident_id=<?php echo $row['incident_id']; ?>"
                                                            data-toggle="tooltip" title="Edit">
                                                            <i class="fa fa-pencil color-muted m-r-5"></i>
                                                        </a>
                                                        <a href="delete-report.php?incident_id=<?php echo $row['incident_id']; ?>"
                                                            data-toggle="tooltip" title="Delete">
                                                            <i class="fa fa-close color-danger"></i>
                                                        </a>


                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No reports found.</td>
                                            </tr>
                                            <?php endif; ?>
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
                <p>Copyright &copy; Designed & Developed by <a href="https://themeforest.net/user/quixlab">Quixlab</a>
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