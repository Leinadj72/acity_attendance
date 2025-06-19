<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$roll_number = trim($_POST['roll_number'] ?? '');
$mode = trim($_POST['mode'] ?? '');

if (empty($roll_number)) {
    exit(json_encode([
        'status' => 'invalid',
        'message' => '❌ Roll number is missing.'
    ]));
}

if (!in_array($mode, ['in', 'out'])) {
    exit(json_encode([
        'status' => 'invalid_mode',
        'message' => '❌ Invalid mode selected.'
    ]));
}

$today = date('Y-m-d');

// Get today's records for the roll number
$stmt = $conn->prepare("SELECT * FROM attendance WHERE roll_number = ? AND date = ? ORDER BY id DESC");
$stmt->bind_param("ss", $roll_number, $today);
$stmt->execute();
$result = $stmt->get_result();
$attRecords = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($mode === 'in') {
    foreach ($attRecords as $record) {
        if (empty($record['time_out']) && empty($record['time_out_requested'])) {
            exit(json_encode([
                'status' => 'already_timed_in',
                'message' => '⚠️ You have already timed in and not timed out.'
            ]));
        }
    }

    // Get available items
    $items = [];
    $itemQuery = $conn->query("SELECT DISTINCT item_name FROM items_tags WHERE is_available = 1");
    while ($row = $itemQuery->fetch_assoc()) {
        $items[] = $row['item_name'];
    }

    // Get available locations
    $locations = [];
    $locQuery = $conn->query("SELECT DISTINCT location_name FROM locations ORDER BY location_name ASC");
    while ($row = $locQuery->fetch_assoc()) {
        $locations[] = $row['location_name'];
    }

    echo json_encode([
        'status' => 'require_inputs',
        'message' => '✅ Roll number valid. Please select item, tag, and location.',
        'roll_number' => $roll_number,
        'items' => $items,
        'locations' => $locations
    ]);
    exit;
}

if ($mode === 'out') {
    foreach ($attRecords as $record) {
        if (
            empty($record['time_out']) &&
            (empty($record['time_out_requested']) || $record['time_out_requested'] == 0) &&
            $record['time_out_approved'] == 0
        ) {
            echo json_encode([
                'status' => 'ready_for_timeout',
                'message' => '✅ Proceed to enter your tag number for Time Out.',
                'roll_number' => $roll_number,
                'tag_number' => $record['tag_number'],
                'record_id' => $record['id']
            ]);
            exit;
        }
    }

    echo json_encode([
        'status' => 'not_timed_in',
        'message' => '⚠️ No active Time In record found for Time Out.'
    ]);
    exit;
}
