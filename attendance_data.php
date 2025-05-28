<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

function escape($conn, $str) {
  return mysqli_real_escape_string($conn, $str);
}

// Get POST parameters from DataTables
$draw = $_POST['draw'] ?? 1;
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$searchValue = $_POST['search']['value'] ?? '';

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$search_roll_location = $_POST['search_roll_location'] ?? '';

// Handle individual record fetch for editing
if (isset($_POST['action']) && $_POST['action'] === 'get' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $res = mysqli_query($conn, "SELECT * FROM attendance WHERE id = $id LIMIT 1");
  if ($res && mysqli_num_rows($res) == 1) {
    $record = mysqli_fetch_assoc($res);
    echo json_encode(['success' => true, 'record' => $record]);
  } else {
    echo json_encode(['success' => false]);
  }
  exit;
}

// Total records without filtering
$totalRecordsQuery = "SELECT COUNT(*) as count FROM attendance";
$totalRecordsResult = mysqli_query($conn, $totalRecordsQuery);
$totalRecords = $totalRecordsResult ? mysqli_fetch_assoc($totalRecordsResult)['count'] : 0;

// Build WHERE clause
$where = [];

if ($start_date !== '') {
  $start_date_esc = escape($conn, $start_date);
  $where[] = "date >= '$start_date_esc'";
}
if ($end_date !== '') {
  $end_date_esc = escape($conn, $end_date);
  $where[] = "date <= '$end_date_esc'";
}
if ($search_roll_location !== '') {
  $search_rl_esc = escape($conn, $search_roll_location);
  $where[] = "(roll_number LIKE '%$search_rl_esc%' OR location LIKE '%$search_rl_esc%')";
}
if ($searchValue !== '') {
  $searchValEsc = escape($conn, $searchValue);
  $where[] = "(roll_number LIKE '%$searchValEsc%' OR location LIKE '%$searchValEsc%' OR item LIKE '%$searchValEsc%')";
}

$whereSql = '';
if (count($where) > 0) {
  $whereSql = ' WHERE ' . implode(' AND ', $where);
}

// Total filtered records
$totalFilteredQuery = "SELECT COUNT(*) as count FROM attendance $whereSql";
$totalFilteredResult = mysqli_query($conn, $totalFilteredQuery);
$totalFiltered = $totalFilteredResult ? mysqli_fetch_assoc($totalFilteredResult)['count'] : 0;

// Ordering
$orderColumnIndex = intval($_POST['order'][0]['column'] ?? 1);
$orderDir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';

$columns = ['id', 'date', 'roll_number', 'location', 'item', 'time_in', 'time_out'];
$orderColumn = $columns[$orderColumnIndex] ?? 'date';

// Fetch records with limits
$query = "SELECT * FROM attendance $whereSql ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = $row;
}

// Prepare response
$response = [
  "draw" => intval($draw),
  "recordsTotal" => intval($totalRecords),
  "recordsFiltered" => intval($totalFiltered),
  "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
