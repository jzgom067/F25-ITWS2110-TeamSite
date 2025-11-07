<?php
// Reset/Delete tables page
require_once 'db_connect.php';

// Function to drop all tables
function dropTables($conn) {
    $tables = ['grades', 'courses', 'students'];
    $dropped = [];
    $errors = [];
    
    foreach ($tables as $table) {
        $sql = "DROP TABLE IF EXISTS $table";
        if ($conn->query($sql) === TRUE) {
            $dropped[] = $table;
        } else {
            $errors[] = "Error dropping table $table: " . $conn->error;
        }
    }
    
    return [
        "success" => empty($errors),
        "dropped" => $dropped,
        "errors" => $errors
    ];
}

// Function to recreate tables from schema
function recreateTables($conn) {
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $queries = array_filter(array_map('trim', explode(';', $schema)));
    $errors = [];
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            if ($conn->query($query) === FALSE) {
                $errors[] = "Error executing query: " . $conn->error;
            }
        }
    }
    
    $schema = file_get_contents(__DIR__ . '/init.sql');
    $queries = array_filter(array_map('trim', explode(';', $schema)));
    $errors = [];
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            if ($conn->query($query) === FALSE) {
                $errors[] = "Error executing query: " . $conn->error;
            }
        }
    }
    
    return [
        "success" => empty($errors),
        "errors" => $errors
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database Tables</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <h1>Database Table Management</h1>
    
    <div class="button-group">
        <form method="POST" onsubmit="return confirmDrop();">
            <input type="hidden" name="action" value="drop">
            <button type="submit">Delete All Tables</button>
        </form>
        
        <form method="POST" onsubmit="return confirmRecreate();">
            <input type="hidden" name="action" value="recreate">
            <button type="submit">Reset Tables (Drop & Recreate)</button>
        </form>
    </div>
    
    <?php
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($conn)) {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'drop') {
                $result = dropTables($conn);
                if ($result['success']) {
                    echo '<div class="message success">Successfully dropped tables: ' . implode(', ', $result['dropped']) . '</div>';
                } else {
                    echo '<div class="message error">Errors: ' . implode('<br>', $result['errors']) . '</div>';
                }
            } elseif ($_POST['action'] === 'recreate') {
                $dropResult = dropTables($conn);
                $createResult = recreateTables($conn);
                if ($createResult['success']) {
                    echo '<div class="message success">Successfully reset all tables!</div>';
                } else {
                    echo '<div class="message error">Errors: ' . implode('<br>', $createResult['errors']) . '</div>';
                }
            }
        }
    } elseif (!isset($conn)) {
        echo '<div class="message error">Database connection not found. Please include your connection file.</div>';
    }
    ?>
</body>
</html>

