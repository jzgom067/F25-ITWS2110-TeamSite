<?php
// Main page for archiving course data from JSON file
// Note: Include your database connection file before this file
// Example: require_once 'your_connection_file.php';
// The connection should be available as $conn
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Course Data</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <h1>Archive Course Data</h1>
    <p>Import course and student data from a JSON file into the database</p>
        
    <div id="message"></div>
        
    <form id="archiveForm">
        <div class="form-group">
            <label for="json_file">JSON File:</label>
            <input type="file" id="json_file" name="json_file" accept=".json" required>
        </div>
        
        <button type="submit">Archive Data</button>
        <a href="reset.php">Reset Tables</a>
        <a href="viewer.php">View Course Content</a>
    </form>
        
        <div>
            <strong>Expected JSON Format:</strong>
            <div class="example">
{
    "courses": [
        {
            "crn": 12345,
            "prefix": "ITWS",
            "number": 2110,
            "title": "Web Systems Development",
            "lectures": [
                {
                    "title": "Introduction to Web Development",
                    "description": "Overview of web technologies",
                    "material": "Slides and examples"
                }
            ],
            "labs": [
                {
                    "title": "Lab 1: HTML Basics",
                    "description": "Creating basic HTML pages",
                    "material": "Lab instructions and starter code"
                }
            ]
        }
    ],
    "students": [
        {
            "RIN": 123456789,
            "RCSID": "doej",
            "first_name": "John",
            "last_name": "Doe",
            "alias": "jdoe",
            "phone": 5185551234
        }
    ]
}
            </div>
        </div>
</body>
</html>

