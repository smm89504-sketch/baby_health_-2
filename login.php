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

$errors = [];
$email_value = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $email_value = $email; 

    if (!$email || !$password) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني وكلمة السر.';
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user_record = $stmt->fetch(); 

            if ($user_record && password_verify($password, $user_record['password'])) {
                
                $_SESSION['user_id'] = $user_record['id'];
                $_SESSION['full_name'] = $user_record['full_name'];
                $_SESSION['user_type'] = $user_record['user_type']; 

                $user_type = $user_record['user_type'] ?? '';

//Specify the page to be redirected to
switch($user_type) {
    case 'parent':
        header('Location: profile.php');
        break;
    case 'nurse':
        header('Location: nurse_dashboard.php');
        break;
    case 'doctor':
        header('Location: doctor/index.php');
        break;
    case 'admin':
        header('Location: admin/index.php');
        break;
         default:
        header('Location: index.php');
   
}
                // منطق التوجيه المعدل: توجيه جميع الأدوار إلى صفحة الملف الشخصي (profile.php)
                // header('Location: profile.php'); 
                exit;
            } else {
                $errors[] = 'بيانات الدخول غير صحيحة. الرجاء التأكد من البريد الإلكتروني أو كلمة السر.';
            }
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ في الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً.';
            
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - متابعة نمو طفلي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #FFF0F5; 
            font-family: 'Nunito', sans-serif; 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 15px;
        }

        .main-box {
            box-shadow: 0 10px 30px rgba(255, 182, 193, 0.4); 
            border-radius: 25px; 
            background: #fff;
            border: 2px solid #FFC0CB; 
            max-width: 480px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .baby-icon {
            font-size: 4rem; 
            color: #FF69B4; 
            margin-bottom: 0.5rem;
        }

        .login-title {
            font-size: 1.8rem;
            color: #DB7093; 
            font-weight: 700;
        }

        .form-label {
            color: #DB7093; 
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 1.25rem; 
        }

        .form-control.cute-input {
            border-radius: 50px; 
            border: 1px solid #FFDAE0; 
            background-color: #FFF7FA; 
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            padding-right: 45px; 
            padding-left: 20px;
            font-size: 1rem;
            height: auto; 
            transition: all 0.3s ease;
        }

        .form-control.cute-input:focus {
            border-color: #FFB6C1; 
            box-shadow: 0 0 0 0.25rem rgba(255, 105, 180, 0.25); 
            background-color: #fff;
        }

        .input-icon {
            position: absolute;
            top: 65%;
            right: 18px; 
            transform: translateY(-50%);
            color: #FF85A2; 
            font-size: 1.1rem;
            pointer-events: none; 
        }

        .btn-cute-login {
            background-color: #FF69B4; 
            border-color: #FF69B4;
            color: white;
            border-radius: 50px; 
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 700; 
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: flex; 
            align-items: center;
            justify-content: center;
        }

        .btn-cute-login:hover {
            background-color: #E75480; 
            border-color: #E75480;
            color: white;
            transform: translateY(-2px);
        }
        .btn-cute-login:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 105, 180, 0.5);
        }
        .btn-cute-login .button-icon {
            margin-right: 8px; 
        }

        .extra-links a {
            color: #DB7093; 
            text-decoration: none;
            font-weight: 600;
        }

        .extra-links a:hover {
            color: #FF69B4; 
            text-decoration: underline;
        }
        
        .alert-danger {
            background-color: #FFE4E9; 
            border-color: #FFB6C1; 
            color: #9B1C3C; 
            border-radius: 15px; 
            font-size: 0.9rem;
        }
        .alert-danger div {
            display: flex;
            align-items: center;
        }
        .alert-danger .fas { 
            margin-left: 8px; 
            font-size: 1.1rem;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9 main-box p-4 p-md-5">
                <div class="login-header">
                    <i class="fas fa-baby baby-icon"></i>
                    <div class="login-title">مرحباً بك!</div>
                    <p style="color: #777; font-size:0.9rem;">سجل دخولك لتتبع حالة طفلك</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" autocomplete="off">
                    <div class="input-wrapper">
                        <label for="emailInput" class="form-label">البريد الإلكتروني</label>
                        <input type="email" id="emailInput" name="email" class="form-control cute-input" required value="<?php echo htmlspecialchars($email_value); ?>">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>

                    <div class="input-wrapper">
                        <label for="passwordInput" class="form-label">كلمة السر</label>
                        <input type="password" id="passwordInput" name="password" class="form-control cute-input" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>

                    <button type="submit" class="btn btn-cute-login w-100 mt-3">
                        <i class="fas fa-sign-in-alt button-icon"></i> دخول
                    </button>

                    <div class="text-center mt-4 extra-links">
                        <a href="register.php">ليس لديك حساب؟ إنشاء حساب جديد</a>
                        <hr style="border-top: 1px dashed #FFDAE0; margin: 1rem 0;">
                        <a href="forgot_password.php">نسيت كلمة السر؟</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </body>
</html>