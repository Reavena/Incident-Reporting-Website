<?php
require_once 'visit-tracker.php';
require_once 'template.php';

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

$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);

$roleMap = [
    'administrator' => 1,
    'incident-reporter' => 2,
    'incident-responder' => 3
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['first-name', 'last-name', 'username', 'email', 'password', 'role'];
    $missing = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        echo '<div class="alert alert-danger text-center" role="alert">';
        echo 'Missing fields: ' . implode(', ', $missing);
        echo '</div>';
        exit;
    }

    $first_name = $mysqli->real_escape_string($_POST['first-name']);
    $last_name = $mysqli->real_escape_string($_POST['last-name']);
    $username = $mysqli->real_escape_string($_POST['username']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = $mysqli->real_escape_string($_POST['password']);
    $role_str = $_POST['role'];
    $role_id = $roleMap[$role_str] ?? null;

    if ($role_id === null) {
        echo '<div class="alert alert-danger text-center" role="alert">Invalid role selected.</div>';
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = <<<END
        INSERT INTO User(username, password, email, first_name, last_name, role_id)
        VALUES('$username', '$hashed_password', '$email', '$first_name', '$last_name', $role_id)
    END;

    if ($mysqli->query($query) !== TRUE) {
        echo '<div class="alert alert-danger text-center" role="alert">
            Could not register user: ' . $mysqli->error . '
        </div>';
    } else {
        echo '<div class="alert alert-success text-center" role="alert">
            User registered successfully!
        </div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Create User</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <!-- Custom Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

</head>

<body>
    <script>
    setTimeout(function() {
        let alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 500); // remove from DOM
        }
    }, 3000); // 3 seconds
    </script>

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
                                <h4 class="card-title">Create user</h4>
                                <div class="form-validation">
                                    <form class="form-valide needs-validation" method="post" action="create-user.php"
                                        novalidate>
                                        <div class="form-group row">
                                            <label class="col-lg-4 col-form-label" for="first-name">First name <span
                                                    class="text-danger">*</span>
                                            </label>
                                            <div class="col-lg-6">
                                                <input type="text" class="form-control" id="first-name"
                                                    name="first-name" placeholder="Enter the first name..." required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-4 col-form-label" for="last-name">Last name <span
                                                    class="text-danger">*</span>
                                            </label>
                                            <div class="col-lg-6">
                                                <input type="text" class="form-control" id="last-name" name="last-name"
                                                    placeholder="Enter the last name..." required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-4 col-form-label" for="username">Username <span
                                                    class="text-danger">*</span>
                                            </label>
                                            <div class="col-lg-6">
                                                <input type="text" class="form-control" id="username" name="username"
                                                    placeholder="Enter a username..." required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-4 col-form-label" for="email">Email <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-lg-6">
                                                <input type="email" class="form-control" id="email" name="email"
                                                    placeholder="Enter a valid email..." required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-4 col-form-label" for="password">Password <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-lg-6">
                                                <input type="password" class="form-control" id="password"
                                                    name="password" placeholder="Enter a password..." required
                                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[-@$!%*?&])[A-Za-z\d\-@$!%*?&]{8,}$"
                                                    title="Password must be at least 8 characters long and include uppercase, lowercase, a number, and a special character.">
                                                <small class="form-text text-muted">
                                                    Must be at least 8 characters, with uppercase, lowercase, number,
                                                    and special character.
                                                </small>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-4 col-form-label" for="role">Role <span
                                                    class="text-danger">*</span>
                                            </label>
                                            <div class="col-lg-6">
                                                <select class="form-control" id="role" name="role" required>
                                                    <option value="">Please select</option>
                                                    <option value="administrator">Administrator</option>
                                                    <option value="incident-reporter">Incident Reporter</option>
                                                    <option value="incident-responder">Incident Responder</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-sm-10">
                                                <button type="submit" class="btn btn-primary">Create</button>
                                            </div>
                                        </div>
                                    </form>
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
    <script>
    // custom validation
    (function() {
        'use strict';
        const form = document.querySelector('.needs-validation');

        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    })();
    </script>

    <script src="plugins/common/common.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/settings.js"></script>
    <script src="js/gleek.js"></script>
    <script src="js/styleSwitcher.js"></script>

    <script src="./plugins/validation/jquery.validate.min.js"></script>
    <script src="./plugins/validation/jquery.validate-init.js"></script>

</body>

</html>