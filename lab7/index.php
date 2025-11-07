<?php
// Main page - auto-imports course content from JSON and displays it
require_once 'db_connect.php';
require_once 'archive.php';

// Auto-import course content from JSON file on page load
if (isset($conn)) {
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
            
            $crn = null;
            if ($checkResult && $checkResult->num_rows > 0) {
                $row = $checkResult->fetch_assoc();
                $crn = $row['crn'];
            } else {
                $crn = 12345; // Default CRN
            }
            
            // Update or insert the course with course_content
            $checkContent = $conn->query("SHOW COLUMNS FROM courses LIKE 'course_content'");
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
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // Also archive any courses/students data if present
            if (isset($data['courses']) || isset($data['students'])) {
                archiveCourses($conn, $data);
            }
        }
    }
}

$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$selectedKey = isset($_GET['key']) ? $_GET['key'] : '';
$syncSuccess = isset($_GET['sync']) && $_GET['sync'] === 'success';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMsg = isset($_GET['msg']) ? $_GET['msg'] : '';
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
                document.getElementById('preview').innerHTML = html;
            } else {
                document.getElementById('preview').innerHTML = '<p>Select an item from the navigation to view details.</p>';
            }
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
        <div class="halloween-decoration">
            <img src="images/pumpkin.png" alt="Pumpkin">
            <img src="images/ghost.png" alt="Ghost">
            <img src="images/bat.png" alt="Bat">
        </div>
        <h1>🎃 Spooky Course Content 🎃</h1>
        <div class="halloween-decoration">
            <img src="images/spider.png" alt="Spider">
            <img src="images/ghost.png" alt="Ghost">
            <img src="images/pumpkin.png" alt="Pumpkin">
        </div>
    </div>
    <div>
        <a href="reset.php">Reset Tables</a>
    </div>
    <?php if ($syncSuccess): ?>
        <div class="message success">Successfully synced JSON file to database!</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error">
            Error: 
            <?php 
            if ($error === 'db_connection') {
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

