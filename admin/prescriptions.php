<?php
session_start();

// Checking permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Get the statistics
$active_prescriptions = $conn->query("
    SELECT COUNT(*) as count FROM prescriptions 
    WHERE status = 'active'
")->fetch_assoc()['count'];

$expiring_prescriptions = $conn->query("
    SELECT COUNT(*) as count FROM prescriptions 
    WHERE status = 'active' 
    AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetch_assoc()['count'];

$expired_prescriptions = $conn->query("
    SELECT COUNT(*) as count FROM prescriptions 
    WHERE status = 'active' 
    AND expiry_date < CURDATE()
")->fetch_assoc()['count'];

$total_notifications = $conn->query("
    SELECT COUNT(*) as count FROM prescription_renewal_notifications
")->fetch_assoc()['count'];

$unread_notifications = $conn->query("
    SELECT COUNT(*) as count FROM prescription_renewal_notifications
    WHERE is_read = 0
")->fetch_assoc()['count'];

// جلب الوصفات قريبة الانتهاء
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.child_id,
        c.name as child_name,
        p.doctor_id,
        u.full_name as doctor_name,
        p.prescription_date,
        p.expiry_date,
        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
        p.status,
        COUNT(pm.id) as medication_count,
        COUNT(prn.id) as notification_count
    FROM prescriptions p
    JOIN children c ON p.child_id = c.id
    JOIN users u ON p.doctor_id = u.id
    LEFT JOIN prescription_medications pm ON p.id = pm.prescription_id
    LEFT JOIN prescription_renewal_notifications prn ON p.id = prn.prescription_id
    WHERE p.status = 'active'
    GROUP BY p.id
    ORDER BY p.expiry_date ASC
    LIMIT 20
");
$stmt->execute();
$recently_expiring = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الوصفات - لوحة التحكم</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-top: 4px solid #667eea;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.warning {
            border-top-color: #f39c12;
        }

        .stat-card.danger {
            border-top-color: #e74c3c;
        }

        .stat-card.success {
            border-top-color: #27ae60;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #999;
            font-size: 14px;
        }

        .table-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .table-header h2 {
            color: #333;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9f9f9;
            padding: 15px;
            text-align: right;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #eee;
            font-size: 14px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #555;
            font-size: 14px;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.active {
            background: #d4edda;
            color: #155724;
        }

        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-view:hover {
            background: #2980b9;
        }

        .btn-manage {
            background: #667eea;
            color: white;
        }

        .btn-manage:hover {
            background: #5568d3;
        }

        .empty-message {
            padding: 40px;
            text-align: center;
            color: #999;
        }

        .cron-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .cron-section h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .cron-info {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 5px;
            border-right: 4px solid #667eea;
            margin-bottom: 15px;
        }

        .cron-info code {
            background: white;
            padding: 8px 12px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            display: block;
            margin-top: 10px;
            direction: ltr;
        }

        .cron-btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .cron-btn:hover {
            background: #229954;
        }

        .notification-summary {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 5px;
            border-right: 4px solid #667eea;
            margin-bottom: 20px;
        }

        .notification-summary strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 إدارة الوصفات الطبية</h1>
            <p>لوحة تحكم شاملة لإدارة الوصفات والإشعارات</p>
        </div>

        <!--Statistics-->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $active_prescriptions; ?></div>
                <div class="stat-label">وصفات نشطة</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?php echo $expiring_prescriptions; ?></div>
                <div class="stat-label">قريبة الانتهاء</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">❌</div>
                <div class="stat-value"><?php echo $expired_prescriptions; ?></div>
                <div class="stat-label">منتهية الصلاحية</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">🔔</div>
                <div class="stat-value"><?php echo $unread_notifications; ?></div>
                <div class="stat-label">إشعارات غير مقروءة</div>
            </div>
        </div>

      

        <!-- Recipes nearing completion-->
        <div class="table-section">
            <div class="table-header">
                <h2>📅 الوصفات قريبة الانتهاء</h2>
                <p style="color: #999; font-size: 14px; margin-top: 5px;">آخر 20 وصفة نشطة مرتبة حسب تاريخ الانتهاء</p>
            </div>

            <?php if (empty($recently_expiring)): ?>
                <div class="empty-message">
                    <p>👍 لا توجد وصفات قريبة الانتهاء في الوقت الحالي</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الطفل</th>
                            <th>الطبيب</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الوقت المتبقي</th>
                            <th>الأدوية</th>
                            <th>الإشعارات</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recently_expiring as $prescription): ?>
                            <?php
                            $days_left = $prescription['days_until_expiry'];
                            $badge_class = 'active';
                            $status_text = '✅ نشطة';

                            if ($days_left < 0) {
                                $badge_class = 'danger';
                                $status_text = '❌ منتهية';
                            } elseif ($days_left <= 7) {
                                $badge_class = 'warning';
                                $status_text = '⚠️ قريبة';
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($prescription['child_name']); ?></strong><br>
                                    <span style="color: #999; font-size: 12px;">ID: <?php echo $prescription['child_id']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($prescription['doctor_name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($prescription['expiry_date'])); ?></td>
                                <td>
                                    <?php if ($days_left > 0): ?>
                                        <span style="color: #27ae60;">✅ <?php echo $days_left; ?> يوم</span>
                                    <?php elseif ($days_left == 0): ?>
                                        <span style="color: #e74c3c; font-weight: 600;">🔴 اليوم!</span>
                                    <?php else: ?>
                                        <span style="color: #e74c3c;">❌ منذ <?php echo abs($days_left); ?> يوم</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background: #e6ffe6; color: #27ae60;">
                                        <?php echo $prescription['medication_count']; ?> أدوية
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background: #f0f4ff; color: #667eea;">
                                        <?php echo $prescription['notification_count']; ?>
                                    </span>
                                </td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></td>
                                <td>
                                    <a href="../prescriptions.php?child_id=<?php echo $prescription['child_id']; ?>" class="action-btn btn-view">عرض</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!--notifications-->
        <div class="table-section" style="margin-top: 25px;">
            <div class="table-header">
                <h2>🔔 آخر الإشعارات المرسلة</h2>
            </div>

            <?php
            $stmt = $conn->prepare("
                SELECT 
                    prn.id,
                    prn.prescription_id,
                    prn.notification_type,
                    prn.notification_sent,
                    prn.is_read,
                    c.name as child_name,
                    CONCAT(u.full_name) as parent_name,
                    p.expiry_date
                FROM prescription_renewal_notifications prn
                JOIN prescriptions p ON prn.prescription_id = p.id
                JOIN children c ON p.child_id = c.id
                JOIN users u ON prn.parent_id = u.id
                ORDER BY prn.notification_sent DESC
                LIMIT 15
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            ?>

            <?php if (empty($notifications)): ?>
                <div class="empty-message">
                    <p>📭 لم يتم إرسال أي إشعارات بعد</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الطفل</th>
                            <th>الوالد</th>
                            <th>النوع</th>
                            <th>تاريخ الإرسال</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notif): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($notif['child_name']); ?></td>
                                <td><?php echo htmlspecialchars($notif['parent_name']); ?></td>
                                <td>
                                    <?php
                                    $type_emoji = [
                                        'email' => '📧',
                                        'sms' => '💬',
                                        'in_app' => '🔔'
                                    ];
                                    echo ($type_emoji[$notif['notification_type']] ?? '') . ' ' . $notif['notification_type'];
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($notif['notification_sent'] ?? 'now')); ?></td>
                                <td>
                                    <span class="badge <?php echo $notif['is_read'] ? 'success' : 'warning'; ?>">
                                        <?php echo $notif['is_read'] ? '✅ مقروء' : '📬 جديد'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function runCronJob() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ جاري التنفيذ...';

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?run_cron=1')
                .then(response => response.json())
                .then(data => {
                    alert(`✅ تم التنفيذ بنجاح!\n\nعدد الإشعارات المرسلة: ${data.notifications_sent}\nعدد الوصفات المفحوصة: ${data.prescriptions_checked}`);
                    btn.disabled = false;
                    btn.textContent = '▶️ تشغيل الآن';
                    location.reload();
                })
                .catch(error => {
                    alert('❌ حدث خطأ: ' + error.message);
                    btn.disabled = false;
                    btn.textContent = '▶️ تشغيل الآن';
                });
        }
    </script>
</body>
</html>
