<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/db_config.php';

$child_id = intval($_GET['child_id'] ?? 0);

if (!$child_id) {
    header('Location: index.php');
    exit;
}
$db = new DatabaseHelper();
$conn = $db->getConnection();
// جلب بيانات الطفل
$stmt = $conn->prepare("SELECT id, name, birth_date FROM children WHERE id = ?");
$stmt->bind_param('i', $child_id);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();
$stmt->close();

if (!$child) {
    header('Location: index.php');
    exit;
}

// التحقق من صلاحيات الوصول للطفل
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

// جلب الوصفات
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.doctor_id,
        u.full_name as doctor_name,
        p.prescription_date,
        p.expiry_date,
        DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
        DATEDIFF(CURDATE(), p.prescription_date) as days_since_creation,
        p.status,
        p.notes
    FROM prescriptions p
    JOIN users u ON p.doctor_id = u.id
    WHERE p.child_id = ?
    ORDER BY 
        CASE 
            WHEN p.status = 'active' THEN 0
            ELSE 1
        END,
        p.expiry_date DESC
");
$stmt->bind_param('i', $child_id);
$stmt->execute();
$result = $stmt->get_result();
$prescriptions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// حساب الإحصائيات
$active_count = count(array_filter($prescriptions, fn($p) => $p['status'] === 'active'));
$expiring_count = count(array_filter($prescriptions, fn($p) => $p['status'] === 'active' && $p['days_until_expiry'] <= 7 && $p['days_until_expiry'] > 0));
$expired_count = count(array_filter($prescriptions, fn($p) => $p['status'] === 'active' && $p['days_until_expiry'] < 0));
$urgent_count = count(array_filter($prescriptions, fn($p) => $p['status'] === 'active' && $p['days_until_expiry'] <= 1));

$can_edit = in_array($_SESSION['user_type'], ['doctor', 'nurse']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الوصفات الطبية - نظام صحة الطفل</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-left h1 {
            color: #333;
            margin-bottom: 5px;
        }

        .header-left p {
            color: #666;
            font-size: 14px;
        }

        .header-right {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid #667eea;
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

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 20px;
            border: 2px solid white;
            background: transparent;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: white;
            color: #667eea;
        }

        .tab-btn:hover {
            background: white;
            color: #667eea;
        }

        .prescriptions-list {
            display: none;
        }

        .prescriptions-list.active {
            display: block;
        }

        .prescription-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-right: 5px solid #667eea;
            position: relative;
        }

        .prescription-card.expiring {
            border-right-color: #f39c12;
        }

        .prescription-card.expired {
            border-right-color: #e74c3c;
            opacity: 0.8;
        }

        .prescription-card.urgent {
            border-right-color: #e74c3c;
            background: #ffe6e6;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.expiring {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.expired {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.urgent {
            background: #f8d7da;
            color: #721c24;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            margin-top: 30px;
        }

        .prescription-info {
            flex: 1;
        }

        .prescription-info h3 {
            color: #333;
            margin-bottom: 8px;
        }

        .prescription-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .prescription-dates {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        @media (max-width: 600px) {
            .prescription-dates {
                grid-template-columns: 1fr;
            }
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-item label {
            font-weight: 600;
            color: #333;
            min-width: 80px;
        }

        .date-item value {
            color: #666;
        }

        .date-item .days-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }

        .date-item .days-badge.warning {
            background: #f39c12;
        }

        .date-item .days-badge.danger {
            background: #e74c3c;
        }

        .medications {
            margin: 15px 0;
            padding: 15px;
            background: #f0f4ff;
            border-radius: 5px;
            border-right: 3px solid #667eea;
        }

        .medications h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .medication-list {
            list-style: none;
        }

        .medication-list li {
            padding: 8px;
            color: #555;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
        }

        .medication-list li:last-child {
            border-bottom: none;
        }

        .medication-details {
            color: #888;
            font-size: 12px;
            margin-top: 3px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-view:hover {
            background: #2980b9;
        }

        .btn-renew {
            background: #27ae60;
            color: white;
        }

        .btn-renew:hover {
            background: #229954;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-edit:hover {
            background: #e67e22;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #999;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .notes-section {
            margin-top: 10px;
            padding: 10px;
            background: #f9f9f9;
            border-right: 3px solid #f39c12;
            border-radius: 5px;
            color: #555;
            font-size: 14px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: white;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 90%;
        }

        .modal-close {
            color: #aaa;
            float: left;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .modal-close:hover {
            color: #000;
        }

        .modal h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .modal-buttons .btn-cancel {
            background: #95a5a6;
            color: white;
        }

        .modal-buttons .btn-cancel:hover {
            background: #7f8c8d;
        }

        .modal-buttons .btn-confirm {
            background: #27ae60;
            color: white;
        }

        .modal-buttons .btn-confirm:hover {
            background: #229954;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>📋 الوصفات الطبية</h1>
                <p>👶 <?php echo htmlspecialchars($child['name']); ?></p>
            </div>
            <div class="header-right">
                <?php if ($can_edit): ?>
                    <a href="add_prescription.php?child_id=<?php echo $child_id; ?>" class="btn btn-primary">
                        ➕ وصفة جديدة
                    </a>
                <?php endif; ?>
                <a href="child_details.php?id=<?php echo $child_id; ?>" class="btn btn-secondary">
                    ← العودة
                </a>
            </div>
        </div>

        <div id="alertBox" class="alert"></div>

        <!-- البطاقات الإحصائية -->
        <div class="stats">
            <div class="stat-card">
                <div style="font-size: 24px;">✅</div>
                <div class="stat-number"><?php echo $active_count; ?></div>
                <div class="stat-label">وصفات نشطة</div>
            </div>
            <div class="stat-card warning">
                <div style="font-size: 24px;">⚠️</div>
                <div class="stat-number"><?php echo $expiring_count; ?></div>
                <div class="stat-label">قريبة الانتهاء</div>
            </div>
            <div class="stat-card danger">
                <div style="font-size: 24px;">❌</div>
                <div class="stat-number"><?php echo $expired_count; ?></div>
                <div class="stat-label">منتهية الصلاحية</div>
            </div>
            <div class="stat-card success">
                <div style="font-size: 24px;">🔴</div>
                <div class="stat-number"><?php echo count($prescriptions); ?></div>
                <div class="stat-label">إجمالي</div>
            </div>
        </div>

        <?php if (empty($prescriptions)): ?>
            <div class="empty-state">
                <h3>📭 لا توجد وصفات طبية</h3>
                <p>لم يتم إضافة أي وصفات طبية لهذا الطفل بعد</p>
                <?php if ($can_edit): ?>
                    <a href="add_prescription.php?child_id=<?php echo $child_id; ?>" class="btn btn-primary" style="margin-top: 20px;">
                        ➕ إضافة وصفة الآن
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- القائمة الرئيسية -->
            <div class="prescriptions-list active" id="listAll">
                <?php foreach ($prescriptions as $prescription): ?>
                    <?php
                    $class = '';
                    $badge_class = '';
                    $status_text = '';
                    
                    if ($prescription['status'] === 'active') {
                        if ($prescription['days_until_expiry'] < 0) {
                            $class = 'expired';
                            $badge_class = 'expired';
                            $status_text = '❌ منتهية الصلاحية';
                        } elseif ($prescription['days_until_expiry'] <= 1) {
                            $class = 'urgent';
                            $badge_class = 'urgent';
                            $status_text = '🔴 عاجل - ستنتهي اليوم!';
                        } elseif ($prescription['days_until_expiry'] <= 7) {
                            $class = 'expiring';
                            $badge_class = 'expiring';
                            $status_text = '⚠️ قريبة الانتهاء';
                        } else {
                            $badge_class = 'active';
                            $status_text = '✅ نشطة';
                        }
                    } else {
                        $badge_class = 'expired';
                        $status_text = 'ملغاة';
                    }
                    ?>
                    <div class="prescription-card <?php echo $class; ?>">
                        <span class="status-badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>

                        <div class="prescription-header">
                            <div class="prescription-info">
                                <h3>🏥 وصفة من د. <?php echo htmlspecialchars($prescription['doctor_name']); ?></h3>
                                <p>📝 تاريخ الكتابة: <?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></p>
                            </div>
                        </div>

                        <div class="prescription-dates">
                            <div class="date-item">
                                <label>📅 تاريخ الانتهاء:</label>
                                <value>
                                    <?php echo date('d/m/Y', strtotime($prescription['expiry_date'])); ?>
                                    <?php if ($prescription['days_until_expiry'] >= 0): ?>
                                        <span class="days-badge <?php echo $prescription['days_until_expiry'] <= 7 ? 'warning' : ''; ?>">
                                            بعد <?php echo $prescription['days_until_expiry']; ?> يوم
                                        </span>
                                    <?php else: ?>
                                        <span class="days-badge danger">منتهية منذ <?php echo abs($prescription['days_until_expiry']); ?> يوم</span>
                                    <?php endif; ?>
                                </value>
                            </div>
                            <div class="date-item">
                                <label>🕐 الصلاحية:</label>
                                <value>
                                    <?php 
                                    $remaining = (strtotime($prescription['expiry_date']) - time()) / (24 * 3600);
                                    if ($remaining > 0) {
                                        echo round($remaining) . ' يوم';
                                    } else {
                                        echo 'منتهية';
                                    }
                                    ?>
                                </value>
                            </div>
                        </div>

                        <div class="medications" id="meds-<?php echo $prescription['id']; ?>">
                            <h4>💊 جاري التحميل...</h4>
                        </div>

                        <?php if ($prescription['notes']): ?>
                            <div class="notes-section">
                                <strong>📌 ملاحظات:</strong> <?php echo htmlspecialchars($prescription['notes']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button class="btn-small btn-view" onclick="viewDetails(<?php echo $prescription['id']; ?>)">
                                👁️ عرض التفاصيل
                            </button>
                            <?php if ($can_edit && $prescription['status'] === 'active'): ?>
                                <button class="btn-small btn-renew" onclick="openRenewModal(<?php echo $prescription['id']; ?>)">
                                    🔄 تجديد الوصفة
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- نموذج تجديد الوصفة -->
    <div id="renewModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeRenewModal()">&times;</span>
            <h2>🔄 تجديد الوصفة</h2>
            
            <form id="renewForm">
                <div class="form-group">
                    <label>📅 تاريخ الانتهاء الجديد</label>
                    <input type="date" id="newExpiryDate" name="new_expiry_date" required>
                </div>
                <div class="form-group">
                    <label>📝 ملاحظات التجديد (اختياري)</label>
                    <textarea id="renewNotes" name="notes" placeholder="أي ملاحظات خاصة بالتجديد..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit;"></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeRenewModal()">إلغاء</button>
                    <button type="submit" class="btn-confirm">✅ تجديد الوصفة</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPrescriptionId = null;

        // تحميل الأدوية لكل وصفة
        window.addEventListener('load', async function() {
            const prescriptionElements = document.querySelectorAll('[id^="meds-"]');
            for (const elem of prescriptionElements) {
                const prescriptionId = elem.id.split('-')[1];
                await loadMedicationsList(prescriptionId);
            }
        });

        async function loadMedicationsList(prescriptionId) {
            try {
                const response = await fetch(`prescription_handler.php?action=get_prescription_details&prescription_id=${prescriptionId}`);
                const result = await response.json();

                if (result.success && result.prescription.medications) {
                    const meds = result.prescription.medications;
                    let html = '<h4>💊 الأدوية:</h4><ul class="medication-list">';
                    
                    meds.forEach(med => {
                        html += `<li>
                            ${med.medication_name}
                            <div class="medication-details">
                                الجرعة: ${med.dosage} | التكرار: ${med.frequency}
                                ${med.duration_days ? '| المدة: ' + med.duration_days + ' أيام' : ''}
                                ${med.notes ? '<br>ملاحظات: ' + med.notes : ''}
                            </div>
                        </li>`;
                    });
                    
                    html += '</ul>';
                    document.getElementById('meds-' + prescriptionId).innerHTML = html;
                }
            } catch (error) {
                console.error('Error loading medications:', error);
                document.getElementById('meds-' + prescriptionId).innerHTML = '<h4>⚠️ خطأ في تحميل الأدوية</h4>';
            }
        }

        function viewDetails(prescriptionId) {
            showAlert('جاري تحميل تفاصيل الوصفة...', 'info');
            // سيتم فتح صفحة تفاصيل جديدة
            window.location.href = `prescription_details.php?id=${prescriptionId}&child_id=<?php echo $child_id; ?>`;
        }

        function openRenewModal(prescriptionId) {
            currentPrescriptionId = prescriptionId;
            const renewModal = document.getElementById('renewModal');
            const expiryInput = document.getElementById('newExpiryDate');
            
            // تعيين التاريخ الافتراضي (هذا اليوم + 30 يوم)
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 30);
            expiryInput.value = tomorrow.toISOString().split('T')[0];
            
            renewModal.classList.add('active');
        }

        function closeRenewModal() {
            document.getElementById('renewModal').classList.remove('active');
            currentPrescriptionId = null;
        }

        document.getElementById('renewForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'renew_prescription');
            formData.append('prescription_id', currentPrescriptionId);
            formData.append('new_expiry_date', document.getElementById('newExpiryDate').value);
            formData.append('notes', document.getElementById('renewNotes').value);

            try {
                const response = await fetch('prescription_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('✅ تم تجديد الوصفة بنجاح', 'success');
                    closeRenewModal();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert('❌ ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('❌ خطأ: ' + error.message, 'error');
            }
        });

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = 'alert ' + type;
            setTimeout(() => {
                alertBox.className = 'alert';
            }, 5000);
        }

        // إغلاق النموذج عند النقر خارجه
        window.addEventListener('click', function(e) {
            const renewModal = document.getElementById('renewModal');
            if (e.target === renewModal) {
                closeRenewModal();
            }
        });
    </script>
</body>
</html>
