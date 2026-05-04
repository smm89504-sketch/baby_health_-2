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
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$errors = [];
$success = '';
$child_id = $_REQUEST['child_id'] ?? null;
$edit_id = $_GET['edit_id'] ?? null;
$child_data = null;
$vaccines_list = [];
$child_vaccines = [];
$edit_record = null;

// Added for sidebar
$user_type = $_SESSION['user_type'] ?? 'nurse';
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'مستخدم'); 

// Make sure there is a folder for uploading certificates.الشهادات
if (!is_dir('uploads/vaccine_certs')) {
    mkdir('uploads/vaccine_certs', 0777, true);
}


if (!$child_id) {
    header('Location: children.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Retrieve child data
    $stmt_child = $pdo->prepare('SELECT id, name, birth_date FROM children WHERE id = ?');
    $stmt_child->execute([$child_id]);
    $child_data = $stmt_child->fetch();
    if (!$child_data) {
        header('Location: children.php');
        exit;
    }
    
    // Bring the standard vaccine list
    $stmt_list = $pdo->query('SELECT * FROM vaccines ORDER BY target_age ASC');
    $vaccines_list = $stmt_list->fetchAll();

    // Bring the vaccination record for editing
    if ($edit_id) {
        $stmt_edit = $pdo->prepare('SELECT * FROM child_vaccines WHERE id = ? AND child_id = ?');
        $stmt_edit->execute([$edit_id, $child_id]);
        $edit_record = $stmt_edit->fetch();
        if (!$edit_record) {
            $errors[] = 'سجل التطعيم غير موجود.';
            $edit_id = null;
        }
    }

    // Add/Modify Handling
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $vaccine_id = $_POST['vaccine_id'] ?? null;
        $due_date = $_POST['due_date'] ?? null;
        $administered_date = $_POST['administered_date'] ?? ''; 
        $nurse_note = trim($_POST['nurse_note'] ?? '');
        $record_id_to_save = $_POST['record_id_to_save'] ?? null;
        $uploaded_file = $_FILES['certificate'] ?? null; 
        
        $certificate_filename = $edit_record['certificate_filename'] ?? null; 

        //The new logic of the vaccine case
        $administered_date_db = empty($administered_date) ? null : $administered_date;

        if ($administered_date_db) {
            $status = 'administered';
        } else {
            if ($due_date < date('Y-m-d')) {
                $status = 'missed';
            } else {
                $status = 'due';
            }
        }
        // The end of the new logic

        if (!$vaccine_id || !$due_date) {
            $errors[] = 'اللقاح وتاريخ الاستحقاق مطلوبان.';
        }
        
        // معالجة رفع الشهادة
        if ($uploaded_file && $uploaded_file['error'] === UPLOAD_ERR_OK) {
            $target_dir = "uploads/vaccine_certs/";
            $file_extension = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array($file_extension, $allowed_types)) {
                $errors[] = 'صيغة الشهادة غير صالحة. يجب أن تكون JPG, PNG أو PDF.';
            } else {
                 // توليد اسم ملف فريد
                $new_file_name = uniqid('cert_') . '.' . $file_extension;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($uploaded_file["tmp_name"], $target_file)) {
                    $certificate_filename = $new_file_name;
                } else {
                    $errors[] = 'حدث خطأ أثناء رفع الشهادة.';
                }
            }
        }
        // نهاية معالجة رفع الشهادة


        if (empty($errors)) {
            if ($record_id_to_save) { // تعديل
                $stmt = $pdo->prepare('UPDATE child_vaccines SET vaccine_id = ?, due_date = ?, administered_date = ?, status = ?, nurse_note = ?, certificate_filename = ?, nurse_id = ? WHERE id = ? AND child_id = ?');
                $stmt->execute([$vaccine_id, $due_date, $administered_date_db, $status, $nurse_note, $certificate_filename, $_SESSION['user_id'], $record_id_to_save, $child_id]);
                $success = 'تم تعديل سجل التطعيم بنجاح.';
            } else { // إضافة
                // التأكد من عدم تكرار اللقاح لنفس الطفل 
                $stmt_check = $pdo->prepare('SELECT id FROM child_vaccines WHERE child_id = ? AND vaccine_id = ? AND status != "administered"');
                $stmt_check->execute([$child_id, $vaccine_id]);
                if ($stmt_check->fetch() && $status !== 'administered') {
                    $errors[] = 'هذا اللقاح مسجل للطفل ومستحق أو فائت بالفعل.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO child_vaccines (child_id, vaccine_id, due_date, administered_date, status, nurse_note, certificate_filename, nurse_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$child_id, $vaccine_id, $due_date, $administered_date_db, $status, $nurse_note, $certificate_filename, $_SESSION['user_id']]);
                    $success = 'تم إضافة سجل التطعيم بنجاح.';
                }
            }
            if (empty($errors)) {
                // إضافة ملاحظة للممرض (ستظهر في سجل الملاحظات العامة)
                $stmt_vaccine_name = $pdo->query("SELECT name FROM vaccines WHERE id = $vaccine_id")->fetchColumn();
                $note_content = "تم تسجيل/تعديل حالة تطعيم: " . htmlspecialchars($stmt_vaccine_name) . ". " . ($administered_date_db ? "تم الإعطاء في $administered_date_db. " : "مستحق في $due_date. الحالة: " . ($status === 'missed' ? 'فائت' : 'مستحق')) . ". ملاحظة الممرض: $nurse_note";
                // تم إضافة note_type
                $stmt_note = $pdo->prepare('INSERT INTO professional_notes (child_id, user_id, user_type, note_content, note_type) VALUES (?, ?, "nurse", ?, "general")');
                $stmt_note->execute([$child_id, $_SESSION['user_id'], $note_content]);

                header('Location: child_vaccination.php?child_id=' . $child_id . '&success=' . urlencode($success));
                exit;
            }
        }
    }
    
    // Bring the child's vaccination records
    $stmt_child_vaccines = $pdo->prepare("SELECT cv.*, v.name as vaccine_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id WHERE cv.child_id = ? ORDER BY cv.due_date ASC");
    $stmt_child_vaccines->execute([$child_id]);
    $child_vaccines = $stmt_child_vaccines->fetchAll();

    // جلب رسالة النجاح من GET
    $success = $_GET['success'] ?? $success;

} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Logic from profile.php for sidebar styling (start)
$dashboard_link = $user_type === 'doctor' ? 'doctor_dashboard.php' : ($user_type === 'nurse' ? 'nurse_dashboard.php' : 'profile.php');

if ($user_type === 'doctor') {
    $main_dark = '#842029'; $main_text = '#dc3545'; $main_light = '#f5c6cb'; $main_deep = '#f1aeb5'; $bg_light = '#f8d7da'; $title_icon = 'fas fa-stethoscope';
} elseif ($user_type === 'nurse') {
    $main_dark = '#0f5132'; $main_text = '#28a745'; $main_light = '#c3e6cb'; $main_deep = '#b1dfbb'; $bg_light = '#d4edda'; $title_icon = 'fas fa-syringe';
} else { // parent
    $main_dark = '#ad1457'; $main_text = '#880e4f'; $main_light = '#ffd1dc'; $main_deep = '#f8bbd0'; $bg_light = '#fff0f5'; $title_icon = 'fas fa-heartbeat';
}
// Logic from profile.php for sidebar styling (end)

// Fetch Alerts (Only for parent - not applicable directly on nurse page, but kept for consistency)
$due_vaccines = [];
$vaccine_alerts = ['upcoming' => [], 'missed' => []];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة تطعيمات: <?= htmlspecialchars($child_data['name'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Extracting and combining key styles from profile.php and current file */
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
            --shadow-md: 0 8px 24px rgba(136, 14, 79, 0.12);
        }
        body { 
            background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%);
            color: #4A4A4A; 
            font-family: 'Cairo', sans-serif;
            display: flex; 
            min-height: 100vh;
        }
        /* Sidebar Styles (copied from profile.php) */
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
        /* End Sidebar Styles */
        .main-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        /* Existing Styles */
        .main-box { margin-top: 0px; margin-bottom: 25px; box-shadow: 0 6px 24px rgba(0, 0, 0, 0.05); border-radius: 12px; background: #ffffff; padding: 40px; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .page-header { font-size: 2rem; color: #0d6efd; font-weight: 700; text-align: center; margin-bottom: 35px; }
        .form-section { border: 1px solid #b8daff; border-radius: 8px; padding: 20px; margin-bottom: 30px; background-color: #e3f2fd; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 main-box">
                <div class="page-header"><i class="bi bi-shield-plus"></i> إدارة تطعيمات: <?= htmlspecialchars($child_data['name'] ?? 'الطفل') ?></div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <div class="form-section">
                    <h5 class="mb-3 text-primary"><i class="bi bi-pencil-square me-2"></i> <?= $edit_id ? 'تعديل سجل' : 'إضافة سجل تطعيم جديد' ?></h5>
                    <form method="POST" action="child_vaccination.php?child_id=<?= $child_id ?>" enctype="multipart/form-data">
                        <input type="hidden" name="record_id_to_save" value="<?= htmlspecialchars($edit_id ?? '') ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="vaccine_id" class="form-label">اللقاح</label>
                                <select name="vaccine_id" id="vaccine_id" class="form-select" required>
                                    <option value="">-- اختر لقاح --</option>
                                    <?php foreach ($vaccines_list as $vac): ?>
                                        <option value="<?= $vac['id'] ?>" <?= ($edit_record['vaccine_id'] ?? null) == $vac['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($vac['name']) ?> (<?= htmlspecialchars($vac['target_age']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="due_date" class="form-label">تاريخ الاستحقاق</label>
                                <input type="date" name="due_date" id="due_date" class="form-control" value="<?= htmlspecialchars($edit_record['due_date'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="administered_date" class="form-label">تاريخ الإعطاء (اختياري)</label>
                                <input type="date" name="administered_date" id="administered_date" class="form-control" value="<?= htmlspecialchars($edit_record['administered_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="certificate_file" class="form-label">تحميل شهادة التطعيم (صورة/PDF)</label>
                                <input type="file" name="certificate" id="certificate_file" class="form-control" accept="image/*,application/pdf">
                                <?php if ($edit_record && !empty($edit_record['certificate_filename'])): ?>
                                    <small class="text-muted mt-1 d-block">الشهادة الحالية: <a href="uploads/vaccine_certs/<?= htmlspecialchars($edit_record['certificate_filename']) ?>" target="_blank"><i class="bi bi-file-earmark-check-fill"></i> موجودة</a>. تحميل ملف جديد سيستبدلها.</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="nurse_note" class="form-label">ملاحظة الممرض للأهل</label>
                                <textarea name="nurse_note" id="nurse_note" class="form-control" rows="2"><?= htmlspecialchars($edit_record['nurse_note'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> <?= $edit_id ? 'حفظ التعديلات' : 'إضافة السجل' ?></button>
                                <?php if ($edit_id): ?>
                                    <a href="child_vaccination.php?child_id=<?= $child_id ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-2"></i> إلغاء التعديل</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <h4 class="mb-3 text-secondary"><i class="bi bi-list-check me-2"></i> سجلات التطعيم الحالية</h4>
                <?php if (empty($child_vaccines)): ?>
                    <div class="alert alert-info text-center">لا يوجد سجلات تطعيم لهذا الطفل.</div>
                <?php else: ?>
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-info">
                            <tr>
                                <th>اللقاح</th>
                                <th>الاستحقاق</th>
                                <th>الحالة</th>
                                <th>الإعطاء</th>
                                <th>شهادة</th>
                                <th>ملاحظات الممرض</th>
                                <th style="width: 100px;">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($child_vaccines as $vac): ?>
                                <tr>
                                    <td><?= htmlspecialchars($vac['vaccine_name']) ?></td>
                                    <td><?= htmlspecialchars($vac['due_date']) ?></td>
                                    <td>
                                        <?php if ($vac['status'] === 'administered'): ?><span class="badge bg-success">تم</span>
                                        <?php elseif ($vac['status'] === 'missed'): ?><span class="badge bg-danger">فائت</span>
                                        <?php else: ?><span class="badge bg-warning text-dark">مستحق</span><?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($vac['administered_date'] ?? '---') ?></td>
                                    <td>
                                        <?php if (!empty($vac['certificate_filename'])): ?>
                                            <a href="uploads/vaccine_certs/<?= htmlspecialchars($vac['certificate_filename']) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-check"></i></a>
                                        <?php else: ?>
                                            ---
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($vac['nurse_note'] ?? 'لا يوجد') ?></td>
                                    <td>
                                        <a href="child_vaccination.php?child_id=<?= $child_id ?>&edit_id=<?= $vac['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>