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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_date = $_POST['visit_date'];
    $diagnosis = $_POST['diagnosis'];
    $prescription = $_POST['prescription'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE medical_visits SET visit_date = ?, diagnosis = ?, prescription = ?, notes = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param('sssiii', $visit_date, $diagnosis, $prescription, $notes, $visit_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $message = 'تم حفظ التعديلات بنجاح';
    } else {
        $error = 'حدث خطأ أثناء حفظ التعديلات';
    }
}

$stmt = $conn->prepare("SELECT mv.*, c.name AS child_name FROM medical_visits mv JOIN children c ON mv.child_id = c.id WHERE mv.id = ? AND mv.doctor_id = ?");
$stmt->bind_param('ii', $visit_id, $_SESSION['user_id']);
$stmt->execute();
$visit = $stmt->get_result()->fetch_assoc();

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
    <title>تعديل الزيارة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
</head>
<body class="p-4">
    <div class="container">
        <h2>تعديل الزيارة - <?php echo htmlspecialchars($visit['child_name']); ?></h2>
        <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">تاريخ الزيارة</label>
                <input type="datetime-local" class="form-control" name="visit_date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($visit['visit_date']))); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">التشخيص</label>
                <textarea class="form-control" name="diagnosis" rows="3"><?php echo htmlspecialchars($visit['diagnosis']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">الوصفة الطبية</label>
                <textarea class="form-control" name="prescription" rows="3"><?php echo htmlspecialchars($visit['prescription']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">ملاحظات</label>
                <textarea class="form-control" name="notes" rows="2"><?php echo htmlspecialchars($visit['notes']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success">حفظ</button>
            <a href="medical_visits.php" class="btn btn-secondary">عودة</a>
        </form>
    </div>
</body>
</html>
