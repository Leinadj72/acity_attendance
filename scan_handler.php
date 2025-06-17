<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$roll = trim($_POST['roll_number'] ?? '');
$mode = trim($_POST['mode'] ?? '');

if (empty($roll)) {
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

$stmt = $conn->prepare("SELECT * FROM attendance WHERE roll_number = ? AND date = ? ORDER BY id DESC");
$stmt->bind_param("ss", $roll, $today);
$stmt->execute();
$result = $stmt->get_result();
$attRecords = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($mode === 'in') {
    $hasActive = false;

    foreach ($attRecords as $record) {
        if (empty($record['time_out']) && empty($record['time_out_requested'])) {
            $hasActive = true;
            break;
        }
    }

    if ($hasActive) {
        exit(json_encode([
            'status' => 'already_timed_in',
            'message' => '⚠️ You have already timed in and not timed out.'
        ]));
    }

    $items = [];
    $itemQuery = $conn->query("SELECT DISTINCT item_name FROM items_tags WHERE is_available = 1");
    while ($row = $itemQuery->fetch_assoc()) {
        $items[] = $row['item_name'];
    }

    $locations = [];
    $locQuery = $conn->query("SELECT DISTINCT location_name FROM locations ORDER BY location_name ASC");
    while ($row = $locQuery->fetch_assoc()) {
        $locations[] = $row['location_name'];
    }

    echo json_encode([
        'status' => 'require_inputs',
        'message' => '✅ Roll number valid. Please select item, tag, and location.',
        'roll_number' => $roll,
        'items' => $items,
        'locations' => $locations
    ]);
    exit;
}

if ($mode === 'out') {
    $active = null;
    foreach ($attRecords as $record) {
        if (empty($record['time_out']) && empty($record['time_out_requested'])) {
            $active = $record;
            break;
        }
    }

    if (!$active) {
        exit(json_encode([
            'status' => 'not_timed_in',
            'message' => '⚠️ No active Time In record found.'
        ]));
    }

    echo json_encode([
        'status' => 'ready_for_timeout',
        'message' => '✅ Proceed to confirm Time Out.',
        'roll_number' => $roll,
        'record_id' => $active['id'],
        'tag_number' => $active['tag_number']
    ]);
    exit;
}
