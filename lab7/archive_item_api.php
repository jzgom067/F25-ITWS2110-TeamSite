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

try {
    $title = isset($item['title']) ? $item['title'] : null;
    $description = isset($item['description']) ? $item['description'] : null;
    $material = isset($item['material']) ? $item['material'] : null;
    $itemJson = json_encode($item);

    // Insert or update archive entry
    $stmt = $conn->prepare("INSERT INTO archive (type, item_key, title, description, material, data) VALUES (?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), material=VALUES(material), data=VALUES(data), archived_at=CURRENT_TIMESTAMP");

    if ($stmt) {
        $stmt->bind_param("ssssss", $type, $key, $title, $description, $material, $itemJson);
        
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(["success" => true, "message" => "Item archived successfully"]);
        } else {
            $errorMsg = $stmt->error;
            $stmt->close();
            echo json_encode(["success" => false, "message" => "Error archiving item: " . $errorMsg]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Exception archiving item: " . $e->getMessage()]);
}
?>

