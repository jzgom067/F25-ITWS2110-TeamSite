<?php
// API endpoint to sync JSON file to database
require_once 'db_connect.php';
require_once 'archive.php';

if (!isset($conn)) {
    header('Location: index.php?error=db_connection');
    exit;
}

$jsonFile = 'course_content.json';

if (!file_exists($jsonFile)) {
    header('Location: index.php?error=file_not_found');
    exit;
}

$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header('Location: index.php?error=json_parse');
    exit;
}

// Convert websys_course structure to courses format for archiving
if (isset($data['websys_course']) && is_array($data['websys_course']) && count($data['websys_course']) > 0) {
    // Find or create ITWS 2110 course entry
    $websysCourse = $data['websys_course'][0];
    
    // Check if ITWS 2110 course exists
    $checkSql = "SELECT crn FROM courses WHERE prefix = 'ITWS' AND number = 2110 LIMIT 1";
    $checkResult = $conn->query($checkSql);
    
    $crn = null;
    if ($checkResult && $checkResult->num_rows > 0) {
        $row = $checkResult->fetch_assoc();
        $crn = $row['crn'];
    } else {
        // Create a default CRN if course doesn't exist
        $crn = 12345; // You may want to set this to the actual CRN
    }
    
    // Prepare course data with websys_course structure
    $courseData = [
        'crn' => $crn,
        'prefix' => 'ITWS',
        'number' => 2110,
        'title' => 'Web Systems Development',
        'course_content' => json_encode($data) // Store the entire JSON structure
    ];
    
    // Update or insert the course with course_content
    $checkContent = $conn->query("SHOW COLUMNS FROM courses LIKE 'course_content'");
    $hasContent = $checkContent && $checkContent->num_rows > 0;
    
    if ($hasContent) {
        $stmt = $conn->prepare("INSERT INTO courses (crn, prefix, number, title, course_content) VALUES (?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE prefix=VALUES(prefix), number=VALUES(number), title=VALUES(title), course_content=VALUES(course_content)");
        $courseContentJson = json_encode($data);
        $stmt->bind_param("isiss", $courseData['crn'], $courseData['prefix'], $courseData['number'], $courseData['title'], $courseContentJson);
        
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php?sync=success');
            exit;
        } else {
            $stmt->close();
            header('Location: index.php?error=sync_failed&msg=' . urlencode($conn->error));
            exit;
        }
    } else {
        header('Location: index.php?error=sync_failed&msg=course_content column not found');
        exit;
    }
} else {
    // Fallback to original archiveCourses function for other formats
    $result = archiveCourses($conn, $data);
    
    if ($result['success']) {
        header('Location: index.php?sync=success');
    } else {
        header('Location: index.php?error=sync_failed&msg=' . urlencode($result['message']));
    }
}
exit;
?>

