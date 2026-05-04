<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$doctor_id = (int)($_GET['doctor_id'] ?? 0);

if ($doctor_id) {
    $stmt = $conn->prepare("UPDATE messages SET status = 'responded' WHERE sender_id = ? AND recipient_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $doctor_id, $_SESSION['user_id']);
    $stmt->execute();
}

echo 'OK';
?>