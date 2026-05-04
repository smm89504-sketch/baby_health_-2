<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'nurse') {
    header('Location: login.php');
    exit;
}
$host = 'localhost';
$db   = 'baby_tracker';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$errors = [];
$success = '';
$vaccines = [];
$edit_id = $_GET['edit'] ?? null;
$edit_vaccine = null;

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

    // Deletion processing
    if (isset($_GET['delete'])) {
        $delete_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
        if ($delete_id) {
            $stmt = $pdo->prepare('DELETE FROM vaccines WHERE id = ?');
            $stmt->execute([$delete_id]);
            $success = 'تم حذف اللقاح بنجاح.';
            header('Location: vaccine_schedule.php?success=' . urlencode($success));
            exit;
        }
    }
    
    // Bringing a vaccine for modification
    if ($edit_id) {
        $stmt = $pdo->prepare('SELECT * FROM vaccines WHERE id = ?');
        $stmt->execute([$edit_id]);
        $edit_vaccine = $stmt->fetch();
        if (!$edit_vaccine) {
            $errors[] = 'اللقاح غير موجود.';
            $edit_id = null;
        }
    }

    //Add/Modify Handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $target_age = trim($_POST['target_age'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $id_to_save = $_POST['id_to_save'] ?? null;
        
        if (!$name || !$target_age) {
            $errors[] = 'الاسم والعمر المستهدف مطلوبان.';
        }

        if (empty($errors)) {
            if ($id_to_save) { // Edit
                $stmt = $pdo->prepare('UPDATE vaccines SET name = ?, target_age = ?, description = ? WHERE id = ?');
                $stmt->execute([$name, $target_age, $description, $id_to_save]);
                $success = 'تم تعديل اللقاح بنجاح.';
            } else { // Add
                $stmt = $pdo->prepare('INSERT INTO vaccines (name, target_age, description) VALUES (?, ?, ?)');
                $stmt->execute([$name, $target_age, $description]);
                $success = 'تم إضافة اللقاح بنجاح.';
            }
            header('Location: vaccine_schedule.php?success=' . urlencode($success));
            exit;
        }
    }

    // Bring all the vaccines
    $stmt = $pdo->query('SELECT * FROM vaccines ORDER BY target_age ASC');
    $vaccines = $stmt->fetchAll();

    // جلب رسالة النجاح من GET
    $success = $_GET['success'] ?? $success;

} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة جدول اللقاحات</title>
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
        .page-header { font-size: 2rem; color: #28a745; font-weight: 700; text-align: center; margin-bottom: 35px; }
        .form-section { border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin-bottom: 30px; background-color: #d4edda; }
        .flex-grow-1 { padding: 0; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <div class="col-lg-10 main-box mt-4">
                <div class="page-header"><i class="bi bi-shield-fill-plus"></i> إدارة جدول اللقاحات</div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="form-section">
                    <h5 class="mb-3 text-success"><i class="bi bi-pencil-square me-2"></i> <?= $edit_id ? 'تعديل لقاح: ' . htmlspecialchars($edit_vaccine['name'] ?? '') : 'إضافة لقاح جديد' ?></h5>
                    <form method="POST" action="vaccine_schedule.php">
                        <input type="hidden" name="id_to_save" value="<?= htmlspecialchars($edit_id ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="name" class="form-label">اسم اللقاح</label>
                                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($edit_vaccine['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="target_age" class="form-label">العمر المستهدف</label>
                                <input type="text" name="target_age" id="target_age" class="form-control" placeholder="مثال: شهرين" value="<?= htmlspecialchars($edit_vaccine['target_age'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="description" class="form-label">الوصف (اختياري)</label>
                                <input type="text" name="description" id="description" class="form-control" value="<?= htmlspecialchars($edit_vaccine['description'] ?? '') ?>">
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-success"><i class="bi bi-save me-2"></i> <?= $edit_id ? 'حفظ التعديلات' : 'إضافة اللقاح' ?></button>
                                <?php if ($edit_id): ?>
                                    <a href="vaccine_schedule.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-2"></i> إلغاء التعديل</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <h4 class="mb-3 text-primary"><i class="bi bi-list-check me-2"></i> قائمة اللقاحات المعيارية</h4>
                <?php if (empty($vaccines)): ?>
                    <div class="alert alert-info text-center">لا يوجد لقاحات مسجلة بعد.</div>
                <?php else: ?>
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>الاسم</th>
                                <th>العمر المستهدف</th>
                                <th>الوصف</th>
                                <th style="width: 150px;">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccines as $vac): ?>
                                <tr>
                                    <td><?= htmlspecialchars($vac['name']) ?></td>
                                    <td><?= htmlspecialchars($vac['target_age']) ?></td>
                                    <td><?= htmlspecialchars($vac['description'] ?? '---') ?></td>
                                    <td>
                                        <a href="vaccine_schedule.php?edit=<?= $vac['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                        <a href="vaccine_schedule.php?delete=<?= $vac['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا اللقاح؟ سيؤثر هذا على جميع الأطفال المرتبطين به.');"><i class="bi bi-trash"></i></a>
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
</body>
</html>