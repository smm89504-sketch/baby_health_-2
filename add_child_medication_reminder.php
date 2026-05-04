<?php
// صفحة إضافة تذكير دواء جديد لطفل
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['doctor', 'nurse'])) {
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

$child_id = $_GET['child_id'] ?? null;
$errors = [];
$success = false;

if (!$child_id) {
    header('Location: children.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Bring the child's name
    $stmt = $pdo->prepare('SELECT name FROM children WHERE id = ?');
    $stmt->execute([$child_id]);
    $child = $stmt->fetch();
    if (!$child) {
        header('Location: children.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $medication_name = trim($_POST['medication_name'] ?? '');
        $dosage = trim($_POST['dosage'] ?? '');
        $reminder_time = trim($_POST['reminder_time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        if ($medication_name === '' || $dosage === '' || $reminder_time === '') {
            $errors[] = 'جميع الحقول مطلوبة باستثناء الملاحظات.';
        } else {
            // البحث عن الدواء في قاعدة البيانات المعروفة
            $stmt_med = $pdo->prepare('SELECT id, name FROM medications WHERE LOWER(name) = LOWER(?) LIMIT 1');
            $stmt_med->execute([$medication_name]);
            $found_medication = $stmt_med->fetch();
            if (!$found_medication) {
                $errors[] = 'الدواء غير موجود في قاعدة البيانات. يرجى استخدام أسماء أدوية موجودة.';
            } else {
                // جلب جميع معرّفات الأدوية الحالية للطفل
                $stmt_current = $pdo->prepare('SELECT cmr.medication_name, m.id FROM child_medication_reminders cmr LEFT JOIN medications m ON LOWER(m.name) = LOWER(cmr.medication_name) WHERE cmr.child_id = ?');
                $stmt_current->execute([$child_id]);
                $current_med_data = $stmt_current->fetchAll();
                $current_med_ids = array_filter(array_column($current_med_data, 'id'));
                // جلب التفاعلات الدوائية
                $interaction_warnings = [];
                if (!empty($current_med_ids)) {
                    $placeholders = rtrim(str_repeat('?,', count($current_med_ids)), ',');
                    $params = array_merge([$found_medication['id']], $current_med_ids, [$found_medication['id']], $current_med_ids);
                    $sql = "SELECT mi.*, ma.name as med_a_name, mb.name as med_b_name FROM medication_interactions mi JOIN medications ma ON mi.medication_a_id = ma.id JOIN medications mb ON mi.medication_b_id = mb.id WHERE (mi.medication_a_id = ? AND mi.medication_b_id IN ($placeholders)) OR (mi.medication_b_id = ? AND mi.medication_a_id IN ($placeholders))";
                    $stmt_inter = $pdo->prepare($sql);
                    $stmt_inter->execute($params);
                    $interactions = $stmt_inter->fetchAll();
                    foreach ($interactions as $inter) {
                        $other_med_name = $inter['medication_a_id'] == $found_medication['id'] ? $inter['med_b_name'] : $inter['med_a_name'];
                        $interaction_warnings[] = "<strong style='color: #d32f2f;'>⚠ تحذير تفاعل دوائي</strong><br>التفاعل بين: <strong>" . htmlspecialchars($medication_name) . "</strong> و <strong>" . htmlspecialchars($other_med_name) . "</strong><br><strong>الخطورة:</strong> " . htmlspecialchars(ucfirst($inter['severity'])) . "<br><strong>الوصف:</strong> " . nl2br(htmlspecialchars($inter['description']));
                    }
                }
                if (empty($interaction_warnings)) {
                    $stmt = $pdo->prepare('INSERT INTO child_medication_reminders (child_id, medication_name, dosage, reminder_time, notes, status) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$child_id, $found_medication['name'], $dosage, $reminder_time, $notes, 'pending']);
                    $success = true;
                } else {
                    $errors[] = 'لم يتم إضافة التذكير لوجود تفاعلات دوائية خطيرة. يرجى استشارة الطبيب.';
                }
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة تذكير دواء للطفل</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">إضافة تذكير دواء للطفل: <?= htmlspecialchars($child['name']) ?></h4>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">تمت إضافة التذكير بنجاح! <a href="child_details.php?id=<?= $child_id ?>">العودة للتفاصيل</a></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error) echo "<div>• $error</div>"; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($interaction_warnings)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($interaction_warnings as $warn) echo "<div>$warn</div>"; ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">اسم الدواء</label>
                    <input type="text" name="medication_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">الجرعة</label>
                    <input type="text" name="dosage" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">وقت التذكير</label>
                    <input type="datetime-local" name="reminder_time" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ملاحظات إضافية</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-success">إضافة التذكير</button>
                <a href="child_details.php?id=<?= $child_id ?>" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
