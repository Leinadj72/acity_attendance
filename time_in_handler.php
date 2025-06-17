<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$roll_number = trim($_POST['roll_number'] ?? '');
$item = trim($_POST['item'] ?? '');
$tag = trim($_POST['tag'] ?? '');
$location = trim($_POST['location'] ?? '');

if (!$roll_number || !$item || !$tag || !$location) {
    exit(json_encode([
        'status' => 'error',
        'message' => '❌ All fields are required.'
    ]));
}

$stmt = $conn->prepare("SELECT * FROM items_tags WHERE tag_number = ? AND item_name = ?");
$stmt->bind_param("ss", $tag, $item);
$stmt->execute();
$tagResult = $stmt->get_result();
$tagData = $tagResult->fetch_assoc();
$stmt->close();

if (!$tagData) {
    exit(json_encode([
        'status' => 'error',
        'message' => '❌ Invalid tag number for selected item.'
    ]));
}

if ((int)$tagData['is_available'] !== 1) {
    exit(json_encode([
        'status' => 'error',
        'message' => '❌ Item is currently unavailable.'
    ]));
}

$date = date('Y-m-d');
$time_in = date('H:i:s');

$check = $conn->prepare("SELECT id FROM attendance WHERE roll_number = ? AND date = ? AND time_out IS NULL");
$check->bind_param("ss", $roll_number, $date);
$check->execute();
$checkResult = $check->get_result();
$check->close();

if ($checkResult->num_rows > 0) {
    exit(json_encode([
        'status' => 'error',
        'message' => '⚠️ Already timed in. Please time out first.'
    ]));
}


$stmt = $conn->prepare("INSERT INTO attendance (roll_number, item, tag_number, location, date, time_in) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $roll_number, $item, $tag, $location, $date, $time_in);
$stmt->execute();
$stmt->close();

$updateStmt = $conn->prepare("UPDATE items_tags SET is_available = 0 WHERE tag_number = ?");
$updateStmt->bind_param("s", $tag);
$updateStmt->execute();
$updateStmt->close();

echo json_encode([
    'status' => 'success',
    'message' => "✅ Time In recorded at $time_in. Item marked as unavailable.",
]);
