<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$roll_number = trim($_POST['token'] ?? '');
$item = trim($_POST['item'] ?? '');
$tag_number = trim($_POST['tag_number'] ?? '');
$location = trim($_POST['location'] ?? '');

if (!$roll_number || !$item || !$tag_number || !$location) {
    echo json_encode(['status' => 'error', 'message' => '❌ Missing required fields.']);
    exit;
}

// Verify tag matches the selected item and is available
$stmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_number = ? AND item_name = ? AND is_available = 1");
$stmt->bind_param("ss", $tag_number, $item);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => '❌ Invalid or unavailable tag.']);
    exit;
}
$stmt->close();

// Prevent duplicate active records
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT id FROM attendance WHERE roll_number = ? AND date = ? AND time_out IS NULL AND time_out_requested IS NULL");
$stmt->bind_param("ss", $roll_number, $today);
$stmt->execute();
$existing = $stmt->get_result();
if ($existing->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => '⚠️ You have already timed in without timing out.']);
    exit;
}
$stmt->close();

// Record time in
$now = date('H:i:s');
$stmt = $conn->prepare("INSERT INTO attendance (roll_number, date, time_in, item, tag_number, location, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssss", $roll_number, $today, $now, $item, $tag_number, $location);
$success = $stmt->execute();

if ($success) {
    // Mark tag as unavailable
    $updateTag = $conn->prepare("UPDATE items_tags SET is_available = 0 WHERE tag_number = ?");
    $updateTag->bind_param("s", $tag_number);
    $updateTag->execute();
    $updateTag->close();

    echo json_encode(['status' => 'success', 'message' => '✅ Time In recorded successfully.', 'redirect' => 'scan.php']);
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ Failed to record Time In.']);
}

$stmt->close();
$conn->close();
