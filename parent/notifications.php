<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Processing notification status update as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: notifications.php');
    exit();
}

// Processing a deleted notification
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    header('Location: notifications.php');
    exit();
}

// Bring notifications
$filter = $_GET['filter'] ?? 'all';
$where_clause = "WHERE user_id = ?";
$params = [$_SESSION['user_id']];
$types = "i";

if ($filter === 'unread') {
    $where_clause .= " AND is_read = 0";
} elseif ($filter === 'read') {
    $where_clause .= " AND is_read = 1";
}

$query = "SELECT * FROM notifications $where_clause ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$notifications_result = $stmt->get_result();

//Notification statistics
$query_stats = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
    SUM(CASE WHEN type = 'error' THEN 1 ELSE 0 END) as errors,
    SUM(CASE WHEN type = 'warning' THEN 1 ELSE 0 END) as warnings,
    SUM(CASE WHEN type = 'success' THEN 1 ELSE 0 END) as successes,
    SUM(CASE WHEN type = 'info' THEN 1 ELSE 0 END) as infos
    FROM notifications WHERE user_id = ?";
$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bind_param("i", $_SESSION['user_id']);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// New messages for the sidebar counter
$query_unread_messages = "SELECT COUNT(*) as unread_count FROM messages WHERE recipient_id = ? AND status = 'pending'";
$stmt_unread = $conn->prepare($query_unread_messages);
$stmt_unread->bind_param("i", $_SESSION['user_id']);
$stmt_unread->execute();
$unread_messages = $stmt_unread->get_result()->fetch_assoc()['unread_count'];

// Setting sidebar variables for parent pages
$base_path = '../';
$parent_path = '';
$dashboard_link = 'index.php';
$main_dark = '#ad1457';
$main_text = '#880e4f';
$main_light = '#ffd1dc';
$main_deep = '#f8bbd0';
$bg_light = '#fff0f5';
$title_icon = 'fas fa-heartbeat';
$vaccine_alerts = ['upcoming'=>[], 'missed'=>[]];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - لوحة الوالد</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <style>
        .notification-item {
            border-left: 4px solid;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .notification-item.unread {
            background: #f8f9fa;
        }
        .notification-error { border-left-color: #dc3545; }
        .notification-warning { border-left-color: #ffc107; }
        .notification-success { border-left-color: #28a745; }
        .notification-info { border-left-color: #17a2b8; }
        .notification-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .notification-item:hover .notification-actions {
            opacity: 1;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body>
    <!-- top strip-->
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👨‍👩‍👧‍👦 لوحة الوالد</h1>
                <span class="badge">v1.0</span>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'P', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'والد'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!--sidebar-->
    <?php include '../includes/sidebar.php'; ?>

    <!-- main contant-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الإشعارات</h1>
            <div>
                <a href="?filter=all" class="btn btn-outline-primary <?php echo $filter === 'all' ? 'active' : ''; ?>">الكل</a>
                <a href="?filter=unread" class="btn btn-outline-warning <?php echo $filter === 'unread' ? 'active' : ''; ?>">غير مقروءة</a>
                <a href="?filter=read" class="btn btn-outline-success <?php echo $filter === 'read' ? 'active' : ''; ?>">مقروءة</a>
            </div>
        </div>

        <!-- Notification statistics-->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-bell fa-2x mb-2"></i>
                        <h6>إجمالي الإشعارات</h6>
                        <h4><?php echo $stats['total']; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-envelope-open fa-2x text-warning mb-2"></i>
                        <h6>غير مقروءة</h6>
                        <h4><?php echo $stats['unread']; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h6>تحذيرات</h6>
                        <h4><?php echo $stats['warnings'] + $stats['errors']; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-info-circle fa-2x text-info mb-2"></i>
                        <h6>معلومات</h6>
                        <h4><?php echo $stats['infos'] + $stats['successes']; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications list-->
        <div class="card">
            <div class="card-header">
                <h5><?php
                    if ($filter === 'unread') echo 'الإشعارات غير المقروءة';
                    elseif ($filter === 'read') echo 'الإشعارات المقروءة';
                    else echo 'جميع الإشعارات';
                ?></h5>
            </div>
            <div class="card-body">
                <?php if ($notifications_result->num_rows > 0): ?>
                    <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                        <div class="notification-item notification-<?php echo $notification['type']; ?> <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-2"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-primary">جديد</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="notification-actions">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success me-1" title="تحديد كمقروء">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإشعار؟')">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد إشعارات</h5>
                        <p class="text-muted">
                            <?php
                            if ($filter === 'unread') echo 'جميع الإشعارات مقروءة';
                            elseif ($filter === 'read') echo 'لا توجد إشعارات مقروءة';
                            else echo 'لم يتم استلام أي إشعارات بعد';
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>