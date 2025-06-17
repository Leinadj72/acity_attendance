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

$stmt = $conn->prepare("SELECT is_available FROM items_tags WHERE tag_number = ? AND item_name = ?");
$stmt->bind_param("ss", $tag, $item);
$stmt->execute();
$result = $stmt->get_result();
$tagData = $result->fetch_assoc();
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
        'message' => '❌ This item is currently unavailable.'
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

$insert = $conn->prepare("INSERT INTO attendance (roll_number, item, tag_number, location, date, time_in) VALUES (?, ?, ?, ?, ?, ?)");
$insert->bind_param("ssssss", $roll_number, $item, $tag, $location, $date, $time_in);
$insert->execute();
$insert->close();

$update = $conn->prepare("UPDATE items_tags SET is_available = 0 WHERE tag_number = ?");
$update->bind_param("s", $tag);
$update->execute();
$update->close();

echo json_encode([
    'status' => 'success',
    'message' => "✅ Time In recorded at $time_in. Item marked as unavailable.",
]);
