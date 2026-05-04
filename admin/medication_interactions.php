<?php
// Drug Interactions Management Page (for admins)
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
    
    //Add a new interaction
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
        $med_a = (int)$_POST['medication_a_id'];
        $med_b = (int)$_POST['medication_b_id'];
        $severity = trim($_POST['severity']);
        $description = trim($_POST['description']);
        
        if ($med_a === 0 || $med_b === 0 || $description === '' || !in_array($severity, ['minor', 'moderate', 'major'])) {
            $errors[] = 'يرجى ملء جميع الحقول بشكل صحيح.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO medication_interactions (medication_a_id, medication_b_id, severity, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$med_a, $med_b, $severity, $description]);
            $success = true;
        }
    }
    
    // Delete interaction
    if (isset($_GET['delete'])) {
        $stmt = $pdo->prepare('DELETE FROM medication_interactions WHERE id = ?');
        $stmt->execute([(int)$_GET['delete']]);
        header('Location: medication_interactions.php');
        exit;
    }
    
    // Bring all interactions
    $interactions = $pdo->query('SELECT mi.*, ma.name as med_a_name, mb.name as med_b_name FROM medication_interactions mi JOIN medications ma ON mi.medication_a_id = ma.id JOIN medications mb ON mi.medication_b_id = mb.id ORDER BY mi.created_at DESC')->fetchAll();
    
    // Bring the medication list
    $medications = $pdo->query('SELECT id, name FROM medications ORDER BY name')->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة التفاعلات الدوائية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">إضافة تفاعل دوائي جديد</h4>
        </div>
        <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success">تمت الإضافة بنجاح!</div><?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e) echo "<div>• $e</div>"; ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">الدواء الأول</label>
                        <select name="medication_a_id" class="form-control" required>
                            <option value="">--- اختر ---</option>
                            <?php foreach ($medications as $med): ?>
                                <option value="<?= $med['id'] ?>"><?= htmlspecialchars($med['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الدواء الثاني</label>
                        <select name="medication_b_id" class="form-control" required>
                            <option value="">--- اختر ---</option>
                            <?php foreach ($medications as $med): ?>
                                <option value="<?= $med['id'] ?>"><?= htmlspecialchars($med['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">درجة الخطورة</label>
                        <select name="severity" class="form-control" required>
                            <option value="minor">طفيفة (minor)</option>
                            <option value="moderate">متوسطة (moderate)</option>
                            <option value="major">خطيرة (major)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mt-3">
                    <label class="form-label">وصف التفاعل</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="add" class="btn btn-success mt-3">إضافة التفاعل</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white">جميع التفاعلات الدوائية المسجلة</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>الدواء الأول</th>
                            <th>الدواء الثاني</th>
                            <th>درجة الخطورة</th>
                            <th>الوصف</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interactions as $inter): ?>
                        <tr>
                            <td><?= htmlspecialchars($inter['med_a_name']) ?></td>
                            <td><?= htmlspecialchars($inter['med_b_name']) ?></td>
                            <td>
                                <?php if ($inter['severity'] === 'major'): ?>
                                    <span class="badge bg-danger">خطيرة</span>
                                <?php elseif ($inter['severity'] === 'moderate'): ?>
                                    <span class="badge bg-warning text-dark">متوسطة</span>
                                <?php else: ?>
                                    <span class="badge bg-info">طفيفة</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($inter['description']) ?></td>
                            <td>
                                <a href="?delete=<?= $inter['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('تأكيد الحذف؟')">حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
