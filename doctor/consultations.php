<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_response'])) {
        $consultation_id = $_POST['consultation_id'];
        $response = $_POST['response'];

        $stmt = $conn->prepare("UPDATE messages SET response = ?, response_date = NOW(), status = 'responded' WHERE id = ?");
        $stmt->bind_param("si", $response, $consultation_id);
        $stmt->execute();
        $message = "تم إرسال الرد بنجاح";
    }
}

// الاستشارات الطبيةBring medical advice
$query = "SELECT m.*, u.full_name as sender_name, c.name as child_name
          FROM messages m
          LEFT JOIN users u ON m.sender_id = u.id
          LEFT JOIN children c ON m.child_id = c.id
          WHERE m.recipient_id = ? AND m.message_type = 'consultation'
          ORDER BY m.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$consultations_result = $stmt->get_result();
$main_dark = '#842029';
$main_text = '#dc3545';
$main_light = '#f5c6cb';
$main_deep = '#f1aeb5';
$bg_light = '#f8d7da';
$title_icon = 'fas fa-chart-line';
$dashboard_link = 'index.php';
$user_type = 'doctor';
$base_path = '';
$parent_path = '../parent/';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاستشارات الطبية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
</head>
  <style>
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }
        .sidebar { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%); width: 250px; min-height: 100vh; padding: 20px; color: white; box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12); transition: all 0.3s; }
        .sidebar a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { color: white; transform: translateX(5px); }
        .sidebar .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255, 255, 255, 0.15); }
        .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar .logo i { color: var(--primary-pink); }
    </style>
<body>
    <!-- top strip-->
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👨‍⚕️ الطبيب</h1>
                <span class="badge">v1.0</span>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً د.</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'طبيب'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الاستشارات الطبية</h1>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row">
            <?php while ($consultation = $consultations_result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">استشارة من <?php echo htmlspecialchars($consultation['sender_name']); ?></h5>
                            <span class="badge bg-<?php echo $consultation['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                <?php echo $consultation['status'] === 'pending' ? 'معلقة' : 'تم الرد'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($consultation['child_id']): ?>
                                <p><strong>الطفل:</strong> <?php echo htmlspecialchars($consultation['child_name']); ?></p>
                            <?php endif; ?>
                            <p><strong>التاريخ:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($consultation['created_at']))); ?></p>
                            <p><strong>الاستشارة:</strong></p>
                            <p class="bg-light p-2 rounded"><?php echo htmlspecialchars(is_string($consultation['message']) ? $consultation['message'] : ''); ?></p>

                            <?php if ($consultation['status'] === 'responded'): ?>
                                <div class="mt-3">
                                    <p><strong>الرد:</strong></p>
                                    <p class="bg-success text-white p-2 rounded"><?php echo htmlspecialchars($consultation['response']); ?></p>
                                    <small class="text-muted">تم الرد في: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($consultation['response_date']))); ?></small>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="consultation_id" value="<?php echo $consultation['id']; ?>">
                                    <div class="mb-3">
                                        <label for="response_<?php echo $consultation['id']; ?>" class="form-label">الرد على الاستشارة</label>
                                        <textarea class="form-control" id="response_<?php echo $consultation['id']; ?>" name="response" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="send_response" class="btn btn-primary">إرسال الرد</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($consultations_result->num_rows === 0): ?>
            <div class="text-center mt-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">لا توجد استشارات طبية</h4>
                <p class="text-muted">سيظهر هنا الاستشارات الطبية المرسلة من الآباء</p>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>