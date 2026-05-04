<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_visit'])) {
        $child_id = $_POST['child_id'];
        $visit_date = $_POST['visit_date'];
        $diagnosis = $_POST['diagnosis'];
        $prescription = $_POST['prescription'];
        $notes = $_POST['notes'];
        $doctor_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO medical_visits (child_id, doctor_id, visit_date, diagnosis, prescription, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $child_id, $doctor_id, $visit_date, $diagnosis, $prescription, $notes);
        if ($stmt->execute()) {
            $message = "تم إضافة الزيارة الطبية بنجاح";
        } else {
            $error = "حدث خطأ في إضافة الزيارة";
        }
    }

    if (isset($_POST['delete_visit'])) {
        $visit_id = (int)$_POST['visit_id'];
        $stmt = $conn->prepare("DELETE FROM medical_visits WHERE id = ? AND doctor_id = ?");
        $stmt->bind_param("ii", $visit_id, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $message = "تم حذف الزيارة بنجاح";
        } else {
            $error = "تعذر حذف الزيارة";
        }
    }
}

// Bringing medical visits to the doctor
$filterChildId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : null;
$query = "SELECT mv.*, c.name as child_name, c.birth_date
          FROM medical_visits mv
          JOIN children c ON mv.child_id = c.id
          WHERE mv.doctor_id = ?";
if ($filterChildId) {
    $query .= " AND mv.child_id = ?";
}
$query .= " ORDER BY mv.visit_date DESC";

$stmt = $conn->prepare($query);
if ($filterChildId) {
    $stmt->bind_param("ii", $_SESSION['user_id'], $filterChildId);
} else {
    $stmt->bind_param("i", $_SESSION['user_id']);
}
$stmt->execute();
$visits_result = $stmt->get_result();

// Bring children to the drop-down list للقائمة المنسدلة
$children_query = "SELECT id, name FROM children ORDER BY name";
$children_result = $conn->query($children_query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الزيارات الطبية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
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
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'D', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً د.</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['name'] ?? 'طبيب'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الزيارات الطبية</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitModal">
                <i class="fas fa-plus"></i> إضافة زيارة جديدة
            </button>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>اسم الطفل</th>
                                <th>تاريخ الزيارة</th>
                                <th>التشخيص</th>
                                <th>الوصفة الطبية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($visits_result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">لا توجد زيارات طبية بعد. اضغط على "إضافة زيارة جديدة" لتسجيل أول زيارة.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($visit = $visits_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($visit['child_name']); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($visit['visit_date']))); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['diagnosis'], 0, 50)) . (strlen($visit['diagnosis']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['prescription'], 0, 50)) . (strlen($visit['prescription']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                           <button class="btn btn-sm btn-info" onclick="viewVisit(<?php echo $visit['id']; ?>)">
    <i class="fas fa-eye"></i> عرض
</button>

<button class="btn btn-sm btn-warning" onclick="editVisit(<?php echo $visit['id']; ?>)">
    <i class="fas fa-edit"></i> تعديل
</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('هل ترغب بالتأكيد في حذف هذه الزيارة؟');">
                                                <input type="hidden" name="delete_visit" value="1">
                                                <input type="hidden" name="visit_id" value="<?php echo $visit['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> حذف
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal To add a new visit-->
    <div class="modal fade" id="addVisitModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة زيارة طبية جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="child_id" class="form-label">الطفل</label>
                                <select class="form-select" id="child_id" name="child_id" required>
                                    <option value="">اختر الطفل</option>
                                    <?php
                                    $children_result->data_seek(0); // Reset result pointer
                                    while ($child = $children_result->fetch_assoc()): ?>
                                        <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label">تاريخ ووقت الزيارة</label>
                                <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">التشخيص</label>
                            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" placeholder="اكتب التشخيص الطبي"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="prescription" class="form-label">الوصفة الطبية</label>
                            <textarea class="form-control" id="prescription" name="prescription" rows="3" placeholder="اكتب الوصفة الطبية والأدوية"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات إضافية</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="ملاحظات إضافية عن الزيارة"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" name="add_visit" class="btn btn-primary">إضافة الزيارة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewVisit(visitId) {
    const modal = new bootstrap.Modal(document.getElementById('viewVisitModal'));
    document.getElementById('visitDetails').innerHTML = 'جاري التحميل...';

    fetch('view_visit_details.php?id=' + visitId)
        .then(res => res.text())
        .then(data => {
            document.getElementById('visitDetails').innerHTML = data;
            modal.show();
        })
        .catch(() => {
            document.getElementById('visitDetails').innerHTML = 'حدث خطأ';
        });
}

       function editVisit(visitId) {
    fetch('get_visit.php?id=' + visitId)
        .then(res => res.json())
        .then(data => {
            // تعبئة الفورم
            document.getElementById('edit_visit_id').value = data.id;
            document.getElementById('edit_diagnosis').value = data.diagnosis;
            document.getElementById('edit_prescription').value = data.prescription;
            document.getElementById('edit_notes').value = data.notes;

            new bootstrap.Modal(document.getElementById('editVisitModal')).show();
        })
        .catch(err => {
            console.error('خطأ في جلب البيانات:', err);
            alert('حدث خطأ في تحميل بيانات الزيارة');
        });
}
    </script>
<!-- Modal visit view-->
<div class="modal fade" id="viewVisitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الزيارة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitDetails">
                جاري التحميل...
            </div>
        </div>
    </div>
</div>
<!-- Modal Edit Visit-->
<div class="modal fade" id="editVisitModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تعديل الزيارة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="update_visit.php">
        <div class="modal-body">
          <input type="hidden" id="edit_visit_id" name="visit_id">
          <div class="mb-3">
            <label for="edit_diagnosis" class="form-label">التشخيص</label>
            <textarea id="edit_diagnosis" name="diagnosis" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label for="edit_prescription" class="form-label">الوصفة الطبية</label>
            <textarea id="edit_prescription" name="prescription" class="form-control" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label for="edit_notes" class="form-label">ملاحظات إضافية</label>
            <textarea id="edit_notes" name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
          <button type="submit" name="update_visit" class="btn btn-warning">حفظ التعديلات</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>