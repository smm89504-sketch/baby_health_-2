<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
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
$userData = [];

$user_type = $_SESSION['user_type'] ?? 'parent';
$dashboard_link = $user_type === 'doctor' ? 'doctor_dashboard.php' : ($user_type === 'nurse' ? 'nurse_dashboard.php' : 'profile.php');

// Color selection based on role
if ($user_type === 'doctor') {
    $main_dark = '#842029'; // dark red/maroon
    $main_text = '#dc3545'; // red
    $main_light = '#f5c6cb'; // light red
    $main_deep = '#f1aeb5'; // deep light red
    $bg_light = '#f8d7da'; // lightest red bg
    $title_icon = 'fas fa-stethoscope';
} elseif ($user_type === 'nurse') {
    $main_dark = '#0f5132'; // dark green
    $main_text = '#28a745'; // green
    $main_light = '#c3e6cb'; // light green
    $main_deep = '#b1dfbb'; // deep light green
    $bg_light = '#d4edda'; // lightest green bg
    $title_icon = 'fas fa-syringe';
} else { // parent
    $main_dark = '#ad1457';
    $main_text = '#880e4f';
    $main_light = '#ffd1dc';
    $main_deep = '#f8bbd0';
    $bg_light = '#fff0f5';
    $title_icon = 'fas fa-heartbeat';
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();
    
    // Retrieve vaccination information for children (only if the user is eligible)
    $due_vaccines = [];
    if ($user_type === 'parent') {
        $stmt_vaccines = $pdo->prepare('SELECT cv.*, v.name as vaccine_name, c.name as child_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id JOIN children c ON cv.child_id = c.id WHERE c.user_id = ? AND cv.status = "due" ORDER BY cv.due_date ASC');
        $stmt_vaccines->execute([$_SESSION['user_id']]);
        $due_vaccines = $stmt_vaccines->fetchAll();
    }
    
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات.';
}

//A function for processing vaccination notifications
function get_parent_alerts($due_vaccines) {
    $alerts = ['upcoming' => [], 'missed' => []];
    $today = new DateTime();
    foreach ($due_vaccines as $rec) {
        $due_date = new DateTime($rec['due_date']);
        $interval = $today->diff($due_date);
        if ($due_date < $today) {
            $alerts['missed'][] = $rec;
        } elseif ($interval->days <= 7) {
            $alerts['upcoming'][] = $rec;
        }
    }
    return $alerts;
}

$vaccine_alerts = $user_type === 'parent' ? get_parent_alerts($due_vaccines) : ['upcoming' => [], 'missed' => []];


// Data update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (!$full_name || !$phone) {
        $errors[] = 'جميع الحقول مطلوبة.';
    }
    if (!preg_match('/^\d{8,15}$/', $phone)) {
        $errors[] = 'رقم الهاتف غير صالح.';
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
            $stmt->execute([$full_name, $phone, $_SESSION['user_id']]);
            $success = true;
            $_SESSION['full_name'] = $full_name;
            // Update displayed data
            $userData['full_name'] = $full_name;
            $userData['phone'] = $phone;
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء تحديث البيانات.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        session_destroy();
        header('Location: register.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        $errors[] = 'حدث خطأ أثناء حذف الحساب.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (!$old_password || !$new_password || !$confirm_password) {
        $errors[] = 'جميع حقول كلمة المرور مطلوبة.';
    } else {
     
        if (!password_verify($old_password, $userData['password'])) {
            $errors[] = 'كلمة المرور القديمة غير صحيحة.';
        } else {
            
            $strong = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$/";
            if (!preg_match($strong, $new_password)) {
                $errors[] = 'كلمة السر الجديدة ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز.';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'كلمتا السر الجديدتان غير متطابقتين.';
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $success = 'تم تغيير كلمة المرور بنجاح!';
                } catch (PDOException $e) {
                    $errors[] = 'حدث خطأ أثناء تغيير كلمة المرور.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - متابعة صحة الأطفال</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            /* Dynamic Colors based on user_type */
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;

            --secondary-light: #e8f5e9;
            --success: #66bb6a;
            --danger: #ef9a9a;
            --warning: #ffd54f;
            --shadow-sm: 0 4px 12px rgba(136, 14, 79, 0.08);
            --shadow-md: 0 8px 24px rgba(136, 14, 79, 0.12);
        }
        
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%);
            min-height: 100vh;
            color: #4a4a4a;
            display: flex;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%);
            width: 250px;
            min-height: 100vh;
            padding: 20px;
            color: white;
            box-shadow: var(--shadow-md);
            transition: all 0.3s;
        }
        
        .sidebar a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .sidebar a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 8px;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .logo i {
            color: var(--primary-pink);
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 12px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: flex-start;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
        }
        
        .main-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .header::after {
            content: "";
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .header h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 5px;
            position: relative;
            z-index: 2;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        
        @media (min-width: 992px) {
            .main-content {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary-pink), var(--primary-deep));
            border: none;
            padding: 20px 25px;
            color: var(--primary-text);
            font-weight: 700;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--primary-light);
            border-radius: 15px;
        }
        
        .info-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-deep);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.4rem;
        }
        
        .info-content h5 {
            font-weight: 600;
            color: var(--primary-text);
            margin-bottom: 5px;
        }
        
        .info-content p {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-text);
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid var(--primary-pink);
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
            background-color: var(--primary-light);
        }
        
        .form-control:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-dark-rgb), 0.15); /* Using RGB for shadow transparency (not defined, keeping original for simplicity) */
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-text));
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
        }
        
        .btn-danger {
            background: linear-gradient(to right, #ef9a9a, #e57373);
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            transform: translateY(-3px);
        }
        
        .btn-warning {
            background: linear-gradient(to right, #ffd54f, #ffca28);
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            color: #5d4037;
            transition: all 0.3s;
        }
        
        .btn-warning:hover {
            transform: translateY(-3px);
        }
        
        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            font-size: 1rem;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: linear-gradient(to right, #ffcdd2, #ef9a9a);
            border: none;
            color: #b71c1c;
        }
        
        .alert-success {
            background: linear-gradient(to right, #c8e6c9, #a5d6a7);
            border: none;
            color: #1b5e20;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 2px dashed var(--primary-pink);
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-title {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--primary-pink);
        }
        
        .form-title i {
            font-size: 1.5rem;
        }
        
        .baby-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .baby-icon {
            font-size: 2.5rem;
            color: var(--primary-dark);
            opacity: 0.7;
        }
        
        /* Styles for Alerts in Sidebar */
        .alert-missed { background: linear-gradient(to right, #ef9a9a, #e57373); color: white; border: none; }
        .alert-upcoming { background: linear-gradient(to right, #ffd54f, #ffca28); color: #5d4037; border: none; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
    
    <div class="main-container">
        <div class="dashboard-container">
            <div class="header">
                <h1><i class="fas fa-user-circle"></i> الملف الشخصي</h1>
                <p>مرحباً بك، هنا يمكنك تحديث معلوماتك الشخصية وإدارة حسابك</p>
            </div>
            
            <div class="main-content">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-circle"></i> معلومات الملف الشخصي
                    </div>
                    <div class="card-body">
                        <div class="profile-info">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="info-content">
                                    <h5>الاسم الكامل</h5>
                                    <p><?= htmlspecialchars($userData['full_name'] ?? '') ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-content">
                                    <h5>البريد الإلكتروني</h5>
                                    <p><?= htmlspecialchars($userData['email'] ?? '') ?></p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="info-content">
                                    <h5>رقم الهاتف</h5>
                                    <p><?= htmlspecialchars($userData['phone'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user-edit"></i> تحديث الملف الشخصي
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger mb-4">
                                <?php foreach ($errors as $error) echo "<div><i class='fas fa-exclamation-circle me-2'></i> $error</div>"; ?>
                            </div>
                        <?php elseif (is_string($success) || $success === true): ?>
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle me-2"></i> <?= is_string($success) ? htmlspecialchars($success) : 'تم تحديث البيانات بنجاح!' ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="profile.php" autocomplete="off">
                            <div class="form-section">
                                <div class="form-title">
                                    <i class="fas fa-user-edit"></i>
                                    <h4 class="m-0">تحديث البيانات الشخصية</h4>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">الاسم الكامل</label>
                                    <input type="text" name="full_name" class="form-control" 
                                           value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" 
                                           required placeholder="أدخل الاسم الكامل">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" 
                                           value="<?= htmlspecialchars($userData['email'] ?? '') ?>" 
                                           disabled placeholder="البريد الإلكتروني">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" name="phone" class="form-control" 
                                           value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" 
                                           required placeholder="أدخل رقم الهاتف">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary w-100">
                                    <i class="fas fa-sync-alt me-2"></i> تحديث البيانات
                                </button>
                            </div>
                        </form>
                        
                        <form method="POST" action="profile.php" autocomplete="off">
                            <div class="form-section">
                                <div class="form-title">
                                    <i class="fas fa-user-lock"></i>
                                    <h4 class="m-0">تغيير كلمة المرور</h4>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور القديمة</label>
                                    <input type="password" name="old_password" class="form-control" 
                                           required placeholder="أدخل كلمة المرور القديمة">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" name="new_password" class="form-control" 
                                           required placeholder="أدخل كلمة المرور الجديدة">
                                    <div class="form-text mt-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز.
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" name="confirm_password" class="form-control" 
                                           required placeholder="أعد إدخال كلمة المرور الجديدة">
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-warning w-100">
                                    <i class="fas fa-key me-2"></i> تغيير كلمة المرور
                                </button>
                            </div>
                        </form>
                        
                        <form method="POST" action="profile.php" onsubmit="return confirm('هل أنت متأكد أنك تريد حذف الحساب؟ لا يمكن التراجع!');">
                            <div class="form-section">
                                <div class="form-title">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <h4 class="m-0">إدارة الحساب</h4>
                                </div>
                                
                                <button type="submit" name="delete_account" class="btn btn-danger w-100">
                                    <i class="fas fa-trash-alt me-2"></i> حذف الحساب نهائياً
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="baby-icons">
                <i class="fas fa-baby baby-icon"></i>
                <i class="fas fa-baby-carriage baby-icon"></i>
                <i class="fas fa-puzzle-piece baby-icon"></i>
                <i class="fas fa-apple-alt baby-icon"></i>
            </div>

        </div>
    </div>
    
    <div class="floating-babies">
        <div class="floating-baby"><i class="fas fa-baby"></i></div>
        <div class="floating-baby"><i class="fas fa-baby-carriage"></i></div>
        <div class="floating-baby"><i class="fas fa-puzzle-piece"></i></div>
        <div class="floating-baby"><i class="fas fa-apple-alt"></i></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
        
        
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mousedown', () => {
                btn.style.transform = 'scale(0.98)';
            });
            btn.addEventListener('mouseup', () => {
                btn.style.transform = 'scale(1)';
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>