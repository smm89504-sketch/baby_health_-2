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
$child_id = $_GET['id'] ?? null;
$user_type = $_SESSION['user_type'] ?? 'parent';

// Defensive defaults
$daily_activities = [];
$latest_activity = null;
$growth_records = [];
$sleep_records = [];
$professional_notes = [];
$vaccine_status = [];
$nutrition_guideline = null;

if (!$child_id) {
    header('Location: children.php');
    exit;
}

// === START Sidebar Setup (Color and Links) ===
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

// === START Base Path Setup ===
$base_path = '';
if ($user_type === 'doctor') {
    $base_path = 'doctor/';
} elseif ($user_type === 'admin') {
    $base_path = 'admin/';
} elseif ($user_type === 'nurse') {
    $base_path = 'nurse/';
} elseif ($user_type === 'parent') {
    $base_path = '/';
}
// === END Base Path Setup ===

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
    
    // جلب معلومات التطعيمات للأطفال (لأجل تنبيهات الشريط الجانبي)
    $due_vaccines = [];
    if ($user_type === 'parent') {
        $stmt_vaccines_sidebar = $pdo->prepare('SELECT cv.*, v.name as vaccine_name, c.name as child_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id JOIN children c ON cv.child_id = c.id WHERE c.user_id = ? AND cv.status = "due" ORDER BY cv.due_date ASC');
        $stmt_vaccines_sidebar->execute([$_SESSION['user_id']]);
        $due_vaccines = $stmt_vaccines_sidebar->fetchAll();
    }
    $vaccine_alerts_sidebar = $user_type === 'parent' ? get_parent_alerts($due_vaccines) : ['upcoming' => [], 'missed' => []];


    // جلب بيانات الطفل
    $stmt = $pdo->prepare('SELECT * FROM children WHERE id = ? ' . ($user_type === 'parent' ? 'AND user_id = ?' : ''));
    if ($user_type === 'parent') {
        $stmt->execute([$child_id, $_SESSION['user_id']]);
    } else {
        $stmt->execute([$child_id]);
    }
    $child = $stmt->fetch();

    if (!$child) {
        header('Location: children.php');
        exit;
    }

    // حساب عمر الطفل بالأشهر للحصول على نظام التغذية المناسب
    $child_age_months = null;
    if (!empty($child['birth_date'])) {
        $birth_date_obj = new DateTime($child['birth_date']);
        $now = new DateTime();
        $interval = $birth_date_obj->diff($now);
        $child_age_months = $interval->y * 12 + $interval->m;
    }

    $nutrition_guideline = null;
    if ($child_age_months !== null) {
        $stmt_nutrition = $pdo->prepare('SELECT * FROM nutrition_guidelines WHERE age_min_months <= ? AND age_max_months >= ? ORDER BY age_min_months DESC LIMIT 1');
        $stmt_nutrition->execute([$child_age_months, $child_age_months]);
        $nutrition_guideline = $stmt_nutrition->fetch();

        $existing_guideline_id = $child['nutrition_guideline_id'] ?? null;
        if ($nutrition_guideline && $existing_guideline_id != $nutrition_guideline['id']) {
            // تحديث حالة التغذية للطفل
            $stmt_update = $pdo->prepare('UPDATE children SET nutrition_guideline_id = ? WHERE id = ?');
            $stmt_update->execute([$nutrition_guideline['id'], $child_id]);

            // إرسال إشعار للوالد عند الانتقال لنظام تغذية جديد
            if ($user_type === 'parent') {
                $notif_title = 'تحديث خطة التغذية للطفل ' . $child['name'];
                $notif_message = 'الطفل الآن بعمر ' . $child_age_months . ' شهراً. تم تحديث نظام التغذية إلى الفئة الجديدة.';
                $notif_stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)');
                $notif_stmt->execute([$_SESSION['user_id'], $notif_title, $notif_message, 'info']);
            }
        }
        // جلب قائمة الأدوية المسموحة والممنوعة حسب الفئة العمرية
        $stmt_age_meds = $pdo->prepare('SELECT * FROM age_group_medication_lists WHERE age_min_months <= ? AND age_max_months >= ? ORDER BY age_min_months DESC LIMIT 1');
        $stmt_age_meds->execute([$child_age_months, $child_age_months]);
        $age_group_meds = $stmt_age_meds->fetch();
    }
        // جلب تذكيرات الأدوية لهذا الطفل
        $stmt_med_reminders = $pdo->prepare('SELECT * FROM child_medication_reminders WHERE child_id = ? ORDER BY reminder_time ASC');
        $stmt_med_reminders->execute([$child_id]);
        $medication_reminders = $stmt_med_reminders->fetchAll();

    // جلب سجلات الأنشطة اليومية
    $stmt_activities = $pdo->prepare('SELECT * FROM daily_activities WHERE child_id = ? ORDER BY date DESC, created_at DESC');
    $stmt_activities->execute([$child_id]);
    $daily_activities = $stmt_activities->fetchAll();

    // جلب آخر سجل حرارة ورضاعة لتنبيه الأهل 
    $stmt_latest_activity = $pdo->prepare("SELECT * FROM daily_activities WHERE child_id = ? AND (activity_type = 'growth_record' OR activity_type = 'breast_feed' OR activity_type = 'formula_feed') ORDER BY created_at DESC LIMIT 1");
    $stmt_latest_activity->execute([$child_id]);
    $latest_activity = $stmt_latest_activity->fetch();

    // جلب سجلات النمو (للمخطط الأساسي)
    $stmt_growth = $pdo->prepare("SELECT date, weight, height, temperature FROM daily_activities WHERE child_id = ? AND activity_type = 'growth_record' ORDER BY date ASC");
    $stmt_growth->execute([$child_id]);
    $growth_records = $stmt_growth->fetchAll();
    
    // جلب سجلات النوم (لتحليل النوم)
    $stmt_sleep = $pdo->prepare("SELECT date, duration, quantity as wake_ups, activity_type FROM daily_activities WHERE child_id = ? AND (activity_type = 'nap' OR activity_type = 'night_sleep') ORDER BY date ASC");
    $stmt_sleep->execute([$child_id]);
    $sleep_records = $stmt_sleep->fetchAll();

    // جلب الوصفات الطبية
    $stmt_prescriptions = $pdo->prepare("
        SELECT 
            p.id,
            p.doctor_id,
            u.full_name as doctor_name,
            p.prescription_date,
            p.expiry_date,
            DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
            DATEDIFF(CURDATE(), p.prescription_date) as days_since_creation,
            p.status,
            p.notes
        FROM prescriptions p
        JOIN users u ON p.doctor_id = u.id
        WHERE p.child_id = ?
        ORDER BY 
            CASE 
                WHEN p.status = 'active' THEN 0
                ELSE 1
            END,
            p.expiry_date DESC
    ");
    $stmt_prescriptions->execute([$child_id]);
    $prescriptions = $stmt_prescriptions->fetchAll();

    // جلب أدوية الوصفات
    $prescription_medications = [];
    if (!empty($prescriptions)) {
        $prescription_ids = array_column($prescriptions, 'id');
        $placeholders = str_repeat('?,', count($prescription_ids) - 1) . '?';
        $stmt_meds = $pdo->prepare("
            SELECT 
                pm.prescription_id,
                m.name as medication_name,
                pm.dosage,
                pm.frequency,
                pm.duration_days,
                pm.notes as med_notes
            FROM prescription_medications pm
            JOIN medications m ON pm.medication_id = m.id
            WHERE pm.prescription_id IN ($placeholders)
            ORDER BY pm.prescription_id, m.name
        ");
        $stmt_meds->execute($prescription_ids);
        $meds_result = $stmt_meds->fetchAll();
        
        // تجميع الأدوية حسب الوصفة
        foreach ($meds_result as $med) {
            $prescription_medications[$med['prescription_id']][] = $med;
        }
    }

    // جلب ملاحظات المهنيين
    $sql_notes = 'SELECT pn.*, u.full_name FROM professional_notes pn JOIN users u ON pn.user_id = u.id WHERE pn.child_id = ? ORDER BY pn.created_at DESC';
    $stmt_notes = $pdo->prepare($sql_notes);
    $stmt_notes->execute([$child_id]);
    $professional_notes = $stmt_notes->fetchAll();

    // جلب حالة التطعيمات
    $stmt_vaccines_status = $pdo->prepare("SELECT cv.*, v.name as vaccine_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id WHERE cv.child_id = ? ORDER BY cv.due_date ASC");
    $stmt_vaccines_status->execute([$child_id]);
    $vaccine_status = $stmt_vaccines_status->fetchAll();

} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
    // احفظ المتغيرات الافتراضية في حالة الفشل
    $daily_activities = $daily_activities ?? [];
    $latest_activity = $latest_activity ?? null;
    $growth_records = $growth_records ?? [];
    $sleep_records = $sleep_records ?? [];
    $professional_notes = $professional_notes ?? [];
    $vaccine_status = $vaccine_status ?? [];
    $nutrition_guideline = $nutrition_guideline ?? null;
}

// دالة لمعالجة إشعارات التطعيم (لجسم الصفحة)
function get_vaccine_alerts_page($status_records) {
    $alerts = ['upcoming' => [], 'missed' => []];
    $today = new DateTime();
    foreach ($status_records as $rec) {
        if ($rec['status'] === 'due') {
            $due_date = new DateTime($rec['due_date']);
            $interval = $today->diff($due_date);
            if ($due_date < $today) {
                $alerts['missed'][] = $rec;
            } 
            elseif ($interval->days <= 14) {
                $alerts['upcoming'][] = $rec;
            }
        }
    }
    return $alerts;
}

$vaccine_alerts = get_vaccine_alerts_page($vaccine_status);

// منطق تنبيهات الحرارة والرضاعة (للأهل)
$health_alerts = [];
if ($user_type === 'parent' && $latest_activity) {
    // 1. تنبيه الحرارة
    if ($latest_activity['activity_type'] === 'growth_record' && $latest_activity['temperature'] !== null && $latest_activity['temperature'] >= 38) {
        $health_alerts['temp'] = "**تنبيه حرارة مرتفعة:** سجلت حرارة طفلك آخر مرة **" . htmlspecialchars($latest_activity['temperature']) . "°م** في **" . htmlspecialchars($latest_activity['date']) . "**. يرجى مراقبة الحالة ومراجعة مقدم الرعاية.";
    }

    // 2. تنبيه الرضاعة (إذا مر أكثر من 4 ساعات على آخر رضعة)
    if (($latest_activity['activity_type'] === 'breast_feed' || $latest_activity['activity_type'] === 'formula_feed') && $latest_activity['start_time']) {
        $last_feed_time = new DateTime($latest_activity['date'] . ' ' . $latest_activity['start_time']);
        $now = new DateTime();
        $interval = $now->diff($last_feed_time);
        
        $hours_passed = $interval->days * 24 + $interval->h + $interval->i / 60;
        
        if ($hours_passed >= 4) {
            $health_alerts['feed'] = "**تنبيه رضاعة:** مر **" . floor($hours_passed) . " ساعة** على آخر رضعة تم تسجيلها في **" . date('h:i A', strtotime($latest_activity['start_time'])) . "**. يرجى تذكير الطفل بالرضاعة المنتظمة.";
        }
    }
}

// فصل ملاحظات النوم والملاحظات العامة
$sleep_advice = array_filter($professional_notes, function($note) {
    return $note['note_type'] === 'sleep_advice';
});
$general_notes = array_filter($professional_notes, function($note) {
    return $note['note_type'] === 'general';
});

// متغير مسار الصورة الافتراضية الجديدة
$default_child_image = 'images/images.jpeg'; 

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الطفل - <?= isset($child) ? htmlspecialchars($child['name']) : 'غير متوفر' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .main-box { margin-top: 0; margin-bottom: 25px; box-shadow: 0 8px 25px rgba(100, 100, 100, 0.12); border-radius: 15px; background: #ffffff; padding: 35px; }
        .page-header { font-size: 2.2rem; color: #C7346F; font-weight: 700; text-align: center; margin-bottom: 30px; }
        .page-header i { margin-left: 15px; font-size: 2rem; vertical-align: -3px; }
        .child-profile-header { padding-bottom: 25px; margin-bottom: 30px; border-bottom: 1px solid #F0E0E6; }
        
        /* تحديث: استخدام الصورة الافتراضية مباشرة أو الصورة المرفوعة */
        .child-image-display { width: 180px; height: 180px; border-radius: 50%; object-fit: cover; border: 6px solid #FFF0F5; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .default-child-placeholder { width: 180px; height: 180px; border-radius: 50%; background-color: #FDEFF3; display: flex; align-items: center; justify-content: center; border: 6px solid #FFF0F5; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .default-child-placeholder i { font-size: 6rem; color: #E7AAB4; }
        /* نهاية تحديث الصورة */
        
        .child-name-title { color: #B34073; font-weight: 700; font-size: 2rem; }
        .child-info-item { font-size: 1.05rem; color: #555; margin-bottom: 0.7rem; }
        .child-info-item i { color: #E7AAB4; font-size: 1.2rem; }
        .chart-section-title { color: #C7346F; font-weight: 600; font-size: 1.7rem; margin-bottom: 20px; text-align: center; }
        .chart-container { height: 480px; position: relative; background-color: #FFFAFB; padding: 20px; border-radius: 10px; box-shadow: inset 0 0 10px rgba(0,0,0,0.03); }
        .alert-no-data { background-color: #FFF9E6; border-color: #FFE082; color: #795548; }
        .activity-card { border-left: 3px solid #E7AAB4; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); }
        .note-card { border-left: 4px solid #28a745; }
        .alert-missed { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-upcoming { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }
        .flex-grow-1 { padding: 0; }
    </style>
</head>
<body>
    <?php if ($_SESSION['user_type'] === 'doctor') {
        include 'doctor/sidebar.php';
    } else {
        include 'includes/sidebar.php';
    } ?>
<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <?php if (isset($child)): ?>
            <div class="col-lg-10 col-md-12 main-box p-lg-5 p-4 mt-4"> 
                <div class="page-header">
                    <i class="bi bi-clipboard-heart"></i> 
                    تفاصيل الطفل: <?= htmlspecialchars($child['name']) ?>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-4">
                        <?php foreach ($errors as $error) echo "<div>• $error</div>"; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_type === 'parent'): ?>
                
                <?php if (!empty($health_alerts)): ?>
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-clipboard-pulse me-2"></i> **تنبيهات صحية فورية:**
                        <ul class="mb-0 mt-2">
                            <?php foreach ($health_alerts as $alert): ?>
                                <li><?= $alert ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php foreach ($vaccine_alerts['missed'] as $alert): ?>
                    <div class="alert alert-missed mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> 
                        **تنبيه:** لقد فات موعد تطعيم **<?= htmlspecialchars($alert['vaccine_name']) ?>** وكان مستحقاً في **<?= htmlspecialchars($alert['due_date']) ?>**. يرجى مراجعة الممرض.
                    </div>
                <?php endforeach; ?>
                <?php foreach ($vaccine_alerts['upcoming'] as $alert): ?>
                    <div class="alert alert-upcoming mb-3"><i class="bi bi-bell-fill me-2"></i> 
                        **إشعار:** تطعيم **<?= htmlspecialchars($alert['vaccine_name']) ?>** قادم في **<?= htmlspecialchars($alert['due_date']) ?>**.
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="row align-items-center child-profile-header">
                    <div class="col-md-4 text-center mb-4 mb-md-0">
                        <img src="<?= htmlspecialchars($default_child_image) ?>" alt="صورة الطفل" class="child-image-display">
                    </div>
                    <div class="col-md-8">
                        <h2 class="child-name-title mb-3"><?= htmlspecialchars($child['name']) ?></h2>
                        <div class="row">
                            <div class="col-lg-6">
                                <p class="child-info-item"><i class="bi bi-calendar-event me-2"></i><strong>تاريخ الميلاد:</strong> <?= htmlspecialchars($child['birth_date']) ?></p>
                                <p class="child-info-item"><i class="bi bi-person-arms-up me-2"></i><strong>العمر:</strong> <?= htmlspecialchars($child['age']) ?></p>
                            </div>
                            <div class="col-lg-6">
                                <p class="child-info-item"><i class="bi bi-speedometer2 me-2"></i><strong>الوزن الحالي:</strong> <?= htmlspecialchars($child['weight']) ?> كغ</p>
                                <p class="child-info-item"><i class="bi bi-rulers me-2"></i><strong>الطول الحالي:</strong> <?= htmlspecialchars($child['height']) ?> سم</p>
                            </div>
                        </div>
                        <?php if (!empty($age_group_meds)): ?>
                        <div class="alert alert-info mt-4">
                            <strong><i class="bi bi-capsule"></i> قائمة الأدوية حسب الفئة العمرية (<?= $age_group_meds['age_min_months'] ?> - <?= $age_group_meds['age_max_months'] ?> شهر):</strong><br>
                            <span class="text-success"><b>مسموح:</b> <?= nl2br(htmlspecialchars($age_group_meds['allowed_medications'])) ?></span><br>
                            <span class="text-danger"><b>ممنوع:</b> <?= nl2br(htmlspecialchars($age_group_meds['restricted_medications'])) ?></span>
                            <?php if (!empty($age_group_meds['notes'])): ?><br><span class="text-muted"><b>ملاحظات:</b> <?= nl2br(htmlspecialchars($age_group_meds['notes'])) ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($user_type === 'parent'): ?>
                        <div class="mt-3">
                            <a href="edit_child.php?id=<?= $child['id'] ?>" class="btn btn-outline-info btn-sm me-2">
                                <i class="bi bi-pencil-square"></i> تعديل البيانات الأساسية
                            </a>
                            <a href="add_daily_activity.php?child_id=<?= $child['id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> إضافة نشاط يومي
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <ul class="nav nav-tabs" id="childTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="growth-tab" data-bs-toggle="tab" data-bs-target="#growth" type="button" role="tab"><i class="bi bi-graph-up-arrow"></i> النمو والنوم</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button" role="tab"><i class="bi bi-list-check"></i> الأنشطة والسجلات</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab"><i class="bi bi-file-text"></i> ملاحظات المهنيين</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="nutrition-tab" data-bs-toggle="tab" data-bs-target="#nutrition" type="button" role="tab"><i class="bi bi-basket3"></i> التغذية</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="vaccines-tab" data-bs-toggle="tab" data-bs-target="#vaccines" type="button" role="tab"><i class="bi bi-shield-plus"></i> التطعيمات</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="medications-tab" data-bs-toggle="tab" data-bs-target="#medications" type="button" role="tab"><i class="bi bi-capsule"></i> تذكيرات الأدوية</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button" role="tab"><i class="bi bi-prescription"></i> الوصفات الطبية</button></li>
                </ul>
                
                <div class="tab-content pt-3" id="childTabsContent">
                    <div class="tab-pane fade show active" id="growth" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-bar-chart-line"></i> تطور النمو (وزن/طول/حرارة)</h4>
                        <?php if (!empty($growth_records)): ?>
                            <div class="chart-container mb-5" style="height: 400px;">
                                <canvas id="growthChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-no-data text-center mt-3 mb-5"><i class="bi bi-info-circle-fill me-2"></i> لا توجد بيانات نمو مسجلة لعرضها في المخطط.</div>
                        <?php endif; ?>

                        <h4 class="chart-section-title mt-5"><i class="bi bi-bookmark-star"></i> نصائح الممرض لتحسين النوم</h4>
                        <?php if (!empty($sleep_advice)): ?>
                            <div class="list-group">
                                <?php foreach ($sleep_advice as $note): ?>
                                    <div class="alert alert-info" role="alert">
                                        <h6 class="alert-heading"><i class="bi bi-lightbulb-fill me-2"></i> نصيحة مقدمة من الممرض/ة: <?= htmlspecialchars($note['full_name']) ?></h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($note['note_content'])) ?></p>
                                        <small class="text-muted float-start"><?= htmlspecialchars(date('Y-m-d', strtotime($note['created_at']))) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-no-data text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا توجد نصائح نوم مسجلة لهذا الطفل بعد.</div>
                        <?php endif; ?>


                        <h4 class="chart-section-title mt-5"><i class="bi bi-moon"></i> تحليل ساعات النوم</h4>
                        <?php if (!empty($sleep_records)): ?>
                            <div class="chart-container" style="height: 400px;">
                                <canvas id="sleepChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-no-data text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا توجد بيانات نوم مسجلة لعرضها في المخطط.</div>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="medications" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-capsule"></i> تذكيرات الأدوية</h4>
                        <?php if (in_array($user_type, ['doctor', 'nurse'])): ?>
                        <div class="mb-3 text-end">
                            <a href="add_child_medication_reminder.php?child_id=<?= $child['id'] ?>" class="btn btn-outline-success">
                                <i class="bi bi-plus-circle"></i> إضافة تذكير دواء جديد
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($medication_reminders)): ?>
                            <table class="table table-bordered table-striped">
                                <thead class="table-info">
                                    <tr>
                                        <th>اسم الدواء</th>
                                        <th>الجرعة</th>
                                        <th>وقت التذكير</th>
                                        <th>ملاحظات</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($medication_reminders as $rem): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($rem['medication_name']) ?></td>
                                        <td><?= htmlspecialchars($rem['dosage']) ?></td>
                                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($rem['reminder_time']))) ?></td>
                                        <td><?= htmlspecialchars($rem['notes'] ?? '---') ?></td>
                                        <td>
                                            <?php if ($rem['status'] === 'pending'): ?><span class="badge bg-warning text-dark">قيد الانتظار</span>
                                            <?php elseif ($rem['status'] === 'sent'): ?><span class="badge bg-info text-dark">تم الإرسال</span>
                                            <?php elseif ($rem['status'] === 'completed'): ?><span class="badge bg-success">تم التنفيذ</span>
                                            <?php elseif ($rem['status'] === 'skipped'): ?><span class="badge bg-secondary">تم التجاوز</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا توجد تذكيرات أدوية مسجلة لهذا الطفل.</div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="activities" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-journal-check"></i> آخر الأنشطة اليومية</h4>
                        <?php if (!empty($daily_activities)): ?>
                            <div class="row g-3">
                                <?php foreach ($daily_activities as $activity): ?>
                                    <div class="col-md-6">
                                        <div class="card activity-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary"><i class="bi bi-calendar-event me-2"></i> <?= htmlspecialchars($activity['date']) ?></h5>
                                                <hr>
                                                <?php 
                                                    $icon = 'bi-question-circle'; $type_text = 'نشاط غير معروف';
                                                    switch ($activity['activity_type']) {
                                                        case 'breast_feed': $icon = 'bi-cup-straw'; $type_text = 'رضاعة طبيعية'; break;
                                                        case 'formula_feed': $icon = 'bi-milk'; $type_text = 'رضاعة صناعية'; break;
                                                        case 'nap': $icon = 'bi-cloud-sun'; $type_text = 'نوم (قيلولة)'; break;
                                                        case 'night_sleep': $icon = 'bi-moon-stars'; $type_text = 'نوم (ليلي)'; break;
                                                        case 'growth_record': $icon = 'bi-bar-chart'; $type_text = 'سجل نمو وحالة صحية'; break;
                                                    }
                                                ?>
                                                <p class="card-text"><strong><i class="bi <?= $icon ?> me-2"></i> النوع:</strong> <?= $type_text ?></p>
                                                
                                                <?php if ($activity['activity_type'] === 'breast_feed'): ?>
                                                    <p class="card-text"><i class="bi bi-clock me-2"></i> **المدة:** <?= htmlspecialchars($activity['duration']) ?> دقيقة</p>
                                                    <?php if ($activity['start_time']): ?><p class="card-text"><i class="bi bi-arrow-up-right-circle me-2"></i> **وقت البدء:** <?= htmlspecialchars(date("h:i A", strtotime($activity['start_time']))) ?></p><?php endif; ?>
                                                <?php elseif ($activity['activity_type'] === 'formula_feed'): ?>
                                                    <p class="card-text"><i class="bi bi-droplet me-2"></i> **الكمية:** <?= htmlspecialchars($activity['quantity']) ?> مل/أونصة</p>
                                                    <p class="card-text"><i class="bi bi-bookmark me-2"></i> **النوع:** <?= htmlspecialchars($activity['details']) ?></p>
                                                    <?php if ($activity['start_time']): ?><p class="card-text"><i class="bi bi-clock me-2"></i> **الوقت:** <?= htmlspecialchars(date("h:i A", strtotime($activity['start_time']))) ?></p><?php endif; ?>
                                                <?php elseif ($activity['activity_type'] === 'nap' || $activity['activity_type'] === 'night_sleep'): ?>
                                                    <p class="card-text"><i class="bi bi-clock me-2"></i> **إجمالي الساعات:** <?= htmlspecialchars($activity['duration']) ?></p>
                                                    <?php if ($activity['quantity']): ?><p class="card-text"><i class="bi bi-exclamation-octagon me-2"></i> **استيقاظ:** <?= htmlspecialchars($activity['quantity']) ?> مرات</p><?php endif; ?>
                                                    <?php if ($activity['note']): ?><p class="card-text"><i class="bi bi-chat-text me-2"></i> **ملاحظة:** <?= htmlspecialchars($activity['note']) ?></p><?php endif; ?>
                                                <?php elseif ($activity['activity_type'] === 'growth_record'): ?>
                                                     <?php if ($activity['weight']): ?><p class="card-text"><i class="bi bi-box me-2"></i> **الوزن:** <?= htmlspecialchars($activity['weight']) ?> كغ</p><?php endif; ?>
                                                     <?php if ($activity['height']): ?><p class="card-text"><i class="bi bi-rulers me-2"></i> **الطول:** <?= htmlspecialchars($activity['height']) ?> سم</p><?php endif; ?>
                                                     <?php if ($activity['temperature']): ?><p class="card-text"><i class="bi bi-thermometer-half me-2"></i> **الحرارة:** <?= htmlspecialchars($activity['temperature']) ?>°م</p><?php endif; ?>
                                                     <?php if ($activity['illness']): ?><p class="card-text"><i class="bi bi-bandaid me-2"></i> **مرض:** <?= htmlspecialchars($activity['illness']) ?></p><?php endif; ?>
                                                     <?php if ($activity['note']): ?><p class="card-text"><i class="bi bi-chat-text me-2"></i> **ملاحظة:** <?= htmlspecialchars($activity['note']) ?></p><?php endif; ?>
                                                <?php endif; ?>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا توجد سجلات أنشطة يومية لهذا الطفل بعد.</div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="notes" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-file-text-fill"></i> ملاحظات المهنيين (عامة)</h4>
                        <?php if (!empty($general_notes)): ?>
                            <div class="list-group">
                                <?php foreach ($general_notes as $note): ?>
                                    <?php 
                                        $user_title = ($note['user_type'] === 'doctor') ? 'الطبيب' : 'الممرض';
                                        $header_icon = ($note['user_type'] === 'doctor') ? 'bi-file-medical-fill' : 'bi-nurse-fill';
                                        $badge_class = ($note['user_type'] === 'doctor') ? 'bg-danger' : 'bg-success';
                                    ?>
                                    <div class="card mb-3 note-card nurse">
                                        <div class="note-header bg-light">
                                            <i class="bi <?= $header_icon ?> me-2"></i> **المهني:** <?= htmlspecialchars($note['full_name']) ?>
                                            <span class="badge bg-secondary float-end"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($note['created_at']))) ?></span>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text"><?= nl2br(htmlspecialchars($note['note_content'])) ?></p>
                                            <span class="badge <?= $badge_class ?>"><i class="bi bi-check-circle-fill"></i> ملاحظة <?= $user_title ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا توجد ملاحظات مهنية مسجلة لهذا الطفل بعد.</div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="nutrition" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-basket3-fill"></i> خطة التغذية حسب العمر</h4>
                        <?php if ($nutrition_guideline): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">المرحلة: <?= htmlspecialchars($nutrition_guideline['age_min_months']) ?>-<?= htmlspecialchars($nutrition_guideline['age_max_months']) ?> شهور</h5>
                                    <p><strong>الأطعمة المسموحة:</strong> <?= nl2br(htmlspecialchars($nutrition_guideline['allowed_foods'])) ?></p>
                                    <p><strong>الأطعمة الممنوعة:</strong> <?= nl2br(htmlspecialchars($nutrition_guideline['restricted_foods'])) ?></p>
                                    <p><strong>نصائح:</strong> <?= nl2br(htmlspecialchars($nutrition_guideline['nutrition_tips'])) ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">لا توجد توجيهات تغذية محددة لهذا العمر حتى الآن. يمكنك مراجعة الطبيب لتحديث خطة التغذية.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane fade" id="vaccines" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-shield-fill"></i> جدول التطعيمات</h4>
                        <?php if (!empty($vaccine_status)): ?>
                            <table class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>التطعيم</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإعطاء</th>
                                        <th>شهادة التطعيم</th>
                                        <th>ملاحظات الممرض</th>
                                        <?php if ($user_type === 'nurse'): ?><th>إجراء</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vaccine_status as $vac): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($vac['vaccine_name']) ?></td>
                                            <td><?= htmlspecialchars($vac['due_date']) ?></td>
                                            <td>
                                                <?php if ($vac['status'] === 'administered'): ?><span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> تم الإعطاء</span>
                                                <?php elseif ($vac['status'] === 'missed'): ?><span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> فائت</span>
                                                <?php else: ?><span class="badge bg-warning text-dark"><i class="bi bi-clock-fill"></i> مستحق</span><?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($vac['administered_date'] ?? '---') ?></td>
                                            <td>
                                                <?php if (!empty($vac['certificate_filename'])): ?>
                                                    <a href="uploads/vaccine_certs/<?= htmlspecialchars($vac['certificate_filename']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-file-earmark-image"></i> عرض
                                                    </a>
                                                <?php else: ?>
                                                    ---
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($vac['nurse_note'] ?? 'لا يوجد') ?></td>
                                            <?php if ($user_type === 'nurse'): ?>
                                                <td>
                                                    <a href="child_vaccination.php?child_id=<?= $child['id'] ?>&edit_id=<?= $vac['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> تعديل</a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا يوجد سجل تطعيمات لهذا الطفل.</div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="prescriptions" role="tabpanel">
                        <h4 class="chart-section-title"><i class="bi bi-prescription"></i> الوصفات الطبية</h4>
                        <?php if (in_array($user_type, ['doctor', 'nurse'])): ?>
                        <div class="mb-3 text-end">
                            <a href="add_prescription.php?child_id=<?= $child['id'] ?>" class="btn btn-outline-success">
                                <i class="bi bi-plus-circle"></i> وصفة جديدة
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($prescriptions)): ?>
                            <div class="row g-3">
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <?php
                                    $class = '';
                                    $badge_class = '';
                                    $status_text = '';

                                    if ($prescription['status'] === 'active') {
                                        if ($prescription['days_until_expiry'] < 0) {
                                            $class = 'border-danger';
                                            $badge_class = 'bg-danger';
                                            $status_text = '❌ منتهية الصلاحية';
                                        } elseif ($prescription['days_until_expiry'] <= 1) {
                                            $class = 'border-danger';
                                            $badge_class = 'bg-danger';
                                            $status_text = '🔴 عاجل - ستنتهي اليوم!';
                                        } elseif ($prescription['days_until_expiry'] <= 7) {
                                            $class = 'border-warning';
                                            $badge_class = 'bg-warning text-dark';
                                            $status_text = '⚠️ قريبة الانتهاء';
                                        } else {
                                            $badge_class = 'bg-success';
                                            $status_text = '✅ نشطة';
                                        }
                                    } else {
                                        $badge_class = 'bg-secondary';
                                        $status_text = 'ملغاة';
                                    }
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card activity-card h-100 <?= $class ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h5 class="card-title text-primary"><i class="bi bi-prescription me-2"></i> وصفة من د. <?= htmlspecialchars($prescription['doctor_name']) ?></h5>
                                                    <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                                </div>

                                                <div class="row mb-3">
                                                    <div class="col-6">
                                                        <p class="card-text mb-1"><strong><i class="bi bi-calendar-event me-1"></i> تاريخ الكتابة:</strong></p>
                                                        <p class="text-muted small"><?= htmlspecialchars(date('d/m/Y', strtotime($prescription['prescription_date']))) ?></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="card-text mb-1"><strong><i class="bi bi-calendar-x me-1"></i> تاريخ الانتهاء:</strong></p>
                                                        <p class="text-muted small"><?= htmlspecialchars(date('d/m/Y', strtotime($prescription['expiry_date']))) ?></p>
                                                    </div>
                                                </div>

                                                <?php if (!empty($prescription_medications[$prescription['id']])): ?>
                                                <div class="mb-3">
                                                    <h6 class="text-primary mb-2"><i class="bi bi-capsule me-1"></i> الأدوية الموصوفة:</h6>
                                                    <ul class="list-group list-group-flush">
                                                        <?php foreach ($prescription_medications[$prescription['id']] as $med): ?>
                                                        <li class="list-group-item px-0 py-2">
                                                            <strong><?= htmlspecialchars($med['medication_name']) ?></strong>
                                                            <br><small class="text-muted">
                                                                الجرعة: <?= htmlspecialchars($med['dosage']) ?> |
                                                                المرات: <?= htmlspecialchars($med['frequency']) ?>
                                                                <?php if ($med['duration_days']): ?>| المدة: <?= htmlspecialchars($med['duration_days']) ?> يوم<?php endif; ?>
                                                            </small>
                                                            <?php if (!empty($med['med_notes'])): ?>
                                                            <br><small class="text-info">ملاحظة: <?= htmlspecialchars($med['med_notes']) ?></small>
                                                            <?php endif; ?>
                                                        </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <?php endif; ?>

                                                <?php if (!empty($prescription['notes'])): ?>
                                                <div class="alert alert-light border">
                                                    <strong><i class="bi bi-chat-text me-1"></i> ملاحظات الطبيب:</strong><br>
                                                    <?= nl2br(htmlspecialchars($prescription['notes'])) ?>
                                                </div>
                                                <?php endif; ?>

                                                <div class="text-end">
                                                    <a href="prescription_details.php?id=<?= $prescription['id'] ?>&child_id=<?= $child['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> عرض التفاصيل
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center mt-3"><i class="bi bi-info-circle-fill me-2"></i> لا توجد وصفات طبية مسجلة لهذا الطفل.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-center mt-4 pt-3 border-top">
                    <a href="<?php 
                        if ($user_type === 'doctor') {
                            echo 'doctor/patients.php';
                        } elseif ($user_type === 'admin') {
                            echo 'admin/children.php';
                        } elseif ($user_type === 'nurse') {
                            echo 'nurse_dashboard.php';
                        } else {
                            echo 'children.php';
                        }
                    ?>" class="btn btn-secondary btn-sm"> 
                        <i class="bi bi-arrow-right-circle me-1"></i> العودة إلى قائمة الأطفال
                    </a>
                </div>

            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isset($child) && (!empty($growth_records) || !empty($sleep_records))): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---------------- Growth Chart ----------------
    const growthRecords = <?= json_encode($growth_records) ?>;
    if (growthRecords.length > 0) {
        const labels = growthRecords.map(r => r.date);
        const weights = growthRecords.map(r => r.weight !== null && r.weight !== undefined ? parseFloat(r.weight) : null);
        const heights = growthRecords.map(r => r.height !== null && r.height !== undefined ? parseFloat(r.height) : null);
        const temps = growthRecords.map(r => r.temperature !== null && r.temperature !== undefined ? parseFloat(r.temperature) : null);

        const ctx = document.getElementById('growthChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'الوزن (كغ)', data: weights, tension: 0.3, borderColor: '#D13878', backgroundColor: 'rgba(209, 56, 120, 0.1)', borderWidth: 2.5, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#D13878', yAxisID: 'yWeight', hidden: weights.every(val => val === null) },
                    { label: 'الطول (سم)', data: heights, tension: 0.3, borderColor: '#4CAF50', backgroundColor: 'rgba(76, 175, 80, 0.1)', borderWidth: 2.5, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#4CAF50', yAxisID: 'yHeight', hidden: heights.every(val => val === null) },
                    { label: 'الحرارة (°C)', data: temps, tension: 0.3, borderColor: '#FFC107', backgroundColor: 'rgba(255, 193, 7, 0.1)', borderWidth: 2.5, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#FFC107', yAxisID: 'yTemp', hidden: temps.every(val => val === null) }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', labels: { font: { family: 'Cairo', size: 13, weight: '600' }, padding: 15 } } },
                scales: {
                    x: { title: { display: true, text: 'تاريخ التسجيل', font: { family: 'Cairo', size: 14, weight: '600' }, color: '#555' }, ticks: { font: { family: 'Cairo', size: 11 }, color: '#666' } },
                    yWeight: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'الوزن (كغ)', font: { family: 'Cairo', size: 14, weight: '600' }, color: '#D13878' }, ticks: { font: { family: 'Cairo', size: 11 }, color: '#666' }, grid: { color: 'rgba(200,200,200,0.2)'} },
                    yHeight: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'الطول (سم)', font: { family: 'Cairo', size: 14, weight: '600' }, color: '#4CAF50' }, ticks: { font: { family: 'Cairo', size: 11 }, color: '#666' }, grid: { drawOnChartArea: false } },
                    yTemp: { type: 'linear', display: temps.some(val => val !== null), position: 'right', title: { display: true, text: 'الحرارة (°م)', font: { family: 'Cairo', size: 14, weight: '600' }, color: '#FFC107' }, ticks: { font: { family: 'Cairo', size: 11 }, color: '#666', stepSize: 0.5 }, suggestedMin: 35, suggestedMax: 42, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // ---------------- Sleep Chart ----------------
    const sleepRecords = <?= json_encode($sleep_records) ?>;
    if (sleepRecords.length > 0) {
        // تجميع ساعات النوم حسب اليوم
        const dailySleep = {};
        sleepRecords.forEach(r => {
            const date = r.date;
            const duration = parseFloat(r.duration) || 0;
            const wakeUps = parseInt(r.wake_ups) || 0;
            if (!dailySleep[date]) {
                dailySleep[date] = { totalDuration: 0, totalWakeUps: 0, count: 0 };
            }
            dailySleep[date].totalDuration += duration;
            dailySleep[date].totalWakeUps += wakeUps;
            dailySleep[date].count++;
        });

        const sleepLabels = Object.keys(dailySleep).sort();
        const totalSleepHours = sleepLabels.map(date => dailySleep[date].totalDuration);
        const averageWakeUps = sleepLabels.map(date => dailySleep[date].totalWakeUps / dailySleep[date].count);

        const sleepCtx = document.getElementById('sleepChart').getContext('2d');
        new Chart(sleepCtx, {
            type: 'bar',
            data: {
                labels: sleepLabels,
                datasets: [
                    {
                        label: 'إجمالي ساعات النوم', data: totalSleepHours,
                        backgroundColor: 'rgba(179, 64, 115, 0.7)',
                        borderColor: '#B34073', borderWidth: 1, yAxisID: 'yHours'
                    },
                    {
                        label: 'متوسط الاستيقاظ (مرات)', data: averageWakeUps, type: 'line',
                        borderColor: '#007bff', backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: false, tension: 0.4, borderWidth: 2, yAxisID: 'yWakeUps'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { family: 'Cairo', size: 13, weight: '600' }, padding: 15 } } },
                scales: {
                    x: { title: { display: true, text: 'التاريخ', font: { family: 'Cairo', size: 14, weight: '600' } } },
                    yHours: {
                        type: 'linear', position: 'left',
                        title: { display: true, text: 'ساعات النوم (ساعة)', font: { family: 'Cairo', size: 14, weight: '600' }, color: '#B34073' },
                        suggestedMin: 0, suggestedMax: 18,
                    },
                    yWakeUps: {
                        type: 'linear', position: 'right',
                        title: { display: true, text: 'متوسط الاستيقاظ', font: { family: 'Cairo', size: 14, weight: '600' }, color: '#007bff' },
                        suggestedMin: 0,
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>