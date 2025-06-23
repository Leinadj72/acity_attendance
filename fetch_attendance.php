<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

include 'db.php';

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$search = $_POST['search'] ?? '';
$tag_number = $_POST['tag_number'] ?? '';
$pending_only = isset($_POST['pending_only']) && $_POST['pending_only'] === "1";

$where = "1";
$params = [];
$types = "";

if (!empty($start_date)) {
  $where .= " AND date >= ?";
  $params[] = $start_date;
  $types .= "s";
}
if (!empty($end_date)) {
  $where .= " AND date <= ?";
  $params[] = $end_date;
  $types .= "s";
}

if (!empty($search)) {
  $where .= " AND (roll_number LIKE ? OR location LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $types .= "ss";
}

if (!empty($tag_number)) {
  $where .= " AND tag_number LIKE ?";
  $params[] = "%$tag_number%";
  $types .= "s";
}

if ($pending_only) {
  $where .= " AND (time_out IS NULL AND (time_out_requested IS NULL OR time_out_requested = 0))";
}

$sql = "SELECT * FROM attendance WHERE $where ORDER BY id DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$data = [];
$index = 1;

while ($row = $result->fetch_assoc()) {
  if ($row['time_out'] && $row['time_out_approved']) {
    $status = "Completed";
  } elseif ($row['time_out_requested'] && !$row['time_out_approved']) {
    $status = "Pending";
  } elseif (!$row['time_out'] && !$row['time_out_requested']) {
    $status = "Active";
  } elseif ($row['time_out_requested'] && $row['time_out_approved'] == 0) {
    $status = "Rejected";
  } else {
    $status = "Unknown";
  }

  $data[] = [
    'index' => $index++,
    'date' => $row['date'],
    'roll_number' => $row['roll_number'],
    'name' => $row['name'] ?? '--',
    'email' => $row['email'] ?? '--',
    'phone' => $row['phone'] ?? '--',
    'item' => $row['item'],
    'tag_number' => $row['tag_number'],
    'location' => $row['location'],
    'time_in' => $row['time_in'],
    'time_out' => $row['time_out'],
    'time_out_requested_at' => $row['time_out_requested_at'],
    'status' => $status,
    'id' => $row['id'],
    'time_out_requested' => $row['time_out_requested'],
    'time_out_approved' => $row['time_out_approved'],
    'approved_by' => $row['approved_by'] ?? '--',
    'rejected_by' => $row['rejected_by'] ?? '--',
    'edited_by' => $row['edited_by'] ?? '--'
  ];
}

echo json_encode(['data' => $data]);
