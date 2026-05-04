<?php
// Page for managing medication lists by age group (add/edit/delete)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
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
$success = false;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Add a new list
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
        $age_min = (int)$_POST['age_min_months'];
        $age_max = (int)$_POST['age_max_months'];
        $allowed = trim($_POST['allowed_medications']);
        $restricted = trim($_POST['restricted_medications']);
        $notes = trim($_POST['notes']);
        if ($age_min < 0 || $age_max < $age_min || $allowed === '' || $restricted === '') {
            $errors[] = 'يرجى إدخال جميع الحقول بشكل صحيح.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO age_group_medication_lists (age_min_months, age_max_months, allowed_medications, restricted_medications, notes) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$age_min, $age_max, $allowed, $restricted, $notes]);
            $success = true;
        }
    }
    //Delete list
    if (isset($_GET['delete'])) {
        $stmt = $pdo->prepare('DELETE FROM age_group_medication_lists WHERE id = ?');
        $stmt->execute([(int)$_GET['delete']]);
        header('Location: age_group_medication_lists.php');
        exit;
    }
    // prepare all lists
    $lists = $pdo->query('SELECT * FROM age_group_medication_lists ORDER BY age_min_months ASC')->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأطفال - لوحة التحكم</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👶 الأطفال</h1>
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
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">إضافة قائمة جديدة</div>
        <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success">تمت الإضافة بنجاح!</div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo "<div>• $e</div>"; ?></div><?php endif; ?>
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-2"><input type="number" name="age_min_months" class="form-control" placeholder="أدنى عمر (شهر)" required></div>
                    <div class="col-md-2"><input type="number" name="age_max_months" class="form-control" placeholder="أقصى عمر (شهر)" required></div>
                    <div class="col-md-3"><input type="text" name="allowed_medications" class="form-control" placeholder="الأدوية المسموحة (أسماء أو IDs)" required></div>
                    <div class="col-md-3"><input type="text" name="restricted_medications" class="form-control" placeholder="الأدوية الممنوعة (أسماء أو IDs)" required></div>
                    <div class="col-md-2"><input type="text" name="notes" class="form-control" placeholder="ملاحظات"></div>
                </div>
                <button type="submit" name="add" class="btn btn-success mt-3">إضافة</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-info text-white">جميع القوائم</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead><tr><th>العمر (شهور)</th><th>مسموح</th><th>ممنوع</th><th>ملاحظات</th><th>حذف</th></tr></thead>
                <tbody>
                <?php foreach ($lists as $row): ?>
                    <tr>
                        <td><?= $row['age_min_months'] ?> - <?= $row['age_max_months'] ?></td>
                        <td><?= htmlspecialchars($row['allowed_medications']) ?></td>
                        <td><?= htmlspecialchars($row['restricted_medications']) ?></td>
                        <td><?= htmlspecialchars($row['notes']) ?></td>
                        <td><a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('تأكيد الحذف؟')">حذف</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
     </main>
     
</body>
</html>
