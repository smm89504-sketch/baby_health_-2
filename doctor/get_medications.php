<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

require_once '../includes/db_config.php';

header('Content-Type: application/json');

$db = new DatabaseHelper();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT * FROM medications ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $medications = [];
    while ($row = $result->fetch_assoc()) {
        $medications[] = $row;
    }

    echo json_encode(['success' => true, 'medications' => $medications]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()]);
}
?>