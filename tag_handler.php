<?php
header('Content-Type: application/json');
require 'db.php'; // adjust to your DB connection

$tag = $_POST['tag'] ?? '';
$token = $_POST['token'] ?? '';

if (empty($tag) || empty($token)) {
    echo json_encode(['status' => 'invalid']);
    exit;
}

// 1. Check if tag exists in DB
$check = $pdo->prepare("SELECT * FROM tags WHERE tag_code = ?");
$check->execute([$tag]);

if ($check->rowCount() === 0) {
    echo json_encode(['status' => 'invalid']);
    exit;
}

// 2. Check if tag is already assigned
$assigned = $pdo->prepare("SELECT * FROM attendance WHERE tag_code = ?");
$assigned->execute([$tag]);

if ($assigned->rowCount() > 0) {
    echo json_encode(['status' => 'taken']);
    exit;
}

// 3. Assign tag
$update = $pdo->prepare("UPDATE attendance SET tag_code = ? WHERE token = ?");
if ($update->execute([$tag, $token])) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error']);
}
