<?php
// Course content viewer page
// Note: Include your database connection file before this file
// Example: require_once 'your_connection_file.php';
// The connection should be available as $conn
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Content Viewer</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <div>
        <a href="index.php">Back to Archive</a>
    </div>
    <div class="container">
        <div class="nav-panel">
            <h2>Course Content</h2>
            <button onclick="refreshContent()">Refresh</button>
            <ul id="contentList"></ul>
        </div>
        <div class="preview-panel">
            <div id="preview">
                <p>Select an item from the navigation to view details.</p>
            </div>
        </div>
    </div>
</body>
</html>

