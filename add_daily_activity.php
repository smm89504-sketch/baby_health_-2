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
$children = [];
$success_msg = '';
$child_id_form = $_REQUEST['child_id'] ?? ''; 

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

// Function to handle vaccination notifications (required for the right side)
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

// تطبيق معايير النمو بناءً على جدول growth_standards
function parseAgeToMonths($ageText) {
    $ageText = trim(mb_strtolower($ageText));
    $months = 0.0;
    $days = 0.0;

    if (preg_match('/([\d\.]+)\s*(?:أشهر|شهور|months?)/u', $ageText, $m)) {
        $months = floatval($m[1]);
    }
    if (preg_match('/([\d\.]+)\s*(?:أيام|يوم|days?)/u', $ageText, $m)) {
        $days = floatval($m[1]);
    }
    if ($months === 0.0 && preg_match('/^([\d\.]+)/u', $ageText, $m)) {
        $months = floatval($m[1]);
    }

    $totalMonths = $months + ($days / 30.0);
    return (int) round(max(0, $totalMonths));
}

function getGrowthStandard($pdo, $age_months) {
    $age_months = max(0, (int) $age_months);
    $stmt = $pdo->prepare('SELECT * FROM growth_standards WHERE age_months = ? LIMIT 1');
    $stmt->execute([$age_months]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }

    $stmtLow = $pdo->prepare('SELECT * FROM growth_standards WHERE age_months <= ? ORDER BY age_months DESC LIMIT 1');
    $stmtLow->execute([$age_months]);
    $low = $stmtLow->fetch();

    $stmtHigh = $pdo->prepare('SELECT * FROM growth_standards WHERE age_months >= ? ORDER BY age_months ASC LIMIT 1');
    $stmtHigh->execute([$age_months]);
    $high = $stmtHigh->fetch();

    if (!$low && !$high) {
        return null;
    }
    if (!$low) {
        return $high;
    }
    if (!$high) {
        return $low;
    }
    if ($low['age_months'] === $high['age_months']) {
        return $low;
    }

    $ratio = ($age_months - $low['age_months']) / max(1, $high['age_months'] - $low['age_months']);
    $interp = [];
    foreach (['avg_weight_kg', 'weight_upper_percentile_kg', 'weight_lower_percentile_kg', 'avg_height_cm', 'height_upper_percentile_cm', 'height_lower_percentile_cm'] as $key) {
        $lowVal = floatval($low[$key]);
        $highVal = floatval($high[$key]);
        $interp[$key] = $lowVal + ($highVal - $lowVal) * $ratio;
    }
    return $interp;
}

function computePercentileValue($value, $lower, $avg, $upper) {
    $value = floatval($value);
    $lower = floatval($lower);
    $avg = floatval($avg);
    $upper = floatval($upper);

    if ($value <= $lower) {
        return 1;
    }
    if ($value >= $upper) {
        return 99;
    }
    if ($value <= $avg) {
        $span = max(0.0001, $avg - $lower);
        return round(1 + 49 * ($value - $lower) / $span);
    }
    $span = max(0.0001, $upper - $avg);
    return round(50 + 49 * ($value - $avg) / $span);
}

function calculateWHOGrowthPercentile($pdo, $age_months, $weight, $height) {
    $standard = getGrowthStandard($pdo, $age_months);
    if (!$standard) {
        return null;
    }

    $weightPercentile = computePercentileValue($weight, $standard['weight_lower_percentile_kg'], $standard['avg_weight_kg'], $standard['weight_upper_percentile_kg']);
    $heightPercentile = computePercentileValue($height, $standard['height_lower_percentile_cm'], $standard['avg_height_cm'], $standard['height_upper_percentile_cm']);

    return (int) round(($weightPercentile + $heightPercentile) / 2);
}

function getLastDoctorForChild($pdo, $child_id) {
    $stmt = $pdo->prepare('SELECT doctor_id FROM medical_visits WHERE child_id = ? AND doctor_id IS NOT NULL ORDER BY visit_date DESC LIMIT 1');
    $stmt->execute([$child_id]);
    $row = $stmt->fetch();
    return $row ? $row['doctor_id'] : null;
}

// === END Sidebar Setup ===

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
  
    $stmt_children = $pdo->prepare('SELECT id, name FROM children WHERE user_id = ? ORDER BY name ASC');
    $stmt_children->execute([$_SESSION['user_id']]);
    $children = $stmt_children->fetchAll();
    
    // Retrieve vaccination information for children (for sidebar alerts)
    $due_vaccines = [];
    if ($user_type === 'parent') {
        $stmt_vaccines_sidebar = $pdo->prepare('SELECT cv.*, v.name as vaccine_name, c.name as child_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id JOIN children c ON cv.child_id = c.id WHERE c.user_id = ? AND cv.status = "due" ORDER BY cv.due_date ASC');
        $stmt_vaccines_sidebar->execute([$_SESSION['user_id']]);
        $due_vaccines = $stmt_vaccines_sidebar->fetchAll();
    }
    $vaccine_alerts = $user_type === 'parent' ? get_parent_alerts($due_vaccines) : ['upcoming' => [], 'missed' => []];


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $child_id = $_POST['child_id'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        $activity_type = $_POST['activity_type'] ?? '';

        if (!$child_id || !$date || !$activity_type) {
            $errors[] = 'يرجى اختيار الطفل والتاريخ ونوع النشاط.';
        }

        if (empty($errors)) {
            $stmt_insert = null;
            $data = [];
            $activity_details = '';

            switch ($activity_type) {
                case 'breast_feed':
                    $start_time = $_POST['bf_start_time'] ?? null;
                    $duration = $_POST['bf_duration'] ?? null;
                    if (!$duration) { $errors[] = 'يرجى إدخال مدة الرضاعة الطبيعية.'; break; }
                    $stmt_insert = $pdo->prepare('INSERT INTO daily_activities (child_id, date, activity_type, start_time, duration, details) VALUES (?, ?, ?, ?, ?, ?)');
                    $data = [$child_id, $date, 'breast_feed', $start_time, $duration, null];
                    $success_msg = 'تم تسجيل الرضاعة الطبيعية بنجاح.';
                    break;

                case 'formula_feed':
                    $time = $_POST['ff_time'] ?? null;
                    $quantity = $_POST['ff_quantity'] ?? null;
                    $formula_type = trim($_POST['ff_formula_type'] ?? '');
                    if (!$quantity || !$formula_type) { $errors[] = 'يرجى إدخال الكمية ونوع الحليب الصناعي.'; break; }
                    $stmt_insert = $pdo->prepare('INSERT INTO daily_activities (child_id, date, activity_type, start_time, quantity, details) VALUES (?, ?, ?, ?, ?, ?)');
                    $data = [$child_id, $date, 'formula_feed', $time, $quantity, $formula_type];
                    $success_msg = 'تم تسجيل الرضاعة الصناعية بنجاح.';
                    break;

                case 'nap':
                case 'night_sleep':
                    $start_time = $_POST['sleep_start'] ?? null;
                    $end_time = $_POST['sleep_end'] ?? null;
                    $duration = $_POST['sleep_hours'] ?? null;
                    $wake_ups = $_POST['wake_ups'] ?? null;
                    if (!$duration) { $errors[] = 'يرجى إدخال إجمالي ساعات النوم.'; break; }
                    $stmt_insert = $pdo->prepare('INSERT INTO daily_activities (child_id, date, activity_type, start_time, end_time, duration, quantity, details) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $data = [$child_id, $date, $activity_type, $start_time, $end_time, $duration, $wake_ups, $_POST['sleep_note'] ?? null];
                    $success_msg = 'تم تسجيل فترة النوم بنجاح.';
                    break;

                case 'growth_record':
                    $weight = $_POST['weight'] ?? null;
                    $height = $_POST['height'] ?? null;
                    $head_circ = $_POST['head_circumference'] ?? null;
                    $temperature = $_POST['temperature'] ?? null;
                    $illness = trim($_POST['illness'] ?? '');
                    $age = trim($_POST['age'] ?? '');
                    
                    if (!$weight || !$height || !$age) { $errors[] = 'حقول الوزن، الطول والعمر مطلوبة لتسجيل النمو.'; break; }

                    $age_months = parseAgeToMonths($age);
                    $bmi = 0;
                    if ($height > 0) {
                        $bmi = round($weight / pow(($height / 100), 2), 2);
                    }

                    $growthPercentile = calculateWHOGrowthPercentile($pdo, $age_months, $weight, $height);

                    $weightStatus = $growthPercentile >= 50 ? 'طبيعي إلى ممتاز' : ($growthPercentile >= 10 ? 'مقبول' : 'تحذير: ناقص وزن');
                    $heightStatus = $growthPercentile >= 50 ? 'طبيعي إلى ممتاز' : ($growthPercentile >= 10 ? 'مقبول' : 'تحذير: قصر نمو');
                    $growthAnalysis = "الطفل في المركز $growthPercentile٪. الوزن: $weightStatus. الطول: $heightStatus.";

                    // 1. تحديث البيانات الأساسية للطفل
                    $stmt_update_child = $pdo->prepare('UPDATE children SET age = ?, weight = ?, height = ? WHERE id = ? AND user_id = ?');
                    $stmt_update_child->execute([$age, $weight, $height, $child_id, $_SESSION['user_id']]);

                    // 2. سجل النمو في جدول growth_measurements (للوصول إليها في مخططات النمو)
                    $stmt_growth = $pdo->prepare('INSERT INTO growth_measurements (child_id, doctor_id, age_months, weight_kg, height_cm, head_circumference_cm, bmi, growth_percentile, measurement_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

                    if ($_SESSION['user_type'] === 'doctor') {
                        $doctor_id = $_SESSION['user_id'];
                    } else {
                        // الربط بطبيب آخر تلقائياً إذا ادخل الوالد القياسات
                        $doctor_id = getLastDoctorForChild($pdo, $child_id);
                    }

                    $stmt_growth->execute([
                        $child_id,
                        $doctor_id,
                        $age_months,
                        $weight,
                        $height,
                        $head_circ,
                        $bmi,
                        $growthPercentile,
                        $date,
                        'تسجيل نمو تلقائي من النشاط اليومي'
                    ]);

                    // 3. إدخال سجل النشاط اليومي كما كان سابقاً
                    $stmt_insert = $pdo->prepare('INSERT INTO daily_activities (child_id, date, activity_type, weight, height, temperature, illness, medicine_name, medicine_dose, medicine_time, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $data = [
                        $child_id, $date, 'growth_record', $weight, $height, $temperature, $illness,
                        $_POST['medicine_name'] ?? null, $_POST['medicine_dose'] ?? null, $_POST['medicine_time'] ?? null, $_POST['note'] ?? null
                    ];
                    $success_msg = 'تم تحديث بيانات النمو بنجاح.<br><strong>تقرير النمو:</strong><br>' .
                        'العمر: ' . $age . ' (' . $age_months . ' أشهر)<br>' .
                        'الوزن: ' . $weight . ' كجم<br>' .
                        'الطول: ' . $height . ' سم<br>' .
                        'مؤشر كتلة الجسم: ' . $bmi . '<br>' .
                        'المئوية حسب منظمة الصحة العالمية: ' . ($growthPercentile !== null ? $growthPercentile . '%' : 'غير متوفرة') . '<br>' .
                        '<strong>التحليل:</strong> ' . $growthAnalysis . '<br>' .
                        ($growthPercentile >= 50 ? '<span style="color: green;">النمو طبيعي وممتاز!</span>' : ($growthPercentile >= 10 ? '<span style="color: orange;">النمو مقبول، استشر الطبيب إذا لزم الأمر.</span>' : '<span style="color: red;">تحذير: قد يكون هناك مشكلة في النمو، استشر الطبيب فوراً.</span>'));
                    break;

                default:
                    $errors[] = 'نوع النشاط غير صالح.';
                    break;
            }

            if (empty($errors) && $stmt_insert) {
                $stmt_insert->execute($data);
                header("Location: add_daily_activity.php?child_id=$child_id&success=1");
                exit;
            }
        }
    }

} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// لملء النموذج بقيم الطفل الحالي (إذا كان موجوداً)
$current_child_data = [];
if ($child_id_form) {
    try {
        // Allowing doctors and nurses access to all children
        $query = 'SELECT * FROM children WHERE id = ?';
        $params = [$child_id_form];
        
        if ($user_type === 'parent') {
            $query .= ' AND user_id = ?';
            $params[] = $_SESSION['user_id'];
        }
        
        $stmt_child = $pdo->prepare($query);
        $stmt_child->execute($params);
        $current_child_data = $stmt_child->fetch();
    } catch (\Throwable $th) {
        // Handle error
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة نشاط يومي</title>
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
        .main-box { margin-top: 0; margin-bottom: 25px; box-shadow: 0 8px 25px rgba(100, 100, 100, 0.12); border-radius: 15px; background: #ffffff; padding: 30px; }
        .page-header { font-size: 2rem; color: #C7346F; font-weight: 700; text-align: center; margin-bottom: 30px; }
        .page-header i { margin-left: 12px; font-size: 1.8rem; vertical-align: -2px; }
        .activity-section { background-color: #FFFAFB; border-radius: 12px; border: 1px solid #FDEEF0; padding: 25px; margin-bottom: 35px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; color: #776268; margin-bottom: 0.5rem; font-size: 0.95rem; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #F0DDE2; padding: 0.65rem 1rem; background-color: #fff; }
        .form-control:focus, .form-select:focus { border-color: #E7AAB4; box-shadow: 0 0 0 0.2rem rgba(231, 170, 180, 0.35); }
        .btn-add-record { background-color: #D13878; border-color: #D13878; color: #fff; font-weight: 600; padding: 0.75rem 1.5rem; font-size: 1.1rem; border-radius: 8px; width: 100%; }
        .btn-add-record:hover { background-color: #B34073; border-color: #B34073; }
        .alert-success { background-color: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }
        .alert-danger { background-color: #FFEBEE; color: #B71C1C; border: 1px solid #FFCDD2; }
        .form-section-title { color: #C7346F; font-weight: 700; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px dashed #F0DDE2; }
        .flex-grow-1 { padding: 0; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-12 main-box p-lg-4 p-3 mt-4">
                <div class="page-header"><i class="bi bi-activity"></i> تسجيل الأنشطة اليومية</div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3"> 
                            <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    <div class="alert alert-success text-center mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i> تم تسجيل النشاط بنجاح!
                    </div>
                <?php endif; ?>

                <section class="activity-section">
                    <form method="POST" action="add_daily_activity.php"> 
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="child_id_form" class="form-label">اختر الطفل <span class="text-danger">*</span></label>
                                <select name="child_id" id="child_id_form" class="form-select" required>
                                    <option value="">-- اختر من القائمة --</option>
                                    <?php foreach ($children as $ch): ?>
                                        <option value="<?= $ch['id'] ?>" <?= ($child_id_form == $ch['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ch['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="date_form" class="form-label">تاريخ النشاط <span class="text-danger">*</span></label>
                                <input type="date" name="date" id="date_form" class="form-control" value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="activity_type_select" class="form-label">نوع النشاط <span class="text-danger">*</span></label>
                                <select name="activity_type" id="activity_type_select" class="form-select" required onchange="showActivityFields(this.value)">
                                    <option value="">-- اختر نوع النشاط --</option>
                                    <option value="breast_feed">رضاعة طبيعية</option>
                                    <option value="formula_feed">رضاعة صناعية</option>
                                    <option value="nap">نوم (قيلولة)</option>
                                    <option value="night_sleep">نوم (ليلي)</option>
                                    <option value="growth_record">تسجيل قياسات النمو والحالة الصحية</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="activity_fields">
                            
                            <div id="breast_feed_fields" class="mt-4" style="display:none;">
                                <div class="form-section-title"><i class="bi bi-cup-straw"></i> رضاعة طبيعية</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="bf_start_time" class="form-label">وقت البدء (اختياري)</label>
                                        <input type="time" name="bf_start_time" id="bf_start_time" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="bf_duration" class="form-label">المدة (بالدقائق) <span class="text-danger">*</span></label>
                                        <input type="number" name="bf_duration" id="bf_duration" class="form-control" min="1" step="1" placeholder="مثال: 15">
                                    </div>
                                </div>
                            </div>

                            
                            <div id="formula_feed_fields" class="mt-4" style="display:none;">
                                <div class="form-section-title"><i class="bi bi-milk"></i> رضاعة صناعية</div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="ff_time" class="form-label">وقت الرضعة (اختياري)</label>
                                        <input type="time" name="ff_time" id="ff_time" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="ff_quantity" class="form-label">الكمية (مل/أونصة) <span class="text-danger">*</span></label>
                                        <input type="number" name="ff_quantity" id="ff_quantity" class="form-control" min="1" step="0.5" placeholder="مثال: 90">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="ff_formula_type" class="form-label">نوع الحليب <span class="text-danger">*</span></label>
                                        <input type="text" name="ff_formula_type" id="ff_formula_type" class="form-control" placeholder="مثال: سيميلاك">
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div id="sleep_fields" class="mt-4" style="display:none;">
                                <div class="form-section-title"><i class="bi bi-moon-stars"></i> تتبع النوم</div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="sleep_start" class="form-label">بداية النوم</label>
                                        <input type="time" name="sleep_start" id="sleep_start" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="sleep_end" class="form-label">نهاية النوم</label>
                                        <input type="time" name="sleep_end" id="sleep_end" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="sleep_hours" class="form-label">إجمالي الساعات <span class="text-danger">*</span></label>
                                        <input type="number" name="sleep_hours" id="sleep_hours" class="form-control" min="0" max="24" step="0.1" placeholder="مثال: 8.5">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="wake_ups" class="form-label">عدد مرات الاستيقاظ</label>
                                        <input type="number" name="wake_ups" id="wake_ups" class="form-control" min="0" step="1" placeholder="مثال: 2">
                                    </div>
                                    <div class="col-12">
                                        <label for="sleep_note" class="form-label">ملاحظات على النوم</label>
                                        <textarea name="sleep_note" id="sleep_note" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            
                            <div id="growth_fields" class="mt-4" style="display:none;">
                                <div class="form-section-title"><i class="bi bi-graph-up"></i> قياسات النمو والحالة الصحية</div>
                                <div class="alert alert-warning">
                                    يرجى إدخال هذه القيم بأحدث القياسات، حتى لو لم يتم قياسها اليوم. سيتم حفظها كسجلات للنمو وتحديث البيانات الأساسية للطفل.
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="age" class="form-label">العمر <span class="text-danger">*</span></label>
                                        <input type="text" name="age" id="age" class="form-control" placeholder="مثال: 5 أشهر، 10 أيام" value="<?= htmlspecialchars($current_child_data['age'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="weight_form" class="form-label">الوزن (كغ) <span class="text-danger">*</span></label>
                                        <input type="number" name="weight" id="weight_form" class="form-control" min="0.1" step="0.01" placeholder="مثال: 7.5" value="<?= htmlspecialchars($current_child_data['weight'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="height_form" class="form-label">الطول (سم) <span class="text-danger">*</span></label>
                                        <input type="number" name="height" id="height_form" class="form-control" min="10" step="0.1" placeholder="مثال: 65" value="<?= htmlspecialchars($current_child_data['height'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="head_circ_form" class="form-label">محيط الرأس (سم)</label>
                                        <input type="number" name="head_circumference" id="head_circ_form" class="form-control" min="20" step="0.1" placeholder="مثال: 42">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="temp_form" class="form-label">الحرارة (°م)</label>
                                        <input type="number" name="temperature" id="temp_form" class="form-control" min="30" max="45" step="0.1" placeholder="مثال: 37.2">
                                    </div>
                                    <div class="col-12">
                                        <label for="illness_form" class="form-label">الأعراض المرضية (إن وجدت)</label>
                                        <input type="text" name="illness" id="illness_form" class="form-control" placeholder="مثال: سعال خفيف، زكام...">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="med_name_form" class="form-label">اسم الدواء</label>
                                        <input type="text" name="medicine_name" id="med_name_form" class="form-control" placeholder="مثال: مسكن حرارة">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="med_dose_form" class="form-label">الجرعة</label>
                                        <input type="text" name="medicine_dose" id="med_dose_form" class="form-control" placeholder="مثال: 5 مل">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="med_time_form" class="form-label">وقت الإعطاء</label>
                                        <input type="time" name="medicine_time" id="med_time_form" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label for="note_form" class="form-label">ملاحظات إضافية</label>
                                        <textarea name="note" id="note_form" class="form-control" rows="3" placeholder="أي ملاحظات أخرى حول الطفل في هذا اليوم..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-add-record"><i class="bi bi-save"></i> حفظ النشاط</button>
                        </div>
                    </form>
                </section>
                
               
            </div>
        </div>
    </div>
</div>
<script>
    function showActivityFields(type) {
        document.getElementById('breast_feed_fields').style.display = 'none';
        document.getElementById('formula_feed_fields').style.display = 'none';
        document.getElementById('sleep_fields').style.display = 'none';
        document.getElementById('growth_fields').style.display = 'none';

        if (type === 'breast_feed') {
            document.getElementById('breast_feed_fields').style.display = 'block';
        } else if (type === 'formula_feed') {
            document.getElementById('formula_feed_fields').style.display = 'block';
        } else if (type === 'nap' || type === 'night_sleep') {
            document.getElementById('sleep_fields').style.display = 'block';
        } else if (type === 'growth_record') {
            document.getElementById('growth_fields').style.display = 'block';
        }
    }
    
    // لإعادة تعبئة القيم بعد خطأ في الإرسال
    document.addEventListener('DOMContentLoaded', function() {
        const selectedType = '<?= $_POST['activity_type'] ?? '' ?>';
        if (selectedType) {
            document.getElementById('activity_type_select').value = selectedType;
            showActivityFields(selectedType);
        }
        
        // تحميل بيانات الطفل تلقائياً عند الاختيار من GET
        const childId = document.getElementById('child_id_form').value;
        if (childId) {
             // يمكن هنا إضافة طلب AJAX لجلب أحدث بيانات الطفل لملء حقول النمو
        }

    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>