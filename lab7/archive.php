<?php
// Archive course data from JSON file into database
// Assumes $conn is a mysqli connection object

// Function to archive courses from JSON data (array)
function archiveCourses($conn, $data) {
    if (!is_array($data)) {
        return ["success" => false, "message" => "Invalid data format"];
    }
    
    $inserted = 0;
    $errors = [];
    
    // Check if data has courses array
    if (isset($data['courses']) && is_array($data['courses'])) {
        // Check if course_content column exists, if not we'll use basic insert
        $checkContent = $conn->query("SHOW COLUMNS FROM courses LIKE 'course_content'");
        $hasContent = $checkContent && $checkContent->num_rows > 0;
        
        if ($hasContent) {
            $stmt = $conn->prepare("INSERT INTO courses (crn, prefix, number, title, course_content) VALUES (?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE prefix=VALUES(prefix), number=VALUES(number), title=VALUES(title), course_content=VALUES(course_content)");
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (crn, prefix, number, title) VALUES (?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE prefix=VALUES(prefix), number=VALUES(number), title=VALUES(title)");
        }
        
        foreach ($data['courses'] as $course) {
            if (isset($course['crn']) && isset($course['prefix']) && isset($course['number']) && isset($course['title'])) {
                // Extract content if it exists (lectures, labs, etc.)
                $contentJson = null;
                if (isset($course['lectures']) || isset($course['labs']) || isset($course['content'])) {
                    $contentData = [];
                    if (isset($course['lectures'])) $contentData['lectures'] = $course['lectures'];
                    if (isset($course['labs'])) $contentData['labs'] = $course['labs'];
                    if (isset($course['content'])) $contentData = $course['content'];
                    $contentJson = json_encode($contentData);
                }
                
                if ($hasContent && $contentJson) {
                    $stmt->bind_param("isiss", $course['crn'], $course['prefix'], $course['number'], $course['title'], $contentJson);
                } else {
                    $stmt->bind_param("isis", $course['crn'], $course['prefix'], $course['number'], $course['title']);
                }
                
                if ($stmt->execute()) {
                    $inserted++;
                } else {
                    $errors[] = "Error inserting course CRN {$course['crn']}: " . $stmt->error;
                }
            }
        }
        $stmt->close();
    }
    
    // Check if data has students array
    if (isset($data['students']) && is_array($data['students'])) {
        $stmt = $conn->prepare("INSERT INTO students (RIN, RCSID, first_name, last_name, alias, phone) 
                                VALUES (?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE RCSID=VALUES(RCSID), first_name=VALUES(first_name), 
                                last_name=VALUES(last_name), alias=VALUES(alias), phone=VALUES(phone)");
        
        foreach ($data['students'] as $student) {
            if (isset($student['RIN']) && isset($student['first_name']) && isset($student['last_name']) && isset($student['alias'])) {
                $rcsid = isset($student['RCSID']) ? $student['RCSID'] : null;
                $phone = isset($student['phone']) ? $student['phone'] : null;
                $stmt->bind_param("issssi", $student['RIN'], $rcsid, $student['first_name'], 
                                 $student['last_name'], $student['alias'], $phone);
                if ($stmt->execute()) {
                    $inserted++;
                } else {
                    $errors[] = "Error inserting student RIN {$student['RIN']}: " . $stmt->error;
                }
            }
        }
        $stmt->close();
    }
    
    return [
        "success" => true,
        "inserted" => $inserted,
        "errors" => $errors
    ];
}

// Example usage (uncomment and modify as needed):
/*
// Assuming you have a database connection $conn
$result = archiveCourses($conn, 'data.json');
if ($result['success']) {
    echo "Successfully archived {$result['inserted']} records.";
    if (!empty($result['errors'])) {
        echo "<br>Errors: " . implode("<br>", $result['errors']);
    }
} else {
    echo "Error: " . $result['message'];
}
*/
?>

