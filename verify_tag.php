include 'db.php';
header('Content-Type: application/json');

$tag = trim($_POST['tag'] ?? '');
$item = trim($_POST['item'] ?? '');
$roll_number = trim($_POST['roll_number'] ?? '');

// Time In verification
if (!empty($tag) && !empty($item)) {
$stmt = $conn->prepare("SELECT id FROM items_tags WHERE tag_number = ? AND item_name = ? AND is_available = 1");
$stmt->bind_param("ss", $tag, $item);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode(['valid' => $result->num_rows > 0]);
exit;
}

// Time Out verification
if (!empty($tag) && !empty($roll_number)) {
$today = date('Y-m-d');

$stmt = $conn->prepare("
SELECT id FROM attendance
WHERE tag_number = ? AND roll_number = ?
AND date = ?
AND time_out IS NULL
AND (time_out_requested IS NULL OR time_out_requested = 0)
AND time_out_approved = 0
");
$stmt->bind_param("sss", $tag, $roll_number, $today);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode(['valid' => $result->num_rows > 0]);
exit;
}

// If no valid input
echo json_encode(['valid' => false]);