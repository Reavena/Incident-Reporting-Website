<?php
session_name('ResponsePortal');
session_start();


if (!isset($_SESSION["user_id"])) {
    header("Location: page-login.php");
    exit();
}

require_once("template.php");
require_once 'visit-tracker.php';

$role = $_SESSION['role'];


// query
$incidentId = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : 0;

if ($incidentId <= 0) {
    echo "Invalid incident ID.";
    exit;
}

// Query : Find all IncidentStatus ID
$incidentStatusQuery = "SELECT incident_status_id,updated_at 
                FROM IncidentStatus 
                WHERE incident_id = ? 
                ORDER BY updated_at DESC";

$stmt = $mysqli->prepare($incidentStatusQuery);
$stmt->bind_param('i', $incidentId);
$stmt->execute();

$incidentStatusResult = $stmt->get_result();

$incidentStatusRecords = [];

// Query : find all comments for each IncidentStatus

while ($incidentStatus = $incidentStatusResult->fetch_assoc())
{
    $incidentStatusId = $incidentStatus['incident_status_id'];

    $commentQuery = "SELECT comment from Comment WHERE incident_status_id = ?";
    $commentStmt = $mysqli->prepare($commentQuery);
    $commentStmt->bind_param('i' ,$incidentStatusId);
    $commentStmt->execute();
    $commentResult = $commentStmt->get_result();

    $comments=[];
    while($comment = $commentResult->fetch_assoc())
    {
        $comments[]=$comment['comment'];
    }

    $commentStmt->close();

// Query : find all files for each IncidentStatus
    $incidentStatusId = $incidentStatus['incident_status_id'];
    
    $fileQuery = "SELECT path FROM Attachment WHERE incident_status_id = ?";
    $fileStmt = $mysqli->prepare($fileQuery);
    $fileStmt->bind_param('i', $incidentStatusId);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    
    $files = [];
    while ($file = $fileResult->fetch_assoc()) 
    {
        $files[] = $file['path'];
    }
    
    $incidentStatusRecords[] = [
        'incident_status_id' => $incidentStatus['incident_status_id'],
        'updated_at' => $incidentStatus['updated_at'],
        'files' => $files,
        'comments'=>$comments
    ];
    
    $fileStmt->close();
}

$stmt->close();
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
                <a href="index.html">
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
        Header end
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
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Files for Incident #<?= htmlspecialchars($incidentId) ?></h4>

                                <?php if (!empty($incidentStatusRecords)): ?>
                                <?php foreach ($incidentStatusRecords as $record): ?>
                                <div class="status-section mb-4">
                                    <h5>
                                        Incident Status ID: <?= htmlspecialchars($record['incident_status_id']) ?>
                                        Updated at: <?= htmlspecialchars($record['updated_at']) ?>
                                    </h5>
                                    <div class="basic-list-group">
                                        <ul class="list-group">
                                            <?php if(!empty($record['files'])): ?>
                                            <?php foreach($record['files'] as $file): ?>
                                            <li class="list-group-item">
                                                <a href="<?= htmlspecialchars($file) ?>" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <?= htmlspecialchars(basename($file)) ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <li class="list-group-item">No files found for this update.</li>
                                            <?php endif; ?>

                                        </ul>
                                    </div>
                                    <?php if(!empty($record['comments'])): ?>
                                    <?php foreach($record['comments'] as $comment): ?>
                                    <div class="alert alert-secondary" role="comment">
                                        <?= nl2br(htmlspecialchars($comment)) ?>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <div class="alert alert-secondary" role="alert">
                                        No comment for this update.
                                    </div>
                                    <?php endif; ?>

                                </div>
                                <?php endforeach; ?>

                                <?php else: ?>
                                <div class="alert alert-info">No records found for this report.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->

        <!--**********************************
        Main wrapper end
    ***********************************-->
    </div>
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