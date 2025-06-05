<?php
date_default_timezone_set("Africa/Accra");

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "acity_attendance";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

?>
