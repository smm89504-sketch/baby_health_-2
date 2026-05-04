<?php
require_once 'includes/check_auth.php';
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$search_term = trim($_GET['search'] ?? '');
$filter_type = $_GET['filter_type'] ?? 'all';
$location_search = trim($_GET['location'] ?? '');

$user_type = $_SESSION['user_type'] ?? 'parent';
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
// === END Sidebar Setup ===

$session_valid = !empty($_SESSION['user_id']);
$vaccine_alerts = ['upcoming' => [], 'missed' => []];
$unread_messages = 0;

if ($session_valid) {
    try {
        //Retrieve vaccination information for children (for sidebar alerts)
    $due_vaccines = [];
    if ($user_type === 'parent') {
        $stmt_vaccines_sidebar = $conn->prepare('SELECT cv.*, v.name as vaccine_name, c.name as child_name FROM child_vaccines cv JOIN vaccines v ON cv.vaccine_id = v.id JOIN children c ON cv.child_id = c.id WHERE c.user_id = ? AND cv.status = "due" ORDER BY cv.due_date ASC');
        $stmt_vaccines_sidebar->bind_param('i', $_SESSION['user_id']);
        $stmt_vaccines_sidebar->execute();
        $result = $stmt_vaccines_sidebar->get_result();
        $due_vaccines = $result->fetch_all(MYSQLI_ASSOC);
    }
    $vaccine_alerts = $user_type === 'parent' ? get_parent_alerts($due_vaccines) : ['upcoming' => [], 'missed' => []];

    // Number of unread messages for the parent (notification counter in the list)
    $unread_messages = 0;
    if ($user_type === 'parent') {
        $stmt_unread = $conn->prepare('SELECT COUNT(*) as unread FROM messages WHERE recipient_id = ? AND status = "pending"');
        $stmt_unread->bind_param('i', $_SESSION['user_id']);
        $stmt_unread->execute();
        $result = $stmt_unread->get_result();
        $row = $result->fetch_assoc();
        $unread_messages = (int) $row['unread'];
    }

    $params = [];
    $types = '';
    $sql = 'SELECT * FROM users WHERE user_type IN ("doctor", "nurse") ';

    if ($filter_type !== 'all' && ($filter_type === 'doctor' || $filter_type === 'nurse')) {
        $sql .= 'AND user_type = ? ';
        $params[] = $filter_type;
        $types .= 's';
    }

    if ($search_term) {
        $sql .= 'AND full_name LIKE ? ';
        $params[] = '%' . $search_term . '%';
        $types .= 's';
    }

    if ($location_search) {
        $sql .= 'AND location LIKE ? ';
        $params[] = '%' . $location_search . '%';
        $types .= 's';
    }

    $sql .= 'ORDER BY full_name ASC';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $providers = $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        $errors[] = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقدمو الرعاية الصحية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .main-box { margin-top: 0; box-shadow: 0 0 20px #eee; border-radius: 16px; background: #fff; }
        .provider-card { border-radius: 18px; box-shadow: 0 2px 12px #e0e0e0; background: linear-gradient(135deg, #f8fafc 80%, #e3f2fd 100%); margin-bottom: 2rem; padding: 1.5rem 1.2rem; position: relative; padding-top: 40px; }
        .provider-icon { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; background:#FF69B4; color: #fff; box-shadow: 0 2px 8px #b0c4de; border: 4px solid #fff; }
        .provider-name { font-size: 1.2rem; font-weight: bold; color: #DB7093; text-align: center; margin-bottom: 0.5rem; }
        .provider-type { color: #388e3c; font-weight: 500; text-align: center; margin-bottom: 1rem; }
        .provider-contact { font-size: 0.95rem; }
        .provider-contact i { margin-left: 8px; color: #FFB6C1; }
        .flex-grow-1 { padding: 0; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-container">
    <div class="dashboard-container">
        <div class="row justify-content-center">
            <div class="col-12 main-box p-4 mt-4">
                <div class="logo mb-4 text-center"><i class="bi bi-person-vcard"></i> قائمة مقدمي الرعاية الصحية</div>
                
                <form method="GET" action="providers.php" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="البحث بالاسم..." value="<?= htmlspecialchars($search_term) ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="location" class="form-control" placeholder="البحث بالموقع (مثل المدينة)..." value="<?= htmlspecialchars($location_search) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="filter_type" class="form-select">
                                <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>جميع مقدمي الرعاية</option>
                                <option value="doctor" <?= $filter_type === 'doctor' ? 'selected' : '' ?>>الأطباء</option>
                                <option value="nurse" <?= $filter_type === 'nurse' ? 'selected' : '' ?>>الممرضون</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> بحث</button>
                            <?php if ($search_term || $location_search || $filter_type !== 'all'): ?>
                                <a href="providers.php" class="btn btn-outline-danger"><i class="bi bi-x"></i></a>
                            <?php endif; ?>
                        </div>
                        <?php if ($user_type === 'parent'): ?>
                            <div class="col-md-3">
                                <a href="my_appointments.php" class="btn btn-info w-100"><i class="bi bi-calendar-check"></i> مواعيدي السابقة</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger mb-3">
                        <?php foreach ($errors as $error) echo "<div>• $error</div>"; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mt-4">
                    <?php if (empty($providers)): ?>
                        <div class="col-12 text-center text-muted">لا يوجد مقدمو رعاية يطابقون المعايير المحددة.</div>
                    <?php else: ?>
                        <?php foreach ($providers as $prov): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="provider-card position-relative">
                                    <div class="provider-icon"><i class="bi <?= $prov['user_type'] === 'doctor' ? 'bi-stethoscope' : 'bi-person-badge-fill' ?>"></i></div>
                                    <h5 class="provider-name mt-3"><?= htmlspecialchars($prov['full_name']) ?></h5>
                                    <div class="provider-type">
                                        <?= $prov['user_type'] === 'doctor' ? 'طبيب أطفال' : 'ممرض/ممرضة' ?>
                                    </div>
                                    <div class="provider-contact text-center">
                                        <div class="mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($prov['email']) ?></div>
                                        <?php if (!empty($prov['phone'])): ?>
                                            <div class="mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($prov['phone']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($prov['location'])): ?>
                                            <div class="mb-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($prov['location']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($user_type === 'parent' && $prov['user_type'] === 'doctor'): ?>
                                            <a href="book_appointment.php?doctor_id=<?= $prov['id'] ?>" class="btn btn-sm btn-success mt-2"><i class="bi bi-calendar-plus"></i> حجز موعد</a>
                                            <a href="messages.php?doctor_id=<?= $prov['id'] ?>" class="btn btn-sm btn-outline-primary mt-2 ms-2">ابدأ الدردشة مع الطبيب</a>
                                        <?php endif; ?>
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