<?php
// Main page for archiving course data from JSON file
require_once 'db_connect.php';

// Fetch courses and students for display
$courses = [];
$students = [];

if (isset($conn)) {
    $coursesResult = $conn->query("SELECT crn, prefix, number, title FROM courses ORDER BY crn");
    if ($coursesResult && $coursesResult->num_rows > 0) {
        while ($row = $coursesResult->fetch_assoc()) {
            $courses[] = $row;
        }
    }
    
    $studentsResult = $conn->query("SELECT RIN, RCSID, first_name, last_name, alias, phone FROM students ORDER BY RIN");
    if ($studentsResult && $studentsResult->num_rows > 0) {
        while ($row = $studentsResult->fetch_assoc()) {
            $students[] = $row;
        }
    }
}
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
    
    <div class="tables-container">
        <div class="table-section">
            <h2>Courses</h2>
            <?php if (empty($courses)): ?>
                <p>No courses in database.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>CRN</th>
                            <th>Prefix</th>
                            <th>Number</th>
                            <th>Title</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['crn']); ?></td>
                                <td><?php echo htmlspecialchars($course['prefix']); ?></td>
                                <td><?php echo htmlspecialchars($course['number']); ?></td>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="table-section">
            <h2>Students</h2>
            <?php if (empty($students)): ?>
                <p>No students in database.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>RIN</th>
                            <th>RCSID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Alias</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['RIN']); ?></td>
                                <td><?php echo htmlspecialchars($student['RCSID'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['alias']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

