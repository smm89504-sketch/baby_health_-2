<?php

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
$full_name = '';
$email = '';
$phone = '';
$security_question = '';
$security_answer = '';
$user_type = 'parent'; //default value

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer = trim($_POST['security_answer'] ?? '');
    $user_type = $_POST['user_type'] ?? 'parent';

    
    if (!$full_name || !$email || !$phone || !$password || !$confirm_password || !$security_question || !$security_answer) {
        $errors[] = 'جميع الحقول مطلوبة.';
    }
   
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }
    
    $strong = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$/";
    if ($password && !preg_match($strong, $password)) {
        $errors[] = 'كلمة السر ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'كلمتا السر غير متطابقتين.';
    }
    
    if ($phone && !preg_match('/^\d{8,15}$/', $phone)) {
        $errors[] = 'رقم الهاتف غير صالح.';
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'البريد الإلكتروني مستخدم بالفعل.';
            } else {
                
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // ** user_type **
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password, security_question, security_answer, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $full_name,
                    $email,
                    $phone,
                    $hashed_password,
                    $security_question,
                    $security_answer,
                    $user_type // **تمت الإضافة**
                ]);
                $success = true;
                
                $full_name = $email = $phone = $security_question = $security_answer = '';
            }
        } catch (PDOException $e) {
           
            $errors[] = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;700;900&family=Noto+Sans:wght@400;500;700;900&display=swap">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... (CSS Styles as in the original file) ... */
        body { font-family: Lexend, "Noto Sans", sans-serif; background:rgb(214, 169, 192); }
        .form-frame { background-color: #FFF7FA; padding: 25px 30px; border-radius: 25px; box-shadow: 0 8px 20px rgba(153, 77, 115, 0.18); margin-top: 20px; }
        .form-input-wrapper { position: relative; display: flex; align-items: center; }
        .form-input-icon { position: absolute; top: 50%; transform: translateY(-50%); left: 20px; color: #994D73; font-size: 1.1em; pointer-events: none; }
         .form-input { background: linear-gradient(135deg,rgb(204, 176, 190) 0%,rgb(236, 208, 222) 100%); border: none; border-radius: 90px; padding: 12px 20px; padding-left: 55px; width: 100%; transition: all 0.3s ease; color: #333; }
        .form-input::placeholder { color: #7a5c6a; }
        .form-input:focus { outline: none; box-shadow: 0 0 0 3.5px rgba(153, 77, 115, 0.25); }
        .form-select { background-color:rgb(226, 198, 212); border: 1px solid #caaab6; border-radius: 50px; padding: 12px 20px; width: 100%; appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23994D73' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: left 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; color: #513845; }
        .form-select:focus { outline: none; border-color: #994D73; box-shadow: 0 0 0 3px rgba(153, 77, 115, 0.2); }
        .strength { font-size: 0.9rem; padding: 5px 15px; border-radius: 50px; display: inline-block; }
        .form-label { color: #994D73; font-weight: 500; margin-bottom: 8px; padding-right: 10px; display: block; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .alert-danger { background-color: #fdecea; border: 1px solid #f5c2c7; color: #842029; }
        .alert-success { background-color: #d1e7dd; border: 1px solid #badbcc; color: #0f5132; }
        .form-link { color: #994D73; text-decoration: none; font-weight: 500; transition: color 0.3s; }
        .form-link:hover { color: #7a3c5c; text-decoration: underline; }
        .form-button { background-color: #F04299; color: white; border-radius: 50px; padding: 12px 20px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; border: none; width: 100%; font-size: 1rem; }
        .form-button:hover { background-color: #d33885; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(153, 77, 115, 0.2); }
        .strength-indicator { height: 4px; border-radius: 2px; margin-top: 5px; transition: all 0.3s ease; background-color: #e9ecef; }
    </style>
</head>
<body class="relative flex size-full min-h-screen flex-col bg-[#fdecea] overflow-x-hidden">
    <div class="layout-container flex h-full grow flex-col">
        <div class="px-4 md:px-40 flex flex-1 justify-center py-5">
            <div class="layout-content-container flex flex-col max-w-[960px] flex-1">
                <div class="@container">
                    <div class="@[480px]:px-4 @[480px]:py-3">
                        <div
                            class="w-full bg-center bg-no-repeat bg-cover flex flex-col justify-end overflow-hidden bg-[#f5c2c7] @[480px]:rounded-xl min-h-80"
                            style='background-image: url("images/image.png");'
                        ></div>
                    </div>
                </div>
                <h2 class="text-[#191014] tracking-light text-[28px] font-bold leading-tight px-4 text-center pb-3 pt-5">أنشئ حسابك</h2>
                <p class="text-[#191014] text-base font-normal leading-normal pb-3 pt-1 px-4 text-center">
                    أدخل بياناتك لإنشاء حساب جديد
                </p>
                
                <div class="flex justify-center">
                    <div class="flex flex-1 gap-3 max-w-[480px] flex-col items-stretch px-4 py-3 form-frame">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <div class="font-bold mb-2">حدثت الأخطاء التالية:</div>
                                <?php foreach ($errors as $error): ?>
                                    <div class="flex items-start">
                                        <span class="ml-2">•</span> <span><?php echo $error; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success">
                                <div class="font-bold text-center mb-2">تم إنشاء الحساب بنجاح!</div>
                                <div class="text-center">
                                    <a href="login.php" class="form-link">اضغط هنا لتسجيل الدخول</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" autocomplete="off" class="space-y-4">
                            
                            <div>
                                <label class="form-label" for="user_type">نوع الحساب:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-user-tag form-input-icon"></i> 
                                    <select id="user_type" name="user_type" class="form-select" required>
                                        <option value="parent" <?php echo ($user_type) === 'parent' ? 'selected' : ''; ?>>أهل</option>
                                        <option value="nurse" <?php echo ($user_type) === 'nurse' ? 'selected' : ''; ?>>ممرض/ممرضة</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="full_name">الاسم الكامل:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-user form-input-icon"></i>
                                    <input type="text" id="full_name" name="full_name" class="form-input" required value="<?php echo htmlspecialchars($full_name); ?>" placeholder="">
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" for="email">البريد الإلكتروني:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-envelope form-input-icon"></i>
                                    <input type="email" id="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" for="phone">رقم الهاتف:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-phone form-input-icon"></i>
                                    <input type="text" id="phone" name="phone" class="form-input" required value="<?php echo htmlspecialchars($phone); ?>" >
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" for="password">كلمة السر:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-lock form-input-icon"></i>
                                    <input type="password" name="password" id="password" class="form-input" required oninput="checkStrength()">
                                </div>
                                <div id="strengthMessage" class="strength mt-1 text-sm"></div>
                                <div id="strengthBar" class="strength-indicator"></div>
                            </div>
                            
                            <div>
                                <label class="form-label" for="confirm_password">تأكيد كلمة السر:</label>
                                <div class="form-input-wrapper">
                                    <i class="fas fa-lock form-input-icon"></i>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" for="security_question">سؤال الأمان:</label>
                                <div class="form-input-wrapper"> <i class="fas fa-question-circle form-input-icon" style="right: auto; left: 18px; z-index: 1;"></i> <select id="security_question" name="security_question" class="form-select" required>
                                        <option value="">اختر سؤالاً...</option>
                                        <option value="اسم أول مدرسة التحقت بها؟" <?php echo ($security_question) === 'اسم أول مدرسة التحقت بها؟' ? 'selected' : ''; ?>>اسم أول مدرسة التحقت بها؟</option>
                                        <option value="ما هو اسم صديق طفولتك المفضل؟" <?php echo ($security_question) === 'ما هو اسم صديق طفولتك المفضل؟' ? 'selected' : ''; ?>>ما هو اسم صديق طفولتك المفضل؟</option>
                                        <option value="ما هو اسم والدتك قبل الزواج؟" <?php echo ($security_question) === 'ما هو اسم والدتك قبل الزواج؟' ? 'selected' : ''; ?>>ما هو اسم والدتك قبل الزواج؟</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label" for="security_answer">جواب الأمان:</label>
                                 <div class="form-input-wrapper">
                                    <i class="fas fa-shield-alt form-input-icon"></i>
                                    <input type="text" id="security_answer" name="security_answer" class="form-input" required value="<?php echo htmlspecialchars($security_answer); ?>" placeholder="إجابتك السرية">
                                </div>
                            </div>
                            
                            <button type="submit" class="form-button">إنشاء حساب</button>
                            
                            <div class="flex justify-between mt-4 pt-2"> <a href="login.php" class="form-link">لديك حساب؟ تسجيل الدخول</a>
                                <a href="forgot_password.php" class="form-link">نسيت كلمة السر؟</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function checkStrength() {
        var pwd = document.getElementById('password').value;
        var msg = document.getElementById('strengthMessage');
        var bar = document.getElementById('strengthBar');
        var strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/; 
        var medium = /^(?=.*[a-zA-Z])(?=.*\d).{6,}$/; 
        
        bar.style.height = '6px'; 
        bar.style.borderRadius = '3px';

        if (pwd.length === 0) {
            msg.textContent = '';
            bar.style.width = '0%';
            bar.style.backgroundColor = 'transparent';
            return;
        }
        
        if (strong.test(pwd)) {
            msg.innerHTML = '<i class="fas fa-check-circle" style="color: #0f5132;"></i> كلمة سر قوية';
            msg.style.color = '#0f5132';
            bar.style.width = '100%';
            bar.style.backgroundColor = '#28a745'; 
        } else if (medium.test(pwd)) {
            msg.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #e67700;"></i> كلمة سر متوسطة';
            msg.style.color = '#e67700';
            bar.style.width = '65%';
            bar.style.backgroundColor = '#ffc107'; 
        } else {
            msg.innerHTML = '<i class="fas fa-times-circle" style="color: #842029;"></i> كلمة سر ضعيفة';
            msg.style.color = '#842029';
            bar.style.width = '30%';
            bar.style.backgroundColor = '#dc3545'; 
        }
    }
    
    </script>
</body>
</html>