<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bring the medication list
$query_medications = "SELECT * FROM medications ORDER BY name ASC";
$stmt = $conn->prepare($query_medications);
$stmt->execute();
$medications_result = $stmt->get_result();

// Bringing medicines الموصوفة للمرضى
$query_prescriptions = "SELECT p.*, c.name as child_name, pr.full_name as parent_name,
                       GROUP_CONCAT(DISTINCT m.name SEPARATOR ', ') as medication_name,
                       GROUP_CONCAT(DISTINCT pm.dosage SEPARATOR ', ') as dosage,
                       GROUP_CONCAT(DISTINCT pm.frequency SEPARATOR ', ') as frequency,
                       GROUP_CONCAT(DISTINCT pm.duration_days SEPARATOR ', ') as duration,
                       GROUP_CONCAT(DISTINCT m.dosage_form SEPARATOR ', ') as dosage_form
                       FROM prescriptions p
                       JOIN children c ON p.child_id = c.id
                       JOIN users pr ON c.user_id = pr.id
                       LEFT JOIN prescription_medications pm ON pm.prescription_id = p.id
                       LEFT JOIN medications m ON pm.medication_id = m.id
                       WHERE p.doctor_id = ?
                       GROUP BY p.id
                       ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query_prescriptions);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$prescriptions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأدوية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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

    <!-- Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>إدارة الأدوية</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicationModal">
                    <i class="fas fa-plus"></i> إضافة دواء جديد
                </button>
                <button class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                    <i class="fas fa-prescription"></i> وصف دواء
                </button>
            </div>
        </div>

        <!-- إحصائيات الأدوية -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-pills fa-2x text-primary mb-2"></i>
                        <h6>إجمالي الأدوية</h6>
                        <h4><?php echo $medications_result->num_rows; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-prescription-bottle fa-2x text-success mb-2"></i>
                        <h6>الوصفات النشطة</h6>
                        <h4><?php echo $prescriptions_result->num_rows; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h6>تنتهي قريباً</h6>
                        <h4>5</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h6>تحتاج متابعة</h6>
                        <h4>2</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- تبويبات الأدوية -->
        <ul class="nav nav-tabs mb-4" id="medicationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="medications-tab" data-bs-toggle="tab" data-bs-target="#medications" type="button" role="tab">قائمة الأدوية</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button" role="tab">الوصفات الطبية</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alerts" type="button" role="tab">التنبيهات</button>
            </li>
        </ul>

        <div class="tab-content" id="medicationTabsContent">
            <!-- List of medications-->
            <div class="tab-pane fade show active" id="medications" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5>قائمة الأدوية المتاحة</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>اسم الدواء</th>
                                        <th>الشكل الدوائي</th>
                                        <th>التركيز</th>
                                        <th>الاستخدامات</th>
                                        <th>الآثار الجانبية</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($medication = $medications_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($medication['name']); ?></td>
                                            <td><?php echo htmlspecialchars($medication['dosage_form']); ?></td>
                                            <td><?php echo htmlspecialchars($medication['concentration']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($medication['indications'], 0, 50) . '...'); ?></td>
                                            <td><?php echo htmlspecialchars(substr($medication['side_effects'], 0, 50) . '...'); ?></td>
                                            <td>
                                                <span class="badge bg-success">متاح</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick="viewMedication(<?php echo $medication['id']; ?>)">
                                                    <i class="fas fa-eye"></i> عرض
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editMedication(<?php echo $medication['id']; ?>)">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prescriptionsالوصفات-->
            <div class="tab-pane fade" id="prescriptions" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5>الوصفات الطبية الموصوفة</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>الطفل</th>
                                        <th>الدواء</th>
                                        <th>الجرعة</th>
                                        <th>التكرار</th>
                                        <th>المدة</th>
                                        <th>تاريخ الوصف</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($prescription = $prescriptions_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prescription['child_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['medication_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['frequency']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                                            <td><?php echo htmlspecialchars($prescription['created_at']); ?></td>
                                            <td>
                                                <?php
                                                $status = $prescription['status'];
                                                $badgeClass = 'bg-secondary';
                                                if ($status === 'active') $badgeClass = 'bg-success';
                                                elseif ($status === 'completed') $badgeClass = 'bg-primary';
                                                elseif ($status === 'discontinued') $badgeClass = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo $status === 'active' ? 'نشط' : ($status === 'completed' ? 'مكتمل' : 'متوقف'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info me-1" onclick="viewPrescription(<?php echo $prescription['id']; ?>)">
                                                    <i class="fas fa-eye"></i> عرض
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="editPrescription(<?php echo $prescription['id']; ?>)">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts-->
            <div class="tab-pane fade" id="alerts" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>أدوية تنتهي قريباً</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <h6>باراسيتامول - الطفل أحمد</h6>
                                    <p class="mb-1">ينتهي في: 2024-01-15</p>
                                    <small>متبقي 3 أيام</small>
                                </div>
                                <div class="alert alert-warning">
                                    <h6>أموكسيسيلين - الطفلة فاطمة</h6>
                                    <p class="mb-1">ينتهي في: 2024-01-18</p>
                                    <small>متبقي 6 أيام</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>تحتاج متابعة</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <h6>الطفل محمد - مضاد حيوي</h6>
                                    <p class="mb-1">لم يتم تناول الجرعة الصباحية</p>
                                    <small>آخر تناول: 2024-01-12 08:00</small>
                                </div>
                                <div class="alert alert-info">
                                    <h6>الطفلة سارة - فيتامين D</h6>
                                    <p class="mb-1">تحتاج لتعديل الجرعة</p>
                                    <small>الوزن الحالي: 8.5 كغ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Adding a new drug دواء-->
    <div class="modal fade" id="addMedicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة دواء جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addMedicationForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم الدواء *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الشكل الدوائي *</label>
                                    <select class="form-select" name="dosage_form" required>
                                        <option value="">اختر الشكل الدوائي</option>
                                        <option value="tablet">أقراص</option>
                                        <option value="capsule">كبسولات</option>
                                        <option value="syrup">شراب</option>
                                        <option value="injection">حقن</option>
                                        <option value="drops">قطرات</option>
                                        <option value="cream">كريم</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">التركيز</label>
                                    <input type="text" class="form-control" name="concentration" placeholder="مثال: 500mg, 5ml">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">التصنيف</label>
                                    <select class="form-select" name="category">
                                        <option value="">اختر التصنيف</option>
                                        <option value="antibiotic">مضاد حيوي</option>
                                        <option value="pain_reliever">مسكن</option>
                                        <option value="antipyretic">خافض حرارة</option>
                                        <option value="vitamin">فيتامين</option>
                                        <option value="antihistamine">مضاد حساسية</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الاستخدامات</label>
                            <textarea class="form-control" name="indications" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الآثار الجانبية</label>
                            <textarea class="form-control" name="side_effects" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تعليمات الاستخدام</label>
                            <textarea class="form-control" name="instructions" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة الدواء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal medicine description-->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">وصف دواء لمريض</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPrescriptionForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الطفل *</label>
                                    <select class="form-select" name="child_id" required>
                                        <option value="">اختر الطفل</option>
                                        <!-- سيتم ملؤها ديناميكياً -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الدواء *</label>
                                    <select class="form-select" name="medication_id" required>
                                        <option value="">اختر الدواء</option>
                                        <?php
                                        $medications_result->data_seek(0); // إعادة المؤشر للبداية
                                        while ($medication = $medications_result->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $medication['id']; ?>">
                                                <?php echo htmlspecialchars($medication['name'] . ' - ' . $medication['dosage_form']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الجرعة *</label>
                                    <input type="text" class="form-control" name="dosage" placeholder="مثال: 5ml, 1 قرص" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">التكرار *</label>
                                    <select class="form-select" name="frequency" required>
                                        <option value="">اختر التكرار</option>
                                        <option value="every_4_hours">كل 4 ساعات</option>
                                        <option value="every_6_hours">كل 6 ساعات</option>
                                        <option value="every_8_hours">كل 8 ساعات</option>
                                        <option value="every_12_hours">كل 12 ساعات</option>
                                        <option value="once_daily">مرة يومياً</option>
                                        <option value="twice_daily">مرتين يومياً</option>
                                        <option value="three_times_daily">ثلاث مرات يومياً</option>
                                        <option value="as_needed">عند الحاجة</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المدة *</label>
                                    <input type="text" class="form-control" name="duration" placeholder="مثال: 7 أيام, 2 أسابيع" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ البدء</label>
                                    <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تعليمات خاصة</label>
                            <textarea class="form-control" name="instructions" rows="3" placeholder="تعليمات إضافية للوالدين"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ملاحظات الطبيب</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="ملاحظات سريرية"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success">وصف الدواء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal View drugالدواء  details-->
    <div class="modal fade" id="viewMedicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الدواء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="medicationDetails">
                    <!-- سيتم ملؤها ديناميكياً -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Drug modification-->
    <div class="modal fade" id="editMedicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الدواء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editMedicationForm">
                    <div class="modal-body" id="editMedicationContent">
                        <!-- سيتم ملؤها ديناميكياً -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // دوال إدارة الأدوية
        function viewMedication(id) {
            // جلب تفاصيل الدواء وفتح modal العرض
            fetch('medication_handler.php?action=get&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const medication = data.medication;
                        document.getElementById('medicationDetails').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>المعلومات الأساسية</h6>
                                    <p><strong>الاسم:</strong> ${medication.name}</p>
                                    <p><strong>الشكل الدوائي:</strong> ${getDosageFormText(medication.dosage_form)}</p>
                                    <p><strong>التركيز:</strong> ${medication.concentration || 'غير محدد'}</p>
                                    <p><strong>التصنيف:</strong> ${getCategoryText(medication.category)}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>المعلومات الطبية</h6>
                                    <p><strong>الاستخدامات:</strong></p>
                                    <p class="bg-light p-2 rounded">${medication.indications}</p>
                                    <p><strong>الآثار الجانبية:</strong></p>
                                    <p class="bg-light p-2 rounded">${medication.side_effects}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h6>تعليمات الاستخدام</h6>
                                <p class="bg-light p-2 rounded">${medication.instructions || 'لا توجد تعليمات محددة'}</p>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('viewMedicationModal')).show();
                    } else {
                        alert('خطأ في جلب بيانات الدواء: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
        }

        function editMedication(id) {
            // جلب بيانات الدواء وفتح modal التعديل
            fetch('medication_handler.php?action=get&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const medication = data.medication;
                        document.getElementById('editMedicationContent').innerHTML = `
                            <input type="hidden" name="id" value="${medication.id}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">اسم الدواء *</label>
                                        <input type="text" class="form-control" name="name" value="${medication.name}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">الشكل الدوائي *</label>
                                        <select class="form-select" name="dosage_form" required>
                                            <option value="tablet" ${medication.dosage_form === 'tablet' ? 'selected' : ''}>أقراص</option>
                                            <option value="capsule" ${medication.dosage_form === 'capsule' ? 'selected' : ''}>كبسولات</option>
                                            <option value="syrup" ${medication.dosage_form === 'syrup' ? 'selected' : ''}>شراب</option>
                                            <option value="injection" ${medication.dosage_form === 'injection' ? 'selected' : ''}>حقن</option>
                                            <option value="drops" ${medication.dosage_form === 'drops' ? 'selected' : ''}>قطرات</option>
                                            <option value="cream" ${medication.dosage_form === 'cream' ? 'selected' : ''}>كريم</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">التركيز</label>
                                        <input type="text" class="form-control" name="concentration" value="${medication.concentration || ''}" placeholder="مثال: 500mg, 5ml">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">التصنيف</label>
                                        <select class="form-select" name="category">
                                            <option value="antibiotic" ${medication.category === 'antibiotic' ? 'selected' : ''}>مضاد حيوي</option>
                                            <option value="pain_reliever" ${medication.category === 'pain_reliever' ? 'selected' : ''}>مسكن</option>
                                            <option value="antipyretic" ${medication.category === 'antipyretic' ? 'selected' : ''}>خافض حرارة</option>
                                            <option value="vitamin" ${medication.category === 'vitamin' ? 'selected' : ''}>فيتامين</option>
                                            <option value="antihistamine" ${medication.category === 'antihistamine' ? 'selected' : ''}>مضاد حساسية</option>
                                            <option value="other" ${medication.category === 'other' ? 'selected' : ''}>أخرى</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الاستخدامات</label>
                                <textarea class="form-control" name="indications" rows="3">${medication.indications}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الآثار الجانبية</label>
                                <textarea class="form-control" name="side_effects" rows="3">${medication.side_effects}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">تعليمات الاستخدام</label>
                                <textarea class="form-control" name="instructions" rows="3">${medication.instructions || ''}</textarea>
                            </div>
                        `;
                        new bootstrap.Modal(document.getElementById('editMedicationModal')).show();
                    } else {
                        alert('خطأ في جلب بيانات الدواء: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
        }

        function viewPrescription(id) {
            // فتح نافذة عرض تفاصيل الوصفة
            alert('عرض تفاصيل الوصفة رقم: ' + id);
        }

        function editPrescription(id) {
            // فتح نافذة تعديل الوصفة
            alert('تعديل الوصفة رقم: ' + id);
        }

        // دوال مساعدة للترجمة
        function getDosageFormText(form) {
            const forms = {
                'tablet': 'أقراص',
                'capsule': 'كبسولات',
                'syrup': 'شراب',
                'injection': 'حقن',
                'drops': 'قطرات',
                'cream': 'كريم'
            };
            return forms[form] || form;
        }

        function getCategoryText(category) {
            const categories = {
                'antibiotic': 'مضاد حيوي',
                'pain_reliever': 'مسكن',
                'antipyretic': 'خافض حرارة',
                'vitamin': 'فيتامين',
                'antihistamine': 'مضاد حساسية',
                'other': 'أخرى'
            };
            return categories[category] || category;
        }

        // معالجة إضافة دواء جديد
        document.getElementById('addMedicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('medication_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إضافة الدواء بنجاح');
                    location.reload();
                } else {
                    alert('خطأ في إضافة الدواء: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        });

        // معالجة وصف دواء
        document.getElementById('addPrescriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('prescription_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم وصف الدواء بنجاح');
                    location.reload();
                } else {
                    alert('خطأ في وصف الدواء: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        });

        // معالجة تعديل الدواء
        document.getElementById('editMedicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('medication_handler.php?action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث الدواء بنجاح');
                    location.reload();
                } else {
                    alert('خطأ في تحديث الدواء: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        });
    </script>
</body>
</html>