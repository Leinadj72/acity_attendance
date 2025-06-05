<?php
include 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? '';
$tag = $data['tag'] ?? '';

if (!$token || !$tag) {
    echo json_encode(['success' => false, 'message' => 'Token or tag missing']);
    exit;
}

$stmt = $conn->prepare("SELECT id, item, tag_number, time_in, time_out FROM attendance WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$row = $result->fetch_assoc();

if ($tag !== $row['tag_number']) {
    echo json_encode(['success' => false, 'message' => 'Tag does not match the item']);
    exit;
}

$id = $row['id'];

if (empty($row['time_in'])) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE attendance SET time_in = ? WHERE id = ?");
    $stmt->bind_param("si", $now, $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Time In recorded']);
} elseif (empty($row['time_out'])) {
    $stmt = $conn->prepare("UPDATE attendance SET time_out_requested = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Time Out requested. Awaiting admin approval.']);
} else {
    echo json_encode(['success' => false, 'message' => 'This token has already been used for Time In and Out']);
}
