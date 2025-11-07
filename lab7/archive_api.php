<?php
// API endpoint for archiving course data via AJAX
require_once 'db_connect.php';
require_once 'archive.php';

header('Content-Type: application/json');

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Database connection not found"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Error parsing JSON: " . json_last_error_msg()]);
    exit;
}

$result = archiveCourses($conn, $data);
echo json_encode($result);
?>

