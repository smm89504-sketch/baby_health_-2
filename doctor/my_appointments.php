<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Color preparation and design
$main_dark = '#842029'; 
$main_text = '#dc3545'; 
$main_light = '#f5c6cb'; 
$main_deep = '#f1aeb5'; 
$bg_light = '#f8d7da'; 
$title_icon = 'fas fa-calendar-check';
$dashboard_link = 'index.php';
$user_type = 'doctor';
$base_path = '';
$parent_path = '../parent/';

// Appointment confirmation processing معالجة تأكيد الموعد
if (isset($_POST['action']) && $_POST['action'] === 'confirm' && isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE appointments SET appointment_status='confirmed' WHERE id=? AND doctor_id=?");
    $stmt->bind_param('ii', $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Appointment completion processing معالجة إكمال الموعد
if (isset($_POST['action']) && $_POST['action'] === 'complete' && isset($_POST['appointment_id'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE appointments SET appointment_status='completed' WHERE id=? AND doctor_id=?");
    $stmt->bind_param('ii', $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Get doctor appointments جلب حجوزات الطبيب
$sql = "SELECT a.*, c.name as child_name, u.full_name as parent_name, u.email, u.phone
        FROM appointments a 
        JOIN children c ON a.child_id = c.id 
        JOIN users u ON c.user_id = u.id 
        WHERE a.doctor_id = ? AND a.appointment_status != 'cancelled'
        ORDER BY a.appointment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجوزاتي</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<style>
    
</style>
<body>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <div class="main-container">
        <div class="dashboard-container">
            <div class="main-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div class="page-header" style="margin: 0;"><i class="<?= $title_icon ?>"></i> حجوزاتي</div>
                <a href="appointment_analytics.php" class="btn btn-info">
                    <i class="bi bi-graph-up"></i> التحليلات
                </a>
            </div>
            
            <?php if (empty($appointments)): ?>
                <div class="empty-message">
                    <i class="bi bi-calendar-check" style="font-size: 3rem; color: #ccc; display: block; margin-bottom: 15px;"></i>
                    لا توجد حجوزات حالياً
                </div>
            <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                    <div class="appointment-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <div class="child-name"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($appt['child_name']) ?></div>
                                <div class="appointment-info"><i class="bi bi-person"></i> ولي الأمر: <?= htmlspecialchars($appt['parent_name']) ?></div>
                                <div class="appointment-info"><i class="bi bi-calendar-event"></i> التاريخ: <?= date('d/m/Y', strtotime($appt['appointment_date'])) ?></div>
                                <div class="appointment-info"><i class="bi bi-clock"></i> الوقت: <?= date('H:i', strtotime($appt['appointment_date'])) ?></div>
                                <div class="appointment-info"><i class="bi bi-telephone"></i> الهاتف: <?= htmlspecialchars($appt['phone']) ?></div>
                                <div class="appointment-info"><i class="bi bi-envelope"></i> البريد: <?= htmlspecialchars($appt['email']) ?></div>
                                <div style="margin-top: 8px;">
                                    <span class="status-badge status-<?= $appt['appointment_status'] ?>">
                                        <?php 
                                        if ($appt['appointment_status'] === 'scheduled') echo 'قيد الانتظار';
                                        elseif ($appt['appointment_status'] === 'confirmed') echo 'مؤكد';
                                        elseif ($appt['appointment_status'] === 'completed') echo 'مكتمل';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div style="margin-left: 15px; display: flex; flex-direction: column; gap: 5px;">
                                <a href="appointment_details.php?id=<?= $appt['id'] ?>" class="btn btn-sm btn-primary btn-action">
                                    <i class="bi bi-eye"></i> التفاصيل
                                </a>
                                <?php if ($appt['appointment_status'] === 'scheduled'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="confirm">
                                        <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success btn-action">
                                            <i class="bi bi-check-circle"></i> تأكيد
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($appt['appointment_status'] !== 'completed' && $appt['appointment_status'] !== 'cancelled'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="appointment_id" value="<?= $appt['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-info btn-action">
                                            <i class="bi bi-check2"></i> إكمال
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
