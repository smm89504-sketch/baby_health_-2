<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>السجلات - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>📜 السجلات</h1>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'إدمن'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h1>سجلات النظام</h1>
        
        <div class="search-box">
            <input type="text" class="search-input" id="logs-search" placeholder="ابحث في السجلات...">
        </div>

        <div class="table-container">
            <table class="admin-table" id="logs-table">
                <thead>
                    <tr>
                        <th>التاريخ والوقت</th>
                        <th>الإدمن</th>
                        <th>الإجراء</th>
                        <th>النوع</th>
                        <th>التفاصيل</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2026-02-25 14:30</td>
                        <td>مسؤول النظام</td>
                        <td>إضافة مستخدم</td>
                        <td>أب</td>
                        <td>تم إضافة حساب جديد</td>
                    </tr>
                    <tr>
                        <td>2026-02-25 14:15</td>
                        <td>مسؤول النظام</td>
                        <td>تحديث بيانات</td>
                        <td>طفل</td>
                        <td>تم تحديث معلومات الطفل</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <script src="admin_shared.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            attachSearchListener('logs-search', 'logs-table');
        });
    </script>
</body>
</html>
