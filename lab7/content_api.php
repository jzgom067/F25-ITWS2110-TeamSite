<?php
// API endpoint for fetching course content
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Database connection not found"]);
    exit;
}

// Fetch courses with their JSON content
$sql = "SELECT crn, prefix, number, title, course_content FROM courses WHERE course_content IS NOT NULL";
$result = $conn->query($sql);

$courses = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $content = json_decode($row['course_content'], true);
        if ($content) {
            $courses[] = [
                'crn' => $row['crn'],
                'prefix' => $row['prefix'],
                'number' => $row['number'],
                'title' => $row['title'],
                'content' => $content
            ];
        }
    }
}

echo json_encode(["success" => true, "courses" => $courses]);
?>

