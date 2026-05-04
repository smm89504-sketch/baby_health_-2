<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_visit'])) {
    $visit_id = (int)$_POST['visit_id'];
    $diagnosis = $_POST['diagnosis'] ?? '';
    $prescription = $_POST['prescription'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $doctor_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE medical_visits 
                            SET diagnosis = ?, prescription = ?, notes = ? 
                            WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("sssii", $diagnosis, $prescription, $notes, $visit_id, $doctor_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "تم تحديث الزيارة بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء تحديث الزيارة";
    }
}

header('Location: medical_visits.php');
exit();