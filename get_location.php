<?php
header('Content-Type: application/json');
include 'db.php';

try {
    $query = "SELECT DISTINCT name FROM locations ORDER BY name ASC";
    $result = $conn->query($query);

    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row['name'];
    }

    echo json_encode($locations);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
