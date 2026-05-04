<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        $email_alerts = isset($_POST['email_alerts']) ? 1 : 0;
        $sms_alerts = isset($_POST['sms_alerts']) ? 1 : 0;
        $language = $_POST['language'];
        $night_mode = isset($_POST['night_mode']) ? 1 : 0;

        // Update settings in database
        $stmt = $conn->prepare("UPDATE doctor_settings SET notifications = ?, email_alerts = ?, sms_alerts = ?, language = ?, night_mode = ? WHERE doctor_id = ?");
        $stmt->bind_param("iiisii", $notifications, $email_alerts, $sms_alerts, $language, $night_mode, $_SESSION['user_id']);

        if ($stmt->execute()) {
            $message = "تم تحديث الإعدادات بنجاح";
        } else {
            $error = "حدث خطأ في تحديث الإعدادات";
        }
    }
}

//Retrieve current settings
$query = "SELECT * FROM doctor_settings WHERE doctor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// إذا لم توجد إعدادات، إنشاء افتراضية
if (!$settings) {
    $settings = [
        'notifications' => 1,
        'email_alerts' => 1,
        'sms_alerts' => 0,
        'language' => 'ar',
        'night_mode' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
</head>
<body>
    <!-- top strip-->
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

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!-- Main contact-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الإعدادات</h1>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
           
         

            <!-- Security settings-->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-shield-alt me-2"></i>إعدادات الأمان</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                            <input type="password" class="form-control" id="current_password">
                        </div>
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" class="form-control" id="new_password">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" class="form-control" id="confirm_password">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-warning" onclick="changePassword()">
                                <i class="fas fa-key"></i> تغيير كلمة المرور
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        

            <div class="text-center">
                <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> حفظ الإعدادات
                </button>
        
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changePassword() {
            const current = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (!current || !newPass || !confirm) {
                alert('يرجى ملء جميع حقول كلمة المرور');
                return;
            }

            if (newPass !== confirm) {
                alert('كلمة المرور الجديدة غير متطابقة');
                return;
            }

            alert('سيتم تغيير كلمة المرور');
        }

        function resetSettings() {
            if (confirm('هل أنت متأكد من استعادة الإعدادات الافتراضية؟')) {
                // استعادة الإعدادات الافتراضية
                document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = cb.id === 'notifications' || cb.id === 'email_alerts' ||
                                cb.id === 'urgent_consultations' || cb.id === 'appointment_reminders' ||
                                cb.id === 'new_messages' || cb.id === 'medication_alerts' ||
                                cb.id === 'vaccination_reminders' || cb.id === 'share_data' ||
                                cb.id === 'anonymous_stats';
                });
                document.getElementById('language').value = 'ar';
                document.getElementById('night_mode').checked = false;
            }
        }
    </script>
</body>
</html>