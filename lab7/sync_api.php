<?php
// API endpoint to sync JSON file to database
require_once 'db_connect.php';
require_once 'archive.php';

if (!isset($conn)) {
    header('Location: viewer.php?error=db_connection');
    exit;
}

$jsonFile = 'course_content.json';

if (!file_exists($jsonFile)) {
    header('Location: viewer.php?error=file_not_found');
    exit;
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header('Location: viewer.php?error=json_parse');
    exit;
}

$result = archiveCourses($conn, $data);

if ($result['success']) {
    header('Location: viewer.php?sync=success');
} else {
    header('Location: viewer.php?error=sync_failed&msg=' . urlencode($result['message']));
}
exit;
?>

