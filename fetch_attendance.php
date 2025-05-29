<?php
include 'db.php';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$pending_only = $_GET['pending_only'] ?? '0';

$where = [];
$params = [];

// Filter by date range
if (!empty($start_date)) {
  $where[] = "date >= ?";
  $params[] = $start_date;
}

if (!empty($end_date)) {
  $where[] = "date <= ?";
  $params[] = $end_date;
}

// Filter by search keyword (roll number or location)
if (!empty($search)) {
  $where[] = "(roll_number LIKE ? OR location LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

// Filter for pending approval only
if ($pending_only === '1') {
  $where[] = "(time_out_requested = 1 AND time_out_approved = 0)";
}

// Build the SQL query
$sql = "SELECT * FROM attendance";
if ($where) {
  $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY date DESC";

$stmt = $conn->prepare($sql);

// Bind parameters if needed
if ($params) {
  $types = str_repeat("s", count($params));
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$id = 1;

// Format data for DataTables
while ($row = $result->fetch_assoc()) {
  $row['index'] = $id++;
  $data[] = $row;
}

// Output JSON for DataTables
echo json_encode(['data' => $data]);
?>
