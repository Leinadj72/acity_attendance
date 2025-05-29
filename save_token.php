<?php
include 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (
    !isset($input['token'], $input['date'], $input['rollNumber'], $input['location'], $input['item'])
) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$token = trim($input['token']);
$date = trim($input['date']);
$rollNumber = trim($input['rollNumber']);
$location = trim($input['location']);
$item = trim($input['item']);

// Optionally check token uniqueness
$checkTokenQuery = "SELECT id FROM qr_tokens WHERE token = ?";
$checkTokenStmt = $conn->prepare($checkTokenQuery);
$checkTokenStmt->bind_param("s", $token);
$checkTokenStmt->execute();
$tokenResult = $checkTokenStmt->get_result();

if ($tokenResult->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Token already exists']);
    $checkTokenStmt->close();
    exit;
}
$checkTokenStmt->close();

// Check if the same roll number has an active token for the same item and location
$checkQuery = "SELECT id FROM qr_tokens 
               WHERE roll_number = ? AND location = ? AND item = ? AND status = 'active'";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("sss", $rollNumber, $location, $item);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'The Item you want has already been taken for that location.']);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Insert new token
$stmt = $conn->prepare('INSERT INTO qr_tokens (token, date, roll_number, location, item, usage_count, max_usage, status, created_at) VALUES (?, ?, ?, ?, ?, 0, 2, "active", NOW())');
$stmt->bind_param('sssss', $token, $date, $rollNumber, $location, $item);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Token saved successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
?>