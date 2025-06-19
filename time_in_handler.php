<?php
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$token = $_POST['token'] ?? '';
$action = 'NWtVMUpiQkVDN0pXNTBKNXlrNWdxY0RlNFFFbzJ2a1l3UUVrbzVFVm5GRT0=';
$item = $_POST['item'] ?? '';
$tag_number = $_POST['tag_number'] ?? '';
$location = $_POST['location'] ?? '';
$date = date('Y-m-d');
$time_in = date('H:i:s');

if (empty($token) || empty($item) || empty($tag_number) || empty($location)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// 🔁 Step 1: Fetch user details from System 2.0 using cURL
$endpoint = "https://acityplus.acity.edu.gh/api_student_details_item_request/?token=" . urlencode($token) . "&action=" . urlencode($action);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// Optional: Log response for debugging
file_put_contents('debug_post.log', $response ?: $curlError);

if (!$response) {
    echo json_encode(['success' => false, 'message' => 'Invalid response from System 2.0.']);
    exit;
}

$data = json_decode($response, true);
if (!isset($data['status']) || $data['status'] !== 1 || !isset($data['data'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid user data from System 2.0.']);
    exit;
}

$user = $data['data'];

$roll_number = $user['student_roll_number'] ?? '';
$name = $user['name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? ($user['phone2'] ?? '');

// 🔒 Step 2: Ensure student does not already have an active attendance
$activeCheck = $conn->prepare("
    SELECT id FROM attendance 
    WHERE roll_number = ? 
      AND date = ? 
      AND time_out IS NULL 
      AND (time_out_requested IS NULL OR time_out_requested = 0)
      AND time_out_approved = 0
");
$activeCheck->bind_param("ss", $roll_number, $date);
$activeCheck->execute();
$activeCheck->store_result();

if ($activeCheck->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '⚠️ You already have an active item. Return it before taking another.']);
    $activeCheck->close();
    exit;
}
$activeCheck->close();

// 🔒 Step 3: Ensure the same item at the same location is not in use
$itemConflict = $conn->prepare("
    SELECT id FROM attendance 
    WHERE item = ? AND location = ? 
      AND date = ? 
      AND time_out IS NULL 
      AND (time_out_requested IS NULL OR time_out_requested = 0)
      AND time_out_approved = 0
");
$itemConflict->bind_param("sss", $item, $location, $date);
$itemConflict->execute();
$itemConflict->store_result();

if ($itemConflict->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '⚠️ This item is already in use at this location.']);
    $itemConflict->close();
    exit;
}
$itemConflict->close();

// 🔁 Step 4: Check if tag is already in use (no time_out)
$check = $conn->prepare("SELECT id FROM attendance WHERE tag_number = ? AND time_out IS NULL");
$check->bind_param("s", $tag_number);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '⚠️ This tag number is already in use.']);
    $check->close();
    exit;
}
$check->close();


// ✅ Step 3: Save attendance
$stmt = $conn->prepare("INSERT INTO attendance (roll_number, name, email, phone, date, item, tag_number, location, time_in, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssssssss", $roll_number, $name, $email, $phone, $date, $item, $tag_number, $location, $time_in);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'status' => 'success', 'message' => 'Time In recorded successfully.', 'redirect' => 'scan.php']);
} else {
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Failed to save attendance.']);
}

$stmt->close();
$conn->close();
