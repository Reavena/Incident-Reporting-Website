<?php
file_put_contents('debug_post.txt', var_export($_POST, true), FILE_APPEND);
file_put_contents('log.txt', "visits-log.php hit at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

include('template.php');

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

// echo "<pre>Data received\n";
// print_r($data);
// echo "</pre>";

// Remove '/project' from the beginning of the URL
$rawUrl = $_POST['url'] ?? '';
$cleanUrl = preg_replace('#^/project/?#', '', $rawUrl);

$phpPos = strpos($cleanUrl, '.php');
if ($phpPos !== false) {
    $cleanUrl = substr($cleanUrl, 0, $phpPos + 4);
}


$url = $mysqli->real_escape_string($url);

$ip = $mysqli->real_escape_string($_POST['ip'] ?? '');
$browser = $mysqli->real_escape_string($_POST['browser'] ?? '');
$time = $mysqli->real_escape_string($_POST['time'] ?? date('Y-m-d H:i:s'));
$userId = $mysqli->real_escape_string($_POST['user_id'] ?? null);

// Insert or get Browser
$stmt = $mysqli->prepare("SELECT web_browser_id FROM WebBrowser WHERE web_browser_name = ?");
$stmt->bind_param("s", $browser);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
	$stmt->bind_result($browserId);
	$stmt->fetch();
} else {
	$stmt->close();
	$stmtInsert = $mysqli->prepare("INSERT INTO WebBrowser (name) VALUES (?)");
	$stmtInsert->bind_param("s", $browser);
	$stmtInsert->execute();
	$browserId = $stmtInsert->insert_id;
	$stmtInsert->close();
}
$stmt->close();

// Insert Page
// Find page
if (empty($url)) {
    $url = 'index.php';
}

$pageId = null;
$query = "SELECT page_id FROM Page WHERE url = ?";
$stmt = $mysqli->prepare($query);

if ($stmt) {
	$stmt->bind_param("s", $url);
	$stmt->execute();
	$stmt->bind_result($pageId);
	$stmt->fetch();
	$stmt->close();
} else {
	die("Prepare failed for SELECT Page: " . $mysqli->error);
}

// If not found, insert the page
if (!$pageId) {
	$insertQuery = "INSERT INTO Page (url) VALUES (?)";
	$stmt = $mysqli->prepare($insertQuery);
	if ($stmt) {
		$stmt->bind_param("s", $url);
		if ($stmt->execute()) {
			$pageId = $stmt->insert_id;
		} else {
            file_put_contents('log.txt', $stmt->error . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
			die("Execute failed for INSERT Page: " . $stmt->error);
		}
		$stmt->close();
	} else {
        file_put_contents('log.txt', $mysqli->error . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
		die("Prepare failed for INSERT Page: " . $mysqli->error);
	}
}

// Insert Visit
$visitId = null;
$query = "INSERT INTO Visit (ip_address, timestamp, web_browser_id, page_id) VALUES (?, ?, ?, ?)";
$stmtVisit = $mysqli->prepare($query);

if ($stmtVisit) {
	$stmtVisit->bind_param("ssii", $ip, $time, $browserId, $pageId);
	if ($stmtVisit->execute()) {
		$visitId = $stmtVisit->insert_id;
	} else {
		die("Execute failed for Visit: " . $stmtVisit->error);
	}
	$stmtVisit->close();
} else {
	die("Prepare failed for Visit: " . $mysqli->error);
}

if ($userId !== null && $visitId !== null) {
	$query = "INSERT INTO VisitUser (visit_id, user_id) VALUES (?, ?)";
	$stmtVU = $mysqli->prepare($query);

	if ($stmtVU) {
		$stmtVU->bind_param("ii", $visitId, $userId);
		if (!$stmtVU->execute()) {
            file_put_contents('log.txt', $stmtVU->error . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
			die("Execute failed for VisitUser: " . $stmtVU->error);
		}
		$stmtVU->close();
	} else {
        file_put_contents('log.txt', $mysqli->error . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
		die("Prepare failed for VisitUser: " . $mysqli->error);
	}
}

$mysqli->close();

echo "Logged";
?>