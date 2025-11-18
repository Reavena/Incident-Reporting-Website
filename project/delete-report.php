<?php
session_name('ResponsePortal');
session_start();

if (!isset($_SESSION["user_id"]) ) { 
    header("Location: page-login.php");
    exit();
}

$role = $_SESSION['role'];

if($role != 'Administrator')
{
    header("Location:page-error-403.html");
    exit();
}

require_once("template.php");

$incidentId = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : 0;
if ($incidentId <= 0) {
    $_SESSION['error'] = "Invalid incident ID";
    header("Location: all-reports.php");
    exit();
}

$incidentId = intval($_GET['incident_id']);

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<script>
        if(confirm('Are you sure you want to delete this report (incident_id = $incidentId) ? This cannot be undone.')) {
            window.location.href = 'delete-report.php?incident_id=$incidentId&confirm=yes';
        } else {
            window.location.href = 'all-reports.php';
        }
    </script>";
    exit();
}

try {
    $mysqli->begin_transaction();


    //1. FILE DELETION
    $fileQuery = "SELECT path FROM Attachment WHERE incident_status_id IN (SELECT incident_status_id FROM IncidentStatus WHERE incident_id = ?)";
    $stmt = $mysqli->prepare($fileQuery);
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $files = $stmt->get_result();
    
    while ($file = $files->fetch_assoc()) {
        $fullPath = __DIR__ . '/' . $file['path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    $stmt->close();

    //2. TABLE DELETION

    $stmt = $mysqli->prepare("DELETE FROM Attachment WHERE incident_status_id IN (SELECT incident_status_id FROM IncidentStatus WHERE incident_id = ?)");
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM AffectedAsset WHERE incident_status_id IN (SELECT incident_status_id FROM IncidentStatus WHERE incident_id = ?)");
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM Comment WHERE incident_status_id IN (SELECT incident_status_id FROM IncidentStatus WHERE incident_id = ?)");
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM IncidentStatus WHERE incident_id = ?");
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM Incident WHERE incident_id = ?");
    $stmt->bind_param('i', $incidentId);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();
    $_SESSION['success'] = "Report deleted successfully";

} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['error'] = "Delete failed";
    error_log("Delete error: " . $e->getMessage());
}

header("Location: all-reports.php");
exit();
?>