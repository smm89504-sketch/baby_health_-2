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
$user_type = $_SESSION['user_type'] ?? 'parent';
$search_term = $_GET['search'] ?? '';

// default image path
$default_child_image = 'images/images.jpeg'; 

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

    // Bring the children's list
    $sql = 'SELECT * FROM children ';
    $params = [];
    $where_clauses = ['is_archived = 0']; // MODIFICATION: Filter out archived children

    // تصفية حسب الدور
    if ($user_type === 'parent') {
        $where_clauses[] = 'user_id = ?';
        $params[] = $_SESSION['user_id'];
    }
    
    // إضافة البحث
    if ($search_term) {
        $where_clauses[] = 'name LIKE ?';
        $params[] = '%' . $search_term . '%';
    }
    
    // ربط الشروط
    if (!empty($where_clauses)) {
        $sql .= 'WHERE ' . implode(' AND ', $where_clauses) . ' ';
    }
    
    $sql .= 'ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $children = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

$back_link = 'profile.php';
if ($user_type === 'nurse') {
    $back_link = 'nurse_dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $user_type === 'parent' ? 'إدارة الأطفال' : 'ملفات الأطفال' ?></title>
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
        .main-box { margin-top: 0; margin-bottom: 25px; box-shadow: 0 6px 24px rgba(100, 100, 100, 0.1); border-radius: 12px; background: #ffffff; padding: 30px; }
        .page-header { font-size: 1.9rem; color: #C7346F; font-weight: 700; }
        .page-header i { margin-left: 12px; font-size: 1.7rem; }
        .btn-success { background-color: #E7AAB4; border-color: #E7AAB4; color: #fff; font-weight: 600; padding: 0.5rem 1rem; }
        .btn-success:hover { background-color: #DB99A6; border-color: #DB99A6; }
        .child-card { border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); background-color: #FFFAFB; border: 1px solid #FDEEF0; transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; height: 100%; }
        .child-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0, 0, 0, 0.09); }
        .child-card .card-title { color: #B34073; font-weight: 600; font-size: 1.25rem; }
        
        /* تحديث: استخدام الصورة الافتراضية */
        .child-icon-wrapper { 
            background-color: transparent; /* إزالة خلفية الأيقونة القديمة */
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-left: auto; 
            margin-right: auto; 
            margin-bottom: 15px;
            overflow: hidden; /* لضمان ظهور الصورة بشكل دائري */
        }
        .child-card-image {
            width: 100%; /* ملء الحاوية */
            height: 100%;
            object-fit: cover;
        }
        /* نهاية تحديث الصورة */
        
        .info-line i { color: #E7AAB4; margin-left: 8px; font-size: 1rem; }
        .btn i { margin-left: 4px; }
        .text-muted { color: #888 !important; font-size: 1.1rem; }
        .flex-grow-1 { padding: 0; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <div class="col-12 main-box p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom"> 
                    <div class="page-header"><i class="bi bi-people"></i> <?= $user_type === 'parent' ? 'إدارة الأطفال' : 'قائمة الأطفال' ?></div>
                    <?php if ($user_type === 'parent'): ?>
                        <a href="add_child.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> إضافة طفل جديد</a>
                    <?php endif; ?>
                </div>
                
                <form method="GET" action="children.php" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="البحث باسم الطفل..." value="<?= htmlspecialchars($search_term) ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> بحث</button>
                        <?php if ($search_term): ?>
                            <a href="children.php" class="btn btn-outline-danger"><i class="bi bi-x"></i> مسح</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-3">
                        <?php foreach ($errors as $error) echo "<div>• $error</div>"; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['archived']) && $_GET['archived'] == '1'): ?>
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-archive-fill me-2"></i> تم أرشفة ملف الطفل بنجاح.
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($children)): ?>
                        <div class="col-12 text-center text-muted p-5"> <i class="bi bi-emoji-frown" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                            <?= $search_term ? 'لا يوجد أطفال يطابقون البحث.' : 'لا يوجد أطفال مضافون بعد.' ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($children as $child): ?>
                            <div class="col-lg-4 col-md-6 mb-4"> <div class="card child-card h-100">
                                    <div class="card-body text-center">
                                        <div class="child-icon-wrapper mb-3"> 
                                            <img src="<?= htmlspecialchars($default_child_image) ?>" alt="صورة الطفل" class="child-card-image">
                                        </div>
                                        
                                        <h5 class="card-title mb-3"><?= htmlspecialchars($child['name']) ?></h5>
                                        <div class="info-line"><i class="bi bi-calendar-event"></i> تاريخ الميلاد: <?= htmlspecialchars($child['birth_date']) ?></div>
                                        <div class="info-line"><i class="bi bi-graph-up"></i> العمر: <?= htmlspecialchars($child['age']) ?></div>
                                        <div class="info-line"><i class="bi bi-bar-chart"></i> الوزن: <?= htmlspecialchars($child['weight']) ?> كغ</div>
                                        <div class="info-line"><i class="bi bi-arrows-collapse"></i> الطول: <?= htmlspecialchars($child['height']) ?> سم</div>
                                        <?php if (!empty($child['twin_group'])): ?>
                                            <div class="info-line text-warning"><i class="bi bi-people-fill"></i> توأم - مجموعة: <?= htmlspecialchars($child['twin_group']) ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-4 d-flex justify-content-center gap-2">
                                            <a href="child_details.php?id=<?= $child['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i> عرض التفاصيل</a>
                                            <?php if ($user_type === 'parent'): ?>
                                                <a href="edit_child.php?id=<?= $child['id'] ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-pencil"></i> تعديل</a>
                                                <a href="archive_child.php?id=<?= $child['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('هل أنت متأكد من أرشفة الطفل؟ لن يتم حذفه نهائياً ولكن سيتم إخفاؤه من هذه القائمة.');"><i class="bi bi-archive"></i> أرشفة</a>
                                            <?php elseif ($user_type === 'nurse'): ?>
                                                 <a href="add_nurse_note.php?child_id=<?= $child['id'] ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-file-text"></i> إضافة ملاحظة</a>
                                                <a href="child_vaccination.php?child_id=<?= $child['id'] ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-shield-plus"></i> تطعيمات</a>
                                            <?php endif; ?>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>