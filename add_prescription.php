<?php

session_start();

// Checking permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['doctor', 'nurse'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$child_id = intval($_GET['child_id'] ?? $_POST['child_id'] ?? 0);

$child_id = intval($_GET['child_id'] ?? $_POST['child_id'] ?? 0);
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

// Calculate the child's age in months to check the list of permitted medications
$birth_date = new DateTime($child['birth_date']);
$now = new DateTime();
$age_months = ($now->format('Y') - $birth_date->format('Y')) * 12 + ($now->format('m') - $birth_date->format('m'));
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

// Calculate the child's age in months to check the list of permitted medications
$birth_date = new DateTime($child['birth_date']);
$now = new DateTime();
$age_months = ($now->format('Y') - $birth_date->format('Y')) * 12 + ($now->format('m') - $birth_date->format('m'));

// Fetch available medications
$stmt = $conn->prepare("SELECT id, name, category FROM medications ORDER BY category, name");
$stmt->execute();
$result = $stmt->get_result();
$all_medications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch the list of permitted medications for the child's age group
$stmt = $conn->prepare("
    SELECT allowed_medications FROM age_group_medication_lists 
    WHERE age_min_months <= ? AND age_max_months >= ?
    LIMIT 1
");
$stmt->bind_param('ii', $age_months, $age_months);
$stmt->execute();
$result = $stmt->get_result();
$age_medication_list = $result->fetch_assoc();
$stmt->close();

$allowed_med_ids = [];
if ($age_medication_list && $age_medication_list['allowed_medications']) {
    $allowed_med_ids = array_map('intval', explode(',', $age_medication_list['allowed_medications']));
}

// Fetch the list of restricted medications for the child's age group
$stmt = $conn->prepare("
    SELECT restricted_medications FROM age_group_medication_lists 
    WHERE age_min_months <= ? AND age_max_months >= ?
    LIMIT 1
");
$stmt->bind_param('ii', $age_months, $age_months);
$stmt->execute();
$result = $stmt->get_result();
$restricted_list = $result->fetch_assoc();
$stmt->close();

$restricted_med_ids = [];
if ($restricted_list && $restricted_list['restricted_medications']) {
    $restricted_med_ids = array_map('intval', explode(',', $restricted_list['restricted_medications']));
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة وصفة طبية - نظام صحة الطفل</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .patient-info {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid #667eea;
        }

        .patient-info p {
            margin: 5px 0;
            color: #555;
        }

        .label {
            font-weight: 600;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 600px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }

        .medications-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px dashed #ddd;
        }

        .medications-section h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .medication-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            position: relative;
        }

        .medication-item.restricted {
            border-left-color: #e74c3c;
            background: #ffe6e6;
        }

        .medication-item.allowed {
            border-left-color: #27ae60;
            background: #e6ffe6;
        }

        .medication-item .remove-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
        }

        .medication-item .remove-btn:hover {
            background: #c0392b;
        }

        .warning-badge {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
            font-weight: 600;
        }

        .danger-badge {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
            font-weight: 600;
        }

        .success-badge {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 10px;
            font-weight: 600;
        }

        .add-medication-btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
            margin-bottom: 15px;
        }

        .add-medication-btn:hover {
            background: #229954;
        }

        .buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        button[type="submit"],
        .btn-submit {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        button[type="submit"]:hover,
        .btn-submit:hover {
            background: #5568d3;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
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

        .medication-select-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .medication-select-group select {
            flex: 1;
        }

        .medication-status {
            font-size: 12px;
            padding: 5px;
            min-width: 120px;
        }

        .three-columns {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 إضافة وصفة طبية جديدة</h1>
            <p>قم بملء البيانات أدناه لإضافة وصفة طبية للطفل</p>
        </div>

        <div id="alertBox" class="alert"></div>

        <div class="patient-info">
            <p>
                <span class="label">👶 الطفل:</span>
                <?php echo htmlspecialchars($child['name']); ?>
            </p>
            <p>
                <span class="label">📅 العمر (بالأشهر):</span>
                <?php echo $age_months; ?> شهر
            </p>
        </div>

        <form id="prescriptionForm" method="POST">
            <input type="hidden" name="action" value="add_prescription">
            <input type="hidden" name="child_id" value="<?php echo $child_id; ?>">

            <div class="two-columns">
                <div class="form-group">
                    <label>📅 تاريخ الوصفة</label>
                    <input type="date" name="prescription_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>⏰ تاريخ انتهاء الوصفة</label>
                    <input type="date" name="expiry_date" required>
                </div>
            </div>

            <div class="form-group">
                <label>📝 ملاحظات (اختياري)</label>
                <textarea name="notes" placeholder="أي ملاحظات أو تعليمات خاصة..."></textarea>
            </div>

            <!-- قسم الأدوية -->
            <div class="medications-section">
                <h3>💊 الأدوية المطلوبة</h3>

                <div class="medication-select-group">
                    <select id="medicationSelect" onchange="addMedication()">
                        <option value="">-- اختر دواء --</option>
                        <?php foreach ($all_medications as $med): ?>
                            <?php
                            $is_restricted = in_array($med['id'], $restricted_med_ids);
                            $is_allowed = in_array($med['id'], $allowed_med_ids);
                            $status_text = '';
                            
                            if ($is_restricted) {
                                $status_text = ' [⚠️ محظور لهذا العمر]';
                            } elseif ($is_allowed) {
                                $status_text = ' [✅ موصى به]';
                            }
                            ?>
                            <option value="<?php echo $med['id']; ?>" 
                                    data-is-restricted="<?php echo $is_restricted ? '1' : '0'; ?>"
                                    data-is-allowed="<?php echo $is_allowed ? '1' : '0'; ?>">
                                <?php echo htmlspecialchars($med['name'] . ' (' . $med['category'] . ')' . $status_text); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="add-medication-btn" onclick="addMedication()">إضافة</button>
                </div>

                <div id="medicationsList"></div>
            </div>

            <div class="buttons">
                <a href="child_details.php?id=<?php echo $child_id; ?>" class="btn-cancel">إلغاء</a>
                <button type="submit" class="btn-submit">💾 حفظ الوصفة</button>
            </div>
        </form>
    </div>

    <script>
        const medicationsData = <?php echo json_encode($all_medications); ?>;
        const allowedMeds = <?php echo json_encode($allowed_med_ids); ?>;
        const restrictedMeds = <?php echo json_encode($restricted_med_ids); ?>;
        const medications = [];

        function addMedication() {
            const select = document.getElementById('medicationSelect');
            const medicationId = select.value;
            
            if (!medicationId) return;

            const medication = medicationsData.find(m => m.id == medicationId);
            if (!medication) return;

            // التحقق من عدم تكرار الدواء
            if (medications.some(m => m.id == medicationId)) {
                showAlert('هذا الدواء موجود بالفعل في الوصفة', 'error');
                return;
            }

            medications.push({
                id: medicationId,
                name: medication.name,
                category: medication.category,
                dosage: '',
                frequency: '',
                duration_days: 0,
                notes: '',
                isRestricted: restrictedMeds.includes(medicationId),
                isAllowed: allowedMeds.includes(medicationId)
            });

            select.value = '';
            renderMedications();
        }

        function removeMedication(index) {
            medications.splice(index, 1);
            renderMedications();
        }

        function renderMedications() {
            const list = document.getElementById('medicationsList');
            const medicationIndices = medications.map((_, i) => i);

            list.innerHTML = medications.map((med, index) => {
                let statusBadge = '';
                let itemClass = '';

                if (med.isRestricted) {
                    statusBadge = '<span class="danger-badge">⚠️ محظور للعمر</span>';
                    itemClass = 'restricted';
                } else if (med.isAllowed) {
                    statusBadge = '<span class="success-badge">✅ موصى به</span>';
                    itemClass = 'allowed';
                }

                return `
                    <div class="medication-item ${itemClass}">
                        <button type="button" class="remove-btn" onclick="removeMedication(${index})">×</button>
                        
                        <div style="margin-bottom: 10px;">
                            <strong>${escapeHtml(med.name)}</strong> 
                            (${escapeHtml(med.category)})
                            ${statusBadge}
                        </div>

                        <div class="three-columns">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 12px;">الجرعة</label>
                                <input type="text" 
                                       placeholder="مثل: 500 mg" 
                                       value="${escapeHtml(med.dosage)}"
                                       onchange="updateMedication(${index}, 'dosage', this.value)">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 12px;">التكرار</label>
                                <select onchange="updateMedication(${index}, 'frequency', this.value)">
                                    <option value="">-- اختر --</option>
                                    <option ${med.frequency === 'مرة واحدة يومياً' ? 'selected' : ''}>مرة واحدة يومياً</option>
                                    <option ${med.frequency === 'مرتين يومياً' ? 'selected' : ''}>مرتين يومياً</option>
                                    <option ${med.frequency === 'ثلاث مرات يومياً' ? 'selected' : ''}>ثلاث مرات يومياً</option>
                                    <option ${med.frequency === 'كل 4 ساعات' ? 'selected' : ''}>كل 4 ساعات</option>
                                    <option ${med.frequency === 'كل 6 ساعات' ? 'selected' : ''}>كل 6 ساعات</option>
                                    <option ${med.frequency === 'كل 8 ساعات' ? 'selected' : ''}>كل 8 ساعات</option>
                                    <option ${med.frequency === 'عند الحاجة' ? 'selected' : ''}>عند الحاجة</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 12px;">المدة (بالأيام)</label>
                                <input type="number" 
                                       min="0" 
                                       placeholder="0" 
                                       value="${med.duration_days}"
                                       onchange="updateMedication(${index}, 'duration_days', this.value)">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 10px;">
                            <label style="font-size: 12px;">ملاحظات</label>
                            <input type="text" 
                                   placeholder="ملاحظات خاصة بهذا الدواء" 
                                   value="${escapeHtml(med.notes)}"
                                   onchange="updateMedication(${index}, 'notes', this.value)">
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateMedication(index, field, value) {
            if (field === 'duration_days') {
                medications[index][field] = parseInt(value) || 0;
            } else {
                medications[index][field] = value;
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = 'alert ' + type;
            setTimeout(() => {
                alertBox.className = 'alert';
            }, 5000);
        }

        document.getElementById('prescriptionForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (medications.length === 0) {
                showAlert('يجب إضافة دواء واحد على الأقل للوصفة', 'error');
                return;
            }

            // التحقق من الأدوية المحظورة
            const restrictedMedsInPrescription = medications.filter(m => m.isRestricted);
            if (restrictedMedsInPrescription.length > 0) {
                if (!confirm(`تحذير: تم اختيار أدوية محظورة للعمر (${restrictedMedsInPrescription.map(m => m.name).join(', ')}). هل تريد المتابعة؟`)) {
                    return;
                }
            }

            const formData = new FormData(this);
            formData.append('medications', JSON.stringify(medications.map(m => ({
                medication_id: m.id,
                dosage: m.dosage,
                frequency: m.frequency,
                duration_days: m.duration_days,
                notes: m.notes
            }))));

            try {
                const response = await fetch('prescription_handler.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('✅ تم حفظ الوصفة بنجاح', 'success');
                    setTimeout(() => {
                        window.location.href = 'child_details.php?id=<?php echo $child_id; ?>';
                    }, 2000);
                } else {
                    showAlert('❌ ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('❌ حدث خطأ: ' + error.message, 'error');
            }
        });
    </script>
</body>
</html>
