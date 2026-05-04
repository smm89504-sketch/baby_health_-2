<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Retrieve doctor's dataجلب بيانات الطبيب
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;

// Data update processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);

    //تأكد من وجود الأعمدة في الجدول لتفادي الأخطاء (برمجياً محلياً)
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS location TEXT NULL");

    // If the location is not entered
    if (empty($location)) {
        $errors[] = 'يرجى إدخال الموقع.';
    }

    // Data update
    $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ?, specialty = ?, location = ?, experience_years = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssssii", $full_name, $email, $phone, $specialty, $location, $experience_years, $_SESSION['user_id']);

    if ($update_stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $success_message = "تم تحديث البيانات بنجاح!";
        // Data retrieval اعادة جلب
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
    } else {
        $error_message = "حدث خطأ في تحديث البيانات";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- الشريط العلوي -->
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

    <!-- top strip-->
    <?php include 'sidebar.php'; ?>

    <!--Main cotact-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-user-md me-2"></i>الملف الشخصي</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Doctor information-->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>تحديث المعلومات الشخصية</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name"
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="specialty" class="form-label">التخصص</label>
                                    <input type="text" class="form-control" id="specialty" name="specialty"
                                           value="<?php echo htmlspecialchars($user['specialty'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">الموقع</label>
                                    <input type="text" class="form-control" id="location" name="location"
                                           value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="مثل: دمشق، سوريا">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="experience_years" class="form-label">سنوات الخبرة</label>
                                    <input type="number" class="form-control" id="experience_years" name="experience_years"
                                           value="<?php echo htmlspecialchars($user['experience_years'] ?? ''); ?>" min="0">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>العودة للوحة التحكم
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Additional information-->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات الحساب</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>نوع الحساب:</strong>
                            <span class="badge bg-primary">طبيب</span>
                        </div>
                        <div class="mb-3">
                            <strong>تاريخ التسجيل:</strong>
                            <span><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at'] ?? 'now'))); ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>آخر دخول:</strong>
                            <span><?php echo htmlspecialchars($user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'لم يسجل دخول بعد'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick statsإحصائيات سريعة -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>إحصائيات سريعة</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        //The number of patients
                        $patients_query = "SELECT COUNT(DISTINCT child_id) as patient_count FROM medical_visits WHERE doctor_id = ?";
                        $patients_stmt = $conn->prepare($patients_query);
                        $patients_stmt->bind_param("i", $_SESSION['user_id']);
                        $patients_stmt->execute();
                        $patients_result = $patients_stmt->get_result()->fetch_assoc();

                        // Number of visits
                        $visits_query = "SELECT COUNT(*) as visit_count FROM medical_visits WHERE doctor_id = ?";
                        $visits_stmt = $conn->prepare($visits_query);
                        $visits_stmt->bind_param("i", $_SESSION['user_id']);
                        $visits_stmt->execute();
                        $visits_result = $visits_stmt->get_result()->fetch_assoc();
                        ?>
                        <div class="mb-3">
                            <strong>عدد المرضى:</strong>
                            <span class="badge bg-info"><?php echo $patients_result['patient_count']; ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>عدد الزيارات:</strong>
                            <span class="badge bg-success"><?php echo $visits_result['visit_count']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>