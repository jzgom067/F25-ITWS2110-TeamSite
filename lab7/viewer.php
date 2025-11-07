<?php
// Course content viewer page - reads from database
require_once 'db_connect.php';

$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$selectedKey = isset($_GET['key']) ? $_GET['key'] : '';
$syncSuccess = isset($_GET['sync']) && $_GET['sync'] === 'success';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMsg = isset($_GET['msg']) ? $_GET['msg'] : '';

$courseContent = null;
$previewData = null;
$lectures = [];
$labs = [];

if (isset($conn)) {
    // Fetch websys course (ITWS 2110) from database
    $sql = "SELECT course_content FROM courses WHERE prefix = 'ITWS' AND number = 2110 AND course_content IS NOT NULL LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $content = json_decode($row['course_content'], true);
        
        if ($content && isset($content['websys_course']) && is_array($content['websys_course']) && count($content['websys_course']) > 0) {
            $courseContent = $content['websys_course'][0];
            
            // Extract lectures and labs
            if (isset($courseContent['lectures']) && is_array($courseContent['lectures'])) {
                $lectures = $courseContent['lectures'];
            }
            if (isset($courseContent['labs']) && is_array($courseContent['labs'])) {
                $labs = $courseContent['labs'];
            }
            
            // Get preview data if item is selected
            if ($selectedType && $selectedKey) {
                if ($selectedType === 'lecture' && isset($lectures[$selectedKey])) {
                    $previewData = $lectures[$selectedKey];
                } elseif ($selectedType === 'lab' && isset($labs[$selectedKey])) {
                    $previewData = $labs[$selectedKey];
                }
            }
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
            <form method="POST" action="sync_api.php" style="display: inline-block; margin-bottom: 15px;" onsubmit="return confirm('Refresh content from JSON file? This will update the database.');">
                <button type="submit" name="sync">Refresh from JSON</button>
            </form>
            <ul id="contentList">
                <?php if (!isset($conn)): ?>
                    <li>Database connection not found</li>
                <?php elseif ($courseContent === null): ?>
                    <li>No course content available. Click "Refresh from JSON" to load data.</li>
                <?php else: ?>
                    <?php if (!empty($lectures)): ?>
                        <li>
                            <strong>Lectures</strong>
                            <ul>
                                <?php foreach ($lectures as $key => $lecture): ?>
                                    <li>
                                        <a href="?type=lecture&key=<?php echo urlencode($key); ?>" 
                                           class="<?php echo ($selectedType === 'lecture' && $selectedKey === $key) ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars(isset($lecture['title']) ? $lecture['title'] : $key); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($labs)): ?>
                        <li>
                            <strong>Labs</strong>
                            <ul>
                                <?php foreach ($labs as $key => $lab): ?>
                                    <li>
                                        <a href="?type=lab&key=<?php echo urlencode($key); ?>" 
                                           class="<?php echo ($selectedType === 'lab' && $selectedKey === $key) ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars(isset($lab['title']) ? $lab['title'] : $key); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
        <div class="preview-panel">
            <div id="preview">
                <?php if ($previewData): ?>
                    <?php if (isset($previewData['title'])): ?>
                        <h3><?php echo htmlspecialchars($previewData['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (isset($previewData['description'])): ?>
                        <p><strong>Description:</strong> <?php echo htmlspecialchars($previewData['description']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($previewData['material'])): ?>
                        <p><strong>Material:</strong> <?php echo htmlspecialchars($previewData['material']); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Select an item from the navigation to view details.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
