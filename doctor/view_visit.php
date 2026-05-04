<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$visit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$visit_id) {
    header('Location: medical_visits.php');
    exit;
}

$stmt = $conn->prepare("SELECT mv.*, c.name AS child_name, c.birth_date, u.full_name AS doctor_name FROM medical_visits mv JOIN children c ON mv.child_id = c.id JOIN users u ON mv.doctor_id = u.id WHERE mv.id = ? AND mv.doctor_id = ?");
$stmt->bind_param('ii', $visit_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$visit = $result->fetch_assoc();

if (!$visit) {
    echo '<div class="alert alert-danger">الزيارة غير موجودة أو غير مخولة.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض الزيارة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h2>تفاصيل الزيارة</h2>
        <table class="table table-bordered">
            <tr><th>الطفل</th><td><?php echo htmlspecialchars($visit['child_name']); ?></td></tr>
            <tr><th>تاريخ الميلاد</th><td><?php echo htmlspecialchars($visit['birth_date']); ?></td></tr>
            <tr><th>الطبيب</th><td><?php echo htmlspecialchars($visit['doctor_name']); ?></td></tr>
            <tr><th>تاريخ الزيارة</th><td><?php echo htmlspecialchars($visit['visit_date']); ?></td></tr>
            <tr><th>التشخيص</th><td><?php echo nl2br(htmlspecialchars($visit['diagnosis'])); ?></td></tr>
            <tr><th>الوصفة</th><td><?php echo nl2br(htmlspecialchars($visit['prescription'])); ?></td></tr>
            <tr><th>ملاحظات</th><td><?php echo nl2br(htmlspecialchars($visit['notes'])); ?></td></tr>
        </table>
        <a href="medical_visits.php" class="btn btn-secondary">عودة</a>
    </div>
</body>
</html>
