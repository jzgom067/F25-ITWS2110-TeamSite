<?php
// API endpoint to serve course content from database or fallback to JSON file
require_once 'db_connect.php';

header('Content-Type: application/json');

// Try to get data from database if connection exists
if (isset($conn) && $conn !== null) {
    try {
        $sql = "SELECT course_content FROM courses WHERE prefix = 'ITWS' AND number = 2110 LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $courseContent = $row['course_content'];
            $data = json_decode($courseContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && $data !== null) {
                echo json_encode(["success" => true, "data" => $data]);
                exit;
            }
        }
    } catch (Exception $e) {
        // Fall through to JSON file fallback
    }
}

// Fallback to JSON file if database is unavailable or has no data
$jsonFile = 'course_content.json';

if (!file_exists($jsonFile)) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Database unavailable and JSON file not found"]);
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

