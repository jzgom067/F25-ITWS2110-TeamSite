<?php
// API endpoint for archiving a lecture/lab item
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Database connection not found"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

try {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["success" => false, "message" => "Error parsing JSON: " . json_last_error_msg()]);
        exit;
    }

    if (!isset($data['type']) || !isset($data['key']) || !isset($data['item'])) {
        echo json_encode(["success" => false, "message" => "Missing required fields: type, key, and item"]);
        exit;
    }

    $type = $data['type']; // 'lecture' or 'lab'
    $key = $data['key']; // e.g., 'lecture1', 'lab2'
    $item = $data['item']; // The full item data

    // Validate type
    if ($type !== 'lecture' && $type !== 'lab') {
        echo json_encode(["success" => false, "message" => "Invalid type. Must be 'lecture' or 'lab'"]);
        exit;
    }

    // Ensure item is an array
    if (!is_array($item)) {
        echo json_encode(["success" => false, "message" => "Item must be an object/array"]);
        exit;
    }

    $title = isset($item['title']) ? $item['title'] : null;
    $description = isset($item['description']) ? $item['description'] : null;
    $material = isset($item['material']) ? $item['material'] : null;
    $itemJson = json_encode($item);

    // Check if archive table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'archive'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Archive table does not exist. Please reset the database tables."]);
        exit;
    }

    // Insert or update archive entry
    $stmt = $conn->prepare("INSERT INTO archive (type, item_key, title, description, material, data) VALUES (?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), material=VALUES(material), data=VALUES(data), archived_at=CURRENT_TIMESTAMP");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ssssss", $type, $key, $title, $description, $material, $itemJson);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(["success" => true, "message" => "Item archived successfully", "type" => $type, "key" => $key]);
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        echo json_encode(["success" => false, "message" => "Error archiving item: " . $errorMsg]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Exception archiving item: " . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(["success" => false, "message" => "Error archiving item: " . $e->getMessage()]);
}
?>

