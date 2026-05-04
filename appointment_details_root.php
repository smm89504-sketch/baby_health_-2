<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Get appointment الموعدdetails
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

        body {
            background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%);
            min-height: 100vh;
            color: #4A4A4A;
            font-family: 'Cairo', sans-serif;
            display: flex;
        }

        .main-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .dashboard-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .main-box {
            margin-top: 30px;
            box-shadow: 0 6px 24px rgba(100, 100, 100, 0.10);
            border-radius: 16px;
            background: #fff;
            padding: 30px;
        }

        .page-header {
            font-size: 1.7rem;
            color: #c62828;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header i {
            margin-left: 12px;
            font-size: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            color: var(--primary-dark);
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-light);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-card {
            background: linear-gradient(135deg, rgba(194, 24, 40, 0.05), rgba(255, 182, 193, 0.1));
            border-left: 4px solid var(--primary-text);
            padding: 15px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.8rem;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .status-scheduled {
            background: #e7f3ff;
            color: #0066cc;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .notes-section {
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            min-height: 100px;
        }

        .notes-section:empty::before {
            content: 'لا توجد ملاحظات من الطبيب حتى الآن';
            color: #999;
            font-style: italic;
        }

        .doctor-card {
            background: linear-gradient(135deg, var(--primary-text) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .doctor-card h5 {
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .doctor-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .doctor-detail {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px;
            border-radius: 8px;
        }

        .doctor-detail strong {
            display: block;
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .doctor-detail span {
            display: block;
            font-weight: 600;
            font-size: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-text), var(--primary-dark));
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(136, 14, 79, 0.2);
            color: white;
            text-decoration: none;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
            color: white;
            text-decoration: none;
        }

        .btn-secondary-custom {
            background: #6c757d;
            color: white;
        }

        .btn-secondary-custom:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }

        .child-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            background: rgba(194, 24, 40, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .child-box {
            text-align: center;
        }

        .child-box .label {
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 5px;
        }

        .child-box .value {
            font-weight: 700;
            color: #333;
            font-size: 1.1rem;
        }

        .success-alert {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <i class="<?= $title_icon ?>"></i>
                تفاصيل الموعد الطبي
            </div>

            <!-- Back Button -->
            <div style="margin-bottom: 20px;">
                <a href="my_appointments.php" class="btn-custom btn-secondary-custom">
                    <i class="bi bi-arrow-right"></i> العودة للمواعيد
                </a>
            </div>

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
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    <a href="<?= htmlspecialchars($attachment['file_path']) ?>" 
                                       download class="ms-2">
                                        <?= htmlspecialchars($attachment['file_name'] ?? 'التقرير الطبي') ?>
                                    </a>
                                    <small class="text-muted">
                                        (<?= date('d/m/Y', strtotime($attachment['created_at'])) ?>)
                                    </small>
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
