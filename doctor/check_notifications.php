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

$query_notifications = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($query_notifications);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread_count'];

$query_messages = "SELECT COUNT(*) as unread_messages FROM messages WHERE recipient_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query_messages);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$unread_messages = $stmt->get_result()->fetch_assoc()['unread_messages'];

echo json_encode([
    'unread_count' => $unread_count,
    'unread_messages' => $unread_messages
]);
