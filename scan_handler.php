<?php
include 'db.php';
date_default_timezone_set("Africa/Accra");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
  $token = $_POST['token'];

  $stmt = $conn->prepare("SELECT * FROM qr_tokens WHERE token = ?");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows !== 1) {
    echo "<div class='alert alert-danger'>❌ Invalid or expired QR Code.</div>";
    exit;
  }

  $row = $result->fetch_assoc();

  if ($row['status'] !== 'active') {
    echo "<div class='alert alert-danger'>❌ This QR Code is no longer active.</div>";
    exit;
  }

  $usage_count = (int)$row['usage_count'];
  $max_usage = (int)$row['max_usage'];
  $roll = htmlspecialchars($row['roll_number']);
  $location = htmlspecialchars($row['location']);
  $item = htmlspecialchars($row['item']);
  $date = date("Y-m-d");

  if ($usage_count >= $max_usage) {
    echo "<div class='alert alert-danger'>❌ QR Code already used for both Time In and Time Out.</div>";
    exit;
  }

  if ($usage_count === 0) {
    // Time In
    $timeIn = date("H:i:s");
    $stmt = $conn->prepare("INSERT INTO attendance (token, date, roll_number, location, item, time_in) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $token, $date, $roll, $location, $item, $timeIn);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = usage_count + 1 WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    echo "<div class='alert alert-success'>✅ Time In recorded at <strong>$timeIn</strong> for <strong>$roll</strong></div>";
  } elseif ($usage_count === 1) {
    // Time Out
    $timeOut = date("H:i:s");

    $stmt = $conn->prepare("UPDATE attendance SET time_out = ? WHERE token = ? AND date = ? AND time_out IS NULL");
    $stmt->bind_param("sss", $timeOut, $token, $date);
    $stmt->execute();

    $new_usage_count = $usage_count + 1;
    $new_status = ($new_usage_count >= $max_usage) ? 'inactive' : 'active';

    $stmt = $conn->prepare("UPDATE qr_tokens SET usage_count = ?, status = ? WHERE token = ?");
    $stmt->bind_param("iss", $new_usage_count, $new_status, $token);
    $stmt->execute();

    echo "<div class='alert alert-success'>✅ Time Out recorded at <strong>$timeOut</strong> for <strong>$roll</strong></div>";
  }
}
?>
