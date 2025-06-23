<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $admin = $result->fetch_assoc();

  if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $admin['username'];
    header("Location: view_attendance.php");
    exit;
  } else {
    $error = "Invalid credentials.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Admin Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="container py-5">
  <h2>Admin Login</h2>
  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
  <form method="POST">
    <div class="mb-3">
      <label>Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary">Login</button>
  </form>
</body>

</html>