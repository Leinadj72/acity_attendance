<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include 'db.php';

date_default_timezone_set('Africa/Accra');

// Debug logs (optional, for development)
file_put_contents('debug_raw_input.log', file_get_contents('php://input'), FILE_APPEND);
file_put_contents('debug_post.log', print_r($_POST, true), FILE_APPEND);

// Sanitize inputs
$tagCode = trim($_POST['tag'] ?? '');
$token = trim($_POST['token'] ?? '');

if (empty($tagCode) || empty($token)) {
    exit(json_encode([
        'status' => 'invalid',
        'message' => 'Tag or token is missing.'
    ]));
}

try {
    $today = date('Y-m-d');

    // 1. Fetch and validate token
    $stmt = $conn->prepare("SELECT * FROM qr_tokens WHERE token = ? AND status = 'active'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $tokenData = $result->fetch_assoc();
    $stmt->close();

    if (!$tokenData) {
        exit(json_encode([
            'status' => 'invalid',
            'message' => 'Invalid or inactive QR token.'
        ]));
    }

    // Extract token details
    $roll = $tokenData['roll_number'];
    $item = $tokenData['item'];
    $location = $tokenData['location'];
    $tokenId = $tokenData['id'];

    // 2. Check tag availability
    $stmt = $conn->prepare("SELECT * FROM items_tags WHERE tag_code = ? AND item_name = ? AND is_available = 1");
    $stmt->bind_param("ss", $tagCode, $item);
    $stmt->execute();
    $tagResult = $stmt->get_result();
    $stmt->close();

    if ($tagResult->num_rows === 0) {
        exit(json_encode([
            'status' => 'invalid',
            'message' => 'Invalid or unavailable tag for this item.'
        ]));
    }

    // 3. Ensure tag hasn't already been used today
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE tag_number = ? AND date = ?");
    $stmt->bind_param("ss", $tagCode, $today);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        exit(json_encode([
            'status' => 'taken',
            'message' => 'This tag has already been used today.'
        ]));
    }
    $stmt->close();

    // Already have $tokenId from earlier
    if (!$tokenId) {
        echo json_encode(["status" => "error", "message" => "Token ID missing."]);
        exit;
    }


    // Now insert into the attendance table
    $timeIn = date("H:i:s"); // full datetime format
    $stmt = $conn->prepare("INSERT INTO attendance 
        (token_id, date, roll_number, location, item, tag_number, time_in, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issssss", $tokenId, $today, $roll, $location, $item, $tagCode, $timeIn);
    $stmt->execute();
    $stmt->close();

    // 5. Update token usage
    $timeInFull = date("Y-m-d H:i:s"); // full datetime format
    $stmt = $conn->prepare("UPDATE qr_tokens 
        SET usage_count = usage_count + 1, time_in = ?, updated_at = NOW()
        WHERE token = ?");
    $stmt->bind_param("ss", $timeInFull, $token);
    $stmt->execute();
    $stmt->close();

    // 6. Mark tag as used
    $stmt = $conn->prepare("UPDATE items_tags SET is_available = 0 WHERE tag_code = ?");
    $stmt->bind_param("s", $tagCode);
    $stmt->execute();
    $stmt->close();

    // 7. Success response
    echo json_encode([
        'status' => 'success',
        'message' => "✅ Time In recorded at $timeIn.",
        'roll_number' => $roll,
        'tag' => $tagCode,
        'time_in' => $timeIn
    ]);

} catch (Exception $e) {
    file_put_contents('error_log.txt', $e->getMessage(), FILE_APPEND);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
