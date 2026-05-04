<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';

$host = 'localhost';
$db   = 'baby_tracker';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$errors = [];
$overdue_children = [];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Bring children who have vaccinations marked 'missed' or 'due' and whose due date is earlier than the current date.
    $sql = "SELECT 
                c.id as child_id, 
                c.name as child_name, 
                c.birth_date, 
                v.name as vaccine_name, 
                cv.due_date,
                cv.status,
                cv.id as record_id
            FROM child_vaccines cv
            JOIN children c ON cv.child_id = c.id
            JOIN vaccines v ON cv.vaccine_id = v.id
            WHERE cv.status IN ('due', 'missed') 
            AND cv.due_date < CURDATE()
            ORDER BY cv.due_date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdue_children = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأطفال المتأخرون عن التطعيم (إدمن)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand"><h1>🛡️ الأطفال المتأخرون</h1></div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A',0,1)); ?></div>
                <div><small style="color:#7a6880;">مرحباً</small><div style="color:#3d2c4d;font-weight:600;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'إدمن'); ?></div></div>
            </div>
        </div>
    </nav>

    <?php include 'sidebar.php'; ?>

    <main class="main-content" style="padding:20px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul></div>
        <?php endif; ?>

        <?php if (empty($overdue_children)): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle-fill me-2"></i> لا يوجد أطفال متأخرون عن مواعيد التطعيم حالياً.
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> يُرجى مراجعة سجلات التطعيم للأطفال أدناه لتحديث حالتها أو التواصل مع الأهل.
            </div>
            <table class="admin-table">
                <thead><tr>
                    <th>اسم الطفل</th>
                    <th>تاريخ الميلاد</th>
                    <th>اللقاح المتأخر</th>
                    <th>تاريخ الاستحقاق</th>
                    <th>إجراء</th>
                </tr></thead>
                <tbody>
                <?php foreach ($overdue_children as $child): ?>
                    <tr>
                        <td><a href="children.php?child_id=<?= $child['child_id'] ?>"><?= htmlspecialchars($child['child_name']) ?></a></td>
                        <td><?= htmlspecialchars($child['birth_date']) ?></td>
                        <td><?= htmlspecialchars($child['vaccine_name']) ?></td>
                        <td><span class="badge bg-danger"><?= htmlspecialchars($child['due_date']) ?></span></td>
                        <td>
                            <a href="vaccines.php?child_id=<?= $child['child_id'] ?>&edit_id=<?= $child['record_id'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-pencil"></i> تحديث</a>
                            <button onclick="sendNotify(<?= $child['record_id'] ?>)" class="btn btn-sm btn-warning text-white"><i class="bi bi-bell-fill"></i> إشعار</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function sendNotify(recordId) {
        if (!confirm('هل تريد إرسال إشعار للوالدين؟')) return;
        fetch('../admin_handler.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action:'notify_overdue', record_id: recordId})
        }).then(r=>r.json()).then(d=>{
            if (d.success) {
                alert('تم إرسال الإشعار بنجاح');
            } else {
                alert('فشل الإرسال: ' + (d.message||'')); 
            }
        }).catch(e=>{
            console.error(e);
            alert('حدث خطأ في الاتصال');
        });
    }
    </script>
</body>
</html>