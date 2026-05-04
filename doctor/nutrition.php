<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_nutrition') {
            $age_min = (int)$_POST['age_min_months'];
            $age_max = (int)$_POST['age_max_months'];
            $allowed_foods = trim($_POST['allowed_foods'] ?? '');
            $restricted_foods = trim($_POST['restricted_foods'] ?? '');
            $nutrition_tips = trim($_POST['nutrition_tips'] ?? '');

            $stmt = $conn->prepare("INSERT INTO nutrition_guidelines (age_min_months, age_max_months, allowed_foods, restricted_foods, nutrition_tips) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $age_min, $age_max, $allowed_foods, $restricted_foods, $nutrition_tips);
            if ($stmt->execute()) {
                $message = 'تم إضافة إرشادات التغذية بنجاح';
            } else {
                $error = 'فشل في إضافة الإرشادات';
            }
        }

        if ($action === 'update_nutrition') {
            $id = (int)$_POST['nutrition_id'];
            $age_min = (int)$_POST['age_min_months'];
            $age_max = (int)$_POST['age_max_months'];
            $allowed_foods = trim($_POST['allowed_foods'] ?? '');
            $restricted_foods = trim($_POST['restricted_foods'] ?? '');
            $nutrition_tips = trim($_POST['nutrition_tips'] ?? '');

            $stmt = $conn->prepare("UPDATE nutrition_guidelines SET age_min_months = ?, age_max_months = ?, allowed_foods = ?, restricted_foods = ?, nutrition_tips = ? WHERE id = ?");
            $stmt->bind_param("iisssi", $age_min, $age_max, $allowed_foods, $restricted_foods, $nutrition_tips, $id);
            if ($stmt->execute()) {
                $message = 'تم تحديث الإرشادات بنجاح';
            } else {
                $error = 'فشل في تحديث الإرشادات';
            }
        }

        if ($action === 'delete_nutrition') {
            $id = (int)$_POST['nutrition_id'];
            $stmt = $conn->prepare("DELETE FROM nutrition_guidelines WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'تم حذف الإرشادات بنجاح';
            } else {
                $error = 'فشل في حذف الإرشادات';
            }
        }
    }
}

// Get nutrition lists by age group جلب قوائم التغذية حسب الفئة العمرية
$query = "SELECT * FROM nutrition_guidelines ORDER BY age_min_months";
$result = $conn->query($query);
$nutrition_records = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $nutrition_records[] = $row;
    }
}

// Bring the children to the meal log.جلب الأطفال لقائمة تسجيل الوجبات
$child_list_query = "SELECT id, name FROM children ORDER BY name";
$children = $conn->query($child_list_query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التغذية - الطبيب</title>
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
            <h1>إدارة التغذية</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNutritionModal">
                <i class="fas fa-plus"></i> إضافة إرشادات تغذية
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($nutrition_records as $nutrition): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100" data-nutrition-id="<?php echo $nutrition['id']; ?>" data-age-min="<?php echo $nutrition['age_min_months']; ?>" data-age-max="<?php echo $nutrition['age_max_months']; ?>" data-allowed-foods="<?php echo htmlspecialchars($nutrition['allowed_foods'], ENT_QUOTES); ?>" data-restricted-foods="<?php echo htmlspecialchars($nutrition['restricted_foods'], ENT_QUOTES); ?>" data-nutrition-tips="<?php echo htmlspecialchars($nutrition['nutrition_tips'], ENT_QUOTES); ?>">
                        <div class="card-header">
                            <h5 class="card-title">
                                <?php echo $nutrition['age_min_months']/12; ?> - <?php echo $nutrition['age_max_months']/12; ?> سنوات
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>الأطعمة المسموحة:</h6>
                            <p class="text-success"><?php echo htmlspecialchars($nutrition['allowed_foods']); ?></p>

                            <h6>الأطعمة الممنوعة:</h6>
                            <p class="text-danger"><?php echo htmlspecialchars($nutrition['restricted_foods']); ?></p>

                            <h6>نصائح تغذية:</h6>
                            <p><?php echo htmlspecialchars($nutrition['nutrition_tips']); ?></p>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    تم التحديث: <?php echo htmlspecialchars(date('Y-m-d', strtotime($nutrition['updated_at']))); ?>
                                </small>
                                <div>
                                    <button class="btn btn-sm btn-warning" onclick="editNutrition(<?php echo $nutrition['id']; ?>)">
                                        <i class="fas fa-edit">تعديل</i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewNutrition(<?php echo $nutrition['id']; ?>)">
                                        <i class="fas fa-eye">عرض</i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteNutrition(<?php echo $nutrition['id']; ?>)">
                                        <i class="fas fa-trash">حذف</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($nutrition_records) === 0): ?>
            <div class="text-center mt-5">
                <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">لا توجد إرشادات تغذية</h4>
                <p class="text-muted">ابدأ بإضافة إرشادات التغذية لمختلف الفئات العمرية</p>
            </div>
        <?php endif; ?>

        <!-- قسم تسجيل الوجبات -->
        <!-- <div class="card mt-5">
            <div class="card-header">
                <h5>تسجيل الوجبات اليومية</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <label for="child_select" class="form-label">الطفل</label>
                        <select class="form-select" id="child_select" name="child_id" required>
                            <option value="">اختر الطفل</option>
                            <?php if ($children && $children->num_rows > 0): ?>
                                <?php while ($child = $children->fetch_assoc()): ?>
                                    <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="meal_date" class="form-label">التاريخ</label>
                        <input type="date" class="form-control" id="meal_date" name="meal_date" required>
                    </div>
                    <div class="col-md-2">
                        <label for="meal_time" class="form-label">الوقت</label>
                        <input type="time" class="form-control" id="meal_time" name="meal_time" required>
                    </div>
                    <div class="col-md-3">
                        <label for="meal_type" class="form-label">نوع الوجبة</label>
                        <select class="form-select" id="meal_type" name="meal_type" required>
                            <option value="breakfast">الفطور</option>
                            <option value="lunch">الغداء</option>
                            <option value="dinner">العشاء</option>
                            <option value="snack">وجبة خفيفة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="add_meal" class="btn btn-success w-100">إضافة</button>
                    </div>
                    <div class="col-12">
                        <label for="meal_description" class="form-label">وصف الوجبة</label>
                        <textarea class="form-control" id="meal_description" name="meal_description" rows="2" placeholder="اكتب مكونات الوجبة والكميات"></textarea>
                    </div>
                </form>
            </div>
        </div> -->
    </main>

    <!-- Modal To view detailed nutrition guidelines-->
    <div class="modal fade" id="viewNutritionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل إرشادات التغذية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewNutritionContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal To modify nutrition guidelines-->
    <div class="modal fade" id="editNutritionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل إرشادات التغذية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editNutritionForm">
                    <input type="hidden" name="action" value="update_nutrition">
                    <input type="hidden" name="nutrition_id" id="edit_nutrition_id" value="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="edit_age_min" class="form-label">العمر الأدنى (بالأشهر)</label>
                                <input type="number" class="form-control" id="edit_age_min" name="age_min_months" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_age_max" class="form-label">العمر الأقصى (بالأشهر)</label>
                                <input type="number" class="form-control" id="edit_age_max" name="age_max_months" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_allowed_foods" class="form-label">الأطعمة المسموحة</label>
                            <textarea class="form-control" id="edit_allowed_foods" name="allowed_foods" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_restricted_foods" class="form-label">الأطعمة الممنوعة</label>
                            <textarea class="form-control" id="edit_restricted_foods" name="restricted_foods" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nutrition_tips" class="form-label">نصائح التغذية</label>
                            <textarea class="form-control" id="edit_nutrition_tips" name="nutrition_tips" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal To add nutrition guidelines-->
    <div class="modal fade" id="addNutritionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة إرشادات تغذية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addNutritionForm" method="POST">
                        <input type="hidden" name="action" value="add_nutrition">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="ageMin" class="form-label">العمر الأدنى (بالأشهر)</label>
                                <input type="number" class="form-control" id="ageMin" name="age_min_months" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ageMax" class="form-label">العمر الأقصى (بالأشهر)</label>
                                <input type="number" class="form-control" id="ageMax" name="age_max_months" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="allowedFoods" class="form-label">الأطعمة المسموحة</label>
                            <textarea class="form-control" id="allowedFoods" name="allowed_foods" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="restrictedFoods" class="form-label">الأطعمة الممنوعة</label>
                            <textarea class="form-control" id="restrictedFoods" name="restricted_foods" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="nutritionTips" class="form-label">نصائح التغذية</label>
                            <textarea class="form-control" id="nutritionTips" name="nutrition_tips" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" form="addNutritionForm">إضافة</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const nutritionData = <?php echo json_encode($nutrition_records); ?>;

        function viewNutrition(id) {
            const item = nutritionData.find(n => n.id == id);
            if (!item) return;
            document.getElementById('viewNutritionContent').innerHTML = `
                <p><strong>العمر:</strong> ${item.age_min_months} - ${item.age_max_months} أشهر</p>
                <p><strong>الأطعمة المسموحة:</strong> ${item.allowed_foods}</p>
                <p><strong>الأطعمة الممنوعة:</strong> ${item.restricted_foods}</p>
                <p><strong>نصائح التغذية:</strong> ${item.nutrition_tips}</p>
                <p><small class="text-muted">آخر تحديث: ${item.updated_at}</small></p>
            `;
            const viewModal = new bootstrap.Modal(document.getElementById('viewNutritionModal'));
            viewModal.show();
        }

        function editNutrition(id) {
            const item = nutritionData.find(n => n.id == id);
            if (!item) return;
            document.getElementById('edit_nutrition_id').value = item.id;
            document.getElementById('edit_age_min').value = item.age_min_months;
            document.getElementById('edit_age_max').value = item.age_max_months;
            document.getElementById('edit_allowed_foods').value = item.allowed_foods;
            document.getElementById('edit_restricted_foods').value = item.restricted_foods;
            document.getElementById('edit_nutrition_tips').value = item.nutrition_tips;
            const editModal = new bootstrap.Modal(document.getElementById('editNutritionModal'));
            editModal.show();
        }

        function deleteNutrition(id) {
            if (confirm('هل أنت متأكد من حذف هذه الإرشادات؟')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_nutrition">
                    <input type="hidden" name="nutrition_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

    </script>
</body>
</html>