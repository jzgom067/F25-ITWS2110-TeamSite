<?php
// Database configuration - FILL IN YOUR DETAILS HERE
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database_name');

// Start session to track database errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize connection variable
$conn = null;
$db_connection_error = null;

try {
    // Suppress mysqli errors to handle them gracefully
    mysqli_report(MYSQLI_REPORT_OFF);
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error, $conn->connect_errno);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Clear any previous error
    unset($_SESSION['db_error']);
    
} catch (Exception $e) {
    $conn = null;
    $error_code = $e->getCode();
    
    // Determine the type of error
    if ($error_code === 1045) {
        // Access denied - wrong credentials
        $db_connection_error = "Database credentials are incorrect. Please update DB_USER and DB_PASS in db_connect.php";
    } elseif ($error_code === 1049) {
        // Unknown database
        $db_connection_error = "Database '" . DB_NAME . "' does not exist. Please update DB_NAME in db_connect.php or create the database.";
    } elseif ($error_code === 2002 || $error_code === 2003) {
        // Can't connect to MySQL server
        $db_connection_error = "Cannot connect to MySQL server at '" . DB_HOST . "'. Please check that MySQL is running and DB_HOST is correct in db_connect.php";
    } else {
        // Other error
        $db_connection_error = "Database connection error: " . $e->getMessage();
    }
    
    // Store error in session for display
    $_SESSION['db_error'] = $db_connection_error;
}
?>

