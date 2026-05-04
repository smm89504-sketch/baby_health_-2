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
$children = [];
$records = [];
$selected_child_id_from_get = $_GET['child_id'] ?? ''; 

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
  
    $stmt_children = $pdo->prepare('SELECT * FROM children WHERE user_id = ? ORDER BY name ASC');
    $stmt_children->execute([$_SESSION['user_id']]);
    $children = $stmt_children->fetchAll();

    
    if ($selected_child_id_from_get && filter_var($selected_child_id_from_get, FILTER_VALIDATE_INT)) {
        $stmt_records = $pdo->prepare('SELECT g.*, c.name as child_name FROM growth_records g JOIN children c ON g.child_id = c.id WHERE c.user_id = ? AND c.id = ? ORDER BY g.date DESC, g.id DESC');
        $stmt_records->execute([$_SESSION['user_id'], $selected_child_id_from_get]);
    } else {
        
        
        $stmt_records = $pdo->prepare('SELECT g.*, c.name as child_name FROM growth_records g JOIN children c ON g.child_id = c.id WHERE c.user_id = ? ORDER BY g.date DESC, g.id DESC LIMIT 20'); // إضافة LIMIT مبدئيًا
        $stmt_records->execute([$_SESSION['user_id']]);
    }
    $records = $stmt_records->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Add a new growth record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_growth'])) {
    $child_id_form = $_POST['child_id'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $weight = $_POST['weight'] ?? '';
    $height = $_POST['height'] ?? '';
    $temperature = $_POST['temperature'] ?? '';
    $feeding = trim($_POST['feeding'] ?? '');
    $illness = trim($_POST['illness'] ?? '');
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $medicine_dose = trim($_POST['medicine_dose'] ?? '');
    $medicine_time = $_POST['medicine_time'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $sleep_start = $_POST['sleep_start'] ?? '';
    $sleep_end = $_POST['sleep_end'] ?? '';
    $sleep_hours = $_POST['sleep_hours'] ?? '';

    if (!$child_id_form) $errors[] = 'يرجى اختيار الطفل.';
    if (!$date) $errors[] = 'يرجى اختيار التاريخ.';
    if ($weight !== '' && (!is_numeric($weight) || $weight <= 0)) $errors[] = 'الوزن المدخل غير صالح.';
    if ($height !== '' && (!is_numeric($height) || $height <= 0)) $errors[] = 'الطول المدخل غير صالح.';
    if ($temperature !== '' && (!is_numeric($temperature) || $temperature < 30 || $temperature > 45)) $errors[] = 'درجة الحرارة المدخلة غير منطقية.';
    if ($sleep_hours !== '' && (!is_numeric($sleep_hours) || $sleep_hours < 0 || $sleep_hours > 24)) $errors[] = 'عدد ساعات النوم المدخلة غير منطقية.';
    
    
    if (empty($errors) && isset($pdo)) {
        try {
            $stmt_insert = $pdo->prepare('INSERT INTO growth_records (child_id, date, weight, height, temperature, feeding, illness, medicine_name, medicine_dose, medicine_time, note, sleep_start, sleep_end, sleep_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt_insert->execute([$child_id_form, $date, ($weight === '' ? null : $weight), ($height === '' ? null : $height), ($temperature === '' ? null : $temperature), $feeding, $illness, $medicine_name, $medicine_dose, ($medicine_time === '' ? null : $medicine_time), $note, ($sleep_start === '' ? null : $sleep_start), ($sleep_end === '' ? null : $sleep_end), ($sleep_hours === '' ? null : $sleep_hours)]);
            header("Location: tracking.php?child_id=$child_id_form&success=1"); // إضافة متغير للنجاح
            exit;
        } catch (PDOException $e) {
            $errors[] = 'حدث خطأ أثناء إضافة السجل: ' . $e->getMessage();
        }
    } elseif (empty($errors) && !isset($pdo)) {
        $errors[] = 'فشل الاتصال بقاعدة البيانات، لا يمكن إضافة السجل.';
    }
}

function analyze_child_status($rec) {
    $alerts = [];
    if ($rec['temperature'] !== null && $rec['temperature'] >= 38) {
        $alerts[] = '<span class="badge bg-danger-soft text-danger-emphasis"><i class="bi bi-thermometer-high"></i> حرارة مرتفعة</span>';
    } elseif ($rec['temperature'] !== null && $rec['temperature'] < 36) {
        $alerts[] = '<span class="badge bg-info-soft text-info-emphasis"><i class="bi bi-thermometer-low"></i> حرارة منخفضة</span>';
    }

    
    
    if (!empty($rec['feeding']) && (stripos($rec['feeding'], 'قليل') !== false || (is_numeric($rec['feeding']) && $rec['feeding'] < 3) )) {
         $alerts[] = '<span class="badge bg-warning-soft text-warning-emphasis"><i class="bi bi-cup-straw"></i> رضاعة قليلة</span>';
    }

    if (!empty($rec['illness']) && preg_match('/(إسهال|قيء|خمول|سعال|زكام|طفح|صعوبة تنفس)/u', $rec['illness'])) {
        $alerts[] = '<span class="badge bg-danger-soft text-danger-emphasis"><i class="bi bi-emoji-dizzy"></i> حالة مرضية</span>';
    }
    
    if ($rec['sleep_hours'] !== null && $rec['sleep_hours'] > 0 && $rec['sleep_hours'] < 7) { // كمثال، أقل من 7 ساعات يعتبر قليل لطفل
        $alerts[] = '<span class="badge bg-warning-soft text-warning-emphasis"><i class="bi bi-moon-stars-fill"></i> نوم غير كافٍ</span>';
    }
    return implode(' ', $alerts);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع حالة الأطفال</title>
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
        .btn-back-to-dashboard {
        background-color: transparent;
        border: 1px solid #D13878; 
        color: #D13878; 
        padding: 0.5rem 1rem;
        border-radius: 0.375rem; 
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    .btn-back-to-dashboard:hover,
    .btn-back-to-dashboard:focus {
        background-color: #D13878; 
        color: #fff; 
        border-color: #D13878;
    }

    .btn-back-to-dashboard i {
        vertical-align: -1px; 
    }
        .main-box {
            margin-top: 25px; margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(100, 100, 100, 0.12);
            border-radius: 15px; background: #ffffff;
            padding: 30px;
        }
        .page-header {
            font-size: 2rem; color: #C7346F;
            font-weight: 700; text-align: center;
            margin-bottom: 30px;
        }
        .page-header i { margin-left: 12px; font-size: 1.8rem; vertical-align: -2px; }

        
        .add-form-section {
            background-color: #FFFAFB; 
            border-radius: 12px;
            border: 1px solid #FDEEF0; 
            padding: 25px;
            margin-bottom: 35px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .add-form-section h5 {
            color: #B34073; 
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #FDEEF0;
        }
        .form-label {
            font-weight: 600; 
            color: #776268; 
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .form-control, .form-select {
            border-radius: 8px; border: 1px solid #F0DDE2; 
            padding: 0.65rem 1rem; 
            background-color: #fff; 
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #E7AAB4; 
            box-shadow: 0 0 0 0.2rem rgba(231, 170, 180, 0.35); 
        }
        .form-control::placeholder { color: #B0A0A5; }
        .form-note { 
            font-size: 1rem; color: #C7346F; font-weight: 600;
            margin-top: 1.5rem; margin-bottom: 0.8rem;
            padding-bottom: 0.3rem;
            border-bottom: 1px dashed #F0DDE2;
        }
         .btn-add-record { 
            background-color: #D13878; border-color: #D13878;
            color: #fff; font-weight: 600;
            padding: 0.75rem 1.5rem; font-size: 1.1rem;
            border-radius: 8px; width: 100%;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .btn-add-record:hover { background-color: #B34073; border-color: #B34073; }
        .btn-add-record i { margin-left: 8px; }
        
        
        .record-card {
            border-radius: 15px; 
            box-shadow: 0 5px 18px rgba(180, 150, 160, 0.15); 
            background: #fff; 
            position: relative;
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
            margin-bottom: 2rem;
            border: 1px solid #FDEEF0;
            overflow: hidden; 
        }
        .record-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(180, 150, 160, 0.2);
        }
        .card-floating-icon {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px; height: 50px; 
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; 
            background-color: #E7AAB4; 
            color: #fff;
            box-shadow: 0 3px 10px rgba(200, 150, 160, 0.3);
            border: 3px solid #fff;
        }
         .record-card-header {
            padding-top: 35px; 
            padding-bottom: 10px;
            text-align: center;
            background-color: #FFFAFB; 
            border-bottom: 1px solid #FDEEF0;
        }
        .record-card-header .child-name {
            font-size: 1.3rem; font-weight: 700; 
            color: #B34073; 
            margin-bottom: 0.25rem;
        }
        .record-card-header .date-badge {
            font-size: 0.9rem; background: #FDEFF3; 
            color: #C7346F; 
            border-radius: 20px; 
            padding: 4px 12px;
            display: inline-block;
            font-weight: 600;
        }
        .record-card-header .status-alerts {
            margin-top: 8px;
            min-height: 24px; 
        }
        .info-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); 
            gap: 12px; 
            padding: 15px; 
        }
        .info-box {
            background: #FDF7F9; 
            border: 1px solid #FBEFF2; 
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            font-size: 0.9rem; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: background-color 0.2s;
        }
        .info-box:hover { background-color: #FFF5F7; }
        .info-box .icon {
            font-size: 1.5rem; 
            margin-bottom: 5px; display: block;
            color: #E7AAB4; 
        }
        .info-box strong { color: #6D4C41; } 
        .info-box .text-muted { font-size: 0.8rem; }
      
        .info-box.temp .icon { color: #FFB74D; } 
        .info-box.ill .icon { color: #E57373; } 
        .info-box.medicine .icon { color: #64B5F6; } 

        .badge { font-size: 0.75rem; padding: 0.4em 0.6em; font-weight: 600;}
        .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
        .text-danger-emphasis { color: #dc3545; }
        .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
        .text-warning-emphasis { color: #fd7e14; }
        .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
        .text-info-emphasis { color: #0dcaf0; }

        .filter-form { margin-bottom: 25px; }
        .filter-form .form-label { margin-bottom: 0.25rem; font-size: 0.9rem; }

        @media (max-width: 767px) {
            .info-grid { grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px; }
            .info-box { font-size: 0.85rem; padding: 8px 5px; }
            .record-card-header .child-name { font-size: 1.15rem; }
            .add-form-section { padding: 20px 15px; }
        }
        .alert { border-radius: 8px; }
        .alert-danger { background-color: #FFEBEE; color: #B71C1C; border: 1px solid #FFCDD2; }
        .alert-success { background-color: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }

    </style>
</head>
<body>
<div class="d-flex">
    <div class="flex-grow-1">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 main-box p-lg-4 p-3 mt-4">
                    <div class="page-header"><i class="bi bi-activity"></i> تتبع حالة ونمو الأطفال</div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0 ps-3"> 
                                <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                        <div class="alert alert-success text-center mb-4">
                             <i class="bi bi-check-circle-fill me-2"></i> تم إضافة السجل بنجاح!
                        </div>
                    <?php endif; ?>

                    <section class="add-form-section">
                        <h5><i class="bi bi-journal-plus"></i> إضافة سجل جديد لتتبع الطفل</h5>
                        <form method="POST" action="tracking.php"> 
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 col-lg-4">
                                    <label for="child_id_form" class="form-label">اختر الطفل <span class="text-danger">*</span></label>
                                    <select name="child_id" id="child_id_form" class="form-select" required>
                                        <option value="">-- اختر من القائمة --</option>
                                        <?php foreach ($children as $ch): ?>
                                            <option value="<?= $ch['id'] ?>" <?= ($selected_child_id_from_get == $ch['id'] || (isset($_POST['child_id']) && $_POST['child_id'] == $ch['id'])) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ch['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="date_form" class="form-label">تاريخ التسجيل <span class="text-danger">*</span></label>
                                    <input type="date" name="date" id="date_form" class="form-control" value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>" required>
                                </div>
                            </div>

                            <div class="form-note">بيانات النمو الأساسية</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="weight_form" class="form-label">الوزن (كغ)</label>
                                    <input type="number" name="weight" id="weight_form" class="form-control" min="0.1" step="0.01" placeholder="مثال: 7.5" value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="height_form" class="form-label">الطول (سم)</label>
                                    <input type="number" name="height" id="height_form" class="form-control" min="10" step="0.1" placeholder="مثال: 65" value="<?= htmlspecialchars($_POST['height'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="temp_form" class="form-label">الحرارة (°م)</label>
                                    <input type="number" name="temperature" id="temp_form" class="form-control" min="30" max="45" step="0.1" placeholder="مثال: 37.2" value="<?= htmlspecialchars($_POST['temperature'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="feeding_form" class="form-label">ملاحظات الرضاعة</label>
                                    <input type="text" name="feeding" id="feeding_form" class="form-control" placeholder="مثال: 5 رضعات، 150مل..." value="<?= htmlspecialchars($_POST['feeding'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="illness_form" class="form-label">الأعراض المرضية (إن وجدت)</label>
                                    <input type="text" name="illness" id="illness_form" class="form-control" placeholder="مثال: سعال خفيف، زكام..." value="<?= htmlspecialchars($_POST['illness'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-note">تتبع الدواء (إن وجد)</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="med_name_form" class="form-label">اسم الدواء</label>
                                    <input type="text" name="medicine_name" id="med_name_form" class="form-control" placeholder="مثال: مسكن حرارة" value="<?= htmlspecialchars($_POST['medicine_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="med_dose_form" class="form-label">الجرعة</label>
                                    <input type="text" name="medicine_dose" id="med_dose_form" class="form-control" placeholder="مثال: 5 مل" value="<?= htmlspecialchars($_POST['medicine_dose'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="med_time_form" class="form-label">وقت الإعطاء</label>
                                    <input type="time" name="medicine_time" id="med_time_form" class="form-control" value="<?= htmlspecialchars($_POST['medicine_time'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-note">تتبع النوم (اختياري)</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="sleep_start_form" class="form-label">بداية النوم</label>
                                    <input type="time" name="sleep_start" id="sleep_start_form" class="form-control" value="<?= htmlspecialchars($_POST['sleep_start'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="sleep_end_form" class="form-label">نهاية النوم</label>
                                    <input type="time" name="sleep_end" id="sleep_end_form" class="form-control" value="<?= htmlspecialchars($_POST['sleep_end'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="sleep_hours_form" class="form-label">إجمالي ساعات النوم</label>
                                    <input type="number" name="sleep_hours" id="sleep_hours_form" class="form-control" min="0" max="24" step="0.1" placeholder="مثال: 8.5" value="<?= htmlspecialchars($_POST['sleep_hours'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="note_form" class="form-label">ملاحظات إضافية</label>
                                <textarea name="note" id="note_form" class="form-control" rows="3" placeholder="أي ملاحظات أخرى حول الطفل في هذا اليوم..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" name="add_growth" class="btn btn-add-record"><i class="bi bi-save"></i> حفظ السجل</button>
                            </div>
                        </form>
                    </section>

                    <hr class="my-5"> 

                    <section class="recorded-data-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 style="color: #B34073; font-weight: 600;"><i class="bi bi-list-check"></i> السجلات المحفوظة</h4>
                            <form method="GET" action="tracking.php" class="filter-form d-flex align-items-end gap-2">
                                <div>
                                    <label for="filter_child_id" class="form-label">عرض سجلات الطفل:</label>
                                    <select name="child_id" id="filter_child_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value=""> الكل</option>
                                        <?php foreach ($children as $ch): ?>
                                            <option value="<?= $ch['id'] ?>" <?= $selected_child_id_from_get == $ch['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ch['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-sm btn-outline-primary"> <i class="bi bi-funnel"></i> تصفية</button>
                            </form>
                        </div>

                        <div class="row g-lg-4 g-md-3 g-2"> 
                            <?php if (empty($records)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle-fill me-2"></i> 
                                        <?= $selected_child_id_from_get ? 'لا توجد سجلات لهذا الطفل بعد.' : 'لا توجد سجلات تتبع محفوظة بعد.' ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($records as $rec): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="record-card">
                                            <div class="card-floating-icon">
                                                <i class="bi bi-clipboard2-pulse"></i> 
                                            </div>
                                            <div class="record-card-header">
                                                <div class="child-name"><?= htmlspecialchars($rec['child_name']) ?></div>
                                                <div class="date-badge"><i class="bi bi-calendar3"></i> <?= htmlspecialchars(date("d M Y", strtotime($rec['date']))) ?></div>
                                                <div class="status-alerts"><?= analyze_child_status($rec) ?></div>
                                            </div>
                                            <div class="info-grid">
                                                <?php if ($rec['weight']): ?>
                                                <div class="info-box weight"><span class="icon bi bi-box"></span>وزن<br><strong><?= htmlspecialchars($rec['weight']) ?> كغ</strong></div>
                                                <?php endif; ?>
                                                <?php if ($rec['height']): ?>
                                                <div class="info-box height"><span class="icon bi bi-rulers"></span>طول<br><strong><?= htmlspecialchars($rec['height']) ?> سم</strong></div>
                                                <?php endif; ?>
                                                <?php if ($rec['temperature']): ?>
                                                <div class="info-box temp"><span class="icon bi bi-thermometer-half"></span>حرارة<br><strong><?= htmlspecialchars($rec['temperature']) ?>°م</strong></div>
                                                <?php endif; ?>
                                                <?php if ($rec['feeding']): ?>
                                                <div class="info-box feed"><span class="icon bi bi-ui-radios"></span>رضاعة<br><strong><?= nl2br(htmlspecialchars($rec['feeding'])) ?></strong></div>
                                                <?php endif; ?>
                                                <?php if ($rec['illness']): ?>
                                                <div class="info-box ill"><span class="icon bi bi-bandaid"></span>مرض<br><strong><?= nl2br(htmlspecialchars($rec['illness'])) ?></strong></div>
                                                <?php endif; ?>
                                                <?php if ($rec['medicine_name']): ?>
                                                <div class="info-box medicine"><span class="icon bi bi-capsule-pill"></span>دواء<br><strong><?= htmlspecialchars($rec['medicine_name']) ?></strong><?php if ($rec['medicine_dose']): ?><br><span class="badge bg-info-soft text-info-emphasis p-1"><?= htmlspecialchars($rec['medicine_dose']) ?></span><?php endif; ?><?php if ($rec['medicine_time']): ?><br><small class="text-muted">[<?= htmlspecialchars(date("h:i A", strtotime($rec['medicine_time']))) ?>]</small><?php endif; ?></div>
                                                <?php endif; ?>
                                                <?php if ($rec['sleep_start'] || $rec['sleep_end'] || $rec['sleep_hours']): ?>
                                                <div class="info-box sleep"><span class="icon bi bi-power"></span>نوم<br><?php if ($rec['sleep_start']): ?>من <b><?= htmlspecialchars(date("h:i A", strtotime($rec['sleep_start']))) ?></b><?php endif; ?><?php if ($rec['sleep_end']): ?><br>إلى <b><?= htmlspecialchars(date("h:i A", strtotime($rec['sleep_end']))) ?></b><?php endif; ?><?php if ($rec['sleep_hours']): ?><br>(<?= htmlspecialchars($rec['sleep_hours']) ?> ساعة)<?php endif; ?></div>
                                                <?php endif; ?>
                                                <?php if ($rec['note']): ?>
                                                <div class="info-box note" style="flex-basis: 100%;"><span class="icon bi bi-card-text"></span>ملاحظة<br><strong><?= nl2br(htmlspecialchars($rec['note'])) ?></strong></div>
                                                <?php endif; ?>
                                            </div>
                                            
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                    <div class="my-3">
   
</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>