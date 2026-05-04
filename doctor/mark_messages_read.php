<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$parent_id = (int)($_GET['parent_id'] ?? 0);

if ($parent_id) {
    $stmt = $conn->prepare("UPDATE messages SET status = 'responded' WHERE sender_id = ? AND recipient_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $parent_id, $_SESSION['user_id']);
    $stmt->execute();
}

echo 'OK';
