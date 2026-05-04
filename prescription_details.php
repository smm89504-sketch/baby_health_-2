<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$prescription_id = intval($_GET['id'] ?? 0);
$child_id = intval($_GET['child_id'] ?? 0);

if (!$prescription_id || !$child_id) {
    header('Location: prescriptions.php?child_id=' . $child_id);
    exit;
}

// جلب بيانات الوصفة
$stmt = $conn->prepare("
    SELECT p.*, 
           c.name as child_name,
           u.full_name as doctor_name,
           u.email as doctor_email,
           u.phone as doctor_phone,
           DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry
    FROM prescriptions p
    JOIN children c ON p.child_id = c.id
    JOIN users u ON p.doctor_id = u.id
    WHERE p.id = ? AND p.child_id = ?
");
$stmt->bind_param('ii', $prescription_id, $child_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: prescriptions.php?child_id=' . $child_id);
    exit;
}

$prescription = $result->fetch_assoc();
$stmt->close();

// التحقق من صلاحيات الوصول
if ($_SESSION['user_type'] === 'parent') {
    $stmt = $conn->prepare("SELECT id FROM children WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $child_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header('Location: index.php');
        exit;
    }
    $stmt->close();
}

// جلب الأدوية الموصوفة
$stmt = $conn->prepare("
    SELECT pm.*, m.name as medication_name
    FROM prescription_medications pm
    JOIN medications m ON pm.medication_id = m.id
    WHERE pm.prescription_id = ?
    ORDER BY pm.id
");
$stmt->bind_param('i', $prescription_id);
$stmt->execute();
$result = $stmt->get_result();
$medications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// إعداد الألوان بناءً على نوع المستخدم
$user_type = $_SESSION['user_type'] ?? 'parent';
if ($user_type === 'doctor') {
    $main_dark = '#842029';
    $main_text = '#dc3545';
    $main_light = '#f5c6cb';
    $main_deep = '#f1aeb5';
    $bg_light = '#f8d7da';
    $title_icon = 'fas fa-stethoscope';
} else {
    $main_dark = '#ad1457';
    $main_text = '#880e4f';
    $main_light = '#ffd1dc';
    $main_deep = '#f8bbd0';
    $bg_light = '#fff0f5';
    $title_icon = 'fas fa-file-medical';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الوصفة الطبية</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-dark: <?= $main_dark ?>;
            --primary-text: <?= $main_text ?>;
            --primary-light: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --bg-light: <?= $bg_light ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, #ffeef3 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary-text);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .header-subtitle {
            color: #666;
            font-size: 0.95rem;
        }

        .header-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-back {
            background: #f0f0f0;
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #e0e0e0;
        }

        .main-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--primary-text);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
            font-weight: 700;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-card {
            background: linear-gradient(135deg, rgba(194, 24, 40, 0.05), rgba(255, 182, 193, 0.1));
            border-left: 4px solid var(--primary-text);
            padding: 15px;
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-expiring {
            background: #fff3cd;
            color: #856404;
        }

        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }

        .medications-list {
            list-style: none;
            padding: 0;
        }

        .medications-list li {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-light);
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 6px;
        }

        .medication-name {
            font-weight: 700;
            color: var(--primary-text);
            font-size: 1.05rem;
            margin-bottom: 8px;
        }

        .medication-details {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.6;
        }

        .doctor-info {
            background: linear-gradient(135deg, var(--primary-text) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .doctor-info h4 {
            margin-bottom: 15px;
            font-weight: 700;
        }

        .doctor-info p {
            margin: 8px 0;
            font-size: 0.95rem;
        }

        .notes-section {
            background: #fffbea;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .notes-section strong {
            color: #333;
            display: block;
            margin-bottom: 8px;
        }

        .notes-section p {
            color: #666;
            margin: 0;
            line-height: 1.6;
        }

        .print-btn {
            background: var(--primary-text);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .print-btn:hover {
            background: var(--primary-dark);
        }

        @media print {
            body {
                background: white;
            }
            .header-buttons,
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="bi bi-file-medical"></i> تفاصيل الوصفة الطبية</h1>
                <p class="header-subtitle">👶 <?= htmlspecialchars($prescription['child_name']) ?></p>
            </div>
         
        </div>

        <div class="main-box">
            <!-- الحالة -->
            <?php
            $status_class = '';
            $status_text = '';
            if ($prescription['status'] === 'active') {
                if ($prescription['days_until_expiry'] < 0) {
                    $status_class = 'status-expired';
                    $status_text = '❌ منتهية الصلاحية';
                } elseif ($prescription['days_until_expiry'] <= 7) {
                    $status_class = 'status-expiring';
                    $status_text = '⚠️ قريبة الانتهاء';
                } else {
                    $status_class = 'status-active';
                    $status_text = '✅ نشطة';
                }
            } else {
                $status_class = 'status-expired';
                $status_text = '❌ ملغاة';
            }
            ?>
            <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>

            <!-- معلومات الوصفة الأساسية -->
            <div class="section-title">📋 معلومات الوصفة</div>
            <div class="info-row">
                <div class="info-card">
                    <div class="info-label">📅 تاريخ الكتابة</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($prescription['prescription_date'])) ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">📅 تاريخ الانتهاء</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($prescription['expiry_date'])) ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">⏳ الصلاحية المتبقية</div>
                    <div class="info-value">
                        <?php if ($prescription['days_until_expiry'] >= 0): ?>
                            <?= $prescription['days_until_expiry'] ?> يوم
                        <?php else: ?>
                            <span style="color: #dc3545;">منتهية</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- الأدوية الموصوفة -->
            <div class="section-title">💊 الأدوية الموصوفة</div>
            <?php if (!empty($medications)): ?>
                <ul class="medications-list">
                    <?php foreach ($medications as $med): ?>
                        <li>
                            <div class="medication-name"><?= htmlspecialchars($med['medication_name']) ?></div>
                            <div class="medication-details">
                                <strong>الجرعة:</strong> <?= htmlspecialchars($med['dosage']) ?> <br>
                                <strong>التكرار:</strong> <?= htmlspecialchars($med['frequency']) ?> <br>
                                <?php if ($med['duration_days']): ?>
                                    <strong>مدة الصلاحية:</strong> <?= $med['duration_days'] ?> يوم <br>
                                <?php endif; ?>
                                <?php if ($med['notes']): ?>
                                    <strong>ملاحظات:</strong> <?= htmlspecialchars($med['notes']) ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">لا توجد أدوية موصوفة</p>
            <?php endif; ?>

            <!-- ملاحظات الطبيب -->
            <?php if ($prescription['notes']): ?>
                <div class="notes-section">
                    <strong>📌 ملاحظات الطبيب:</strong>
                    <p><?= htmlspecialchars($prescription['notes']) ?></p>
                </div>
            <?php endif; ?>

            <!-- معلومات الطبيب -->
            <div class="doctor-info">
                <h4>👨‍⚕️ بيانات الطبيب</h4>
                <p><strong>الاسم:</strong> <?= htmlspecialchars($prescription['doctor_name']) ?></p>
                <?php if ($prescription['doctor_email']): ?>
                    <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($prescription['doctor_email']) ?></p>
                <?php endif; ?>
                <?php if ($prescription['doctor_phone']): ?>
                    <p><strong>الهاتف:</strong> <?= htmlspecialchars($prescription['doctor_phone']) ?></p>
                <?php endif; ?>
            </div>

            <button class="print-btn" onclick="window.print();">🖨️ طباعة</button>
        </div>
    </div>
</body>
</html>
