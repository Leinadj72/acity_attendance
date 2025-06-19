<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

function escape($conn, $str)
{
  return mysqli_real_escape_string($conn, $str);
}

$draw = intval($_POST['draw'] ?? 1);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$searchValue = $_POST['search']['value'] ?? '';

$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$search_roll_location = $_POST['search_roll_location'] ?? '';
$tag_number = $_POST['tag_number'] ?? '';
$pending_only = isset($_POST['pending_only']) && $_POST['pending_only'] === "1";

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
  $s = escape($conn, $search_roll_location);
  $where[] = "(roll_number LIKE '%$s%' OR location LIKE '%$s%')";
}
if (!empty($tag_number)) {
  $t = escape($conn, $tag_number);
  $where[] = "tag_number LIKE '%$t%'";
}
if (!empty($searchValue)) {
  $s = escape($conn, $searchValue);
  $where[] = "(roll_number LIKE '%$s%' OR location LIKE '%$s%' OR item LIKE '%$s%' OR tag_number LIKE '%$s%')";
}
if ($pending_only) {
  $where[] = "(time_out IS NULL AND (time_out_requested IS NULL OR time_out_requested = 0))";
}

$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$totalRecordsQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance");
$totalRecords = $totalRecordsQuery ? mysqli_fetch_assoc($totalRecordsQuery)['count'] : 0;

$totalFilteredQuery = mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance $whereSql");
$totalFiltered = $totalFilteredQuery ? mysqli_fetch_assoc($totalFilteredQuery)['count'] : 0;

$orderColumnIndex = intval($_POST['order'][0]['column'] ?? 1);
$orderDir = ($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$columns = ['id', 'date', 'roll_number', 'location', 'item', 'time_in', 'time_out', 'time_out_requested', 'time_out_approved', 'time_out_requested_at'];
$orderColumn = $columns[$orderColumnIndex] ?? 'date';

$query = "
  SELECT * FROM attendance 
  $whereSql 
  ORDER BY 
    (time_out IS NULL OR time_out = '') DESC, 
    $orderColumn $orderDir 
  LIMIT $start, $length
";

$result = mysqli_query($conn, $query);
$data = [];

if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $status = 'Active';
    if (!empty($row['time_out'])) {
      $status = 'Completed';
    } elseif ($row['time_out_requested'] && !$row['time_out_approved']) {
      $status = 'Pending';
    } elseif ($row['time_out_requested'] && $row['time_out_approved'] == 0) {
      $status = 'Rejected';
    }

    $data[] = [
      'id' => $row['id'],
      'date' => $row['date'],
      'roll_number' => $row['roll_number'],
      'name' => $row['name'] ?? '--',
      'email' => $row['email'] ?? '--',
      'phone' => $row['phone'] ?? '--',
      'item' => $row['item'],
      'tag_number' => $row['tag_number'],
      'location' => $row['location'],
      'time_in' => $row['time_in'] ? date('H:i:s', strtotime($row['time_in'])) : '',
      'time_out' => $row['time_out'] ? date('Y-m-d H:i:s', strtotime($row['time_out'])) : '',
      'time_out_requested_at' => $row['time_out_requested_at'] ? date('Y-m-d H:i:s', strtotime($row['time_out_requested_at'])) : '',
      'status' => $status,
      'time_out_requested' => $row['time_out_requested'],
      'time_out_approved' => $row['time_out_approved']
    ];
  }
} else {
  http_response_code(500);
  echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalFiltered,
    'data' => [],
    'error' => 'Query failed.'
  ]);
  exit;
}

echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $totalRecords,
  'recordsFiltered' => $totalFiltered,
  'data' => $data
]);
