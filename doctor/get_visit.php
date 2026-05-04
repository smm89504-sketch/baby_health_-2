<?php
require_once '../includes/db_config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

$db = new DatabaseHelper();
$conn = $db->getConnection();

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT mv.id, mv.diagnosis, mv.prescription, mv.notes, mv.visit_date, c.name as child_name
    FROM medical_visits mv
    JOIN children c ON mv.child_id = c.id
    WHERE mv.id = ? AND mv.doctor_id = ?
");

$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');

if ($visit = $result->fetch_assoc()) {
    echo json_encode([
        'id' => $visit['id'],
        'diagnosis' => $visit['diagnosis'],
        'prescription' => $visit['prescription'],
        'notes' => $visit['notes'],
        'visit_date' => $visit['visit_date'],
        'child_name' => $visit['child_name']
    ]);
} else {
    echo json_encode(['error' => 'الزيارة غير موجودة']);
}