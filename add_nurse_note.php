<?php
session_start();
// تأكدEnsuring the user is a nurse
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
$success = false;
$child_id = $_REQUEST['child_id'] ?? null;
$note_user_type = $_SESSION['user_type'];
$child_name = 'الطفل';

if (!$child_id) {
    header('Location: children.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    //Bring the child's name
    $stmt_child = $pdo->prepare('SELECT name FROM children WHERE id = ?');
    $stmt_child->execute([$child_id]);
    $child_data = $stmt_child->fetch();
    if ($child_data) {
        $child_name = $child_data['name'];
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $note_content = trim($_POST['note_content'] ?? '');
        $note_type = $_POST['note_type'] ?? 'general'; 
        
        if (!$note_content) {
            $errors[] = 'محتوى الملاحظة مطلوب.';
        }

        if (empty($errors)) {
            // الملاحظات كلها من الممرض (user_type='nurse')
            $stmt = $pdo->prepare('INSERT INTO professional_notes (child_id, user_id, user_type, note_content, note_type) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$child_id, $_SESSION['user_id'], $note_user_type, $note_content, $note_type]);
            $success = true;
            $_POST['note_content'] = ''; // مسح المحتوى بعد النجاح
        }
    }
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

$dashboard_link = 'nurse_dashboard.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة ملاحظة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-box { margin-top: 6vh; box-shadow: 0 6px 24px rgba(0, 0, 0, 0.05); border-radius: 12px; background: #ffffff; padding: 40px; }
        .page-header { font-size: 2rem; color: #28a745; font-weight: 700; text-align: center; margin-bottom: 35px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 main-box">
            <div class="page-header">
                <i class="bi bi-file-text"></i> إضافة ملاحظة لـ: <?= htmlspecialchars($child_name) ?>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success text-center">تمت إضافة الملاحظة بنجاح!</div>
            <?php endif; ?>

            <div class="alert alert-info">
                **ملاحظة الممرض:** ستظهر هذه الملاحظة للوالدين في ملف الطفل.
            </div>

            <form method="POST" action="add_nurse_note.php?child_id=<?= $child_id ?>">
                 <div class="mb-3">
                    <label for="note_type" class="form-label">نوع الملاحظة</label>
                    <select name="note_type" id="note_type" class="form-select" required>
                        <option value="general" <?= (($_POST['note_type'] ?? 'general') === 'general') ? 'selected' : '' ?>>ملاحظة عامة</option>
                        <option value="sleep_advice" <?= (($_POST['note_type'] ?? '') === 'sleep_advice') ? 'selected' : '' ?>>نصيحة لتحسين النوم</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="note_content" class="form-label">محتوى الملاحظة / النصيحة</label>
                    <textarea name="note_content" id="note_content" class="form-control" rows="6" required><?= htmlspecialchars($_POST['note_content'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-save me-2"></i> حفظ الملاحظة</button>
            </form>

            <div class="text-center mt-4">
                <a href="children.php" class="btn btn-secondary"><i class="bi bi-arrow-right-circle"></i> العودة لقائمة الأطفال</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>