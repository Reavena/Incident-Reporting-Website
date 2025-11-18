<?php
include('template.php');

function get_clean_browser_name($userAgent) {
	$userAgent = strtolower($userAgent);

	if (strpos($userAgent, 'edg') !== false) return 'Edge';
	if (strpos($userAgent, 'chrome') !== false && strpos($userAgent, 'edg') === false) return 'Chrome';
	if (strpos($userAgent, 'firefox') !== false) return 'Firefox';
	if (strpos($userAgent, 'safari') !== false && strpos($userAgent, 'chrome') === false) return 'Safari';
	if (strpos($userAgent, 'opera') !== false || strpos($userAgent, 'opr') !== false) return 'Opera';
	if (strpos($userAgent, 'msie') !== false || strpos($userAgent, 'trident') !== false) return 'Internet Explorer';

	return 'Other';
}

$visitData = !empty($_POST) ? $_POST : [
	'url' => $_SERVER['REQUEST_URI'],
	'ip' => $_SERVER['REMOTE_ADDR'],
	'browser' => get_clean_browser_name($_SERVER['HTTP_USER_AGENT']),
	'time' => date('Y-m-d H:i:s')
];

if (isset($_SESSION['user_id'])) {
	$visitData['user_id'] = $_SESSION['user_id'];
}

$ch = curl_init('https://luccou25.ddi.hh.se/project/visits-log.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($visitData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if ($response === false) {
	error_log('cURL error: ' . curl_error($ch));
}
curl_close($ch);

?>