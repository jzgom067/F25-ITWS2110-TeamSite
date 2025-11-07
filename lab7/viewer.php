<?php
// Course content viewer page - reads from database
require_once 'db_connect.php';

$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$selectedIndex = isset($_GET['index']) ? intval($_GET['index']) : -1;
$selectedCourse = isset($_GET['course']) ? intval($_GET['course']) : -1;
$syncSuccess = isset($_GET['sync']) && $_GET['sync'] === 'success';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMsg = isset($_GET['msg']) ? $_GET['msg'] : '';

$courses = [];
$previewData = null;

if (isset($conn)) {
    // Fetch courses with their JSON content from database
    $sql = "SELECT crn, prefix, number, title, course_content FROM courses WHERE course_content IS NOT NULL ORDER BY crn";
    $result = $conn->query($sql);
    
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
    
    // Get preview data if item is selected
    if ($selectedCourse >= 0 && $selectedCourse < count($courses) && 
        $selectedType && $selectedIndex >= 0) {
        $course = $courses[$selectedCourse];
        if ($selectedType === 'lecture' && isset($course['content']['lectures'][$selectedIndex])) {
            $previewData = [
                'label' => 'Lecture ' . ($selectedIndex + 1),
                'item' => $course['content']['lectures'][$selectedIndex]
            ];
        } elseif ($selectedType === 'lab' && isset($course['content']['labs'][$selectedIndex])) {
            $previewData = [
                'label' => 'Lab ' . ($selectedIndex + 1),
                'item' => $course['content']['labs'][$selectedIndex]
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Content Viewer</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div>
        <a href="index.php">Back to Archive</a>
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
            <form method="POST" action="sync_api.php" style="display: inline;" onsubmit="return confirm('Sync JSON file to database? This will update all course content.');">
                <button type="submit" name="sync">Sync from JSON</button>
            </form>
            <ul id="contentList">
                <?php if (!isset($conn)): ?>
                    <li>Database connection not found</li>
                <?php elseif (empty($courses)): ?>
                    <li>No course content available. Click "Sync from JSON" to load data.</li>
                <?php else: ?>
                    <?php foreach ($courses as $courseIndex => $course): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($course['prefix'] . $course['number'] . ': ' . $course['title']); ?></strong>
                            <ul>
                                <?php if (isset($course['content']['lectures']) && is_array($course['content']['lectures']) && count($course['content']['lectures']) > 0): ?>
                                    <li>
                                        <strong>Lectures</strong>
                                        <ul>
                                            <?php foreach ($course['content']['lectures'] as $lectureIndex => $lecture): ?>
                                                <li>
                                                    <a href="?course=<?php echo $courseIndex; ?>&type=lecture&index=<?php echo $lectureIndex; ?>">
                                                        Lecture <?php echo $lectureIndex + 1; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($course['content']['labs']) && is_array($course['content']['labs']) && count($course['content']['labs']) > 0): ?>
                                    <li>
                                        <strong>Labs</strong>
                                        <ul>
                                            <?php foreach ($course['content']['labs'] as $labIndex => $lab): ?>
                                                <li>
                                                    <a href="?course=<?php echo $courseIndex; ?>&type=lab&index=<?php echo $labIndex; ?>">
                                                        Lab <?php echo $labIndex + 1; ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="preview-panel">
            <div id="preview">
                <?php if ($previewData): ?>
                    <h3><?php echo htmlspecialchars($previewData['label']); ?></h3>
                    <?php if (isset($previewData['item']['title'])): ?>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($previewData['item']['title']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($previewData['item']['description'])): ?>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($previewData['item']['description']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($previewData['item']['material'])): ?>
                        <p><strong>Material:</strong> <?php echo htmlspecialchars($previewData['item']['material']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Select an item from the navigation to view details.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
