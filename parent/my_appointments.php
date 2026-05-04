<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bring appointments to parents
$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'upcoming'; // upcoming أو past

//Search and filter
$search = $_GET['search'] ?? '';
$search = trim($search);

if ($tab === 'past') {
    $query = "SELECT a.*, 
              c.name as child_name, 
              u.full_name as doctor_name, u.email as doctor_email, u.phone as doctor_phone,
              CASE 
                WHEN a.appointment_status = 'completed' THEN 'مكتمل'
                WHEN a.appointment_status = 'cancelled' THEN 'ملغى'
                ELSE 'غير محدد'
              END as status_text
              FROM appointments a
              JOIN children c ON a.child_id = c.id
              JOIN users u ON a.doctor_id = u.id
              WHERE c.user_id = ? 
              AND DATE(a.appointment_date) < CURDATE()
              " . (!empty($search) ? "AND (c.name LIKE ? OR u.full_name LIKE ?)" : "") . "
              ORDER BY a.appointment_date DESC";
} else {
    $query = "SELECT a.*, 
              c.name as child_name, 
              u.full_name as doctor_name, u.email as doctor_email, u.phone as doctor_phone,
              CASE 
                WHEN a.appointment_status = 'scheduled' THEN 'مجدول'
                WHEN a.appointment_status = 'confirmed' THEN 'مؤكد'
                WHEN a.appointment_status = 'completed' THEN 'مكتمل'
                WHEN a.appointment_status = 'cancelled' THEN 'ملغى'
                ELSE 'غير محدد'
              END as status_text
              FROM appointments a
              JOIN children c ON a.child_id = c.id
              JOIN users u ON a.doctor_id = u.id
              WHERE c.user_id = ? 
              AND DATE(a.appointment_date) >= CURDATE()
              AND a.appointment_status IN ('scheduled', 'confirmed')
              " . (!empty($search) ? "AND (c.name LIKE ? OR u.full_name LIKE ?)" : "") . "
              ORDER BY a.appointment_date ASC";
}

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("iss", $user_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Setting variables
$base_path = '../';
$dashboard_link = 'index.php';
$main_dark = '#ad1457';
$main_text = '#880e4f';
$main_light = '#ffd1dc';
$main_deep = '#f8bbd0';
$bg_light = '#fff0f5';
$title_icon = 'fas fa-calendar-check';
$user_type = 'parent';
$unread_messages = 0;
$vaccine_alerts = ['missed' => [], 'upcoming' => []];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المواعيد الطبية</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/admin_shared.css">
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
            display: flex;
        }

        .main-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .main-box {
            margin-top: 40px;
            box-shadow: 0 6px 24px rgba(100, 100, 100, 0.10);
            border-radius: 16px;
            background: #fff;
            padding: 30px;
        }

        .page-header {
            font-size: 1.7rem;
            color: #c62828;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header i {
            margin-left: 12px;
            font-size: 1.5rem;
        }

        .appointment-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border-right: 5px solid;
        }

        .appointment-card.upcoming {
            border-right-color: #28a745;
            background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white);
        }

        .appointment-card.past {
            border-right-color: #6c757d;
            background: linear-gradient(to right, rgba(108, 117, 125, 0.05), white);
        }

        .appointment-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .appointment-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #333;
        }

        .appointment-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-scheduled {
            background: #e7f3ff;
            color: #0066cc;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-completed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .appointment-footer {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-items: center;
        }

        .btn-details {
            background: linear-gradient(135deg, #c62828, #8b1a1a) !important;
            color: white !important;
            border: none !important;
            padding: 12px 20px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-size: 0.95rem !important;
            white-space: nowrap !important;
            box-shadow: 0 2px 6px rgba(198, 40, 40, 0.2);
        }

        .btn-details:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 16px rgba(198, 40, 40, 0.4) !important;
            color: white !important;
            text-decoration: none !important;
            background: linear-gradient(135deg, #b51e1e, #7a1515) !important;
        }

        .btn-details i {
            font-size: 1.1rem !important;
        }

        .tabs {
            margin-bottom: 30px;
        }

        .tab-link {
            padding: 10px 20px;
            background: transparent;
            border: 2px solid #ddd;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-right: 10px;
        }

        .tab-link.active {
            background: linear-gradient(135deg, var(--primary-text), var(--primary-dark));
            color: white;
            border-color: transparent;
        }

        .search-box {
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .tabs-container {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <i class="<?= $title_icon ?>"></i>
                المواعيد الطبية
            </div>

            <!-- Search -->
            <div class="search-box">
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <input type="text" name="search" placeholder="ابحث عن طفل أو طبيب..." 
                           class="form-control" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">🔍 بحث</button>
                    <?php if (!empty($search)): ?>
                        <a href="my_appointments.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-secondary">إلغاء</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabs -->
            <div class="tabs-container">
                <a href="?tab=upcoming<?= !empty($search) ? "&search=" . urlencode($search) : "" ?>" 
                   class="tab-link <?= $tab === 'upcoming' ? 'active' : '' ?>">
                    📅 المواعيد القادمة
                </a>
                <a href="?tab=past<?= !empty($search) ? "&search=" . urlencode($search) : "" ?>" 
                   class="tab-link <?= $tab === 'past' ? 'active' : '' ?>">
                    📋 السجل (المواعيد السابقة)
                </a>
            </div>

            <!-- Main Box -->
            <div class="main-box">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($appointment = $result->fetch_assoc()): ?>
                        <div class="appointment-card <?= $tab === 'upcoming' ? 'upcoming' : 'past' ?>">
                            <!-- Header -->
                            <div class="appointment-header">
                                <div>
                                    <div class="appointment-title">
                                        👧 <?= htmlspecialchars($appointment['child_name']) ?>
                                    </div>
                                    <small class="text-muted">
                                        👨‍⚕️ دكتور: <?= htmlspecialchars($appointment['doctor_name']) ?>
                                    </small>
                                </div>
                                <span class="appointment-badge badge-<?= strtolower(str_replace(' ', '_', $appointment['appointment_status'])) ?>">
                                    <?= $appointment['status_text'] ?>
                                </span>
                            </div>

                            <!-- Details Grid -->
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <div class="detail-label">📅 التاريخ</div>
                                    <div class="detail-value">
                                        <?= date('d/m/Y', strtotime($appointment['appointment_date'])) ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">🕐 الوقت</div>
                                    <div class="detail-value">
                                        <?= date('H:i', strtotime($appointment['appointment_date'])) ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">📞 رقم الطبيب</div>
                                    <div class="detail-value">
                                        <?= htmlspecialchars($appointment['doctor_phone'] ?? 'غير متوفر') ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">✉️ البريد الإلكتروني</div>
                                    <div class="detail-value" style="font-size: 0.9rem;">
                                        <?= htmlspecialchars($appointment['doctor_email'] ?? 'غير متوفر') ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="appointment-footer" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #f0f0f0;">
                                <a href="appointment_details.php?id=<?= $appointment['id'] ?>" class="btn-details" title="عرض التفاصيل الكاملة">
                                    <i class="bi bi-eye"></i> 
                                    <span>التفاصيل</span>
                                </a>
                                <?php if ($tab === 'upcoming' && $appointment['appointment_status'] !== 'cancelled'): ?>
                                    <button onclick="cancelAppointment(<?= $appointment['id'] ?>)" class="btn-details" 
                                            style="background: linear-gradient(135deg, #dc3545, #c82333);" title="إلغاء الموعد">
                                        <i class="bi bi-x-circle"></i> 
                                        <span>إلغاء</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h5>لا توجد مواعيد</h5>
                        <p>
                            <?php if ($tab === 'upcoming'): ?>
                                لم تقم بحجز أي مواعيد قادمة. 
                                <a href="book_appointment.php" style="color: var(--primary-text); font-weight: 600;">احجز موعد الآن</a>
                            <?php else: ?>
                                لا توجد مواعيد سابقة في السجل.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelAppointment(appointmentId) {
            if (confirm('هل تريد فعلاً إلغاء هذا الموعد؟')) {
                // TODO: إضافة AJAX call لإلغاء الموعد
                alert('سيتم التطوير قريباً');
            }
        }
    </script>
</body>
</html>
