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
    $stmt = $conn->prepare("
        SELECT c.id, c.name, c.name as full_name, p.full_name as parent_name, c.birth_date
        FROM children c
        JOIN users p ON c.user_id = p.id
        ORDER BY c.name ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $children = [];
    while ($row = $result->fetch_assoc()) {
        // حساب العمر
        $birth_date = new DateTime($row['birth_date']);
        $today = new DateTime();
        $age = $today->diff($birth_date);

        $row['age_years'] = $age->y;
        $row['age_months'] = $age->m;
        $row['age_days'] = $age->d;

        $children[] = $row;
    }

    echo json_encode(['success' => true, 'children' => $children]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()]);
}
?>