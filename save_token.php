<?php
include 'db.php';
header('Content-Type: application/json');

// Decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
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

// 1. Ensure token is unique
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

// 2. Check if the same roll number already has an active request for the same item & location
$checkActiveQuery = "SELECT id FROM qr_tokens 
                     WHERE roll_number = ? AND location = ? AND item = ? AND status = 'active'";
$checkActiveStmt = $conn->prepare($checkActiveQuery);
$checkActiveStmt->bind_param("sss", $rollNumber, $location, $item);
$checkActiveStmt->execute();
$activeResult = $checkActiveStmt->get_result();

if ($activeResult->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'You already have an active token for this item and location.']);
    $checkActiveStmt->close();
    exit;
}
$checkActiveStmt->close();

// 3. Save token into `qr_tokens` table with default usage count = 0 and max usage = 2
$saveQuery = "INSERT INTO qr_tokens 
              (token, date, roll_number, location, item, usage_count, max_usage, status, created_at) 
              VALUES (?, ?, ?, ?, ?, 0, 2, 'active', NOW())";
$saveStmt = $conn->prepare($saveQuery);
$saveStmt->bind_param("sssss", $token, $date, $rollNumber, $location, $item);

if ($saveStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Token saved successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $saveStmt->error]);
}

$saveStmt->close();
?>
