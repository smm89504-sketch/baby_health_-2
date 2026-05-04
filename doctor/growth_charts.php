<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bright growth data for children ل (أطفال الذين قام الطبيب بزيارتهم أو متابعتهم)
$query_growth = "SELECT DISTINCT c.id as child_id, c.name, c.birth_date, g.age_months, g.weight_kg, g.height_cm, g.head_circumference_cm, g.bmi, g.growth_percentile, g.measurement_date
                 FROM children c
                 LEFT JOIN growth_measurements g ON c.id = g.child_id AND g.doctor_id = ?
                 WHERE c.id IN (
                   SELECT DISTINCT child_id FROM medical_visits WHERE doctor_id = ?
                   UNION
                   SELECT DISTINCT child_id FROM growth_measurements WHERE doctor_id = ?
                 )
                 ORDER BY c.name, g.measurement_date DESC";

$stmt = $conn->prepare($query_growth);
$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$growth_result = $stmt->get_result();

// تهيئة مصفوفة use in JavaScript
$growth_data = [];
while ($row = $growth_result->fetch_assoc()) {
    $growth_data[] = $row;
}

// Bring the children whom the doctor visited
$children_query = "SELECT DISTINCT c.id, c.name FROM children c 
                   WHERE c.id IN (
                     SELECT DISTINCT child_id FROM medical_visits WHERE doctor_id = ?
                     UNION
                     SELECT DISTINCT child_id FROM growth_measurements WHERE doctor_id = ?
                   )
                   ORDER BY c.name";
$stmt_children = $conn->prepare($children_query);
$stmt_children->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt_children->execute();
$children_result = $stmt_children->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مخططات النمو - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!--Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>مخططات النمو</h1>
            <div>
                <select class="form-select d-inline-block w-auto me-2" id="childSelect">
                    <option value="">اختر الطفل</option>
                    <?php while ($child = $children_result->fetch_assoc()): ?>
                        <option value="<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <button class="btn btn-primary" onclick="generateChart()">
                    <i class="fas fa-chart-line"></i> إنشاء المخطط
                </button>
            </div>
        </div>

        <!-- Growth information-->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-weight fa-2x text-primary mb-2"></i>
                        <h6>متوسط الوزن</h6>
                        <h4 id="avgWeight">-</h4>
                        <small class="text-muted">كيلوغرام</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ruler-vertical fa-2x text-success mb-2"></i>
                        <h6>متوسط الطول</h6>
                        <h4 id="avgHeight">-</h4>
                        <small class="text-muted">سنتيمتر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-circle fa-2x text-warning mb-2"></i>
                        <h6>متوسط محيط الرأس</h6>
                        <h4 id="avgHeadCirc">-</h4>
                        <small class="text-muted">سنتيمتر</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-bar fa-2x text-info mb-2"></i>
                        <h6>متوسط مؤشر كتلة الجسم</h6>
                        <h4 id="avgBMI">-</h4>
                        <small class="text-muted">BMI</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth charts-->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مخطط الوزن مع الوقت</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="weightChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مخطط الطول مع الوقت</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="heightChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مخطط محيط الرأس</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="headCircChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مخطط مؤشر كتلة الجسم</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="bmiChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth data table-->
        <div class="card">
            <div class="card-header">
                <h5>بيانات النمو التفصيلية</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>الطفل</th>
                                <th>تاريخ القياس</th>
                                <th>العمر (شهر)</th>
                                <th>الوزن (كغ)</th>
                                <th>الطول (سم)</th>
                                <th>محيط الرأس (سم)</th>
                                <th>BMI</th>
                                <th>المئوية</th>
                                <th>التقييم</th>
                            </tr>
                        </thead>
                        <tbody id="growthTableBody">
                            <?php foreach ($growth_data as $growth): ?>
                                <tr data-child-id="<?php echo htmlspecialchars($growth['child_id']); ?>">
                                    <td><?php echo htmlspecialchars($growth['name']); ?></td>
                                    <td><?php echo htmlspecialchars($growth['measurement_date']); ?></td>
                                    <td><?php echo $growth['age_months']; ?></td>
                                    <td><?php echo $growth['weight_kg']; ?></td>
                                    <td><?php echo $growth['height_cm']; ?></td>
                                    <td><?php echo $growth['head_circumference_cm']; ?></td>
                                    <td><?php echo number_format($growth['bmi'], 1); ?></td>
                                    <td><?php echo $growth['growth_percentile']; ?>%</td>
                                    <td>
                                        <?php
                                        $percentile = $growth['growth_percentile'];
                                        if ($percentile < 5) echo '<span class="badge bg-danger">منخفض</span>';
                                        elseif ($percentile < 25) echo '<span class="badge bg-warning">منخفض قليلاً</span>';
                                        elseif ($percentile <= 75) echo '<span class="badge bg-success">طبيعي</span>';
                                        elseif ($percentile <= 95) echo '<span class="badge bg-warning">مرتفع قليلاً</span>';
                                        else echo '<span class="badge bg-danger">مرتفع</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!--Growth tips-->
        <div class="card mt-4">
            <div class="card-header">
                <h5>نصائح وإرشادات النمو</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb"></i> مراقبة الوزن</h6>
                            <p class="mb-0">يجب مراقبة زيادة الوزن بانتظام. الزيادة الطبيعية 150-200غرام أسبوعياً في الأشهر الأولى.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-ruler"></i> قياس الطول</h6>
                            <p class="mb-0">قيس طول الطفل شهرياً. الطول الطبيعي يزيد بمعدل 2.5 سم شهرياً في السنة الأولى.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-brain"></i> نمو الدماغ</h6>
                            <p class="mb-0">محيط الرأس يعكس نمو الدماغ. يزيد بمعدل 1.5 سم شهرياً في الأشهر الأولى.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let weightChart, heightChart, headCircChart, bmiChart;
        const growthData = <?= json_encode($growth_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

        function formatNumber(value, decimals = 1) {
            if (value === null || value === undefined || value === '') return '-';
            const floatValue = parseFloat(value);
            if (Number.isNaN(floatValue)) return '-';
            return floatValue.toFixed(decimals);
        }

        function updateSummary(values, elementId) {
            if (!values.length) {
                document.getElementById(elementId).textContent = '-';
                return;
            }
            const average = values.reduce((a, b) => a + b, 0) / values.length;
            document.getElementById(elementId).textContent = average.toFixed(1);
        }

        function generateChart() {
            const childId = document.getElementById('childSelect').value;
            if (!childId) {
                alert('يرجى اختيار طفل أولاً');
                return;
            }

            const childRecords = growthData
                .filter(r => String(r.child_id) === String(childId))
                .filter(r => r.measurement_date)
                .sort((a, b) => new Date(a.measurement_date) - new Date(b.measurement_date));

            if (!childRecords.length) {
                alert('لا توجد بيانات نمو لهذا الطفل بعد.');
                ['avgWeight', 'avgHeight', 'avgHeadCirc', 'avgBMI'].forEach(id => document.getElementById(id).textContent = '-');
                if (weightChart) weightChart.destroy();
                if (heightChart) heightChart.destroy();
                if (headCircChart) headCircChart.destroy();
                if (bmiChart) bmiChart.destroy();
                return;
            }

            const months = childRecords.map(r => r.age_months || r.measurement_date);
            const weightData = childRecords.map(r => parseFloat(r.weight_kg) || 0);
            const heightData = childRecords.map(r => parseFloat(r.height_cm) || 0);
            const headCircData = childRecords.map(r => parseFloat(r.head_circumference_cm) || 0);
            const bmiData = childRecords.map(r => parseFloat(r.bmi) || 0);

            updateSummary(weightData, 'avgWeight');
            updateSummary(heightData, 'avgHeight');
            updateSummary(headCircData, 'avgHeadCirc');
            updateSummary(bmiData, 'avgBMI');

            // weight
            if (weightChart) weightChart.destroy();
            weightChart = new Chart(document.getElementById('weightChart'), {
                type: 'line',
                data: {
                    labels: months.map(m => `${m} شهر`),
                    datasets: [{
                        label: 'الوزن (كغ)',
                        data: weightData,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }]
                },
                options: { responsive: true }
            });

            // height
            if (heightChart) heightChart.destroy();
            heightChart = new Chart(document.getElementById('heightChart'), {
                type: 'line',
                data: {
                    labels: months.map(m => `${m} شهر`),
                    datasets: [{
                        label: 'الطول (سم)',
                        data: heightData,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    }]
                },
                options: { responsive: true }
            });

            // head circumference
            if (headCircChart) headCircChart.destroy();
            headCircChart = new Chart(document.getElementById('headCircChart'), {
                type: 'line',
                data: {
                    labels: months.map(m => `${m} شهر`),
                    datasets: [{
                        label: 'محيط الرأس (سم)',
                        data: headCircData,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4
                    }]
                },
                options: { responsive: true }
            });

            // BMI
            if (bmiChart) bmiChart.destroy();
            bmiChart = new Chart(document.getElementById('bmiChart'), {
                type: 'line',
                data: {
                    labels: months.map(m => `${m} شهر`),
                    datasets: [{
                        label: 'BMI',
                        data: bmiData,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4
                    }]
                },
                options: { responsive: true }
            });

            // تصفية الجدول لعرض الطفل المحدد فقط
            const tbody = document.getElementById('growthTableBody');
            const allRows = tbody.querySelectorAll('tr');
            allRows.forEach(row => {
                const childName = row.querySelector('td').textContent.trim();
                const recordChildId = row.dataset.childId;
                if (String(recordChildId) === String(childId)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // generateChart(); // أترك المستخدم يضغط زر إنشاء
        });
    </script>
</body>
</html>