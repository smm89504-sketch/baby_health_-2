<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Get appointmentالموعد details
$appointment_id = $_GET['id'] ?? 0;
$appointment_id = intval($appointment_id);

if ($appointment_id <= 0) {
    header('Location: my_appointments.php');
    exit();
}

$query = "SELECT a.*, 
          c.name as child_name, c.birth_date,
          u.full_name as doctor_name, u.email as doctor_email, u.phone as doctor_phone, u.specialty,
          p.full_name as parent_name
          FROM appointments a
          JOIN children c ON a.child_id = c.id
          JOIN users u ON a.doctor_id = u.id
          JOIN users p ON c.user_id = p.id
          WHERE a.id = ? AND c.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: my_appointments.php');
    exit();
}

$appointment = $result->fetch_assoc();

// Setting variables
$base_path = './';
$dashboard_link = 'index.php';
$main_dark = '#ad1457';
$main_text = '#880e4f';
$main_light = '#ffd1dc';
$main_deep = '#f8bbd0';
$bg_light = '#fff0f5';
$title_icon = 'fas fa-file-medical';
$user_type = 'parent';
$unread_messages = 0;
$vaccine_alerts = ['missed' => [], 'upcoming' => []];

// Processing Procedures
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'cancel' && $appointment['appointment_status'] !== 'cancelled') {
            $update_query = "UPDATE appointments SET appointment_status = 'cancelled' WHERE id = ? AND id IN (
                               SELECT id FROM appointments a 
                               JOIN children c ON a.child_id = c.id 
                               WHERE c.user_id = ?
                            )";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
            if ($update_stmt->execute()) {
                $appointment['appointment_status'] = 'cancelled';
                $success_message = "تم إلغاء الموعد بنجاح";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الموعد الطبي</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin/admin_shared.css">
  <style>
    :root {
    --primary-light: <?= $bg_light ?>;
    --primary-pink: <?= $main_light ?>;
    --primary-deep: <?= $main_deep ?>;
    --primary-text: <?= $main_text ?>;
    --primary-dark: <?= $main_dark ?>;
}

/* عام */
body {
    margin: 0;
    background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%);
    font-family: 'Cairo', sans-serif;
    display: flex;
}

.main-container {
    flex: 1;
    padding: 20px;
}

.dashboard-container {
    max-width: 1000px;
    margin: auto;
}

/* الكارد الرئيسي */
.main-box {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 6px 24px rgba(0,0,0,0.08);
}

/* العنوان */
.page-header {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-text);
    margin-bottom: 25px;
    text-align: center;
}

/* الأقسام */
.section-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--primary-text);
    margin: 25px 0 15px;
    border-bottom: 2px solid var(--primary-light);
    padding-bottom: 8px;
}

/* grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
}

/* كارد صغير */
.info-card {
    background: #fff7fa;
    border: 1px solid #f3d6df;
    border-radius: 12px;
    padding: 15px;
}

.info-label {
    font-size: 0.8rem;
    color: #999;
}

.info-value {
    font-weight: 700;
    font-size: 1.1rem;
}

/* الحالة */
.status-badge {
    background: var(--primary-light);
    color: var(--primary-text);
    padding: 8px 16px;
    border-radius: 20px;
    display: inline-block;
    margin-bottom: 20px;
    font-weight: 600;
}

/* الطفل */
.child-info {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.child-box {
    flex: 1;
    min-width: 150px;
    background: #fff7fa;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
}

/* الطبيب */
.doctor-card {
    background: linear-gradient(135deg, var(--primary-text), var(--primary-dark));
    color: white;
    border-radius: 16px;
    padding: 20px;
}

.doctor-detail {
    background: rgba(255,255,255,0.15);
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 10px;
}

/* ملاحظات */
.notes-section {
    background: #fff7fa;
    border: 1px dashed #f3c6d2;
    border-radius: 12px;
    padding: 20px;
}

/* الأزرار */
.action-buttons {
    margin-top: 30px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-custom {
    border-radius: 12px;
    padding: 10px 20px;
    font-weight: 600;
    border: none;
}

.btn-primary-custom {
    background: var(--primary-text);
    color: white;
}

.btn-danger-custom {
    background: #dc3545;
    color: white;
}

.btn-secondary-custom {
    background: #6c757d;
    color: white;
}

/* hover */
.btn-custom:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}
     .sidebar { top:0; background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%); width: 250px; min-height: 100vh; padding: 20px; color: white; box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12); transition: all 0.3s; }
        .sidebar a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { color: white; transform: translateX(5px); }
        .sidebar .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255, 255, 255, 0.15); }
        .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar .logo i { color: var(--primary-pink); }
  </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include './includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <i class="<?= $title_icon ?>"></i>
                تفاصيل الموعد الطبي
            </div>

            <!-- Back Button -->
           

            <!-- Main Box -->
            <div class="main-box">
                <!-- Success Message -->
                <?php if (isset($success_message)): ?>
                    <div class="success-alert">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <!-- Status Badge -->
                <div>
                    <span class="status-badge status-<?= strtolower($appointment['appointment_status']) ?>">
                        📋 الحالة: 
                        <?php 
                            $status_map = [
                                'scheduled' => 'مجدول',
                                'confirmed' => 'مؤكد',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغى'
                            ];
                            echo $status_map[$appointment['appointment_status']] ?? $appointment['appointment_status'];
                        ?>
                    </span>
                </div>

                <!-- Appointment Basic Info -->
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">📅 التاريخ</div>
                        <div class="info-value">
                            <?= date('d/m/Y (l)', strtotime($appointment['appointment_date'])) ?>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">🕐 الوقت</div>
                        <div class="info-value">
                            <?= date('H:i', strtotime($appointment['appointment_date'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Child Information -->
                <div class="section-title">
                    <i class="bi bi-person-circle" style="margin-left: 8px;"></i> معلومات الطفل
                </div>
                <div class="child-info">
                    <div class="child-box">
                        <div class="label">👧 الاسم</div>
                        <div class="value"><?= htmlspecialchars($appointment['child_name']) ?></div>
                    </div>
                    <div class="child-box">
                        <div class="label">🎂 تاريخ الميلاد</div>
                        <div class="value"><?= date('d/m/Y', strtotime($appointment['birth_date'])) ?></div>
                    </div>
                </div>

                <!-- Doctor Information -->
                <div class="section-title">
                    <i class="bi bi-stethoscope" style="margin-left: 8px;"></i> معلومات الطبيب
                </div>
                <div class="doctor-card">
                    <h5><?= htmlspecialchars($appointment['doctor_name']) ?></h5>
                    <div class="doctor-info">
                        <div class="doctor-detail">
                            <strong>التخصص:</strong>
                            <span><?= htmlspecialchars($appointment['specialty'] ?? 'ممارس عام') ?></span>
                        </div>
                        <div class="doctor-detail">
                            <strong>البريد:</strong>
                            <span><?= htmlspecialchars($appointment['doctor_email'] ?? 'غير متوفر') ?></span>
                        </div>
                        <div class="doctor-detail">
                            <strong>الهاتف:</strong>
                            <span><?= htmlspecialchars($appointment['doctor_phone'] ?? 'غير متوفر') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Doctor's Notes (if any) -->
                <div class="section-title">
                    <i class="bi bi-file-text" style="margin-left: 8px;"></i> ملاحظات الطبيب
                </div>
                <div class="notes-section">
                    <?php if (!empty($appointment['diagnosis']) || !empty($appointment['appointment_notes'])): ?>
                        <div style="color: #333;">
                            <strong>التشخيص:</strong>
                            <p><?= nl2br(htmlspecialchars($appointment['diagnosis'] ?? 'لا يوجد')) ?></p>
                            
                            <strong>ملاحظات طبية:</strong>
                            <p><?= nl2br(htmlspecialchars($appointment['appointment_notes'] ?? 'لا توجد')) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Prescriptions (if available) -->
                <?php if (!empty($appointment['prescribed_medication'])): ?>
                    <div class="section-title">
                        <i class="bi bi-capsule" style="margin-left: 8px;"></i> الأدوية الموصوفة
                    </div>
                    <div class="notes-section" style="background: #fffbf0; border-color: #fed7aa;">
                        <div style="color: #333;">
                            <?= nl2br(htmlspecialchars($appointment['prescribed_medication'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Attachments (if available) -->
                <?php
                $attachment_query = "SELECT * FROM appointment_attachments WHERE appointment_id = ?";
                $attach_stmt = $conn->prepare($attachment_query);
                $attach_stmt->bind_param("i", $appointment_id);
                $attach_stmt->execute();
                $attachments_result = $attach_stmt->get_result();
                
                if ($attachments_result->num_rows > 0):
                ?>
                    <div class="section-title">
                        <i class="bi bi-paperclip" style="margin-left: 8px;"></i> الملفات المرفقة
                    </div>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <ul class="list-unstyled">
                            <?php while ($attachment = $attachments_result->fetch_assoc()): ?>
                                <li style="margin-bottom: 10px;">
                                    <?php 
                                    $file_ext = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
                                    $icons = [
                                        'pdf' => 'bi-file-earmark-pdf',
                                        'doc' => 'bi-file-earmark-word',
                                        'docx' => 'bi-file-earmark-word',
                                        'xls' => 'bi-file-earmark-excel',
                                        'xlsx' => 'bi-file-earmark-excel',
                                        'jpg' => 'bi-file-earmark-image',
                                        'jpeg' => 'bi-file-earmark-image',
                                        'png' => 'bi-file-earmark-image',
                                        'gif' => 'bi-file-earmark-image',
                                    ];
                                    $icon = $icons[$file_ext] ?? 'bi-file-earmark';
                                    $dir = 'appointment_reports';
                                    ?>
                                    <i class="bi <?= $icon ?>"></i>
                                    <a href="download.php?file=<?= urlencode(basename($attachment['file_path'])) ?>&dir=<?= urlencode($dir) ?>" 
                                       class="ms-2" target="_blank">
                                        <?= htmlspecialchars($attachment['file_name'] ?? 'التقرير الطبي') ?>
                                    </a>
                                    <?php if (!empty($attachment['created_at'])): ?>
                                        <small class="text-muted">
                                            (<?= date('d/m/Y', strtotime($attachment['created_at'])) ?>)
                                        </small>
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="my_appointments.php" class="btn-custom btn-secondary-custom">
                        <i class="bi bi-arrow-right"></i> العودة للمواعيد
                    </a>
                    
                    <?php if ($appointment['appointment_status'] !== 'cancelled'): ?>
                        <button onclick="confirmCancel()" class="btn-custom btn-danger-custom">
                            <i class="bi bi-x-circle"></i> إلغاء الموعد
                        </button>
                    <?php endif; ?>

                    <?php if ($appointment['appointment_status'] === 'confirmed' && strtotime($appointment['appointment_date']) > time()): ?>
                        <a href="messages.php?doctor_id=<?= $appointment['doctor_id'] ?>" class="btn-custom btn-primary-custom">
                            <i class="bi bi-chat-dots"></i> تواصل مع الطبيب
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Form (hidden) -->
    <form id="cancelForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="cancel">
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancel() {
            if (confirm('هل تريد فعلاً إلغاء هذا الموعد؟\n\nلا يمكن التراجع عن هذا الإجراء')) {
                document.getElementById('cancelForm').submit();
            }
        }
    </script>
</body>
</html>
