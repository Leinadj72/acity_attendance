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
$types = '';

try {
  // 📅 Date filters
  if (!empty($start_date)) {
    $where[] = "date >= ?";
    $params[] = $start_date;
    $types .= 's';
  }

  if (!empty($end_date)) {
    $where[] = "date <= ?";
    $params[] = $end_date;
    $types .= 's';
  }

  // 🔍 Search filter
  if (!empty($search)) {
    $where[] = "(roll_number LIKE CONCAT('%', ?, '%') OR location LIKE CONCAT('%', ?, '%'))";
    $params[] = $search;
    $params[] = $search;
    $types .= 'ss';
  }

  // 🏷️ Tag number filter
  if (!empty($tag_number)) {
    $where[] = "tag_number LIKE CONCAT('%', ?, '%')";
    $params[] = $tag_number;
    $types .= 's';
  }

  // 🕒 Pending Time Out requests only
  if ($pending_only === '1') {
    $where[] = "(time_out_requested = 1 AND time_out_approved = 0 AND time_out IS NULL)";
  }

  // 📦 Main query
  $sql = "SELECT 
                id,
                date,
                roll_number,
                name,
                email,
                phone,
                location,
                item,
                tag_number,
                TIME(time_in) as time_in,
                TIME(time_out) as time_out,
                time_out_requested,
                TIME(time_out_requested_at) as time_out_requested_at,
                time_out_approved,
                created_at
            FROM attendance";

  if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }

  $sql .= " ORDER BY 
    (time_out IS NULL) DESC,
    date DESC, 
    created_at DESC";

  $stmt = $conn->prepare($sql);

  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }

  $stmt->execute();
  $result = $stmt->get_result();

  $data = [];
  $index = 1;

  while ($row = $result->fetch_assoc()) {
    // 🟡 Determine status
    $status = 'Active';
    if ($row['time_out']) {
      $status = 'Completed';
    } elseif ($row['time_out_requested'] && $row['time_out_approved']) {
      $status = 'Approved';
    } elseif ($row['time_out_requested'] && !$row['time_out_approved']) {
      $status = 'Pending';
    }

    $data[] = [
      'index' => $index++,
      'id' => (int)$row['id'],
      'date' => $row['date'],
      'roll_number' => $row['roll_number'],
      'name' => $row['name'],
      'email' => $row['email'],
      'phone' => $row['phone'],
      'location' => $row['location'],
      'item' => $row['item'],
      'tag_number' => $row['tag_number'],
      'time_in' => $row['time_in'] ?? null,
      'time_out' => $row['time_out'] ?? null,
      'time_out_requested' => (bool)$row['time_out_requested'],
      'time_out_requested_at' => $row['time_out_requested_at'] ?? null,
      'time_out_approved' => isset($row['time_out_approved']) ? (bool)$row['time_out_approved'] : null,
      'status' => $status,
      'created_at' => $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : null
    ];
  }

  echo json_encode([
    'success' => true,
    'data' => $data,
    'count' => count($data)
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Server error while fetching attendance records.',
    'message' => $e->getMessage() // Optional: remove in production
  ]);
}
