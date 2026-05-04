<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'nurse') {
    header('Location: login.php');
    exit;
}
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'ممرض');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الممرض</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #e9ecef; color: #4a4a4a; font-family: 'Cairo', sans-serif; }
        .main-box { margin-top: 6vh; box-shadow: 0 0 20px rgba(0,0,0,0.1); border-radius: 16px; background: #fff; padding: 40px; }
        .dashboard-header { background: #28a745; color: white; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
        .dashboard-header h1 { font-weight: 700; font-size: 2.5rem; }
        .card-link { text-decoration: none; }
        .feature-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: all 0.3s; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .card-icon { font-size: 3rem; color: #28a745; margin-bottom: 15px; }
        .card-body h5 { font-weight: 600; color: #28a745; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12 main-box mt-5">
            <div class="dashboard-header text-center">
                <h1><i class="bi bi-heart-pulse-fill me-3"></i> لوحة تحكم الممرض</h1>
                <p class="lead">مرحباً بك <?= $full_name ?>. هذه هي أدواتك لإدارة التطعيمات والملفات.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <a href="children.php" class="card-link">
                        <div class="card feature-card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-file-earmark-person card-icon"></i>
                                <h5 class="card-title">عرض ملفات الأطفال</h5>
                                <p class="card-text text-muted">راجع سجلات الأطفال وابحث عن ملفات.</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="vaccine_schedule.php" class="card-link">
                        <div class="card feature-card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-shield-fill-plus card-icon" style="color:#0d6efd;"></i>
                                <h5 class="card-title">إدارة قائمة اللقاحات</h5>
                                <p class="card-text text-muted">أضف، عدل، واحذف اللقاحات المعيارية.</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="providers.php" class="card-link">
                        <div class="card feature-card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-person-circle card-icon" style="color:#ffc107;"></i>
                                <h5 class="card-title">قائمة الزملاء والأطباء</h5>
                                <p class="card-text text-muted">تواصل مع مقدمي الرعاية الآخرين.</p>
                            </div>
                        </div>
                    </a>
                </div>
                 <div class="col-md-6 col-lg-4">
                    <a href="profile.php" class="card-link">
                        <div class="card feature-card text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-person-lines-fill card-icon" style="color:#ffc107;"></i>
                                <h5 class="card-title">إدارة الملف الشخصي</h5>
                                <p class="card-text text-muted">تحديث بياناتك وتغيير كلمة المرور.</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="logout.php" class="btn btn-danger btn-lg"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>