<?php
// Main page - auto-imports course content from JSON and displays it
require_once 'db_connect.php';
require_once 'archive.php';

// Function to drop all tables
function dropTables($conn) {
    $tables = ['grades', 'courses', 'students', 'archive'];
    $dropped = [];
    $errors = [];
    
    try {
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS $table";
            if ($conn->query($sql) === TRUE) {
                $dropped[] = $table;
            } else {
                $errors[] = "Error dropping table $table: " . $conn->error;
            }
        }
    } catch (Exception $e) {
        $errors[] = "Exception dropping tables: " . $e->getMessage();
    }
    
    return [
        "success" => empty($errors),
        "dropped" => $dropped,
        "errors" => $errors
    ];
}

// Function to recreate tables from schema
function recreateTables($conn) {
    $errors = [];
    
    try {
        // Read and execute schema.sql
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $queries = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                if ($conn->query($query) === FALSE) {
                    $errors[] = "Error executing schema query: " . $conn->error;
                }
            }
        }
        
        // Read and execute init.sql if it exists
        if (file_exists(__DIR__ . '/init.sql')) {
            $init = file_get_contents(__DIR__ . '/init.sql');
            $initQueries = array_filter(array_map('trim', explode(';', $init)));
            
            foreach ($initQueries as $query) {
                if (!empty($query)) {
                    if ($conn->query($query) === FALSE) {
                        $errors[] = "Error executing init query: " . $conn->error;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Exception recreating tables: " . $e->getMessage();
    }
    
    return [
        "success" => empty($errors),
        "errors" => $errors
    ];
}

// Handle reset table action
$resetSuccess = false;
$resetError = '';
$resetDbError = false;
if (isset($_POST['action']) && $_POST['action'] === 'reset' && isset($conn)) {
    try {
        $dropResult = dropTables($conn);
        $createResult = recreateTables($conn);
        if ($createResult['success']) {
            $resetSuccess = true;
        } else {
            $resetError = implode('<br>', $createResult['errors']);
            $resetDbError = true;
        }
    } catch (Exception $e) {
        $resetError = "Exception during reset: " . $e->getMessage();
        $resetDbError = true;
    }
}

// Auto-import course content from JSON file on page load
$dbError = false;
$dbErrorMessage = '';
if (isset($conn)) {
    try {
        $jsonFile = 'course_content.json';
        if (file_exists($jsonFile)) {
            $jsonData = file_get_contents($jsonFile);
            $data = json_decode($jsonData, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['websys_course'])) {
                // Sync websys course to database
                $websysCourse = $data['websys_course'][0];
                
                // Check if ITWS 2110 course exists
                $checkSql = "SELECT crn FROM courses WHERE prefix = 'ITWS' AND number = 2110 LIMIT 1";
                $checkResult = $conn->query($checkSql);
                
                if ($checkResult === false) {
                    $dbError = true;
                    $dbErrorMessage = $conn->error;
                } else {
                    $crn = null;
                    if ($checkResult && $checkResult->num_rows > 0) {
                        $row = $checkResult->fetch_assoc();
                        $crn = $row['crn'];
                    } else {
                        $crn = 12345; // Default CRN
                    }
                    
                    // Update or insert the course with course_content
                    $checkContent = $conn->query("SHOW COLUMNS FROM courses LIKE 'course_content'");
                    if ($checkContent === false) {
                        $dbError = true;
                        $dbErrorMessage = $conn->error;
                    } else {
                        $hasContent = $checkContent && $checkContent->num_rows > 0;
                        
                        if ($hasContent) {
                            $stmt = $conn->prepare("INSERT INTO courses (crn, prefix, number, title, course_content) VALUES (?, ?, ?, ?, ?) 
                                                    ON DUPLICATE KEY UPDATE prefix=VALUES(prefix), number=VALUES(number), title=VALUES(title), course_content=VALUES(course_content)");
                            if ($stmt) {
                                $courseContentJson = json_encode($data);
                                $title = 'Web Systems Development';
                                $prefix = 'ITWS';
                                $number = 2110;
                                $stmt->bind_param("isiss", $crn, $prefix, $number, $title, $courseContentJson);
                                if (!$stmt->execute()) {
                                    $dbError = true;
                                    $dbErrorMessage = $stmt->error;
                                }
                                $stmt->close();
                            } else {
                                $dbError = true;
                                $dbErrorMessage = $conn->error;
                            }
                        }
                    }
                    
                    // Also archive any courses/students data if present
                    if (isset($data['courses']) || isset($data['students'])) {
                        try {
                            archiveCourses($conn, $data);
                        } catch (Exception $e) {
                            $dbError = true;
                            $dbErrorMessage = "Error archiving courses: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $dbError = true;
        $dbErrorMessage = "Exception during auto-import: " . $e->getMessage();
    }
}

$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$selectedKey = isset($_GET['key']) ? $_GET['key'] : '';
$syncSuccess = isset($_GET['sync']) && $_GET['sync'] === 'success';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMsg = isset($_GET['msg']) ? $_GET['msg'] : '';
$hasSqlError = $dbError || $resetDbError || ($error === 'sync_failed' && (strpos(strtolower($errorMsg), 'sql') !== false || strpos(strtolower($errorMsg), 'table') !== false || strpos(strtolower($errorMsg), 'database') !== false)) || !empty($resetError);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Content Viewer</title>
    <link rel="stylesheet" href="style.css">
    <script>
        let courseData = null;
        let selectedType = '<?php echo htmlspecialchars($selectedType); ?>';
        let selectedKey = '<?php echo htmlspecialchars($selectedKey); ?>';

        function loadCourseContent() {
            fetch('course_content_api.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data && result.data.websys_course && result.data.websys_course.length > 0) {
                        courseData = result.data.websys_course[0];
                        renderNavigation();
                        renderPreview();
                    } else {
                        document.getElementById('contentList').innerHTML = '<li>No course content available.</li>';
                    }
                })
                .catch(error => {
                    console.error('Error loading course content:', error);
                    document.getElementById('contentList').innerHTML = '<li>Error loading course content.</li>';
                });
        }

        function renderNavigation() {
            if (!courseData) return;

            const contentList = document.getElementById('contentList');
            let html = '';

            // Render lectures
            if (courseData.lectures && Object.keys(courseData.lectures).length > 0) {
                html += '<li><strong>Lectures</strong><ul>';
                const lectureKeys = Object.keys(courseData.lectures).sort();
                lectureKeys.forEach(key => {
                    const lecture = courseData.lectures[key];
                    const isActive = selectedType === 'lecture' && selectedKey === key;
                    html += `<li><a href="?type=lecture&key=${encodeURIComponent(key)}" class="${isActive ? 'active' : ''}">${escapeHtml(lecture.title || key)}</a></li>`;
                });
                html += '</ul></li>';
            }

            // Render labs
            if (courseData.labs && Object.keys(courseData.labs).length > 0) {
                html += '<li><strong>Labs</strong><ul>';
                const labKeys = Object.keys(courseData.labs).sort();
                labKeys.forEach(key => {
                    const lab = courseData.labs[key];
                    const isActive = selectedType === 'lab' && selectedKey === key;
                    html += `<li><a href="?type=lab&key=${encodeURIComponent(key)}" class="${isActive ? 'active' : ''}">${escapeHtml(lab.title || key)}</a></li>`;
                });
                html += '</ul></li>';
            }

            contentList.innerHTML = html || '<li>No course content available.</li>';
        }

        function renderPreview() {
            if (!courseData || !selectedType || !selectedKey) {
                document.getElementById('preview').innerHTML = '<p>Select an item from the navigation to view details.</p>';
                return;
            }

            let item = null;
            if (selectedType === 'lecture' && courseData.lectures && courseData.lectures[selectedKey]) {
                item = courseData.lectures[selectedKey];
            } else if (selectedType === 'lab' && courseData.labs && courseData.labs[selectedKey]) {
                item = courseData.labs[selectedKey];
            }

            if (item) {
                let html = '';
                if (item.title) {
                    html += `<h3>${escapeHtml(item.title)}</h3>`;
                }
                if (item.description) {
                    html += `<p><strong>Description:</strong> ${escapeHtml(item.description)}</p>`;
                }
                if (item.material) {
                    html += `<p><strong>Material:</strong> ${escapeHtml(item.material)}</p>`;
                }
                html += `<div style="margin-top: 20px;">
                    <button onclick="archiveItem('${selectedType}', ${JSON.stringify(selectedKey)})">Archive</button>
                </div>`;
                document.getElementById('preview').innerHTML = html;
            } else {
                document.getElementById('preview').innerHTML = '<p>Select an item from the navigation to view details.</p>';
            }
        }

        function archiveItem(type, key) {
            if (!courseData) return;

            let item = null;
            if (type === 'lecture' && courseData.lectures && courseData.lectures[key]) {
                item = courseData.lectures[key];
            } else if (type === 'lab' && courseData.labs && courseData.labs[key]) {
                item = courseData.labs[key];
            }

            if (!item) {
                alert('Item not found');
                return;
            }

            if (!confirm('Archive this item?')) {
                return;
            }

            fetch('archive_item_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    key: key,
                    item: item
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Item archived successfully!');
                } else {
                    alert('Error archiving item: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error archiving item:', error);
                alert('Error archiving item. Please try again.');
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadCourseContent();
        });
    </script>
</head>
<body>
    <div class="halloween-header">
        <h1>🎃 Spooky Course Content 🎃</h1>
    </div>
    <div style="margin-bottom: 15px;">
        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reset all database tables? This will drop and recreate all tables, deleting all data!');">
            <input type="hidden" name="action" value="reset">
            <button type="submit">Reset Tables</button>
        </form>
    </div>
    <?php if ($resetSuccess): ?>
        <div class="message success">Successfully reset all tables!</div>
    <?php endif; ?>
    <?php if ($syncSuccess): ?>
        <div class="message success">Successfully synced JSON file to database!</div>
    <?php endif; ?>
    <?php if ($dbError): ?>
        <div class="message error">
            Database Error: <?php echo htmlspecialchars($dbErrorMessage ? $dbErrorMessage : (isset($conn) ? $conn->error : 'Unknown database error')); ?>
            <div style="margin-top: 10px;">
                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reset all database tables? This will drop and recreate all tables, deleting all data!');">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit">Reset Tables</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($error || $resetError): ?>
        <div class="message error">
            Error: 
            <?php 
            if ($resetError) {
                echo $resetError;
            } elseif ($error === 'db_connection') {
                echo 'Database connection not found';
            } elseif ($error === 'file_not_found') {
                echo 'JSON file not found';
            } elseif ($error === 'json_parse') {
                echo 'Error parsing JSON file';
            } elseif ($error === 'sync_failed') {
                echo htmlspecialchars($errorMsg);
            } else {
                echo 'Unknown error';
            }
            ?>
            <div style="margin-top: 10px;">
                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reset all database tables? This will drop and recreate all tables, deleting all data!');">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit">Reset Tables</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <div class="container">
        <div class="nav-panel">
            <h2>Course Content</h2>
            <form method="POST" action="sync_api.php" style="display: inline-block; margin-bottom: 15px;" onsubmit="return confirm('Refresh content from JSON file? This will update the database.');">
                <button type="submit" name="sync">Refresh from JSON</button>
            </form>
            <ul id="contentList">
                <li>Loading course content...</li>
            </ul>
        </div>
        <div class="preview-panel">
            <div id="preview">
                <p>Select an item from the navigation to view details.</p>
            </div>
            </div>
        </div>
</body>
</html>

