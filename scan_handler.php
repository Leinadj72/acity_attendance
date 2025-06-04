<?php
include 'db.php';
ob_clean();
header('Content-Type: application/json');
date_default_timezone_set("Africa/Accra");

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token']);
    $today = date("Y-m-d");

    // Fetch token record
    $stmt = $conn->prepare("SELECT * FROM qr_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $qr = $stmt->get_result();

    if ($qr->num_rows !== 1) {
        echo json_encode(['status' => 'invalid']);
        exit;
    }

    $qr_row = $qr->fetch_assoc();

    // Inactive or expired QR
    if ($qr_row['status'] !== 'active') {
        echo json_encode(['status' => 'used']);
        exit;
    }

    if ((int)$qr_row['usage_count'] >= (int)$qr_row['max_usage']) {
        echo json_encode(['status' => 'used']);
        exit;
    }

    $item = $qr_row['item'];
    $roll = $qr_row['roll_number'];
    $location = $qr_row['location'];
    $tokenId = $qr_row['id'];

    // Check if attendance already exists for today
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE token_id = ? AND date = ?");
    $stmt->bind_param("is", $tokenId, $today);
    $stmt->execute();
    $attendance = $stmt->get_result();

    if ($attendance->num_rows > 0) {
        $att_row = $attendance->fetch_assoc();

        if ($att_row['time_out']) {
            echo json_encode(['status' => 'timeout_approved']);
            exit;
        }

        if ($att_row['time_out_requested']) {
            echo json_encode(['status' => 'timeout_requested']);
            exit;
        }

        // Handle Time Out request
        if ((int)$qr_row['usage_count'] === 1) {
            // Mark Time Out as requested
            $stmt = $conn->prepare("UPDATE attendance SET time_out_requested = 1 WHERE token_id = ? AND date = ? AND time_out IS NULL");
            $stmt->bind_param("is", $tokenId, $today);
            $stmt->execute();

            // Update QR token usage count and status
            $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = usage_count + 1, status = 'pending_approval' WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            echo json_encode(['status' => 'timeout_requested']);
            exit;
        }

        echo json_encode(['status' => 'already_timed_in']);
        exit;
    }

    // First scan (Time In not yet recorded)
    echo json_encode([
        'status' => 'require_tag',
        'message' => '✅ QR Code scanned successfully. Please enter your tag number.',
        'token' => $token,
        'item' => $item,
        'roll' => $roll,
        'location' => $location
    ]);
    exit;
}

echo json_encode(['status' => 'invalid_request']);
exit;
