<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    echo json_encode(['error' => 'غير مصرح']);
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$parent_id = (int)($_GET['parent_id'] ?? 0);
if (!$parent_id) {
    echo json_encode(['error' => 'معرف ولي الأمر مطلوب']);
    exit();
}

$query = "SELECT m.message, DATE_FORMAT(m.created_at, '%d/%m/%Y %H:%i') as time
          FROM messages m
          WHERE m.sender_id = ? AND m.recipient_id = ? AND m.status = 'pending'
          ORDER BY m.created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $parent_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$new_messages = [];
while ($row = $result->fetch_assoc()) {
    $new_messages[] = $row;
}

echo json_encode(['new_messages' => $new_messages]);
