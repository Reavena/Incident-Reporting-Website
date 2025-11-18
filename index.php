<?php
    require_once 'visit-tracker.php';
    require_once 'template.php';

    session_name('ResponsePortal');
    session_start();
    
    if (!isset($_SESSION["user_id"])) {
        header("Location:page-login.php");
        exit();
    }

	
	
	$role = $_SESSION['role'];
    //var_dump($_SESSION);

    $dataUsers = [];
    $resultUsers = $mysqli->query("SELECT role, COUNT(*) as count FROM User u JOIN Role r ON u.role_id = r.role_id WHERE u.user_id > 0 GROUP BY role");

    while ($row = $resultUsers->fetch_assoc()) {
        $dataUsers['labels'][] = $row['role'];
        $dataUsers['counts'][] = $row['count'];
    }

    $dataReportStatus = [
        'labels' => [],
        'counts' => []
    ];
    
    $query = "
        SELECT st.status, COUNT(*) as quantity 
        FROM IncidentStatus s 
        JOIN Status st ON s.status_id = st.status_id 
        GROUP BY s.status_id
    ";
    
    $resultReportStatus = $mysqli->query($query);
    while ($row = $resultReportStatus->fetch_assoc()) {
        $dataReportStatus['labels'][] = $row['status'];
        $dataReportStatus['counts'][] = $row['quantity'];
    }

    $dataVisits = [];

    $queryVisitsPerHour = "
        SELECT 
        DATE_FORMAT(v.timestamp, '%Y-%m-%d %H:00:00') AS hour,
        p.url,
        COUNT(*) AS visit_count
        FROM VisitUser vu
        JOIN Visit v ON vu.visit_id = v.visit_id 
        JOIN User u ON vu.user_id = u.user_id
        JOIN Page p ON v.page_id = p.page_id
        WHERE v.timestamp >= NOW() - INTERVAL 24 HOUR
        GROUP BY hour, p.url
        ORDER BY hour ASC;
    ";

    $resultVisits = $mysqli->query($queryVisitsPerHour);

    $labels = [];
    $dataByPage = [];

    while ($row = $resultVisits->fetch_assoc()) {
        $hour = $row['hour'];
        $url = $row['url'];
        $count = (int)$row['visit_count'];

        if (!in_array($hour, $labels)) {
            $labels[] = $hour;
        }

        if (!isset($dataByPage[$url])) {
            $dataByPage[$url] = [];
        }

        $dataByPage[$url][$hour] = $count;
    }

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <!-- theme meta -->
    <meta name="theme-name" content="quixlab" />

    <title> Incidents Reports Portal </title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <!-- Pignose Calender -->
    <link href="./plugins/pg-calendar/css/pignose.calendar.min.css" rel="stylesheet">
    <!-- Chartist -->
    <link rel="stylesheet" href="./plugins/chartist/css/chartist.min.css">
    <link rel="stylesheet" href="./plugins/chartist-plugin-tooltips/css/chartist-plugin-tooltip.css">
    <!-- Custom Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

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
                        <img class="irp" src="images/irp.png" alt="">
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
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Dashboard</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Home</a></li>
                    </ol>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" style="height: 500px;">
                                <h4 class="card-title text-center">Team</h4>
                                <div class="d-flex justify-content-center align-items-center"
                                    style="height: calc(100% - 40px);">
                                    <canvas id="userChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" style="height: 500px;">
                                <h4 class="card-title text-center">Reports by status</h4>
                                <div class="d-flex justify-content-center align-items-center"
                                    style="height: calc(100% - 40px);">
                                    <canvas id="statusDonutChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($role == 'Administrator') : ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" style="height: 500px;">
                                <h4 class="card-title text-center">Visits per hour</h4>
                                <div class="d-flex justify-content-center align-items-center"
                                    style="height: calc(100% - 40px);">
                                    <canvas id="visitsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- #/ container -->
            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Count users
    const userChartDOM = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(userChartDOM, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dataUsers['labels']) ?>,
            datasets: [{
                label: 'Users by Role',
                data: <?= json_encode($dataUsers['counts']) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Count reports by status
    const statusDonutChartDOM = document.getElementById('statusDonutChart').getContext('2d');
    const statusDonutChart = new Chart(statusDonutChartDOM, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($dataReportStatus['labels']) ?>,
            datasets: [{
                label: 'Reports by Status',
                data: <?= json_encode($dataReportStatus['counts']) ?>,
                backgroundColor: [
                    '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6c757d'
                ],
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Incident Reports by Status'
                }
            }
        }
    });

    // Visits per hour
    const visitsLabels = <?= json_encode($labels) ?>;
    const rawDataByPage = <?= json_encode($dataByPage) ?>;

    const visitsDatasets = Object.keys(rawDataByPage).map((url) => {
        const pageData = rawDataByPage[url];
        return {
            label: url,
            data: visitsLabels.map(label => pageData[label] || 0),
            borderColor: '#' + Math.floor(Math.random() * 16777215).toString(16),
            fill: false,
            cubicInterpolationMode: 'monotone',
            tension: 0.4
        };
    });

    const visitsConfig = {
        type: 'line',
        data: {
            labels: visitsLabels,
            datasets: visitsDatasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Page Visits Per Hour'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Hour'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Visits'
                    }
                }
            }
        }
    };

    new Chart(document.getElementById('visitsChart').getContext('2d'), visitsConfig);
    </script>
    <script src="plugins/common/common.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/gleek.js"></script>
    <script src="js/styleSwitcher.js"></script>

    <!-- Chartjs -->
    <script src="./plugins/chart.js/Chart.bundle.min.js"></script>
    <!-- Circle progress -->
    <script src="./plugins/circle-progress/circle-progress.min.js"></script>
    <!-- Datamap -->
    <script src="./plugins/d3v3/index.js"></script>
    <script src="./plugins/topojson/topojson.min.js"></script>
    <script src="./plugins/datamaps/datamaps.world.min.js"></script>
    <!-- Morrisjs -->
    <script src="./plugins/raphael/raphael.min.js"></script>
    <script src="./plugins/morris/morris.min.js"></script>
    <!-- Pignose Calender -->
    <script src="./plugins/moment/moment.min.js"></script>
    <script src="./plugins/pg-calendar/js/pignose.calendar.min.js"></script>
    <!-- ChartistJS -->
    <script src="./plugins/chartist/js/chartist.min.js"></script>
    <script src="./plugins/chartist-plugin-tooltips/js/chartist-plugin-tooltip.min.js"></script>



    <script src="./js/dashboard/dashboard-1.js"></script>

</body>

</html>