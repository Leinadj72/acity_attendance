<?php
include 'db.php';  // your connection setup in $conn

header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (
    !isset($input['token'], $input['date'], $input['rollNumber'], $input['location'], $input['item'])
) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$token = $input['token'];
$date = $input['date'];
$rollNumber = $input['rollNumber'];
$location = $input['location'];
$item = $input['item'];

// Check if the same roll number has an active token for the same item and location
$checkQuery = "SELECT id FROM qr_tokens 
               WHERE roll_number = ? AND location = ? AND item = ? AND status = 'active'";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("sss", $rollNumber, $location, $item);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'The Item you want has already been taken for that location.']);
    exit;
}
$checkStmt->close();


$stmt = $conn->prepare('INSERT INTO qr_tokens (token, date, roll_number, location, item, usage_count, max_usage, status, created_at) VALUES (?, ?, ?, ?, ?, 0, 2, "active", NOW())');
if ($stmt === false) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('sssss', $token, $date, $rollNumber, $location, $item);
$exec = $stmt->execute();

if ($exec) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
