<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$token = trim($_POST['roll_number'] ?? '');
$mode = trim($_POST['mode'] ?? '');

if (!$token) {
    exit(json_encode(['status' => 'invalid', 'message' => '❌ QR code token is missing.']));
}

if (!in_array($mode, ['in', 'out'])) {
    exit(json_encode(['status' => 'invalid_mode', 'message' => '❌ Invalid mode selected.']));
}

// 🔐 Verify with System 2.0
$action = 'NWtVMUpiQkVDN0pXNTBKNXlrNWdxY0RlNFFFbzJ2a1l3UUVrbzVFVm5GRT0=';
$endpoint = "https://acityplus.acity.edu.gh/api_student_details_item_request/?token=" . urlencode($token) . "&action=" . urlencode($action);

$response = @file_get_contents($endpoint);
if (!$response) {
    exit(json_encode(['status' => 'invalid', 'message' => '❌ No response from System 2.0.']));
}

$data = json_decode($response, true);
if (!empty($data['data']['student_roll_number'])) {
    $student = [
        'roll_number' => $data['data']['student_roll_number'],
        'name' => $data['data']['name'] ?? '',
        'email' => $data['data']['email'] ?? '',
        'phone' => $data['data']['phone'] !== 'NULL' ? $data['data']['phone'] : ($data['data']['phone2'] ?? ''),
    ];
    $roll_number = $student['roll_number'];
} else {
    exit(json_encode(['status' => 'invalid', 'message' => '❌ Invalid QR code or student not found.']));
};

$roll_number = $student['roll_number'];
$today = date('Y-m-d');

// 📅 Check today's attendance
$stmt = $conn->prepare("SELECT * FROM attendance WHERE roll_number = ? AND date = ? ORDER BY id DESC");
$stmt->bind_param("ss", $roll_number, $today);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($mode === 'in') {
    foreach ($records as $record) {
        if (empty($record['time_out']) && (empty($record['time_out_requested']) || $record['time_out_requested'] == 0)) {
            exit(json_encode(['status' => 'already_timed_in', 'message' => '⚠️ You have already timed in today.']));
        }
    }

    // 📦 Available items
    $items = [];
    $itemQuery = $conn->query("SELECT DISTINCT item_name FROM items_tags WHERE is_available = 1");
    while ($row = $itemQuery->fetch_assoc()) {
        $items[] = $row['item_name'];
    }

    // 📍 Available locations
    $locations = [];
    $locQuery = $conn->query("SELECT location_name FROM locations");
    while ($row = $locQuery->fetch_assoc()) {
        $locations[] = $row['location_name'];
    }

    echo json_encode([
        'status' => 'require_inputs',
        'message' => '✅ QR code verified. Please complete Time In.',
        'roll_number' => $roll_number,
        'student' => $student,
        'items' => $items,
        'locations' => $locations
    ]);
    exit;
}

if ($mode === 'out') {
    foreach ($records as $record) {
        if (empty($record['time_out']) && empty($record['time_out_requested']) && empty($record['time_out_approved'])) {
            exit(json_encode([
                'status' => 'ready_for_timeout',
                'message' => '✅ You may proceed to Time Out.',
                'roll_number' => $roll_number,
                'tag_number' => $record['tag_number'],
                'record_id' => $record['id']
            ]));
        }
    }

    exit(json_encode(['status' => 'not_timed_in', 'message' => '⚠️ You have not timed in yet today.']));
}

exit(json_encode(['status' => 'error', 'message' => '❌ An unexpected error occurred.']));
