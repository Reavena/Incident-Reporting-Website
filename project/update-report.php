<?php
    session_name('ResponsePortal');
    session_start();
    require_once 'template.php';
    require_once 'visit-tracker.php';
    
    if (!isset($_SESSION["user_id"])) {
        header("Location:page-login.php");
        exit();
    }
    
    
    if (isset($_GET['incident_id'])) {
        $incidentId = intval($_GET['incident_id']);
    } elseif (isset($_POST['incident_id'])) {
        $incidentId = intval($_POST['incident_id']);
    } else {
        echo 'No incident ID provided.';
        exit;
    }
    
    $role = $_SESSION['role'];
    $incidentId = intval($_POST['incident_id']);


    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__.'/php_errors.log');
    
    
    if (!isset($mysqli)) {
        die("Database connection not initialized.");
    }
    
    //var_dump($_SESSION);
    
    // Verify database connection
    if (mysqli_connect_errno()) {
        error_log("Database connection failed: " . mysqli_connect_error());
        die("System error: Unable to connect to database. Please try again later.");
    }
       
    
    // Initialize variables
    $errors = [];
    $comment = '';
    $status = 0;
    $affectedAssets = [];

    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("FILES: " . print_r($_FILES, true));
        try {
            // Validate and sanitize inputs
            $comment = $mysqli->real_escape_string($_POST['comment'] ?? '');
            $status = filter_input(INPUT_POST, "status", FILTER_VALIDATE_INT);
            $affectedAssets = $_POST['affected_assets'] ?? [];        
    
    
    
            // Proceed if no errors
            if (empty($errors)) {
                $currentDateTime = date('Y-m-d H:i:s');
                $userId = $_SESSION['user_id'];
    
                $mysqli->begin_transaction();
    
                // 1. Insert into IncidentStatus table
                $stmt = $mysqli->prepare("INSERT INTO IncidentStatus (incident_id, status_id, updated_by, updated_at) 
                                        VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception("Update : Prepare 1 failed: " . $mysqli->error);
                }
                
                $stmt->bind_param('iiis', $incidentId, $status, $userId, $currentDateTime);
                if (!$stmt->execute()) {
                    throw new Exception("Update : Execute 1 failed: " . $stmt->error);
                }
                $incidentStatusId = $mysqli->insert_id;
                $stmt->close();
    
                // 2. Insert affected assets
                if (!empty($affectedAssets)) 
                {
                    $stmt = $mysqli->prepare("INSERT INTO AffectedAsset (incident_status_id, asset_id) 
                                            VALUES (?, ?)");
                    if (!$stmt) {
                        throw new Exception("Update : Prepare 2 failed: " . $mysqli->error);
                    }
                    
                    foreach ($affectedAssets as $assetId) {
                        $stmt->bind_param('ii', $incidentStatusId, $assetId);
                        if (!$stmt->execute()) {
                            throw new Exception("Update :Execute 2 failed: " . $stmt->error);
                        }
                    }
                    $stmt->close();
                }

                // 3. Insert Comment
                if(!empty($comment))
                {
                    $stmt = $mysqli->prepare("INSERT INTO Comment (incident_status_id, comment) 
                                                VALUES (?, ?)");
                    $stmt->bind_param('is', $incidentStatusId, $comment);
                    $stmt->execute();
                    $stmt->close();

                }


    
                // 4. Insert Attachments 
                if (!empty($_FILES['attachments']['name'][0])) {
                    $base_upload_dir = __DIR__ . '/uploads/';
                    $incident_upload_dir = $base_upload_dir . 'incident_' . $incidentId . '/';
                    
                    if (!file_exists($incident_upload_dir)) {
                        mkdir($incident_upload_dir, 0755, true);
                    }
                
                    $file_count = min(count($_FILES['attachments']['name']), 5);
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
                    $max_size = 1 * 1024 * 1024; // 1MB
                
                    for ($i = 0; $i < $file_count; $i++) {
                        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
                
                        $original_name = $_FILES['attachments']['name'][$i];
                        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                        $safe_name = preg_replace('/[^a-z0-9\.\-]/i', '_', $original_name);
                        $target_path = $incident_upload_dir . $safe_name;

                        // Get filename without extension
                        $filename = pathinfo($safe_name, PATHINFO_FILENAME);
                        
                        //duplicate files logic
                        $counter = 1;
                        while (file_exists($target_path)) 
                        {
                            $target_path = $incident_upload_dir . $filename . '(' . $counter . ').' . $file_ext;
                            $counter++;
                        }
                
                        if (in_array($file_ext, $allowed_types) && 
                            $_FILES['attachments']['size'][$i] <= $max_size &&
                            move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $target_path)) //checks if POSTED anmd moves from tmp to uploads
                            {
                            
                            // with og name
                            $relative_path = 'uploads/incident_' . $incidentId . '/' . basename($target_path);

                            $stmt = $mysqli->prepare("INSERT INTO Attachment (incident_status_id, path) VALUES (?, ?)");
                            $stmt->bind_param('is', $incidentStatusId, $relative_path);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
    
                $mysqli->commit();

                header("Location: your-reports.php");
                exit();
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Database error: " . $e->getMessage());
            $errors[] = "A system error occurred. Please try again.";
        }
    }
    
    
    ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Update Report</title>
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
                            <li><a href="./all-reports.php">See all reports</a></li> <!-- For responders and admins -->
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
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Incident Reporting</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Update report</a></li>
                    </ol>
                </div>
            </div>
            <!-- row -->

            <div class="container-fluid">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            <!-- Display errors if any -->
                            <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>


                            <h4 class="card-title">Update Report for Incident
                                #<?php echo htmlspecialchars($_GET['incident_id']); ?></h4>
                            <div class="basic-form">
                                <form action="update-report.php" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="incident_id"
                                        value="<?php echo htmlspecialchars($_GET['incident_id']); ?>">

                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Add comment</label>
                                        <div class="col-sm-10">
                                            <textarea class="form-control h-150px" rows="6" id="comment"
                                                name="comment"></textarea>
                                        </div>
                                    </div>

                                    <!-- Only for admins and reporters -->
                                    <?php if ($role == 'Administrator' || $role == 'Responder') : ?>
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Incident status</label>
                                        <div class="form-group">

                                            <div class="radio mb-3">
                                                <label>
                                                    <input type="radio" name="status" value="1" id="pending" required>
                                                    <span class="label gradient-1 ml-2 btn-rounded">Pending
                                                        (1)</span></label>
                                            </div>

                                            <div class="radio mb-3">
                                                <label>
                                                    <input type="radio" name="status" value="2" id="inProgress"
                                                        required>
                                                    <span class="label gradient-2 ml-2 btn-rounded">In Progress
                                                        (2)</span></label>
                                            </div>
                                            <div class="radio mb-3">
                                                <label>
                                                    <input type="radio" name="status" value="3" id="resolved">
                                                    <span class="label gradient-3 ml-2 btn-rounded">Resolved
                                                        (3)</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label"> Affected Assets</label>
                                        <div class="col-sm-10">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="affected_assets[]"
                                                    value="1" id="asset1">
                                                <label class="form-check-label" for="asset1">Router A1</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="affected_assets[]"
                                                    value="2" id="asset2">
                                                <label class="form-check-label" for="asset2">Server B2</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="affected_assets[]"
                                                    value="3" id="asset3">
                                                <label class="form-check-label" for="asset3">Firewall X</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Attachment</label>
                                        <div class="col-sm-10">
                                            <input type="file" name="attachments[]" multiple class="form-control-file">
                                            <small class="form-text text-muted">Max 1MB (PDF, JPG, PNG, DOCX). Max 5
                                                attachments. </small>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-sm-10">
                                            <button type="submit" class="btn btn-primary">Update Report</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
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
            <p>Copyright &copy; Designed & Developed by <a href="https://themeforest.net/user/quixlab">Quixlab</a> 2018
            </p>
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

</body>

</html>