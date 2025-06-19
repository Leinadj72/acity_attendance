<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');
include 'db.php';

$roll_number = trim($_POST['roll_number'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$item = trim($_POST['item'] ?? '');
$tag_number = trim($_POST['tag_number'] ?? '');
$location = trim($_POST['location'] ?? '');

if (!$roll_number || !$item || !$tag_number || !$location) {
    echo json_encode(['status' => 'error', 'message' => '❌ Missing required fields.']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_number = ? AND item_name = ? AND is_available = 1");
$stmt->bind_param("ss", $tag_number, $item);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => '❌ Invalid or unavailable tag.']);
    exit;
}
$stmt->close();

$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT id FROM attendance WHERE roll_number = ? AND date = ? AND time_out IS NULL AND time_out_requested IS NULL");
$stmt->bind_param("ss", $roll_number, $today);
$stmt->execute();
$existing = $stmt->get_result();
if ($existing->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => '⚠️ You have already timed in and not timed out.']);
    exit;
}
$stmt->close();

$now = date('H:i:s');
$stmt = $conn->prepare("INSERT INTO attendance 
    (roll_number, name, email, phone, date, time_in, item, tag_number, location, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssssssss", $roll_number, $name, $email, $phone, $today, $now, $item, $tag_number, $location);
$success = $stmt->execute();

if ($success) {
    $update = $conn->prepare("UPDATE items_tags SET is_available = 0 WHERE tag_number = ?");
    $update->bind_param("s", $tag_number);
    $update->execute();
    $update->close();

    echo json_encode(['status' => 'success', 'message' => '✅ Time In recorded successfully.', 'redirect' => 'scan.php']);
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ Failed to record Time In.']);
}
$stmt->close();
$conn->close();
