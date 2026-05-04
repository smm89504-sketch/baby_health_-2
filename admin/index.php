<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
require_once '../notification_system.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

//Enable automatic notifications when the page is opened (once a day)
$today = date('Y-m-d');
$last_run_file = '../logs/last_notification_run.txt';

$run_notifications = false;
if (!file_exists($last_run_file) || file_get_contents($last_run_file) !== $today) {
    $run_notifications = true;
    file_put_contents($last_run_file, $today);
}

if ($run_notifications) {
    try {
        $notifier = new NotificationManager();
        $notifier->runAllChecks();
    } catch (Exception $e) {
        //The error was recorded in the log file.
        error_log("خطأ في تشغيل الإشعارات: " . $e->getMessage());
    }
}

// Get the statistics
$query_users = "SELECT COUNT(*) as total, user_type FROM users GROUP BY user_type";
$result_users = $conn->query($query_users);
$user_stats = [];
while ($row = $result_users->fetch_assoc()) {
    $user_stats[$row['user_type']] = $row['total'];
}

$query_children = "SELECT COUNT(*) as total FROM children";
$result_children = $conn->query($query_children);
$total_children = $result_children->fetch_assoc()['total'];

$query_vaccines = "SELECT COUNT(*) as total FROM child_vaccines WHERE status = 'due'";
$result_vaccines = $conn->query($query_vaccines);
$due_vaccines = $result_vaccines->fetch_assoc()['total'];

//New: Late and due payments within a week
$query_overdue = "SELECT COUNT(*) as total FROM child_vaccines WHERE status='due' AND due_date < CURDATE()";
$result_overdue = $conn->query($query_overdue);
$overdue_vaccines = $result_overdue->fetch_assoc()['total'];

$query_upcoming = "SELECT COUNT(*) as total FROM child_vaccines WHERE status='due' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$result_upcoming = $conn->query($query_upcoming);
$upcoming_vaccines = $result_upcoming->fetch_assoc()['total'];

// New: Breastfeeding jobs
$query_feed = "SELECT COUNT(*) as total FROM feeding_schedule WHERE scheduled_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)";
$result_feed = $conn->query($query_feed);
$due_feed = $result_feed->fetch_assoc()['total'];

// New: Sleep statistics
$query_sleep = "SELECT AVG(TIMESTAMPDIFF(HOUR,start_datetime,end_datetime)) as avg_sleep_hours FROM sleep_records";
$result_sleep = $conn->query($query_sleep);
$avg_sleep = round($result_sleep->fetch_assoc()['avg_sleep_hours'],1);

$parents_count = $user_stats['parent'] ?? 0;
$nurses_count = $user_stats['nurse'] ?? 0;
$doctors_count = $user_stats['doctor'] ?? 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الإدمن</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- top strip-->
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>🛡️ الإدمن</h1>
                <span class="badge">v1.0</span>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'إدمن'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!--Main Content-->
    <main class="main-content">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(157, 132, 202, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #9d84ca;"><?php echo $parents_count; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">الآباء والأمهات</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(157, 132, 202, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #9d84ca;"><?php echo $nurses_count; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">الممرضات</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(157, 132, 202, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #9d84ca;"><?php echo $doctors_count; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">الأطباء</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(157, 132, 202, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #9d84ca;"><?php echo $total_children; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">الأطفال</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(157, 132, 202, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #f59e0b;"><?php echo $due_vaccines; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">تطعيمات مستحقة</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(229, 62, 62, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #e53e3e;"><?php echo $overdue_vaccines; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">تطعيمات متأخرة</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(56, 161, 105, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #3182ce;"><?php echo $upcoming_vaccines; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">تطعيمات خلال أسبوع</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(128, 90, 213, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #805ad5;"><?php echo $due_feed; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">مواعيد رضاعة اليوم</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(56, 161, 105, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #38a169;"><?php echo $avg_sleep; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">ساعات نوم متوسط</div>
            </div>
        </div>
          <h1>مرحباً بك في لوحة التحكم</h1>
 

        <!-- Data charts-->
        <div class="chart-container" style="margin-top:30px; display:flex; gap:40px; flex-wrap:wrap;">
            <div style="flex:1; min-width:300px;">
                <canvas id="usersChart"></canvas>
            </div>
            <div style="flex:1; min-width:300px;">
                <canvas id="vaccinesChart"></canvas>
            </div>
        </div>
        <script>
            const dashboardData = {
                userStats: <?php echo json_encode($user_stats); ?>,
                totalChildren: <?php echo json_encode($total_children); ?>,
                dueVaccines: <?php echo json_encode($due_vaccines); ?>,
                overdueVaccines: <?php echo json_encode($overdue_vaccines); ?>,
                upcomingVaccines: <?php echo json_encode($upcoming_vaccines); ?>,
                dueFeed: <?php echo json_encode($due_feed); ?>,
                avgSleep: <?php echo json_encode($avg_sleep); ?>
            };
        </script>

         </main>

    <script src="admin_shared.js"></script>
</body>
</html>
