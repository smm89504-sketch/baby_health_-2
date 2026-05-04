<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
require_once '../includes/AppointmentNotificationManager.php';

$db = new DatabaseHelper();
$conn = $db->getConnection();
$notif_manager = new AppointmentNotificationManager($conn);

//  الإجراءاتProcessing Procedures
$appointment_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// View appointment الموعد information
$sql = "SELECT a.*, c.name as child_name, u.full_name as parent_name, u.email, u.phone, 
        d.full_name as doctor_name, c.birth_date
        FROM appointments a
        JOIN children c ON a.child_id = c.id
        JOIN users u ON c.user_id = u.id
        JOIN users d ON a.doctor_id = d.id
        WHERE a.id = ? AND a.doctor_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die('الموعد غير موجود');
}

// الإجراءاتProcessing Procedures
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'send_confirmation') {
            if ($notif_manager->sendConfirmation($appointment_id)) {
                $message = '<div class="alert alert-success">✓ تم إرسال التأكيد بنجاح</div>';
            }
        } elseif ($_POST['action'] === 'send_reminder') {
            if ($notif_manager->sendReminder($appointment_id)) {
                $message = '<div class="alert alert-success">✓ تم إرسال التذكير بنجاح</div>';
            }
        } elseif ($_POST['action'] === 'add_diagnosis') {
            $diagnosis = $_POST['diagnosis'] ?? '';
            $medication = $_POST['medication'] ?? '';
            $follow_up_date = $_POST['follow_up_date'] ?? NULL;
            $appointment_notes = $_POST['appointment_notes'] ?? '';
            
            $update_sql = "UPDATE appointments SET diagnosis = ?, prescribed_medication = ?, 
                           follow_up_date = ?, appointment_notes = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('sdssi', $diagnosis, $medication, $follow_up_date, $appointment_notes, $appointment_id);
            if ($update_stmt->execute()) {
                $message = '<div class="alert alert-success">✓ تم حفظ البيانات بنجاح</div>';
                $appointment['diagnosis'] = $diagnosis;
                $appointment['prescribed_medication'] = $medication;
                $appointment['follow_up_date'] = $follow_up_date;
                $appointment['appointment_notes'] = $appointment_notes;
            }
            $update_stmt->close();
        } elseif ($_POST['action'] === 'upload_report') {
            if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
                $upload_dir = '../uploads/appointment_reports/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // List of allowed extensionsالامتدادات 
                $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xlsx', 'xls'];
                $file_name = basename($_FILES['report_file']['name']);
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                //الامتداد Extension check
                if (!in_array($file_extension, $allowed_extensions)) {
                    $message = '<div class="alert alert-danger">✗ صيغة الملف غير مسموحة. الصيغ المسموحة: PDF, DOC, DOCX, JPG, PNG, XLSX</div>';
                } else {
                    $file_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $file_name);
                    $file_name = time() . '_' . $file_name;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['report_file']['tmp_name'], $file_path)) {
                        $attachment_type = $_POST['attachment_type'] ?? 'report';
                        $file_size = $_FILES['report_file']['size'];
                        $file_type = $_FILES['report_file']['type'];
                        
                        $insert_sql = "INSERT INTO appointment_attachments 
                                      (appointment_id, uploaded_by, file_name, file_path, file_size, file_type, attachment_type)
                                      VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param('iisssss', $appointment_id, $_SESSION['user_id'], 
                                               $_FILES['report_file']['name'], $file_path, $file_size, $file_type, $attachment_type);
                        
                        if ($insert_stmt->execute()) {
                            $message = '<div class="alert alert-success">✓ تم رفع المرفق بنجاح</div>';
                        } else {
                            $message = '<div class="alert alert-danger">✗ خطأ في حفظ المرفق</div>';
                        }
                        $insert_stmt->close();
                    } else {
                        $message = '<div class="alert alert-danger">✗ خطأ في رفع الملف. تأكد من صلاحيات المجلد uploads/</div>';
                    }
                }
            }
        }
    }
}

// جلب المرفقات
$attachments_sql = "SELECT * FROM appointment_attachments WHERE appointment_id = ? ORDER BY uploaded_at DESC";
$attachments_stmt = $conn->prepare($attachments_sql);
$attachments_stmt->bind_param('i', $appointment_id);
$attachments_stmt->execute();
$attachments_result = $attachments_stmt->get_result();
$attachments = [];
while ($row = $attachments_result->fetch_assoc()) {
    $attachments[] = $row;
}
$attachments_stmt->close();

// جلب الإشعارات المرسلة
$notifications_sql = "SELECT * FROM appointment_notifications WHERE appointment_id = ? ORDER BY sent_at DESC";
$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param('i', $appointment_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();
$notifications = [];
while ($row = $notifications_result->fetch_assoc()) {
    $notifications[] = $row;
}
$notifications_stmt->close();


?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الموعد</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <style>
       
     
        .section-box { background: white; padding: 20px; border-radius: 10px; margin: 15px 0; border-right: 4px solid #dc3545; }
        .section-title { color: #c62828; font-weight: 700; font-size: 1.2rem; margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: 600; color: #666; }
        .info-value { color: #333; }
        .btn-group-custom { display: flex; gap: 10px; flex-wrap: wrap; margin: 15px 0; }
        .form-section { margin: 20px 0; }
        .file-list { list-style: none; padding: 0; }
        .file-item { background: #f9f9f9; padding: 10px; margin: 5px 0; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .notification-item { background: #f0f8ff; padding: 10px; margin: 5px 0; border-right: 3px solid #084298; border-radius: 5px; }
        .notification-item.reminder { background: #fff3cd; border-right-color: #ffc107; }
        .notification-item.confirmation { background: #d3d3ff; border-right-color: #084298; }
      
       
    </style>
</head>
<body>
        <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👨‍⚕️ الطبيب</h1>
                <span class="badge">v1.0</span>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً د.</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'طبيب'); ?></div>
                </div>
            </div>
        </div>
    </nav>
<?php include 'sidebar.php'; ?>

<div style="flex: 1; padding: 20px;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <h2 style="color: #c62828; margin-bottom: 20px;">
            <i class="bi bi-calendar-event"></i> تفاصيل الموعد
        </h2>
        
        <?= $message ?>
        
        <!-- Basic appointment information-->
        <div class="section-box">
            <div class="section-title">معلومات الموعد</div>
            <div class="info-row">
                <span class="info-label">الطفل:</span>
                <span class="info-value"><strong><?= htmlspecialchars($appointment['child_name']) ?></strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">ولي الأمر:</span>
                <span class="info-value"><?= htmlspecialchars($appointment['parent_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">التاريخ والوقت:</span>
                <span class="info-value"><?= date('d/m/Y - H:i', strtotime($appointment['appointment_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">الحالة:</span>
                <span class="info-value">
                    <span class="badge bg-<?= 
                        $appointment['appointment_status'] === 'scheduled' ? 'warning' :
                        ($appointment['appointment_status'] === 'confirmed' ? 'info' :
                        ($appointment['appointment_status'] === 'completed' ? 'success' : 'danger'))
                    ?>">
                        <?= 
                            $appointment['appointment_status'] === 'scheduled' ? 'قيد الانتظار' :
                            ($appointment['appointment_status'] === 'confirmed' ? 'مؤكد' :
                            ($appointment['appointment_status'] === 'completed' ? 'مكتمل' : 'ملغي'))
                        ?>
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">الاتصال:</span>
                <span class="info-value">
                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($appointment['phone']) ?>
                    | <i class="bi bi-envelope"></i> <?= htmlspecialchars($appointment['email']) ?>
                </span>
            </div>
        </div>
        
        <!-- إجراءات الإخطار Notification procedures-->
        <div class="section-box">
            <div class="section-title">إرسال التأكيدات والتذكيرات</div>
            <div class="btn-group-custom">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="send_confirmation">
                    <button type="submit" class="btn btn-success" 
                            <?= ($appointment['confirmation_status'] === 'confirmed' || $appointment['confirmation_date']) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-square"></i> 
                        <?= ($appointment['confirmation_status'] === 'confirmed' || $appointment['confirmation_date']) ? 'تم إرسال التأكيد' : 'إرسال تأكيد' ?>
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="send_reminder">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-bell"></i> 
                        إرسال تذكير
                    </button>
                </form>
            </div>
            
            <?php if (!empty($notifications)): ?>
                <div style="margin-top: 20px;">
                    <h5 style="color: #c62828;">الإخطارات المرسلة:</h5>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?= $notif['notification_type'] ?>">
                            <div>
                                <strong><?= ucfirst($notif['notification_type']) ?></strong><br>
                                <small style="color: #666;">
                                    <?= date('d/m/Y H:i', strtotime($notif['sent_at'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-<?= $notif['read_at'] ? 'success' : 'secondary' ?>">
                                <?= $notif['read_at'] ? 'مقروء' : 'جديد' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- إضافة التشخيص والأدوية Adding diagnosis and medications-->
        <div class="section-box">
            <div class="section-title">ملاحظات الموعد والتشخيص</div>
            <form method="post">
                <input type="hidden" name="action" value="add_diagnosis">
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-chat-left-text"></i> ملاحظات الموعد:</label>
                    <textarea name="appointment_notes" class="form-control" rows="2" placeholder="ملاحظات طبية عن الموعد">
                        <?= htmlspecialchars($appointment['appointment_notes'] ?? '') ?>
                    </textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-capsule"></i> سبب الموعد/التشخيص:</label>
                    <textarea name="diagnosis" class="form-control" rows="2" placeholder="التشخيص الأولي">
                        <?= htmlspecialchars($appointment['diagnosis'] ?? '') ?>
                    </textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-prescription2"></i> الأدوية الموصوفة:</label>
                    <textarea name="medication" class="form-control" rows="2" placeholder="الأدوية والجرعات الموصوفة">
                        <?= htmlspecialchars($appointment['prescribed_medication'] ?? '') ?>
                    </textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-calendar-check"></i> تاريخ المتابعة:</label>
                    <input type="date" name="follow_up_date" class="form-control" 
                           value="<?= htmlspecialchars($appointment['follow_up_date'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> حفظ البيانات
                </button>
            </form>
        </div>
        
        <!-- رUpload attachments المرفقات -->
        <div class="section-box">
            <div class="section-title">المرفقات والتقارير</div>
            
            <form method="post" enctype="multipart/form-data" class="form-section">
                <input type="hidden" name="action" value="upload_report">
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-file-earmark-pdf"></i> نوع المرفق:</label>
                    <select name="attachment_type" class="form-control">
                        <option value="report">تقرير طبي</option>
                        <option value="prescription">وصفة طبية</option>
                        <option value="test_result">نتيجة اختبار</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-upload"></i> رفع الملف:</label>
                    <input type="file" name="report_file" class="form-control" 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xlsx" required>
                    <small class="text-muted">الملفات المدعومة: PDF, DOC, DOCX, JPG, PNG, XLSX</small>
                </div>
                
                <button type="submit" class="btn btn-info">
                    <i class="bi bi-cloud-upload"></i> رفع المرفق
                </button>
            </form>
            
            <?php if (!empty($attachments)): ?>
                <div style="margin-top: 20px;">
                    <h5 style="color: #c62828;">المرفقات المرفوعة:</h5>
                    <ul class="file-list">
                        <?php foreach ($attachments as $file): ?>
                            <li class="file-item">
                                <div>
                                    <i class="bi bi-file"></i> 
                                    <strong><?= htmlspecialchars($file['file_name']) ?></strong>
                                    <br>
                                    <small style="color: #666;">
                                        <?= ucfirst($file['attachment_type']) ?> - 
                                        <?= number_format($file['file_size'] / 1024, 2) ?> KB - 
                                        <?= date('d/m/Y H:i', strtotime($file['uploaded_at'])) ?>
                                    </small>
                                </div>
                                <a href="<?= htmlspecialchars($file['file_path']) ?>" class="btn btn-sm btn-outline-primary" download>
                                    <i class="bi bi-download"></i> تحميل
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        
        <a href="my_appointments.php" class="btn btn-secondary" style="margin-top: 20px;">
            <i class="bi bi-arrow-left"></i> العودة
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
