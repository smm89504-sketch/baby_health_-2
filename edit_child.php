<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$host = 'localhost';
$db   = 'baby_tracker';
$user = 'root';
$pass = ''; //Your password
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$errors = [];
$success = false;
$child_id_for_form_action = $_GET['id'] ?? null; // تم تغيير الاسم لتمييزه عن $id المستخدم لاحقاً داخل POST

if (!$child_id_for_form_action) {
    header('Location: children.php'); 
    exit;
}

// === START Sidebar Setup (Color and Links) ===
$user_type = $_SESSION['user_type'] ?? 'parent';
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
// === END Sidebar Setup ===

// دالة لمعالجة إشعارات التطعيم (مطلوبة للجانب الأيمن)
function get_parent_alerts($due_vaccines) {
    $alerts = ['upcoming' => [], 'missed' => []];
    $today = new DateTime();
    foreach ($due_vaccines as $rec) {
        if ($rec['status'] === 'due') {
            $due_date = new DateTime($rec['due_date']);
            $interval = $today->diff($due_date);
            if ($due_date < $today) {
                $alerts['missed'][] = $rec;
            } elseif ($interval->days <= 7) {
                $alerts['upcoming'][] = $rec;
            }
        }
    }
    return $alerts;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Retrieve vaccination information for children (for sidebar alerts)
    $due_vaccines = [];
    if ($user_type === 'parent') {
        $stmt_vaccines_sidebar = $pdo->prepare('SELECT cv.*, v.name as vaccine_name, c.name as child_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id JOIN children c ON cv.child_id = c.id WHERE c.user_id = ? AND cv.status = "due" ORDER BY cv.due_date ASC');
        $stmt_vaccines_sidebar->execute([$_SESSION['user_id']]);
        $due_vaccines = $stmt_vaccines_sidebar->fetchAll();
    }
    $vaccine_alerts_sidebar = $user_type === 'parent' ? get_parent_alerts($due_vaccines) : ['upcoming' => [], 'missed' => []];


    //Allowing doctors and nurses access to all children, and parents to their children only.
    $query = 'SELECT * FROM children WHERE id = ?';
    $params = [$child_id_for_form_action];
    
    if ($user_type === 'parent') {
        $query .= ' AND user_id = ?';
        $params[] = $_SESSION['user_id'];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $child = $stmt->fetch();
    if (!$child) {
        header('Location: children.php'); // أو manage_children.php
        exit;
    }
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // $id هنا هو $child_id_for_form_action الذي تم تمريره للـ form action
    $id_from_post = $child_id_for_form_action; 

    $name = trim($_POST['name'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $twin_group = trim($_POST['twin_group'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $height = trim($_POST['height'] ?? '');

    if (!$name || !$birth_date || !$age || !$weight || !$height) {
        $errors[] = 'جميع الحقول مطلوبة.';
    }
    if (!empty($weight) && (!is_numeric($weight) || $weight <= 0)) {
        $errors[] = 'الوزن غير صالح.';
    }
    if (!empty($height) && (!is_numeric($height) || $height <= 0)) {
        $errors[] = 'الطول غير صالح.';
    }

    if (empty($errors)) {
        try {
            // التأكد من أن $pdo ما زال معرفاً إذا كان الاتصال قد فشل في البداية
            if (!isset($pdo)) {
                 $pdo = new PDO($dsn, $user, $pass, $options);
            }
            $stmt = $pdo->prepare('UPDATE children SET name = ?, birth_date = ?, age = ?, weight = ?, height = ?, twin_group = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([
                $name,
                $birth_date,
                $age,
                $weight,
                $height,
                $twin_group ?: null,
                $id_from_post, // استخدام المعرف الصحيح هنا
                $_SESSION['user_id']
            ]);
            $success = true;
            // Update child data for immediate viewing after modification
            $child['name'] = $name;
            $child['birth_date'] = $birth_date;
            $child['age'] = $age;
            $child['weight'] = $weight;
            $child['height'] = $height;
            $child['twin_group'] = $twin_group;
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء تحديث البيانات: ' . $e->getMessage();
        }
    }
}

// New default image path variable
$default_child_image = 'images/images.jpeg'; 
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل بيانات الطفل - <?= isset($child) ? htmlspecialchars($child['name']) : '' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
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
        .alert-missed { background: linear-gradient(to right, #ef9a9a, #e57373); color: white; border: none; }
        .alert-upcoming { background: linear-gradient(to right, #ffd54f, #ffca28); color: #5d4037; border: none; }
        
        /* Original File Styles */
        .main-box {
            margin-top: 0;
            margin-bottom: 25px;
            box-shadow: 0 6px 24px rgba(100, 100, 100, 0.1);
            border-radius: 12px;
            background: #ffffff;
            padding: 40px;
        }
        .page-header { /* تم تغيير اسم الكلاس من .logo */
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
        /* ... (rest of style tags) ... */
        .btn-save-form { /* استخدام كلاس مخصص لزر الحفظ */
            background-color: #007bff; /* لون أزرق مميز للتعديل */
            border-color: #007bff;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            border-radius: 8px;
            width: 100%;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .btn-save-form:hover {
            background-color: #0056b3; /* درجة أغمق عند المرور */
            border-color: #0056b3;
        }
        
        .current-child-image-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .current-child-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #FFF0F5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .default-image-placeholder { /* تم استبدالها بصورة */
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: auto;
            border: 4px solid #FFF0F5;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .flex-grow-1 { padding: 0; }

    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9 main-box mt-4">
                    <?php if (isset($child)): // تحقق من وجود بيانات الطفل قبل عرض العنوان ?>
                    <div class="page-header"><i class="bi bi-pencil-square"></i> تعديل بيانات: <?= htmlspecialchars($child['name']) ?></div>
                    <?php else: ?>
                    <div class="page-header"><i class="bi bi-pencil-square"></i> تعديل بيانات الطفل</div>
                    <?php endif; ?>


                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                            </ul>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success text-center">
                             <i class="bi bi-check-circle-fill" style="font-size: 1.5rem; margin-bottom: 10px; display:block;"></i>
                            تم تحديث بيانات الطفل بنجاح!
                            <hr>
                            <a href="children.php" class="alert-link">العودة لإدارة الأطفال</a>
                            
                        </div>
                    <?php endif; ?>

                    <?php if (isset($child) && !$success): // لا تعرض النموذج إذا تم التحديث بنجاح أو إذا كان هناك خطأ فادح في جلب الطفل ?>
                        
                        <div class="current-child-image-container">
                             <img src="<?= htmlspecialchars($default_child_image) ?>" alt="صورة افتراضية للطفل" class="default-image-placeholder">
                             <small class="form-text text-muted d-block mt-2">صورة الطفل الافتراضية.</small>
                        </div>
                        

                        <form method="POST" action="edit_child.php?id=<?= htmlspecialchars($child_id_for_form_action) ?>" autocomplete="off">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الطفل</label>
                                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($child['name']) ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label">تاريخ الميلاد</label>
                                    <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= htmlspecialchars($child['birth_date']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="twin_group" class="form-label">مجموعة التوائم (اختياري)</label>
                                    <input type="text" name="twin_group" id="twin_group" class="form-control" value="<?= htmlspecialchars($child['twin_group'] ?? '') ?>" placeholder="مثلاً: TWINS-001">
                                    <small class="form-text text-muted">استخدم نفس الكود للتوائم حتى تظهر المقارنة بدقة.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="age" class="form-label">العمر</label>
                                    <input type="text" name="age" id="age" class="form-control" value="<?= htmlspecialchars($child['age']) ?>" required placeholder="مثال: 3 سنوات، 6 أشهر، 10 أيام">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="weight" class="form-label">الوزن (كغ)</label>
                                    <input type="number" name="weight" id="weight" class="form-control" min="0.1" step="0.01" value="<?= htmlspecialchars($child['weight']) ?>" required placeholder="مثال: 3.5">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="height" class="form-label">الطول (سم)</label>
                                    <input type="number" name="height" id="height" class="form-control" min="10" step="0.1" value="<?= htmlspecialchars($child['height']) ?>" required placeholder="مثال: 50">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-save-form mt-3"><i class="bi bi-check-lg"></i> حفظ التعديلات</button>
                            <div class="text-center mt-4">
                                <a href="children.php" class="back-link"><i class="bi bi-arrow-right-circle"></i> العودة لإدارة الأطفال</a>
                                
                            </div>
                        </form>
                    <?php elseif(!isset($child) && empty($errors)): ?>
                        <div class="alert alert-warning text-center">لا يمكن تحميل بيانات الطفل للتعديل.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>