<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['nurse','admin','doctor'])) {
    header('Location: login.php');
    exit;
}
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

// === START Sidebar Setup (Color and Links) ===
$user_type = $_SESSION['user_type'] ?? 'nurse';
$dashboard_link = $user_type === 'nurse' ? 'nurse_dashboard.php' : 'profile.php';

if ($user_type === 'doctor') {
    $main_dark = '#842029'; 
    $main_text = '#dc3545'; 
    $main_light = '#f5c6cb'; 
    $main_deep = '#f1aeb5'; 
    $bg_light = '#f8d7da'; 
    $title_icon = 'fas fa-stethoscope';
} elseif ($user_type === 'nurse') {
    $main_dark = '#0f5132'; 
    $main_text = '#28a745'; 
    $main_light = '#c3e6cb'; 
    $main_deep = '#b1dfbb'; 
    $bg_light = '#d4edda'; 
    $title_icon = 'fas fa-syringe';
} else { // parent
    $main_dark = '#ad1457';
    $main_text = '#880e4f';
    $main_light = '#ffd1dc';
    $main_deep = '#f8bbd0';
    $bg_light = '#fff0f5';
    $title_icon = 'fas fa-heartbeat';
}
// There are no vaccination alerts on this nurse page.
$vaccine_alerts = ['upcoming' => [], 'missed' => []];

// === END Sidebar Setup ===

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // جلب الأطفال الذين لديهم لقاحات بحالة 'missed' أو 'due' وتاريخ استحقاق سابق للتاريخ الحالي
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
    <title>الأطفال المتأخرون عن التطعيم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Dynamic Colors from profile.php */
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }

        /* Sidebar Styles */
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); min-height: 100vh; color: #4A4A4A; font-family: 'Cairo', sans-serif; display: flex; }
        .sidebar { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%); width: 250px; min-height: 100vh; padding: 20px; color: white; box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12); transition: all 0.3s; }
        .sidebar a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { color: white; transform: translateX(5px); }
        .sidebar .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255, 255, 255, 0.15); }
        .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar .logo i { color: var(--primary-pink); }
        .logout-btn { background: rgba(255, 255, 255, 0.15); border: none; border-radius: 12px; padding: 12px; color: white; font-weight: 600; transition: all 0.3s; width: 100%; text-align: right; display: flex; align-items: center; gap: 10px; justify-content: flex-start; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25); transform: translateY(-3px); }
        .main-container { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; }
        
        /* Original File Styles */
        .main-box { margin-top: 0; margin-bottom: 25px; box-shadow: 0 6px 24px rgba(0, 0, 0, 0.05); border-radius: 12px; background: #ffffff; padding: 40px; }
        .page-header { font-size: 2rem; color: #dc3545; font-weight: 700; text-align: center; margin-bottom: 35px; }
        .flex-grow-1 { padding: 0; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <div class="col-lg-10 main-box mt-4">
                <div class="page-header"><i class="bi bi-calendar-x-fill"></i> قائمة الأطفال المتأخرين عن التطعيم</div>

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
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-danger">
                            <tr>
                                <th>اسم الطفل</th>
                                <th>تاريخ الميلاد</th>
                                <th>اللقاح المتأخر</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdue_children as $child): ?>
                                <tr>
                                    <td><a href="child_details.php?id=<?= $child['child_id'] ?>"><?= htmlspecialchars($child['child_name']) ?></a></td>
                                    <td><?= htmlspecialchars($child['birth_date']) ?></td>
                                    <td><?= htmlspecialchars($child['vaccine_name']) ?></td>
                                    <td><span class="badge bg-danger"><?= htmlspecialchars($child['due_date']) ?></span></td>
                                    <td>
                                        <a href="child_vaccination.php?child_id=<?= $child['child_id'] ?>&edit_id=<?= $child['record_id'] ?>" class="btn btn-sm btn-info text-white"><i class="bi bi-pencil"></i> تحديث</a>
                                        <button onclick="sendNotify(<?= $child['record_id'] ?>)" class="btn btn-sm btn-warning text-white"><i class="bi bi-bell-fill"></i> إشعار</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function sendNotify(recordId) {
    if (!confirm('هل تريد إرسال إشعار للوالدين؟')) return;
    fetch('admin_handler.php', {
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