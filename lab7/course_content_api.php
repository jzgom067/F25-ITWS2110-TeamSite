<?php
// API endpoint to serve course_content.json file
header('Content-Type: application/json');

$jsonFile = 'course_content.json';

if (!file_exists($jsonFile)) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "JSON file not found"]);
    exit;
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error parsing JSON: " . json_last_error_msg()]);
    exit;
}

echo json_encode(["success" => true, "data" => $data]);
?>

