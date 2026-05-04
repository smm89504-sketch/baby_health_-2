<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

require_once 'includes/db_config.php';
require_once 'includes/AppointmentNotificationManager.php';

$db = new DatabaseHelper();
$conn = $db->getConnection();
$notif_manager = new AppointmentNotificationManager($conn);

//Processing Procedures الاجرائات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
        $notif_manager->markAsRead(intval($_POST['notification_id']));
    }
}

// جلب جميع الإخطارات
$notifications = $notif_manager->getUserNotifications($_SESSION['user_id'], false);

$main_dark = '#842029';
$main_text = '#dc3545';
$main_light = '#f5c6cb';
$main_deep = '#f1aeb5';
$bg_light = '#f8d7da';
$title_icon = 'fas fa-bell';
$dashboard_link = 'index.php';
$user_type = 'parent';
$base_path = './';
$parent_path = '';
$unread_messages = 0;
$vaccine_alerts = ['missed' => [], 'upcoming' => []];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإخطارات والتذكيرات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); min-height: 100vh; color: #4A4A4A; font-family: 'Cairo', sans-serif; display: flex; }
        .main-container { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 900px; margin: 0 auto; }
        .main-box { margin-top: 40px; box-shadow: 0 6px 24px rgba(100, 100, 100, 0.10); border-radius: 16px; background: #fff; padding: 30px; }
        .page-header { font-size: 1.7rem; color: #c62828; font-weight: 700; margin-bottom: 30px; text-align: center; }
        .page-header i { margin-left: 12px; font-size: 1.5rem; }
        
        .notification-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border-right: 5px solid #dc3545;
        }
        
        .notification-item.unread {
            background: #fffbf0;
            border-right-color: #ffc107;
        }
        
        .notification-item.confirmation {
            border-right-color: #28a745;
        }
        
        .notification-item.reminder {
            border-right-color: #ffc107;
        }
        
        .notification-item.follow_up {
            border-right-color: #17a2b8;
        }
        
        .notification-item:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .notification-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .type-confirmation { background: #d4edda; color: #155724; }
        .type-reminder { background: #fff3cd; color: #856404; }
        .type-follow_up { background: #d1ecf1; color: #0c5460; }
        
        .notification-time {
            color: #999;
            font-size: 0.85rem;
        }
        
        .notification-content {
            color: #666;
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .empty-message { 
            text-align: center; 
            color: #999; 
            padding: 60px 20px; 
            font-size: 1.1rem; 
        }
        
        .empty-message i {
            font-size: 4rem;
            color: #ddd;
            display: block;
            margin-bottom: 20px;
        }
        
        .badge-new {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
        }
           body { background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); min-height: 100vh; color: #4A4A4A; font-family: 'Cairo', sans-serif; display: flex; }
        .main-container { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 900px; margin: 0 auto; }
        .main-box { margin-top: 40px; box-shadow: 0 6px 24px rgba(100, 100, 100, 0.10); border-radius: 16px; background: #fff; padding: 30px; }
        .page-header { font-size: 1.7rem; color: #C7346F; font-weight: 700; margin-bottom: 30px; text-align: center; }
        .page-header i { margin-left: 12px; font-size: 1.5rem; }
        .appointment-card { border: 1px solid #FDEEF0; border-radius: 12px; padding: 20px; margin-bottom: 15px; background: #FFFAFB; transition: all 0.3s; }
        .appointment-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .doctor-name { color: #ad1457; font-weight: 700; font-size: 1.15rem; }
        .appointment-info { color: #666; font-size: 0.95rem; margin: 8px 0; }
        .appointment-info i { margin-left: 8px; color: #E7AAB4; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-scheduled { background: #c3e6cb; color: #0f5132; }
        .status-confirmed { background: #cfe2ff; color: #084298; }
        .btn-cancel { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; font-weight: 600; padding: 0.4rem 0.8rem; font-size: 0.9rem; }
        .btn-cancel:hover { background-color: #f5c6cb; border-color: #f1b0b7; color: #721c24; }
        .empty-message { text-align: center; color: #999; padding: 40px 20px; font-size: 1.1rem; }
        .sidebar { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%); width: 250px; min-height: 100vh; padding: 20px; color: white; box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12); transition: all 0.3s; }
        .sidebar a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { color: white; transform: translateX(5px); }
        .sidebar .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255, 255, 255, 0.15); }
        .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar .logo i { color: var(--primary-pink); }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="main-container">
    <div class="dashboard-container">
        <div class="main-box">
            <div class="page-header">
                <i class="<?= $title_icon ?>"></i> الإخطارات والتذكيرات
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="empty-message">
                    <i class="bi bi-bell"></i>
                    <p>لا توجد إخطارات حالياً</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?= $notif['notification_type'] ?> <?= empty($notif['read_at']) ? 'unread' : '' ?>">
                        <div class="notification-header">
                            <div>
                                <span class="notification-type type-<?= $notif['notification_type'] ?>">
                                    <?php
                                    if ($notif['notification_type'] === 'confirmation') echo '✓ تأكيد الموعد';
                                    elseif ($notif['notification_type'] === 'reminder') echo '🔔 تذكير';
                                    elseif ($notif['notification_type'] === 'follow_up') echo 'ℹ متابعة';
                                    ?>
                                </span>
                                <?php if (empty($notif['read_at'])): ?>
                                    <span class="badge-new">جديد</span>
                                <?php endif; ?>
                            </div>
                            <span class="notification-time">
                                <?= date('d/m/Y H:i', strtotime($notif['sent_at'])) ?>
                            </span>
                        </div>
                        
                        <div style="margin: 15px 0; padding: 15px; background: #f9f9f9; border-radius: 8px; border-right: 3px solid #dc3545;">
                            <p style="color: #333; margin: 0; line-height: 1.6;">
                                <strong><?= htmlspecialchars($notif['child_name']) ?></strong> - 
                                <span style="color: #666;">الطبيب: <?= htmlspecialchars($notif['full_name']) ?></span>
                            </p>
                            <div style="margin-top: 8px; font-size: 0.9rem; color: #666;">
                                <i class="bi bi-calendar-event"></i> 
                                <?= date('d/m/Y', strtotime($notif['appointment_date'])) ?> - 
                                <?= date('H:i', strtotime($notif['appointment_date'])) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($notif['message_content'])): ?>
                            <div class="notification-content">
                                <?php echo $notif['message_content']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="notification-actions">
                            <a href="my_appointments.php" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> عرض المواعيد
                            </a>
                            <?php if (empty($notif['read_at'])): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-check-square"></i> وضع علامة مقروء
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
