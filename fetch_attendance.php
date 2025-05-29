<?php
include 'db.php';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

// Filter by start date
if (!empty($start_date)) {
  $where[] = "date >= ?";
  $params[] = $start_date;
}

// Filter by end date
if (!empty($end_date)) {
  $where[] = "date <= ?";
  $params[] = $end_date;
}

// Filter by roll number or location (partial match)
if (!empty($search)) {
  $where[] = "(roll_number LIKE ? OR location LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$sql = "SELECT * FROM attendance";
if ($where) {
  $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY date DESC";

$stmt = $conn->prepare($sql);

// Bind parameters dynamically
if ($params) {
  $types = str_repeat("s", count($params)); // all params are strings
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$id = 1;

while ($row = $result->fetch_assoc()) {
  $row['index'] = $id++;
  $data[] = $row;
}

echo json_encode(['data' => $data]);
?>
