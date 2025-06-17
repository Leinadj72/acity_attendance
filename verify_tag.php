<?php
include 'db.php';
header('Content-Type: application/json');

$tag = trim($_POST['tag'] ?? '');
$item = trim($_POST['item'] ?? '');

if (empty($tag) || empty($item)) {
    echo json_encode(['valid' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_number = ? AND item_name = ? AND is_available = 1");
$stmt->bind_param("ss", $tag, $item);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['valid' => $result->num_rows > 0]);
