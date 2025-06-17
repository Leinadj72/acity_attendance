<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

include 'db.php';
date_default_timezone_set('Africa/Accra');

$roll = trim($_POST['roll_number'] ?? '');

if (empty($roll)) {
    exit(json_encode([
        'status' => 'invalid',
        'message' => '❌ Roll number missing.'
    ]));
}

$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT * FROM attendance WHERE roll_number = ? AND date = ?");
$stmt->bind_param("ss", $roll, $today);
$stmt->execute();
$result = $stmt->get_result();
$attData = $result->fetch_assoc();
$stmt->close();

if ($attData) {
    if ($attData['time_out']) {
        exit(json_encode([
            'status' => 'timeout_approved',
            'message' => '✅ Time Out already approved.'
        ]));
    }

    if ($attData['time_out_requested']) {
        exit(json_encode([
            'status' => 'timeout_requested',
            'message' => '⏳ Time Out already requested.'
        ]));
    }

    exit(json_encode([
        'status' => 'already_timed_in',
        'message' => '⚠️ You have already timed in today.'
    ]));
}

$items = [];
$itemQuery = $conn->query("SELECT DISTINCT item_name FROM items_tags WHERE is_available = 1");
while ($row = $itemQuery->fetch_assoc()) {
    $items[] = $row['item_name'];
}

$locations = [];
$locationQuery = $conn->query("SELECT DISTINCT location_name FROM locations ORDER BY location_name ASC");
while ($row = $locationQuery->fetch_assoc()) {
    $locations[] = $row['location_name'];
}

echo json_encode([
    'status' => 'require_inputs',
    'message' => '✅ Roll number valid. Please select item, tag, and location.',
    'roll_number' => $roll,
    'items' => $items,
    'locations' => $locations
]);
