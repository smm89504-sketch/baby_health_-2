<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    echo json_encode(['error' => 'غير مصرح']);
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Fetch new (unread) notifications من آخر 5 دقائق
$query = "SELECT an.*, a.appointment_date, a.appointment_time, c.name as child_name, d.full_name as doctor_name
          FROM appointment_notifications an
          JOIN appointments a ON an.appointment_id = a.id
          JOIN children c ON a.child_id = c.id
          JOIN users d ON a.doctor_id = d.id
          WHERE an.user_id = ? 
          AND an.read_at IS NULL
          AND an.sent_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
          ORDER BY an.sent_at DESC
          LIMIT 10";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'خطأ في الاستعلام']);
    exit();
}

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();

echo json_encode([
    'success' => true,
    'count' => count($notifications),
    'notifications' => $notifications
]);
?>
