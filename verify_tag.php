<?php
include 'db.php';
header('Content-Type: application/json');

$tag = trim($_POST['tag'] ?? '');
$item = trim($_POST['item'] ?? '');
$roll_number = trim($_POST['roll_number'] ?? '');

$today = date('Y-m-d');

if (!empty($tag) && !empty($item) && empty($roll_number)) {
    // 🟢 Time In verification
    $stmt = $conn->prepare("
        SELECT id FROM items_tags 
        WHERE tag_number = ? 
          AND item_name = ? 
          AND is_available = 1
    ");
    $stmt->bind_param("ss", $tag, $item);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode(['valid' => $result->num_rows > 0, 'mode' => 'time_in']);
    exit;
}

if (!empty($tag) && !empty($roll_number) && empty($item)) {
    // 🔴 Time Out verification
    $stmt = $conn->prepare("
        SELECT id FROM attendance
        WHERE tag_number = ?
          AND roll_number = ?
          AND date = ?
          AND time_out IS NULL
          AND IFNULL(time_out_requested, 0) = 0
          AND time_out_approved = 0
    ");
    $stmt->bind_param("sss", $tag, $roll_number, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode(['valid' => $result->num_rows > 0, 'mode' => 'time_out']);
    exit;
}

// ⚠️ No valid combination of parameters
echo json_encode([
    'valid' => false,
    'message' => 'Invalid parameters. Provide either (tag + item) for Time In or (tag + roll_number) for Time Out.'
]);
