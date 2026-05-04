<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

//Retrieve children's data for the parent
$query_children = "SELECT * FROM children WHERE user_id = ? ORDER BY birth_date DESC";
$stmt_children = $conn->prepare($query_children);
$stmt_children->bind_param("i", $_SESSION['user_id']);
$stmt_children->execute();
$children_result = $stmt_children->get_result();

// Fetch unread notifications
$query_notifications = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10";
$stmt_notifications = $conn->prepare($query_notifications);
$stmt_notifications->bind_param("i", $_SESSION['user_id']);
$stmt_notifications->execute();
$notifications_result = $stmt_notifications->get_result();

//Bring the children's due vaccinations
$query_due_vaccines = "SELECT cv.*, c.name as child_name, vs.vaccine_name as vaccine_name, vs.age_months as schedule_age_months
                      FROM child_vaccines cv
                      JOIN children c ON cv.child_id = c.id
                      JOIN vaccine_schedule vs ON cv.vaccine_schedule_id = vs.id
                      WHERE c.user_id = ? AND cv.status = 'due'
                      ORDER BY cv.due_date ASC LIMIT 5";
$stmt_due_vaccines = $conn->prepare($query_due_vaccines);
$stmt_due_vaccines->bind_param("i", $_SESSION['user_id']);
$stmt_due_vaccines->execute();
$due_vaccines_result = $stmt_due_vaccines->get_result();

// Retrieve unread messages
$query_unread_messages = "SELECT COUNT(*) as unread_count FROM messages
                         WHERE recipient_id = ? AND status = 'pending'";
$stmt_unread_messages = $conn->prepare($query_unread_messages);
$stmt_unread_messages->bind_param("i", $_SESSION['user_id']);
$stmt_unread_messages->execute();
$unread_messages = $stmt_unread_messages->get_result()->fetch_assoc()['unread_count'];

//Setting up variables for a uniform sidebar display
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
    <title>لوحة الوالد - تطبيق متابعة الطفل</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <style>
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .alert-card {
            border-left: 4px solid;
            margin-bottom: 10px;
        }
        .alert-vaccine { border-left-color: #ffc107; }
        .alert-medication { border-left-color: #17a2b8; }
        .alert-growth { border-left-color: #28a745; }
        .alert-general { border-left-color: #6c757d; }
    </style>
</head>
<body>
    <!-- Top strip-->
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
                <!-- Notifications icon-->
                <div class="dropdown">
                    <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($notifications_result->num_rows > 0): ?>
                            <span class="notification-badge"><?php echo $notifications_result->num_rows; ?></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="min-width: 300px;">
                        <li><h6 class="dropdown-header">الإشعارات</h6></li>
                        <?php if ($notifications_result->num_rows > 0): ?>
                            <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted"><?php echo date('d/m H:i', strtotime($notification['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li><span class="dropdown-item-text">لا توجد إشعارات جديدة</span></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="notifications.php">عرض جميع الإشعارات</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- sidebar-->
    <?php include '../includes/sidebar.php'; ?>

            <li class="sidebar-item">
                <a href="../growth_charts.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i>
                    <span>النمو</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="messages.php" class="sidebar-link position-relative">
                    <i class="fas fa-comments"></i>
                    <span>الرسائل</span>
                    <?php if ($unread_messages > 0): ?>
                        <span class="notification-badge"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="profile.php" class="sidebar-link">
                    <i class="fas fa-user"></i>
                    <span>الملف الشخصي</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="../logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main contant-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>لوحة التحكم</h1>
        </div>

        <!-- Quick statistics-->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-child fa-2x text-primary mb-2"></i>
                        <h6>أطفالي</h6>
                        <h4><?php echo $children_result->num_rows; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-syringe fa-2x text-success mb-2"></i>
                        <h6>تطعيمات مستحقة</h6>
                        <h4><?php echo $due_vaccines_result->num_rows; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                        <h6>إشعارات جديدة</h6>
                        <h4><?php echo $notifications_result->num_rows; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-comments fa-2x text-info mb-2"></i>
                        <h6>رسائل جديدة</h6>
                        <h4><?php echo $unread_messages; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts and notifications-->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>التنبيهات المهمة</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($due_vaccines_result->num_rows > 0): ?>
                            <?php while ($vaccine = $due_vaccines_result->fetch_assoc()): ?>
                                <div class="alert alert-warning alert-card alert-vaccine">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>تطعيم مستحق:</strong> <?php echo htmlspecialchars($vaccine['vaccine_name']); ?>
                                            <br><small>للطفل: <?php echo htmlspecialchars($vaccine['child_name']); ?> - الموعد: <?php echo date('d/m/Y', strtotime($vaccine['due_date'])); ?></small>
                                        </div>
                                        <a href="vaccinations.php" class="btn btn-sm btn-warning">عرض التفاصيل</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> جميع التطعيمات محدثة
                            </div>
                        <?php endif; ?>

                        <!-- Other alerts can be added here-->
                        <div class="alert alert-info alert-card alert-general">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>تذكير:</strong> تابعي نمو طفلك يومياً
                                    <br><small>سجل الوزن والطول بانتظام لمتابعة النمو الصحي</small>
                                </div>
                                <a href="growth.php" class="btn btn-sm btn-info">تسجيل القياسات</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>الأطفال</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($children_result->num_rows > 0): ?>
                            <?php while ($child = $children_result->fetch_assoc()): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar-circle me-3">
                                        <?php echo strtoupper(substr($child['name'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($child['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php
                                            $age_months = floor((time() - strtotime($child['birth_date'])) / (30 * 24 * 60 * 60));
                                            echo $age_months . ' شهر';
                                            ?>
                                        </small>
                                    </div>
                                    <a href="child_details.php?id=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-child fa-2x mb-2"></i>
                                <p>لا توجد أطفال مسجلة</p>
                                <a href="add_child.php" class="btn btn-primary">إضافة طفل</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحديث الإشعارات كل دقيقة
        setInterval(function() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.unread_count > 0) {
                        // تحديث عداد الإشعارات
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.unread_count;
                        } else {
                            // إنشاء عداد جديد
                            const notificationBtn = document.querySelector('#notificationDropdown');
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.unread_count;
                            notificationBtn.appendChild(newBadge);
                        }
                    }

                    // تحديث عداد الرسائل
                    const messagesBadge = document.querySelector('.sidebar-link[href*="messages.php"] .notification-badge');
                    if (data.unread_messages > 0) {
                        if (messagesBadge) {
                            messagesBadge.textContent = data.unread_messages;
                        } else {
                            // إنشاء عداد جديد للرسائل
                            const messagesLink = document.querySelector('.sidebar-link[href*="messages.php"]');
                            if (messagesLink) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.textContent = data.unread_messages;
                                messagesLink.appendChild(newBadge);
                            }
                        }
                    }
                })
                .catch(error => console.error('Error checking notifications:', error));
        }, 60000); // كل دقيقة
    </script>
</body>
</html>