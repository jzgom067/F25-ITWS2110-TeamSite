<?php
// API endpoint for getting archived items
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Database connection not found"]);
    exit;
}

try {
    $sql = "SELECT type, item_key FROM archive";
    $result = $conn->query($sql);

    $archived = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $archived[] = [
                'type' => $row['type'],
                'item_key' => $row['item_key']
            ];
        }
    }

    echo json_encode(["success" => true, "archived" => $archived]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Exception getting archived items: " . $e->getMessage()]);
}
?>

