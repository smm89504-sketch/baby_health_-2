<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

/* ✅ Query improvement*/
$query = "SELECT c.*, 
COUNT(mv.id) as visit_count,
MAX(mv.visit_date) as last_visit
FROM children c
LEFT JOIN medical_visits mv 
ON c.id = mv.child_id AND mv.doctor_id = ?
GROUP BY c.id
ORDER BY c.name";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير الطبية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
<?php include 'sidebar.php'; ?>
        <main class="main-content">


<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="page-header">👨‍⚕️ إدارة المرضى</div>

    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPatientModal">
        + إضافة مريض
    </button>
</div>

<div class="table-responsive">
<table class="table table-hover align-middle">

<thead>
<tr>
    <th>الاسم</th>
    <th>تاريخ الميلاد</th>
    <th>العمر</th>
    <th>الجنس</th>
    <th>عدد الزيارات</th>
    <th>آخر زيارة</th>
    <th>الإجراءات</th>
</tr>
</thead>

<tbody>
<?php if ($result->num_rows > 0): ?>
<?php while ($patient = $result->fetch_assoc()): ?>
<tr>

<td><?= htmlspecialchars($patient['name']) ?></td>

<td><?= htmlspecialchars($patient['birth_date']) ?></td>

<td>
<?php
$age = (new DateTime())->diff(new DateTime($patient['birth_date']));
echo $age->y . ' سنة';
?>
</td>

<td><?= $patient['gender'] === 'male' ? 'ذكر' : 'أنثى' ?></td>

<td><?= $patient['visit_count'] ?></td>

<td><?= $patient['last_visit'] ? date('Y-m-d', strtotime($patient['last_visit'])) : '-' ?></td>

<td>
<div class="d-flex gap-2">
<a href="../child_details.php?id=<?= $patient['id'] ?>" class="btn btn-sm btn-info">عرض</a>
<a href="../medical_visits.php?child_id=<?= $patient['id'] ?>" class="btn btn-sm btn-primary">زيارات</a>
<a href="../medical_visits.php?child_id=<?= $patient['id'] ?>" class="btn btn-sm btn-success">+ زيارة</a>
</div>
</td>

</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="7" class="empty-box">
لا يوجد مرضى حالياً
</td>
</tr>
<?php endif; ?>
</tbody>

</table>
</div>

</div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="addPatientModal">
<div class="modal-dialog">
<div class="modal-content">

<div class="modal-header">
<h5>إضافة مريض</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input type="text" class="form-control mb-2" placeholder="اسم الطفل">
<input type="date" class="form-control mb-2">
<select class="form-control mb-2">
<option>ذكر</option>
<option>أنثى</option>
</select>
<input type="email" class="form-control" placeholder="بريد الوالد">
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
<button class="btn btn-primary">حفظ</button>
</div>

</div>
</div>
</div>
        </main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>