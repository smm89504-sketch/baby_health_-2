<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// جلب بيانات التوائم (أطفال نفس الوالد ولدوا في نفس التاريخ تقريباً أو تم تعيين نفس مجموعة توائم)
$query_twins = "SELECT c1.id as child1_id, c1.name as child1_name, c1.birth_date as child1_birth,
                       c2.id as child2_id, c2.name as child2_name, c2.birth_date as child2_birth,
                       p.full_name as parent_name
                FROM children c1
                JOIN children c2 ON c1.user_id = c2.user_id AND c1.id < c2.id
                JOIN users p ON c1.user_id = p.id
                WHERE (
                    (c1.twin_group IS NOT NULL AND c1.twin_group != '' AND c1.twin_group = c2.twin_group)
                    OR
                    ((c1.twin_group IS NULL OR c1.twin_group = '') AND (c2.twin_group IS NULL OR c2.twin_group = '') AND DATEDIFF(c1.birth_date, c2.birth_date) BETWEEN -7 AND 7)
                )
                ORDER BY p.full_name, c1.birth_date";

$stmt = $conn->prepare($query_twins);
$stmt->execute();
$twins_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مقارنة التوائم - الطبيب</title>
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

    <!-- Main contact-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>مقارنة التوائم</h1>
            <div>
                <select class="form-select d-inline-block w-auto me-2" id="twinsSelect">
                    <option value="">اختر زوج التوائم</option>
                    <?php while ($twins = $twins_result->fetch_assoc()): ?>
                        <option value="<?php echo $twins['child1_id'] . ',' . $twins['child2_id']; ?>">
                            <?php echo htmlspecialchars($twins['child1_name'] . ' و ' . $twins['child2_name'] . ' - ' . $twins['parent_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button class="btn btn-primary" onclick="compareTwins()">
                    <i class="fas fa-balance-scale"></i> مقارنة
                </button>
            </div>
        </div>

        <!-- المقارنةComparison Information-->
        <div class="row mb-4" id="comparisonInfo" style="display: none;">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 id="child1Name">-</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h6>الوزن الحالي</h6>
                                <h4 id="child1Weight">-</h4>
                                <small class="text-muted">كيلوغرام</small>
                            </div>
                            <div class="col-6">
                                <h6>الطول الحالي</h6>
                                <h4 id="child1Height">-</h4>
                                <small class="text-muted">سنتيمتر</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h6>متوسط الوزن</h6>
                                <h4 id="child1AvgWeight">-</h4>
                            </div>
                            <div class="col-6">
                                <h6>متوسط الطول</h6>
                                <h4 id="child1AvgHeight">-</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 id="child2Name">-</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h6>الوزن الحالي</h6>
                                <h4 id="child2Weight">-</h4>
                                <small class="text-muted">كيلوغرام</small>
                            </div>
                            <div class="col-6">
                                <h6>الطول الحالي</h6>
                                <h4 id="child2Height">-</h4>
                                <small class="text-muted">سنتيمتر</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h6>متوسط الوزن</h6>
                                <h4 id="child2AvgWeight">-</h4>
                            </div>
                            <div class="col-6">
                                <h6>متوسط الطول</h6>
                                <h4 id="child2AvgHeight">-</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الفرق بين التوائم -->
        <div class="card mb-4" id="differenceCard" style="display: none;">
            <div class="card-header">
                <h5>الفرق بين التوائم</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="alert alert-info">
                            <h6>فرق الوزن</h6>
                            <h4 id="weightDiff">-</h4>
                            <small class="text-muted">كيلوغرام</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success">
                            <h6>فرق الطول</h6>
                            <h4 id="heightDiff">-</h4>
                            <small class="text-muted">سنتيمتر</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning">
                            <h6>فرق محيط الرأس</h6>
                            <h4 id="headCircDiff">-</h4>
                            <small class="text-muted">سنتيمتر</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-danger">
                            <h6>فرق BMI</h6>
                            <h4 id="bmiDiff">-</h4>
                            <small class="text-muted">نقطة</small>
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary mt-3">
                    <h6><i class="fas fa-info-circle"></i> ملاحظات</h6>
                    <p id="comparisonNotes" class="mb-0">الفرق بين التوائم طبيعي ويعتبر ضمن الحدود المقبولة. استمر في مراقبة نموهما بانتظام.</p>
                </div>
            </div>
        </div>

        <!--Graph المقارنة -->
        <div class="row" id="chartsContainer" style="display: none;">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مقارنة الوزن</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="weightComparisonChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مقارنة الطول</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="heightComparisonChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مقارنة محيط الرأس</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="headCircComparisonChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>مقارنة مؤشر كتلة الجسم</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="bmiComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed comparison table-->
        <div class="card" id="comparisonTable" style="display: none;">
            <div class="card-header">
                <h5>جدول المقارنة التفصيلي</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>العمر (شهر)</th>
                                <th id="child1TableHeader">-</th>
                                <th id="child2TableHeader">-</th>
                                <th>الفرق</th>
                                <th>التقييم</th>
                            </tr>
                        </thead>
                        <tbody id="comparisonTableBody">
                            <!-- سيتم ملؤها ديناميكياً -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tips for twins-->
        <div class="card mt-4">
            <div class="card-header">
                <h5>نصائح خاصة برعاية التوائم</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-primary">
                            <h6><i class="fas fa-heart"></i> الرعاية العاطفية</h6>
                            <p class="mb-0">قضِ وقتاً منفصلاً مع كل توأم لتعزيز الشعور بالأمان والاستقلالية.</p>
                        </div>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-utensils"></i> التغذية</h6>
                            <p class="mb-0">تأكد من حصول كل توأم على تغذيته الكاملة دون تدخل الآخر.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-brain"></i> التطور</h6>
                            <p class="mb-0">شجع كل توأم على تطوير مهاراته الخاصة واهتماماته المستقلة.</p>
                        </div>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-stethoscope"></i> المتابعة الطبية</h6>
                            <p class="mb-0">قارن نمو التوائم بانتظام واستشر الطبيب عند وجود فروقات كبيرة.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let weightComparisonChart, heightComparisonChart, headCircComparisonChart, bmiComparisonChart;

        function compareTwins() {
            const twinsValue = document.getElementById('twinsSelect').value;
            if (!twinsValue) {
                alert('يرجى اختيار زوج التوائم أولاً');
                return;
            }

            const [child1Id, child2Id] = twinsValue.split(',');

            // محاكاة بيانات المقارنة
            const child1Data = {
                name: 'أحمد محمد',
                currentWeight: 9.5,
                currentHeight: 74,
                avgWeight: 9.2,
                avgHeight: 73.5,
                measurements: [
                    { month: 0, weight: 3.2, height: 50, headCirc: 35, bmi: 12.8 },
                    { month: 3, weight: 6.8, height: 62, headCirc: 41, bmi: 17.7 },
                    { month: 6, weight: 8.4, height: 69, headCirc: 45, bmi: 18.0 },
                    { month: 9, weight: 9.1, height: 73, headCirc: 46.5, bmi: 17.1 },
                    { month: 12, weight: 9.5, height: 74, headCirc: 47, bmi: 16.8 }
                ]
            };

            const child2Data = {
                name: 'محمد محمد',
                currentWeight: 9.8,
                currentHeight: 75,
                avgWeight: 9.4,
                avgHeight: 74.2,
                measurements: [
                    { month: 0, weight: 3.3, height: 51, headCirc: 35.5, bmi: 12.7 },
                    { month: 3, weight: 6.9, height: 63, headCirc: 41.5, bmi: 17.3 },
                    { month: 6, weight: 8.6, height: 70, headCirc: 45.5, bmi: 17.6 },
                    { month: 9, weight: 9.4, height: 74, headCirc: 47, bmi: 16.9 },
                    { month: 12, weight: 9.8, height: 75, headCirc: 47.5, bmi: 16.5 }
                ]
            };

            // عرض معلومات المقارنة
            document.getElementById('comparisonInfo').style.display = 'block';
            document.getElementById('child1Name').textContent = child1Data.name;
            document.getElementById('child1Weight').textContent = child1Data.currentWeight;
            document.getElementById('child1Height').textContent = child1Data.currentHeight;
            document.getElementById('child1AvgWeight').textContent = child1Data.avgWeight;
            document.getElementById('child1AvgHeight').textContent = child1Data.avgHeight;

            document.getElementById('child2Name').textContent = child2Data.name;
            document.getElementById('child2Weight').textContent = child2Data.currentWeight;
            document.getElementById('child2Height').textContent = child2Data.currentHeight;
            document.getElementById('child2AvgWeight').textContent = child2Data.avgWeight;
            document.getElementById('child2AvgHeight').textContent = child2Data.avgHeight;

            // حساب الفرق
            document.getElementById('differenceCard').style.display = 'block';
            document.getElementById('weightDiff').textContent = (child2Data.currentWeight - child1Data.currentWeight).toFixed(1);
            document.getElementById('heightDiff').textContent = (child2Data.currentHeight - child1Data.currentHeight).toFixed(1);
            document.getElementById('headCircDiff').textContent = (child2Data.measurements[4].headCirc - child1Data.measurements[4].headCirc).toFixed(1);
            document.getElementById('bmiDiff').textContent = (child2Data.measurements[4].bmi - child1Data.measurements[4].bmi).toFixed(1);

            // إنشاء المخططات
            document.getElementById('chartsContainer').style.display = 'block';
            createComparisonCharts(child1Data, child2Data);

            // ملء الجدول
            document.getElementById('comparisonTable').style.display = 'block';
            document.getElementById('child1TableHeader').textContent = child1Data.name;
            document.getElementById('child2TableHeader').textContent = child2Data.name;
            fillComparisonTable(child1Data, child2Data);
        }

        function createComparisonCharts(child1Data, child2Data) {
            const months = child1Data.measurements.map(m => m.month + ' شهر');

            // مخطط الوزن
            if (weightComparisonChart) weightComparisonChart.destroy();
            weightComparisonChart = new Chart(document.getElementById('weightComparisonChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: child1Data.name,
                        data: child1Data.measurements.map(m => m.weight),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }, {
                        label: child2Data.name,
                        data: child2Data.measurements.map(m => m.weight),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'مقارنة الوزن بين التوائم'
                        }
                    }
                }
            });

            // مخطط الطول
            if (heightComparisonChart) heightComparisonChart.destroy();
            heightComparisonChart = new Chart(document.getElementById('heightComparisonChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: child1Data.name,
                        data: child1Data.measurements.map(m => m.height),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }, {
                        label: child2Data.name,
                        data: child2Data.measurements.map(m => m.height),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'مقارنة الطول بين التوائم'
                        }
                    }
                }
            });

            // مخطط محيط الرأس
            if (headCircComparisonChart) headCircComparisonChart.destroy();
            headCircComparisonChart = new Chart(document.getElementById('headCircComparisonChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: child1Data.name,
                        data: child1Data.measurements.map(m => m.headCirc),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }, {
                        label: child2Data.name,
                        data: child2Data.measurements.map(m => m.headCirc),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'مقارنة محيط الرأس بين التوائم'
                        }
                    }
                }
            });

            // مخطط BMI
            if (bmiComparisonChart) bmiComparisonChart.destroy();
            bmiComparisonChart = new Chart(document.getElementById('bmiComparisonChart'), {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: child1Data.name,
                        data: child1Data.measurements.map(m => m.bmi),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        tension: 0.4
                    }, {
                        label: child2Data.name,
                        data: child2Data.measurements.map(m => m.bmi),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'مقارنة BMI بين التوائم'
                        }
                    }
                }
            });
        }

        function fillComparisonTable(child1Data, child2Data) {
            const tbody = document.getElementById('comparisonTableBody');
            tbody.innerHTML = '';

            child1Data.measurements.forEach((measurement, index) => {
                const child2Measurement = child2Data.measurements[index];
                const weightDiff = (child2Measurement.weight - measurement.weight).toFixed(1);
                const heightDiff = (child2Measurement.height - measurement.height).toFixed(1);

                let assessment = 'طبيعي';
                let badgeClass = 'bg-success';
                if (Math.abs(weightDiff) > 0.5 || Math.abs(heightDiff) > 2) {
                    assessment = 'يحتاج متابعة';
                    badgeClass = 'bg-warning';
                }
                if (Math.abs(weightDiff) > 1 || Math.abs(heightDiff) > 3) {
                    assessment = 'يحتاج تدخل';
                    badgeClass = 'bg-danger';
                }

                tbody.innerHTML += `
                    <tr>
                        <td>${measurement.month} شهر</td>
                        <td>${measurement.month}</td>
                        <td>${measurement.weight} كغ / ${measurement.height} سم</td>
                        <td>${child2Measurement.weight} كغ / ${child2Measurement.height} سم</td>
                        <td>${weightDiff} كغ / ${heightDiff} سم</td>
                        <td><span class="badge ${badgeClass}">${assessment}</span></td>
                    </tr>
                `;
            });
        }

        // تحميل البيانات عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            // تحميل قائمة التوائم
        });
    </script>
</body>
</html>