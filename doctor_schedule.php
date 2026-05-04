<?php
/**
 * جدول الطبيب - إدارة الجدول الزمني
 * Doctor Schedule Management
 */

include 'includes/auth.php';
include 'includes/db.php';

// Checking the doctor's qualificationsصلاحيات
if ($_SESSION['user_type'] !== 'doctor') {
    header('Location: index.php');
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Get the doctor's schedule
$query = "SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY 
          CASE day_of_week 
            WHEN 'Saturday' THEN 1
            WHEN 'Sunday' THEN 2
            WHEN 'Monday' THEN 3
            WHEN 'Tuesday' THEN 4
            WHEN 'Wednesday' THEN 5
            WHEN 'Thursday' THEN 6
            WHEN 'Friday' THEN 7
          END";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get daily appointmentsالمواعيد
$today = date('Y-m-d');
$query = "SELECT 
            a.id,
            a.appointment_date,
            a.appointment_type,
            a.appointment_status,
            a.confirmation_status,
            c.name as child_name,
            a.reason_for_visit
          FROM appointments a
          JOIN children c ON a.child_id = c.id
          WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ?
          ORDER BY a.appointment_date";
$stmt = $conn->prepare($query);
$stmt->bind_param('is', $doctor_id, $today);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// الحصول على الطلبات المعلقة
$query = "SELECT 
            ar.id,
            ar.child_id,
            c.name as child_name,
            ar.appointment_type,
            ar.urgency_level,
            ar.created_at,
            ar.reason_for_visit
          FROM appointment_requests ar
          JOIN children c ON ar.child_id = c.id
          WHERE ar.doctor_id = ? AND ar.request_status IN ('pending', 'accepted')
          ORDER BY 
            CASE ar.urgency_level 
              WHEN 'emergency' THEN 1
              WHEN 'urgent' THEN 2
              WHEN 'routine' THEN 3
            END,
            ar.created_at";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جدولي - جدول الطبيب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .schedule-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .day-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .time-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 15px;
            margin-right: 10px;
            font-size: 0.9em;
        }
        .appointment-item {
            background: white;
            border-right: 4px solid #667eea;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-scheduled {
            background: #cfe2ff;
            color: #084298;
        }
        .status-confirmed {
            background: #d1e7dd;
            color: #0f5132;
        }
        .status-emergency {
            background: #f8d7da;
            color: #842029;
        }
        .request-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            border-right: 4px solid #ff9800;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .today-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-hospital"></i> نظام صحة الأطفال
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="doctor_schedule.php">
                            <i class="bi bi-calendar"></i> جدولي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctor_dashboard.php">
                            <i class="bi bi-graph-up"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> الملف الشخصي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-lg">
        <!-- إحصائيات اليوم -->
        <div class="today-stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($today_appointments) ?></div>
                <div class="stat-label">مواعيد اليوم</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($pending_requests) ?></div>
                <div class="stat-label">طلبات معلقة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count(array_filter($today_appointments, fn($a) => $a['confirmation_status'] === 'confirmed')) ?></div>
                <div class="stat-label">مؤكدة</div>
            </div>
        </div>

        <!-- المواعيد اليومية -->
        <div class="schedule-card">
            <h4 class="mb-3">
                <i class="bi bi-calendar-day"></i> مواعيد اليوم (<?= date('d/m/Y') ?>)
            </h4>
            <?php if (empty($today_appointments)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> لا توجد مواعيد محجوزة اليوم
                </div>
            <?php else: ?>
                <?php foreach ($today_appointments as $apt): ?>
                    <div class="appointment-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-2">
                                    <i class="bi bi-clock"></i> 
                                    <?= date('H:i', strtotime($apt['appointment_date'])) ?>
                                </h6>
                                <p class="mb-1">
                                    <strong>الطفل:</strong> <?= htmlspecialchars($apt['child_name']) ?>
                                </p>
                                <p class="mb-1">
                                    <strong>النوع:</strong> 
                                    <?php
                                    $types = ['check-up' => 'فحص دوري', 'follow-up' => 'متابعة', 'vaccination' => 'تطعيم', 'consultation' => 'استشارة', 'emergency' => 'طوارئ'];
                                    echo $types[$apt['appointment_type']] ?? $apt['appointment_type'];
                                    ?>
                                </p>
                                <?php if ($apt['reason_for_visit']): ?>
                                    <p class="mb-0 text-muted">
                                        <small><?= htmlspecialchars($apt['reason_for_visit']) ?></small>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $apt['appointment_status'] ?>">
                                    <?php
                                    $statuses = ['scheduled' => 'مجدول', 'confirmed' => 'مؤكد', 'completed' => 'مكتمل'];
                                    echo $statuses[$apt['appointment_status']] ?? $apt['appointment_status'];
                                    ?>
                                </span>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> تعديل
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- الطلبات المعلقة -->
        <?php if (!empty($pending_requests)): ?>
            <div class="schedule-card">
                <h4 class="mb-3">
                    <i class="bi bi-clock-history"></i> الطلبات المعلقة
                </h4>
                <?php foreach ($pending_requests as $request): ?>
                    <div class="request-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-2">
                                    <i class="bi bi-bell"></i> 
                                    <?= htmlspecialchars($request['child_name']) ?>
                                </h6>
                                <p class="mb-1">
                                    <strong>النوع:</strong> 
                                    <?php
                                    $types = ['check-up' => 'فحص دوري', 'follow-up' => 'متابعة', 'vaccination' => 'تطعيم', 'consultation' => 'استشارة', 'emergency' => 'طوارئ'];
                                    echo $types[$request['appointment_type']] ?? $request['appointment_type'];
                                    ?>
                                </p>
                                <p class="mb-0 text-muted">
                                    <small><?= htmlspecialchars($request['reason_for_visit']) ?></small>
                                </p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning">
                                    <?php
                                    $urgency = [
                                        'routine' => 'عادي',
                                        'urgent' => 'عاجل',
                                        'emergency' => 'طوارئ'
                                    ];
                                    echo $urgency[$request['urgency_level']] ?? $request['urgency_level'];
                                    ?>
                                </span>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-success" onclick="acceptRequest(<?= $request['id'] ?>)">
                                        <i class="bi bi-check"></i> قبول
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?= $request['id'] ?>)">
                                        <i class="bi bi-x"></i> رفض
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- جدول أيام العمل -->
        <div class="schedule-card">
            <h4 class="mb-3">
                <i class="bi bi-calendar-check"></i> جدول أيام العمل
            </h4>
            <?php if (empty($schedules)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> لم تقم بتعيين جدول عمل بعد. <a href="#" onclick="setupSchedule()">قم بالإعداد الآن</a>
                </div>
            <?php else: ?>
                <?php foreach ($schedules as $schedule): ?>
                    <div class="schedule-card-inner">
                        <div class="day-badge"><?= $schedule['day_of_week'] ?></div>
                        <div>
                            <span class="time-badge">
                                <i class="bi bi-clock"></i> 
                                <?= date('H:i', strtotime($schedule['start_time'])) ?> - 
                                <?= date('H:i', strtotime($schedule['end_time'])) ?>
                            </span>
                            <?php if ($schedule['break_start']): ?>
                                <span class="time-badge">
                                    <i class="bi bi-pause-circle"></i> استراحة: 
                                    <?= date('H:i', strtotime($schedule['break_start'])) ?> - 
                                    <?= date('H:i', strtotime($schedule['break_end'])) ?>
                                </span>
                            <?php endif; ?>
                            <span class="time-badge">
                                <i class="bi bi-hourglass"></i> مدة الجلسة: <?= $schedule['slot_duration_minutes'] ?> دقيقة
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function acceptRequest(requestId) {
            if (confirm('هل تريد قبول هذا الطلب؟')) {
                // قبول الطلب - يمكن فتح نموذج لاختيار موعد متاح
                alert('تم قبول الطلب. يمكنك الآن اختيار موعد متاح.');
            }
        }

        function rejectRequest(requestId) {
            const reason = prompt('أدخل سبب الرفض:');
            if (reason !== null) {
                // رفض الطلب مع السبب
                alert('تم رفض الطلب.');
            }
        }

        function setupSchedule() {
            // يمكن فتح نموذج لإعداد جدول العمل
            alert('سيتم توجيهك لنموذج إعداد جدول العمل');
        }
    </script>
</body>
</html>
