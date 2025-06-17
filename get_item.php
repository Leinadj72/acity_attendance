<?php
header('Content-Type: application/json');
include 'db.php';

try {
    $query = "SELECT DISTINCT item_name FROM items_tags WHERE is_available = 1 ORDER BY item_name ASC";
    $result = $conn->query($query);

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row['item_name'];
    }

    echo json_encode($items);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
