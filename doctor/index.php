<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bring the statistics to the doctor
$query_patients = "SELECT COUNT(DISTINCT child_id) as total FROM medical_visits WHERE doctor_id = ?";
$stmt = $conn->prepare($query_patients);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total'];

$query_visits = "SELECT COUNT(*) as total FROM medical_visits WHERE doctor_id = ?";
$stmt = $conn->prepare($query_visits);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$total_visits = $stmt->get_result()->fetch_assoc()['total'];

$query_today_visits = "SELECT COUNT(*) as total FROM medical_visits WHERE doctor_id = ? AND DATE(visit_date) = CURDATE()";
$stmt = $conn->prepare($query_today_visits);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$today_visits = $stmt->get_result()->fetch_assoc()['total'];

$query_upcoming_appointments = "SELECT COUNT(*) as total FROM medical_visits WHERE doctor_id = ? AND visit_date > NOW() AND visit_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($query_upcoming_appointments);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_assoc()['total'];

$query_consultations = "SELECT COUNT(*) as total FROM messages WHERE recipient_id = ? AND message_type = 'consultation'";
$stmt = $conn->prepare($query_consultations);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_consultations = $stmt->get_result()->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>

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

    <!--Main Content-->
    <main class="main-content">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(13, 110, 253, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #0d6efd;"><?php echo $total_patients; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">المرضى</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(13, 110, 253, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #0d6efd;"><?php echo $total_visits; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">إجمالي الزيارات</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(25, 135, 84, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #198754;"><?php echo $today_visits; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">زيارات اليوم</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(255, 193, 7, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?php echo $upcoming_appointments; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">مواعيد الأسبوع</div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(220, 53, 69, 0.08);">
                <div style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $pending_consultations; ?></div>
                <div style="color: #7a6880; margin-top: 5px;">استشارات معلقة</div>
            </div>
        </div>

        <h1>مرحباً بك في لوحة تحكم الطبيب</h1>
        <p>إدارة المرضى والرعاية الطبية الشاملة</p>

        <!-- Data charts-->
        <div class="chart-container" style="margin-top:30px; display:flex; gap:40px; flex-wrap:wrap;">
            <div style="flex:1; min-width:300px;">
                <canvas id="visitsChart"></canvas>
            </div>
            <div style="flex:1; min-width:300px;">
                <canvas id="consultationsChart"></canvas>
            </div>
        </div>

        <script>
            // Data for the charts
            const doctorData = {
                totalPatients: <?php echo json_encode($total_patients); ?>,
                totalVisits: <?php echo json_encode($total_visits); ?>,
                todayVisits: <?php echo json_encode($today_visits); ?>,
                upcomingAppointments: <?php echo json_encode($upcoming_appointments); ?>,
                pendingConsultations: <?php echo json_encode($pending_consultations); ?>
            };

            // Visit Graph
            const visitsCtx = document.getElementById('visitsChart').getContext('2d');
            new Chart(visitsCtx, {
                type: 'bar',
                data: {
                    labels: ['المرضى', 'إجمالي الزيارات', 'زيارات اليوم', 'مواعيد الأسبوع'],
                    datasets: [{
                        label: 'الإحصائيات',
                        data: [doctorData.totalPatients, doctorData.totalVisits, doctorData.todayVisits, doctorData.upcomingAppointments],
                        backgroundColor: [
                            '#0d6efd',
                            '#198754',
                            '#ffc107',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'إحصائيات الطبيب'
                        }
                    }
                }
            });

            //  الاستشاراتGraph
            const consultationsCtx = document.getElementById('consultationsChart').getContext('2d');
            new Chart(consultationsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['استشارات معلقة', 'استشارات منجزة'],
                    datasets: [{
                        data: [doctorData.pendingConsultations, doctorData.totalVisits - doctorData.pendingConsultations],
                        backgroundColor: ['#dc3545', '#198754']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'حالة الاستشارات'
                        }
                    }
                }
            });
        </script>
    </main>
</body>
</html>