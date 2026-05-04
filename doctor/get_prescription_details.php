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

$id = (int)($_GET['id'] ?? 0);

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'معرف الوصفة مطلوب']);
    exit();
}

try {
    //bring data الوصفة
    $stmt = $conn->prepare("
        SELECT p.*, c.name as child_name, pr.full_name as parent_name
        FROM prescriptions p
        JOIN children c ON p.child_id = c.id
        JOIN users pr ON c.user_id = pr.id
        WHERE p.id = ? AND p.doctor_id = ?
    ");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'الوصفة غير موجودة']);
        exit();
    }

    $prescription = $result->fetch_assoc();

    // جلب الأدوية الموصوفة
    $stmt = $conn->prepare("
        SELECT pm.*, m.name, m.dosage_form, m.concentration,
               pm.notes AS instructions,
               pm.duration_days AS duration
        FROM prescription_medications pm
        JOIN medications m ON pm.medication_id = m.id
        WHERE pm.prescription_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $medications_result = $stmt->get_result();

    $medications = [];
    while ($med = $medications_result->fetch_assoc()) {
        $medications[] = $med;
    }

    $prescription['medications'] = $medications;

    echo json_encode(['success' => true, 'data' => $prescription]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()]);
}
?>