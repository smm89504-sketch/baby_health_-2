<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bring the prescriptions الوصفات الطبية
$query_prescriptions = "SELECT p.*, p.prescription_date, c.name as child_name, c.birth_date, pr.full_name as parent_name,
                              COALESCE(GROUP_CONCAT(DISTINCT m.name SEPARATOR ', '), '') as medication_name,
                              COALESCE(GROUP_CONCAT(DISTINCT pm.dosage SEPARATOR ', '), '') as dosage,
                              COALESCE(GROUP_CONCAT(DISTINCT pm.frequency SEPARATOR ', '), '') as frequency,
                              COALESCE(GROUP_CONCAT(DISTINCT pm.duration_days SEPARATOR ', '), '') as duration,
                              COALESCE(GROUP_CONCAT(DISTINCT m.dosage_form SEPARATOR ', '), '') as dosage_form,
                              COALESCE(GROUP_CONCAT(DISTINCT m.concentration SEPARATOR ', '), '') as concentration
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

// Recipe statisticsإحصائيات الوصفات
$query_stats = "SELECT
    COUNT(*) as total_prescriptions,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_prescriptions,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_prescriptions,
    COUNT(DISTINCT child_id) as unique_patients
    FROM prescriptions WHERE doctor_id = ?";

$stmt = $conn->prepare($query_stats);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الوصفات الطبية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" referrerpolicy="no-referrer" />
</head>
<body>
    <!-- Top strip-->
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

    <!-- Main contant-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الوصفات الطبية</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                    <i class="fas fa-plus"></i> وصفة جديدة
                </button>
                <button class="btn btn-success ms-2" onclick="printPrescriptions()">
                    <i class="fas fa-print"></i> طباعة
                </button>
            </div>
        </div>

        <!-- prescription statistics-->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-prescription-bottle fa-2x text-primary mb-2"></i>
                        <h6>إجمالي الوصفات</h6>
                        <h4><?php echo $stats['total_prescriptions']; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-play-circle fa-2x text-success mb-2"></i>
                        <h6>الوصفات النشطة</h6>
                        <h4><?php echo $stats['active_prescriptions']; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                        <h6>الوصفات المكتملة</h6>
                        <h4><?php echo $stats['completed_prescriptions']; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-warning mb-2"></i>
                        <h6>المرضى المعالجون</h6>
                        <h4><?php echo $stats['unique_patients']; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلترة الوصفات -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">البحث بالطفل</label>
                        <input type="text" class="form-control" id="searchChild" placeholder="اسم الطفل">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">البحث بالدواء</label>
                        <input type="text" class="form-control" id="searchMedication" placeholder="اسم الدواء">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="filterStatus">
                            <option value="">جميع الحالات</option>
                            <option value="active">نشط</option>
                            <option value="expired">منتهية</option>
                            <option value="cancelled">ملغاة</option>
                            <option value="completed">مكتمل</option>
                            <option value="discontinued">متوقف</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">التاريخ</label>
                        <input type="date" class="form-control" id="filterDate">
                    </div>
                </div>
            </div>
        </div>

        <!-- الوصفاتRecipe table-->
        <div class="card">
            <div class="card-header">
                <h5>قائمة الوصفات الطبية</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="prescriptionsTable">
                        <thead>
                            <tr>
                                <th>رقم الوصفة</th>
                                <th>الطفل</th>
                                <th>الوالد</th>
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
                                <?php $status = $prescription['status']; ?>
                                <tr data-status="<?php echo htmlspecialchars($status); ?>">
                                    <td><?php echo str_pad($prescription['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($prescription['child_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php
                                                $birth_date = new DateTime($prescription['birth_date']);
                                                $today = new DateTime();
                                                $age = $today->diff($birth_date);
                                                echo $age->y . ' سنة ' . $age->m . ' شهر';
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($prescription['parent_name']); ?></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($prescription['medication_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($prescription['dosage_form']); ?></small>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['frequency']); ?></td>
                                    <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($prescription['prescription_date']))); ?></td>
                                    <td>
                                        <?php
                                        $status = $prescription['status'];
                                        $status_text = '';
                                        $badge_class = '';

                                        switch ($status) {
                                            case 'active':
                                                $status_text = 'نشط';
                                                $badge_class = 'bg-success';
                                                break;
                                            case 'expired':
                                                $status_text = 'منتهية';
                                                $badge_class = 'bg-secondary';
                                                break;
                                            case 'cancelled':
                                                $status_text = 'ملغاة';
                                                $badge_class = 'bg-danger';
                                                break;
                                            case 'completed':
                                                $status_text = 'مكتمل';
                                                $badge_class = 'bg-primary';
                                                break;
                                            case 'discontinued':
                                                $status_text = 'متوقف';
                                                $badge_class = 'bg-danger';
                                                break;
                                            default:
                                                $status_text = 'غير محدد';
                                                $badge_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewPrescription(<?php echo $prescription['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" onclick="editPrescription(<?php echo $prescription['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="printPrescription(<?php echo $prescription['id']; ?>)">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="renewPrescription(<?php echo $prescription['id']; ?>)">تجديد الوصفة</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="stopPrescription(<?php echo $prescription['id']; ?>)">إيقاف الوصفة</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deletePrescription(<?php echo $prescription['id']; ?>)">حذف</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recipe reminders-->
        <div class="card mt-4">
            <div class="card-header">
                <h5>تذكيرات الوصفات</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-clock"></i> وصفات تنتهي قريباً</h6>
                            <ul class="mb-0">
                                <li>باراسيتامول للطفل أحمد - ينتهي في 3 أيام</li>
                                <li>أموكسيسيلين للطفلة فاطمة - ينتهي في 5 أيام</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-bell"></i> تذكيرات اليوم</h6>
                            <ul class="mb-0">
                                <li>فحص مستوى الدواء للطفل محمد</li>
                                <li>متابعة الآثار الجانبية للطفلة سارة</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal View recipe details-->
    <div class="modal fade" id="viewPrescriptionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل الوصفة الطبية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="prescriptionDetails">
                    <!-- سيتم ملؤها ديناميكياً -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-primary" onclick="printPrescriptionModal()">طباعة</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add a new recipe-->
    <div class="modal fade" id="addPrescriptionModal" tabindex="-1">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة وصفة طبية جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPrescriptionForm">
                    <input type="hidden" name="prescription_id" id="prescriptionId" value="">
                    <input type="hidden" name="action" id="prescriptionAction" value="add_prescription">
                    <div class="modal-body">
                        <!--Patient information-->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6>معلومات المريض</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">الطفل *</label>
                                            <select class="form-select" name="child_id" id="childSelect" required>
                                                <option value="">اختر الطفل</option>
                                                <!-- سيتم ملؤها ديناميكياً -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">تاريخ الزيارة</label>
                                            <input type="date" class="form-control" name="prescription_date" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">تشخيص أولي</label>
                                            <input type="text" class="form-control" name="diagnosis" placeholder="مثال: التهاب في الحلق">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">الأعراض</label>
                                            <input type="text" class="form-control" name="symptoms" placeholder="مثال: سعال، حمى">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Prescribed medications-->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6>الأدوية الموصوفة</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addMedicationRow()">
                                    <i class="fas fa-plus"></i> إضافة دواء
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="medicationsContainer">
                                    <div class="medication-row border rounded p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">الدواء *</label>
                                                <select class="form-select medication-select" name="medications[0][medication_id]" required>
                                                    <option value="">اختر الدواء</option>
                                                    <!-- سيتم ملؤها ديناميكياً -->
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">الجرعة *</label>
                                                <input type="text" class="form-control" name="medications[0][dosage]" placeholder="مثال: 5ml" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">التكرار *</label>
                                                <select class="form-select" name="medications[0][frequency]" required>
                                                    <option value="">اختر</option>
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
                                            <div class="col-md-2">
                                                <label class="form-label">المدة *</label>
                                                <input type="text" class="form-control" name="medications[0][duration]" placeholder="مثال: 7 أيام" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">تاريخ البدء</label>
                                                <input type="date" class="form-control" name="medications[0][start_date]" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeMedicationRow(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <label class="form-label">تعليمات خاصة</label>
                                                <textarea class="form-control" name="medications[0][instructions]" rows="2" placeholder="تعليمات إضافية للوالدين"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor's Notes-->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6>ملاحظات الطبيب</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">ملاحظات سريرية</label>
                                    <textarea class="form-control" name="clinical_notes" rows="3" placeholder="ملاحظات سريرية خاصة بالطبيب"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">توصيات للوالدين</label>
                                    <textarea class="form-control" name="parent_instructions" rows="3" placeholder="تعليمات ونصائح للوالدين"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">زيارة متابعة</label>
                                    <input type="date" class="form-control" name="follow_up_date" placeholder="تاريخ الزيارة التالية">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success" id="prescriptionActionButton">حفظ الوصفة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let medicationRowCount = 1;        let prescriptionModalMode = 'add';

        function resetPrescriptionForm() {
            prescriptionModalMode = 'add';
            document.getElementById('prescriptionId').value = '';
            document.getElementById('prescriptionAction').value = 'add_prescription';
            document.getElementById('prescriptionActionButton').textContent = 'حفظ الوصفة';
            document.getElementById('addPrescriptionModal').querySelector('.modal-title').textContent = 'إضافة وصفة طبية جديدة';
            document.getElementById('addPrescriptionForm').reset();
            medicationRowCount = 1;
            const container = document.getElementById('medicationsContainer');
            container.innerHTML = '';
            addMedicationRow();
        }

        function buildPrescriptionMedicationRow(index, medication = {}) {
            return `
                <div class="medication-row border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">الدواء *</label>
                            <select class="form-select medication-select" name="medications[${index}][medication_id]" data-selected-value="${medication.medication_id || ''}" required>
                                <option value="">اختر الدواء</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الجرعة *</label>
                            <input type="text" class="form-control" name="medications[${index}][dosage]" placeholder="مثال: 5ml" value="${medication.dosage || ''}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">التكرار *</label>
                            <select class="form-select" name="medications[${index}][frequency]" required>
                                <option value="">اختر</option>
                                <option value="every_4_hours" ${medication.frequency === 'every_4_hours' ? 'selected' : ''}>كل 4 ساعات</option>
                                <option value="every_6_hours" ${medication.frequency === 'every_6_hours' ? 'selected' : ''}>كل 6 ساعات</option>
                                <option value="every_8_hours" ${medication.frequency === 'every_8_hours' ? 'selected' : ''}>كل 8 ساعات</option>
                                <option value="every_12_hours" ${medication.frequency === 'every_12_hours' ? 'selected' : ''}>كل 12 ساعات</option>
                                <option value="once_daily" ${medication.frequency === 'once_daily' ? 'selected' : ''}>مرة يومياً</option>
                                <option value="twice_daily" ${medication.frequency === 'twice_daily' ? 'selected' : ''}>مرتين يومياً</option>
                                <option value="three_times_daily" ${medication.frequency === 'three_times_daily' ? 'selected' : ''}>ثلاث مرات يومياً</option>
                                <option value="as_needed" ${medication.frequency === 'as_needed' ? 'selected' : ''}>عند الحاجة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">المدة *</label>
                            <input type="text" class="form-control" name="medications[${index}][duration]" placeholder="مثال: 7 أيام" value="${medication.duration || ''}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">تاريخ البدء</label>
                            <input type="date" class="form-control" name="medications[${index}][start_date]" value="${medication.start_date || ''}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeMedicationRow(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label class="form-label">تعليمات خاصة</label>
                            <textarea class="form-control" name="medications[${index}][instructions]" rows="2" placeholder="تعليمات إضافية للوالدين">${medication.instructions || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
        }

        function addMedicationRow(medication = {}) {
            const container = document.getElementById('medicationsContainer');
            const rowIndex = medicationRowCount++;
            container.insertAdjacentHTML('beforeend', buildPrescriptionMedicationRow(rowIndex, medication));
            loadMedications();
        }

        function populateMedicationRows(medications) {
            const container = document.getElementById('medicationsContainer');
            container.innerHTML = '';
            medicationRowCount = 0;
            medications.forEach(med => addMedicationRow({
                medication_id: med.medication_id,
                dosage: med.dosage,
                frequency: med.frequency,
                duration: med.duration || med.duration_days || '',
                start_date: med.start_date || '',
                instructions: med.instructions || med.notes || ''
            }));
            if (medications.length === 0) {
                addMedicationRow();
            }
        }

        // Download the children's list
        function loadChildren() {
            fetch('get_children.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'فشل في تحميل قائمة الأطفال');
                    }
                    const childSelect = document.getElementById('childSelect');
                    childSelect.innerHTML = '<option value="">اختر الطفل</option>';

                    data.children.forEach(child => {
                        const option = document.createElement('option');
                        option.value = child.id;
                        const childName = child.name || child.full_name || '';
                        option.textContent = childName + ' - ' + child.parent_name;
                        childSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading children:', error));
        }

        // Download the medication list
        function loadMedications() {
            fetch('get_medications.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'فشل في تحميل قائمة الأدوية');
                    }
                    const medicationSelects = document.querySelectorAll('.medication-select');

                    medicationSelects.forEach(select => {
                        const selectedValue = select.dataset.selectedValue || select.value || '';
                        select.innerHTML = '<option value="">اختر الدواء</option>';

                        data.medications.forEach(medication => {
                            const option = document.createElement('option');
                            option.value = medication.id;
                            option.textContent = medication.name + ' - ' + medication.dosage_form;
                            select.appendChild(option);
                        });

                        if (selectedValue) {
                            select.value = selectedValue;
                        }
                        select.removeAttribute('data-selected-value');
                    });
                })
                .catch(error => console.error('Error loading medications:', error));
        }

        // Delete a medication descriptionحذف صف دواء
        function removeMedicationRow(button) {
            if (document.querySelectorAll('.medication-row').length > 1) {
                button.closest('.medication-row').remove();
            } else {
                alert('يجب الاحتفاظ بصف دواء واحد على الأقل');
            }
        }

        //View recipe details عرض تفاصيل الوصفة
        function viewPrescription(id) {
            fetch(`get_prescription_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'فشل في تحميل بيانات الوصفة');
                    }
                    const prescription = data.data;
                    document.getElementById('prescriptionDetails').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6>معلومات المريض</h6>
                                <p><strong>الطفل:</strong> ${prescription.child_name}</p>
                                <p><strong>الوالد:</strong> ${prescription.parent_name}</p>
                                <p><strong>التشخيص:</strong> ${prescription.diagnosis || 'غير محدد'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>معلومات الوصفة</h6>
                                <p><strong>رقم الوصفة:</strong> ${String(prescription.id).padStart(6, '0')}</p>
                                <p><strong>تاريخ الوصف:</strong> ${new Date(prescription.created_at).toLocaleDateString('ar')}</p>
                                <p><strong>الحالة:</strong> <span class="badge bg-${prescription.status === 'active' ? 'success' : (prescription.status === 'expired' ? 'secondary' : 'danger')}">${prescription.status}</span></p>
                            </div>
                        </div>
                        <hr>
                        <h6>الأدوية الموصوفة</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>الدواء</th>
                                        <th>الجرعة</th>
                                        <th>التكرار</th>
                                        <th>المدة</th>
                                        <th>تعليمات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${prescription.medications.map(med => `
                                        <tr>
                                            <td>${med.name}</td>
                                            <td>${med.dosage}</td>
                                            <td>${med.frequency}</td>
                                            <td>${med.duration || med.duration_days || '-'}</td>
                                            <td>${med.instructions || med.notes || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ${prescription.clinical_notes ? `<hr><h6>ملاحظات سريرية</h6><p>${prescription.clinical_notes}</p>` : ''}
                        ${prescription.parent_instructions ? `<hr><h6>تعليمات للوالدين</h6><p>${prescription.parent_instructions}</p>` : ''}
                    `;

                    new bootstrap.Modal(document.getElementById('viewPrescriptionModal')).show();
                })
                .catch(error => {
                    console.error('Error loading prescription details:', error);
                    alert('حدث خطأ في تحميل تفاصيل الوصفة');
                });
        }

        // Print recipes
        function printPrescriptions() {
            window.print();
        }

        // Print a specific recipe
        function printPrescription(prescriptionId) {
            // Open a new print window
            const printWindow = window.open('/baby_health/doctor/print_prescription.php?id=' + prescriptionId, '_blank');
            if (printWindow) {
                printWindow.focus();
                // Wait for the page to load, then print.
                printWindow.onload = function() {
                    printWindow.print();
                };
            } else {
                alert('يرجى السماح بفتح النوافذ المنبثقة لطباعة الوصفة');
            }
        }

        // Print the recipe from the pop-up windowالنافذة المنبثقة
        function editPrescription(prescriptionId) {
            fetch('prescription_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'get_prescription', prescription_id: prescriptionId})
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'فشل في جلب الوصفة');
                }
                const prescription = data.prescription;
                resetPrescriptionForm();
                prescriptionModalMode = 'edit';
                document.getElementById('prescriptionId').value = prescription.id;
                document.getElementById('prescriptionAction').value = 'update_prescription';
                document.getElementById('prescriptionActionButton').textContent = 'تحديث الوصفة';
                document.getElementById('addPrescriptionModal').querySelector('.modal-title').textContent = 'تعديل الوصفة الطبية';

                document.getElementById('childSelect').value = prescription.child_id;
                document.querySelector('input[name="prescription_date"]').value = prescription.prescription_date || '';
                document.querySelector('input[name="diagnosis"]').value = prescription.diagnosis || '';
                document.querySelector('input[name="symptoms"]').value = prescription.symptoms || '';
                document.querySelector('textarea[name="clinical_notes"]').value = prescription.clinical_notes || '';
                document.querySelector('textarea[name="parent_instructions"]').value = prescription.parent_instructions || '';
                document.querySelector('input[name="follow_up_date"]').value = prescription.follow_up_date || '';

                populateMedicationRows(prescription.medications || []);
                new bootstrap.Modal(document.getElementById('addPrescriptionModal')).show();
            })
            .catch(error => {
                console.error('Error editing prescription:', error);
                alert('حدث خطأ في تحميل بيانات الوصفة للتعديل');
            });
        }

        function renewPrescription(prescriptionId) {
            fetch('prescription_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'change_status', prescription_id: prescriptionId, status: 'active'})
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'فشل في تجديد الوصفة');
                }
                alert(data.message);
                location.reload();
            })
            .catch(error => {
                console.error('Error renewing prescription:', error);
                alert('حدث خطأ في تجديد الوصفة');
            });
        }

        function stopPrescription(prescriptionId) {
            if (!confirm('هل أنت متأكد أنك تريد إيقاف هذه الوصفة؟')) {
                return;
            }
            fetch('prescription_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'change_status', prescription_id: prescriptionId, status: 'cancelled'})
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'فشل في إيقاف الوصفة');
                }
                alert(data.message);
                location.reload();
            })
            .catch(error => {
                console.error('Error stopping prescription:', error);
                alert('حدث خطأ في إيقاف الوصفة');
            });
        }

        function printPrescriptionModal() {
            const modalContent = document.querySelector('#viewPrescriptionModal .modal-body').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>طباعة الوصفة الطبية</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { font-family: Arial, sans-serif; direction: rtl; }
                        .no-print { display: none; }
                        @media print {
                            body { margin: 0; }
                            .modal-header, .modal-footer { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container mt-4">
                        ${modalContent}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        // Data loading when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadChildren();
            loadMedications();

            //Table filtering
            document.getElementById('searchChild').addEventListener('input', filterTable);
            document.getElementById('searchMedication').addEventListener('input', filterTable);
            document.getElementById('filterStatus').addEventListener('change', filterTable);
            document.getElementById('filterDate').addEventListener('change', filterTable);
        });

        // Table filtering
        function filterTable() {
            const searchChild = document.getElementById('searchChild').value.toLowerCase().trim();
            const searchMedication = document.getElementById('searchMedication').value.toLowerCase().trim();
            const filterStatus = document.getElementById('filterStatus').value;
            const filterDate = document.getElementById('filterDate').value;

            const rows = document.querySelectorAll('#prescriptionsTable tbody tr');

            rows.forEach(row => {
                const childName = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                const medicationName = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
                const status = row.dataset.status || '';
                const date = row.cells[7] ? row.cells[7].textContent.trim() : '';

                const matchesChild = !searchChild || childName.includes(searchChild);
                const matchesMedication = !searchMedication || medicationName.includes(searchMedication);
                const matchesStatus = !filterStatus || status === filterStatus;
                const matchesDate = !filterDate || date.includes(filterDate);

                row.style.display = matchesChild && matchesMedication && matchesStatus && matchesDate ? '' : 'none';
            });
        }

        // معالجة إضافة أو تحديث وصفة
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
                    alert(data.message);
                    location.reload();
                } else {
                    alert('خطأ في حفظ الوصفة: ' + data.message);
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