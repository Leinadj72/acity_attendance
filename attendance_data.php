<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

function escape($conn, $str) {
  return mysqli_real_escape_string($conn, $str);
}

$draw = $_POST['draw'] ?? 1;
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$searchValue = $_POST['search']['value'] ?? '';

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$search_roll_location = $_POST['search_roll_location'] ?? '';

if (isset($_POST['action']) && $_POST['action'] === 'get' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $res = mysqli_query($conn, "SELECT * FROM attendance WHERE id = $id LIMIT 1");
  if ($res && mysqli_num_rows($res) === 1) {
    $record = mysqli_fetch_assoc($res);
    echo json_encode(['success' => true, 'record' => $record]);
  } else {
    echo json_encode(['success' => false, 'error' => 'Record not found']);
  }
  exit;
}

$totalRecordsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance");
$totalRecords = $totalRecordsResult ? mysqli_fetch_assoc($totalRecordsResult)['count'] : 0;

$where = [];

if (!empty($start_date)) {
  $start_date_esc = escape($conn, $start_date);
  $where[] = "date >= '$start_date_esc'";
}
if (!empty($end_date)) {
  $end_date_esc = escape($conn, $end_date);
  $where[] = "date <= '$end_date_esc'";
}
if (!empty($search_roll_location)) {
  $search_rl_esc = escape($conn, $search_roll_location);
  $where[] = "(roll_number LIKE '%$search_rl_esc%' OR location LIKE '%$search_rl_esc%')";
}
if (!empty($searchValue)) {
  $searchValEsc = escape($conn, $searchValue);
  $where[] = "(roll_number LIKE '%$searchValEsc%' OR location LIKE '%$searchValEsc%' OR item LIKE '%$searchValEsc%')";
}

$whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

$totalFilteredResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance $whereSql");
$totalFiltered = $totalFilteredResult ? mysqli_fetch_assoc($totalFilteredResult)['count'] : 0;

$orderColumnIndex = intval($_POST['order'][0]['column'] ?? 1);
$orderDir = ($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$columns = ['id', 'date', 'roll_number', 'location', 'item', 'time_in', 'time_out', 'time_out_approved'];
$orderColumn = $columns[$orderColumnIndex] ?? 'date';

$query = "SELECT * FROM attendance $whereSql ORDER BY time_out IS NULL DESC, $orderColumn $orderDir LIMIT $start, $length";
$result = mysqli_query($conn, $query);

$data = [];
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
  }
} else {
  http_response_code(500);
  echo json_encode([
    'draw' => intval($draw),
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => [],
    'error' => 'Failed to fetch attendance data.'
  ]);
  exit;
}

$response = [
  'draw' => intval($draw),
  'recordsTotal' => $totalRecords,
  'recordsFiltered' => $totalFiltered,
  'data' => $data
];

echo json_encode($response);
?>
