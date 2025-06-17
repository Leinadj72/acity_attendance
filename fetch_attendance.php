<?php
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$pending_only = $_GET['pending_only'] ?? '0';
$tag_number = $_GET['tag_number'] ?? '';

$where = [];
$params = [];

try {
  if (!empty($start_date)) {
    $where[] = "date >= ?";
    $params[] = $start_date;
  }

  if (!empty($end_date)) {
    $where[] = "date <= ?";
    $params[] = $end_date;
  }

  if (!empty($search)) {
    $where[] = "(roll_number LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
  }

  if (!empty($tag_number)) {
    $where[] = "tag_number LIKE ?";
    $params[] = "%$tag_number%";
  }

  if ($pending_only === '1') {
    $where[] = "(time_out_requested = 1 AND time_out_approved = 0)";
  }

  $sql = "SELECT * FROM attendance";
  if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }
  $sql .= " ORDER BY date DESC";

  $stmt = $conn->prepare($sql);

  if ($params) {
    $types = str_repeat("s", count($params));
    $stmt->bind_param($types, ...$params);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  $id = 1;

  while ($row = $result->fetch_assoc()) {
    if ($row['time_out_requested'] && $row['time_out_approved']) {
      $status = 'Approved';
    } elseif ($row['time_out_requested'] && $row['time_out_approved'] === '0') {
      $status = 'Rejected';
    } elseif ($row['time_out_requested']) {
      $status = 'Pending';
    } else {
      $status = 'Not Requested';
    }

    $row['index'] = $id++;
    $row['status'] = $status;

    $row['time_in'] = $row['time_in'] ? date('Y-m-d H:i:s', strtotime($row['time_in'])) : '';
    $row['time_out'] = $row['time_out'] ? date('Y-m-d H:i:s', strtotime($row['time_out'])) : '';
    $row['created_at'] = $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '';

    $data[] = $row;
  }

  echo json_encode(['data' => $data]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'data' => [],
    'error' => 'Server error while fetching attendance records.'
  ]);
}
?>
