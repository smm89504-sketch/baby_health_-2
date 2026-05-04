<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_visit'])) {
        // Add medical visit logic here
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
}

// Fetch children for dropdown
$children_query = "SELECT id, name FROM children ORDER BY name";
$children_result = $conn->query($children_query);

// Fetch medical visits
$visits_query = "SELECT mv.*, c.name as child_name FROM medical_visits mv JOIN children c ON mv.child_id = c.id ORDER BY mv.visit_date DESC";
$visits_result = $conn->query($visits_query);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الزيارات الطبية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-4">إدارة الزيارات الطبية</h1>

            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add New Visit Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>إضافة زيارة طبية جديدة</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="child_id" class="form-label">الطفل</label>
                                <select class="form-select" id="child_id" name="child_id" required>
                                    <option value="">اختر الطفل</option>
                                    <?php while ($child = $children_result->fetch_assoc()): ?>
                                        <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="visit_date" class="form-label">تاريخ الزيارة</label>
                                <input type="datetime-local" class="form-control" id="visit_date" name="visit_date" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">التشخيص</label>
                            <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="prescription" class="form-label">الوصفة الطبية</label>
                            <textarea class="form-control" id="prescription" name="prescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        <button type="submit" name="add_visit" class="btn btn-primary">إضافة الزيارة</button>
                    </form>
                </div>
            </div>

            <!-- List of Visits -->
            <div class="card">
                <div class="card-header">
                    <h5>قائمة الزيارات الطبية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>الطفل</th>
                                <th>تاريخ الزيارة</th>
                                <th>التشخيص</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($visits_result->num_rows > 0): ?>
                                <?php while ($visit = $visits_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($visit['child_name']); ?></td>
                                        <td><?php echo htmlspecialchars($visit['visit_date']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($visit['diagnosis'], 0, 50)) . (strlen($visit['diagnosis']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="viewVisit(<?php echo $visit['id']; ?>)">عرض</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">لا توجد زيارات طبية مسجلة</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>