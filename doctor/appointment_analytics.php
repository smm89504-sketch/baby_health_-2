<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Analysis period
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// General statistics
$stats_sql = "SELECT 
    COUNT(*) as total_appointments,
    SUM(CASE WHEN appointment_status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN appointment_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN appointment_status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN appointment_status = 'completed' THEN 1 ELSE 0 END) as attended,
    SUM(CASE WHEN appointment_status = 'cancelled' THEN 1 ELSE 0 END) as no_show,
    SUM(CASE WHEN appointment_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM appointments 
WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ? AND appointment_status != 'cancelled'";

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param('iss', $_SESSION['user_id'], $start_date, $end_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// الحضورAttendance rate
$attendance_rate = ($stats['total_appointments'] > 0) ? 
    round(($stats['completed'] / $stats['total_appointments']) * 100, 1) : 0;

// المواعيدAppointments by day of the week
$daily_sql = "SELECT 
    DATE_FORMAT(appointment_date, '%w') as dow,
    DATE_FORMAT(appointment_date, '%W') as day_name,
    COUNT(*) as count
FROM appointments 
WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ? AND appointment_status != 'cancelled'
GROUP BY DATE_FORMAT(appointment_date, '%w')
ORDER BY dow";

$stmt = $conn->prepare($daily_sql);
$stmt->bind_param('iss', $_SESSION['user_id'], $start_date, $end_date);
$stmt->execute();
$daily_result = $stmt->get_result();
$daily_data = [];
while ($row = $daily_result->fetch_assoc()) {
    $daily_data[] = $row;
}
$stmt->close();

//ازدحاما Busiest hours
$busy_hours_sql = "SELECT 
    DATE_FORMAT(appointment_date, '%H:00') as hour,
    COUNT(*) as count
FROM appointments 
WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ? AND appointment_status != 'cancelled'
GROUP BY HOUR(appointment_date)
ORDER BY count DESC
LIMIT 5";

$stmt = $conn->prepare($busy_hours_sql);
$stmt->bind_param('iss', $_SESSION['user_id'], $start_date, $end_date);
$stmt->execute();
$busy_result = $stmt->get_result();
$busy_hours = [];
while ($row = $busy_result->fetch_assoc()) {
    $busy_hours[] = $row;
}
$stmt->close();

// التقييمات - محاولة جلب من جدول appointment_ratings إن وجد
$ratings = ['avg_rating' => 0, 'total_ratings' => 0];
$ratings_sql = "SELECT 
    ROUND(AVG(rating), 1) as avg_rating,
    COUNT(id) as total_ratings
FROM appointment_ratings 
WHERE appointment_id IN (
    SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
)";

$stmt = $conn->prepare($ratings_sql);
if ($stmt) {
    $stmt->bind_param('iss', $_SESSION['user_id'], $start_date, $end_date);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $ratings = $result->fetch_assoc() ?: ['avg_rating' => 0, 'total_ratings' => 0];
    }
    $stmt->close();
}

// لMost booked parents  الأكثر حجزاً
$frequent_parents_sql = "SELECT 
    u.full_name,
    COUNT(*) as count
FROM appointments a
JOIN children c ON a.child_id = c.id
JOIN users u ON c.user_id = u.id
WHERE a.doctor_id = ? AND a.appointment_date BETWEEN ? AND ? AND a.appointment_status != 'cancelled'
GROUP BY u.id
ORDER BY count DESC
LIMIT 5";

$stmt = $conn->prepare($frequent_parents_sql);
$stmt->bind_param('iss', $_SESSION['user_id'], $start_date, $end_date);
$stmt->execute();
$frequent_result = $stmt->get_result();
$frequent_parents = [];
while ($row = $frequent_result->fetch_assoc()) {
    $frequent_parents[] = $row;
}
$stmt->close();

// Monthly data
$monthly_sql = "SELECT 
    DATE_FORMAT(appointment_date, '%Y-%m') as month,
    COUNT(*) as total,
    SUM(CASE WHEN appointment_status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN appointment_status = 'cancelled' THEN 1 ELSE 0 END) as no_show
FROM appointments 
WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?
GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
ORDER BY month DESC
LIMIT 12";

$stmt = $conn->prepare($monthly_sql);
$stmt->bind_param('iss', $_SESSION['user_id'], $start_date, $end_date);
$stmt->execute();
$monthly_result = $stmt->get_result();
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}
$stmt->close();

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
    <title>تحليلات المواعيد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }
        body { 
            background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); 
            min-height: 100vh; 
            color: #4A4A4A; 
            font-family: 'Cairo', sans-serif; 
            display: block;
        }
        .main-container { padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 1220px; margin: 0 auto; }
        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-top: 4px solid #dc3545;
        }
        .stat-number { 
            font-size: 2.5rem; 
            font-weight: 700; 
            color: #c62828;
            line-height: 1;
        }
        .stat-label { 
            color: #666; 
            font-size: 0.95rem;
            margin-top: 8px;
        }
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-title {
            color: #c62828;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        .table-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .rating-stars {
            color: #ffc107;
            font-size: 1.5rem;
        }
        .progress-bar {
            background-color: #dc3545;
        }
        .filter-section {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="main-content">
    <div class="dashboard-container">
        <div class="main-box">
            <div style="margin-bottom: 30px;">
                <h1 class="page-header">
                    <i class="<?= $title_icon ?>"></i> تحليلات المواعيد
                </h1>
            
            <!-- Date filters-->
            <div class="filter-section">
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">من:</label>
                        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">إلى:</label>
                        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> تحديث
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Key statistical cards-->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_appointments'] ?? 0 ?></div>
                    <div class="stat-label">إجمالي المواعيد</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['completed'] ?? 0 ?></div>
                    <div class="stat-label">مواعيد مكتملة</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $attendance_rate ?>%</div>
                    <div class="stat-label">معدل الحضور</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-number">
                        <span class="rating-stars">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                echo ($i <= round($ratings['avg_rating'] ?? 0)) ? '★' : '☆';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="stat-label">متوسط التقييم (<?= $ratings['total_ratings'] ?? 0 ?> تقييم)</div>
                </div>
            </div>
        </div>
        
        <!-- graphs-->
        <div class="row">
            <!--  المواعيدAppointment cases-->
            <div class="col-lg-6">
                <div class="chart-box">
                    <div class="chart-title"><i class="bi bi-pie-chart"></i> توزيع حالات المواعيد</div>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <!-- الساعات الازدحام -->
            <div class="col-lg-6">
                <div class="chart-box">
                    <div class="chart-title"><i class="bi bi-bar-chart"></i> أكثر الساعات ازدحاماً</div>
                    <canvas id="busyHoursChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Days-->
        <div class="row">
            <div class="col-lg-12">
                <div class="chart-box">
                    <div class="chart-title"><i class="bi bi-calendar-week"></i> المواعيد حسب يوم الأسبوع</div>
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Detail tables-->
        <div class="row">
            <!-- Most booked parents-->
            <div class="col-lg-6">
                <div class="table-box">
                    <div class="chart-title"><i class="bi bi-people"></i> الآباء الأكثر حجزاً</div>
                    <table class="table table-sm table-hover">
                        <thead style="background: #f8d7da;">
                            <tr>
                                <th>الاسم</th>
                                <th style="text-align: center;">عدد المواعيد</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($frequent_parents as $parent): ?>
                                <tr>
                                    <td><?= htmlspecialchars($parent['full_name']) ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge bg-primary"><?= $parent['count'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Monthly data-->
            <div class="col-lg-6">
                <div class="table-box">
                    <div class="chart-title"><i class="bi bi-calendar"></i> الإحصائيات الشهرية</div>
                    <table class="table table-sm table-hover">
                        <thead style="background: #f8d7da;">
                            <tr>
                                <th>الشهر</th>
                                <th style="text-align: center;">إجمالي</th>
                                <th style="text-align: center;">مكتمل</th>
                                <th style="text-align: center;">لم يحضر</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_data as $month): ?>
                                <tr>
                                    <td><?= date('m/Y', strtotime($month['month'] . '-01')) ?></td>
                                    <td style="text-align: center;"><strong><?= $month['total'] ?></strong></td>
                                    <td style="text-align: center;">
                                        <span class="badge bg-success"><?= $month['completed'] ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge bg-danger"><?= $month['no_show'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Graph data
const statusData = {
    labels: ['مكتمل', 'مؤكد', 'قيد الانتظار'],
    datasets: [{
        data: [<?= $stats['completed'] ?? 0 ?>, <?= $stats['confirmed'] ?? 0 ?>, <?= $stats['scheduled'] ?? 0 ?>],
        backgroundColor: ['#28a745', '#17a2b8', '#ffc107']
    }]
};

const busyHoursData = {
    labels: [<?php echo implode(',', array_map(function($h) { return '"' . $h['hour'] . '"'; }, $busy_hours)); ?>],
    datasets: [{
        label: 'عدد المواعيد',
        data: [<?php echo implode(',', array_map(function($h) { return $h['count']; }, $busy_hours)); ?>],
        backgroundColor: '#dc3545'
    }]
};

const dailyData = {
    labels: [<?php echo implode(',', array_map(function($d) { return '"' . $d['day_name'] . '"'; }, $daily_data)); ?>],
    datasets: [{
        label: 'عدد المواعيد',
        data: [<?php echo implode(',', array_map(function($d) { return $d['count']; }, $daily_data)); ?>],
        borderColor: '#dc3545',
        backgroundColor: 'rgba(220, 53, 69, 0.1)',
        tension: 0.4
    }]
};

//Drawing graphs
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: statusData,
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

new Chart(document.getElementById('busyHoursChart'), {
    type: 'bar',
    data: busyHoursData,
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: dailyData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
