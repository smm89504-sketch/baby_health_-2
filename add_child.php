<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
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
$success = false;

$name = '';
$birth_date = '';
$twin_group = '';
$age = ''; //  هنا للتحقق

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $twin_group = trim($_POST['twin_group'] ?? '');
    
    // تم إزالة Age, Weight, Height من النموذج، لكن تبقى التحقق من الاسم وتاريخ الميلاد
    if (!$name || !$birth_date) {
        $errors[] = 'اسم الطفل وتاريخ الميلاد حقول أساسية مطلوبة.';
    }
   

    if (empty($errors)) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            
            // to remove the weight, height, and age fields.
            $stmt = $pdo->prepare('INSERT INTO children (user_id, name, birth_date, age, weight, height, twin_group, nutrition_guideline_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            
            // يتم تعيين قيم افتراضية مؤقتة للحقول التي تم إزالتها لتجنب الخطأ لحين تحديثها في أول تسجيل نشاط
            $default_age = 'غير محدد';
            $default_float = 0.0;
            $default_group = $twin_group ?: null;
            
            $stmt->execute([
                $_SESSION['user_id'],
                $name,
                $birth_date,
                $default_age,
                $default_float,
                $default_float,
                $default_group,
                null
            ]);
            $child_id = $pdo->lastInsertId();

            // إضافة جدولة اللقاحات تلقائياً
            $stmt_schedule = $pdo->prepare('SELECT * FROM vaccine_schedule');
            $stmt_schedule->execute();
            $vaccines = $stmt_schedule->fetchAll();

            foreach ($vaccines as $vaccine) {
                $due_date = date('Y-m-d', strtotime($birth_date . ' +' . $vaccine['age_months'] . ' months'));
            $stmt_cv = $pdo->prepare('INSERT INTO child_vaccines (child_id, vaccine_id, vaccine_schedule_id, due_date, status) VALUES (?, ?, ?, ?, ?)');
            $stmt_cv->execute([$child_id, $vaccine['id'], $vaccine['id'], $due_date, 'due']);
            }

            $success = true;
          
            $name = $birth_date = '';
        } catch (PDOException $e) {
            // عرض الخطأ الفعلي للتشخيص
            $errors[] = 'حدث خطأ أثناء محاولة إضافة الطفل: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة طفل جديد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #FFF5F7;
            color: #4A4A4A;
            font-family: 'Cairo', sans-serif;
        }
        .main-box {
            margin-top: 25px;
            margin-bottom: 25px;
            box-shadow: 0 6px 24px rgba(100, 100, 100, 0.1);
            border-radius: 12px;
            background: #ffffff;
            padding: 40px;
        }
        .page-header {
            font-size: 2rem; 
            color: #C7346F;
            font-weight: 700;
            text-align: center;
            margin-bottom: 35px; 
        }
        .page-header i {
            margin-left: 12px;
            font-size: 1.9rem;
            color: #D13878;
            vertical-align: middle; 
        }
        .form-label {
            font-weight: 600;
            color: #505050; 
            margin-bottom: 0.6rem;
            display: block; 
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #D8D8D8; 
            padding: 0.75rem 1rem; 
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #FCFCFC; 
        }
        .form-control:focus {
            border-color: #E7AAB4;
            box-shadow: 0 0 0 0.2rem rgba(231, 170, 180, 0.35);
            background-color: #fff;
        }
        .form-control::placeholder {
            color: #A0A0A0; 
        }
        input[type="date"].form-control {
            position: relative; 
        }
        
        .btn-submit-form {
            background-color: #D13878;
            border-color: #D13878;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            border-radius: 8px;
            width: 100%;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .btn-submit-form:hover {
            background-color: #B34073;
            border-color: #B34073;
        }
        .btn-submit-form i {
            margin-left: 8px;
        }
       
        .back-link {
            color: #777;
            text-decoration: none;
            font-size: 0.95rem;
            display: inline-block;
        }
        .back-link:hover {
            color: #C7346F;
            text-decoration: underline;
        }
        .back-link i {
            margin-left: 5px;
            vertical-align: middle;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem; 
        }
        .alert-danger {
            background-color: #FFEBEE;
            color: #B71C1C;
            border: 1px solid #FFCDD2;
        }
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        .alert-success .alert-link {
            color: #1B5E20;
            font-weight: bold;
        }
        .form-text.text-muted { 
            font-size: 0.85rem;
            color: #777 !important;
        }
        .flex-grow-1 { 
             padding: 15px;
        }
    </style>
</head>
<body>
<div class="d-flex">
   
    <div class="flex-grow-1">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-9 main-box"> 
                    <div class="page-header"><i class="bi bi-clipboard-plus"></i> إضافة طفل جديد</div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"> {/* استخدام قائمة لعرض الأخطاء بشكل أفضل */}
                                <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                            </ul>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle-fill" style="font-size: 1.5rem; margin-bottom: 10px; display:block;"></i>
                            تم إضافة الطفل بنجاح!
                            <hr>
                            <a href="children.php" class="alert-link">العودة لإدارة الأطفال</a>
                            او <a href="add_child.php" class="alert-link">إضافة طفل آخر</a>
                            <br><a href="add_daily_activity.php?child_id=<?= $pdo->lastInsertId() ?>" class="alert-link">بدء تسجيل أنشطة الطفل</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" action="add_child.php" autocomplete="off">
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم الطفل</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">تاريخ الميلاد</label>
                            <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= htmlspecialchars($birth_date) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="twin_group" class="form-label">مجموعة التوائم (اختياري)</label>
                            <input type="text" name="twin_group" id="twin_group" class="form-control" value="<?= htmlspecialchars($twin_group) ?>" placeholder="مثلاً: TWINS-001">
                            <small class="form-text text-muted">أدخل رمزاً مشتركاً إذا كان الطفل توأماً لتسهيل المقارنة لاحقاً.</small>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <strong>ملاحظة:</strong> سيتم تسجيل الوزن والطول والعمر عند إضافة أول سجل نشاط يومي في الصفحة التالية.
                        </div>
                        
                        <button type="submit" class="btn btn-submit-form mt-3"><i class="bi bi-plus-lg"></i> إضافة الطفل</button>
                        <div class="text-center mt-4">
                            <a href="children.php" class="back-link"><i class="bi bi-arrow-right-circle"></i> العودة لإدارة الأطفال</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>