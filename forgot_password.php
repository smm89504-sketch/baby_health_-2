<?php
session_start();
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
$step = 1;
$errors = [];
$success = false;
$security_question = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (!$email) {
            $errors[] = 'يرجى إدخال البريد الإلكتروني.';
        } else {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                $stmt = $pdo->prepare('SELECT security_question FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $row = $stmt->fetch();
                if ($row) {
                    $security_question = $row['security_question'];
                    $step = 2;
                } else {
                    $errors[] = 'البريد الإلكتروني غير موجود.';
                }
            } catch (PDOException $e) {
                $errors[] = 'خطأ في الاتصال بقاعدة البيانات.';
            }
        }
    } elseif (isset($_POST['answer'], $_POST['new_password'], $_POST['email_hidden'])) {
        $email = trim($_POST['email_hidden']);
        $answer = trim($_POST['answer']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        if (!$answer || !$new_password || !$confirm_password) {
            $errors[] = 'جميع الحقول مطلوبة.';
            $step = 2;
        } else {
            try {
                $pdo = new PDO($dsn, $user, $pass, $options);
                $stmt = $pdo->prepare('SELECT security_answer FROM users WHERE email = ?');
                $stmt->execute([$email]);
                $row = $stmt->fetch();
                if ($row && strtolower($row['security_answer']) === strtolower($answer)) {
                    // Check the strength of your password
                    $strong = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$/";
                    if (!preg_match($strong, $new_password)) {
                        $errors[] = 'كلمة السر الجديدة ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز.';
                        $step = 2;
                    } elseif ($new_password !== $confirm_password) {
                        $errors[] = 'كلمتا السر غير متطابقتين.';
                        $step = 2;
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
                        $stmt->execute([$hashed_password, $email]);
                        $success = true;
                        $step = 3;
                    }
                } else {
                    $errors[] = 'إجابة سؤال الأمان غير صحيحة.';
                    $step = 2;
                }
            } catch (PDOException $e) {
                $errors[] = 'خطأ في الاتصال بقاعدة البيانات.';
                $step = 2;
            }
        }
        // Repeat the question in step 2
        if ($step == 2) {
            $stmt = $pdo->prepare('SELECT security_question FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            $security_question = $row['security_question'] ?? '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; }
        .main-box { margin-top: 6vh; box-shadow: 0 0 20px #eee; border-radius: 16px; background: #fff; }
        .logo { font-size: 2rem; color: #0d6efd; font-weight: bold; }
    </style>
</head>
<body>
<div class="d-flex">
    
    <div class="flex-grow-1">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 main-box p-5 mt-4">
                    <div class="logo mb-4 text-center"><i class="bi bi-key"></i> استعادة كلمة المرور</div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-3">
                            <?php foreach ($errors as $error) echo "<div>• $error</div>"; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success && $step == 3): ?>
                        <div class="alert alert-success text-center mb-3">
                            تم تغيير كلمة المرور بنجاح! <a href="login.php" class="alert-link">اضغط هنا لتسجيل الدخول</a>
                        </div>
                    <?php elseif ($step == 1): ?>
                        <form method="POST" action="forgot_password.php" autocomplete="off">
                            <div class="mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">متابعة</button>
                        </form>
                    <?php elseif ($step == 2): ?>
                        <form method="POST" action="forgot_password.php" autocomplete="off">
                            <input type="hidden" name="email_hidden" value="<?= htmlspecialchars($email) ?>">
                            <div class="mb-3">
                                <label class="form-label">سؤال الأمان</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($security_question) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الإجابة</label>
                                <input type="text" name="answer" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">كلمة السر الجديدة</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">تأكيد كلمة السر الجديدة</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">تغيير كلمة المرور</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html> 