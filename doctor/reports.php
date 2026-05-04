<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Get report statistics احصائيات
$query_stats = "SELECT
    COUNT(DISTINCT child_id) as total_patients,
    COUNT(*) as total_visits,
    COUNT(CASE WHEN visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as visits_last_30_days,
    COUNT(DISTINCT CASE WHEN visit_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN child_id END) as active_patients
    FROM medical_visits WHERE doctor_id = ?";

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
    <title>التقارير الطبية - الطبيب</title>
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

    <!--Main contact-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>التقارير الطبية</h1>
            <div>
                <button class="btn btn-success me-2" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </button>
                <button class="btn btn-primary" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </button>
            </div>
        </div>

        <!-- Quick statsاحصائيات-->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?php echo $stats['total_patients']; ?></h3>
                        <p class="mb-0">إجمالي المرضى</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo $stats['total_visits']; ?></h3>
                        <p class="mb-0">إجمالي الزيارات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?php echo $stats['visits_last_30_days']; ?></h3>
                        <p class="mb-0">زيارات آخر 30 يوم</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?php echo $stats['active_patients']; ?></h3>
                        <p class="mb-0">المرضى النشطون</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report filters-->
        <div class="card mb-4">
            <div class="card-header">
                <h5>فلاتر التقرير</h5>
            </div>
            <div class="card-body">
                <form class="row g-3" id="reportFilters">
                    <div class="col-md-3">
                        <label for="report_type" class="form-label">نوع التقرير</label>
                        <select class="form-select" id="report_type">
                            <option value="visits">تقرير الزيارات</option>
                            <option value="diagnoses">تقرير التشخيصات</option>
                            <option value="medications">تقرير الأدوية</option>
                            <option value="growth">تقرير النمو</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">من تاريخ</label>
                        <input type="date" class="form-control" id="date_from">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">إلى تاريخ</label>
                        <input type="date" class="form-control" id="date_to">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary w-100" onclick="generateReport()">
                            <i class="fas fa-chart-bar"></i> إنشاء التقرير
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!--Report display area-->
        <div class="card">
            <div class="card-header">
                <h5>نتائج التقرير</h5>
            </div>
            <div class="card-body">
                <canvas id="reportChart" style="max-height: 400px;"></canvas>
                <div id="reportTable" class="mt-4" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-striped" id="reportDataTable">
                            <thead>
                                <tr id="tableHeader">
                                    <!-- سيتم ملؤها ديناميكياً -->
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- سيتم ملؤها ديناميكياً -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly reports-->
        <div class="card mt-4">
            <div class="card-header">
                <h5>التقارير الشهرية</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h4 class="text-primary">تقرير شهر <?php echo date('M Y'); ?></h4>
                                <p>الزيارات والتشخيصات والأدوية الموصوفة</p>
                                <button class="btn btn-outline-primary" onclick="viewMonthlyReport('<?php echo date('Y-m'); ?>')">
                                    عرض التقرير
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h4 class="text-success">تقرير النمو</h4>
                                <p>تطور الأطفال ومقارنة التوائم</p>
                                <button class="btn btn-outline-success" onclick="viewGrowthReport()">
                                    عرض التقرير
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h4 class="text-warning">تقرير التغذية</h4>
                                <p>الوجبات المسجلة وقيمها الغذائية</p>
                                <button class="btn btn-outline-warning" onclick="viewNutritionReport()">
                                    عرض التقرير
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let reportChart = null;

        function generateReport() {
            const reportType = document.getElementById('report_type').value;
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;

            // إرسال طلب AJAX للحصول على البيانات
            fetch('get_report_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_type: reportType,
                    date_from: dateFrom,
                    date_to: dateTo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateChart(data.chart_data, reportType);
                    updateTable(data.table_data, reportType);
                } else {
                    alert('خطأ في جلب البيانات: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
        }

        function updateChart(chartData, reportType) {
            if (reportChart) {
                reportChart.destroy();
            }

            let title = '';
            switch(reportType) {
                case 'visits':
                    title = 'تقرير الزيارات الطبية';
                    break;
                case 'diagnoses':
                    title = 'تقرير التشخيصات';
                    break;
                case 'medications':
                    title = 'تقرير الأدوية الموصوفة';
                    break;
                case 'growth':
                    title = 'تقرير النمو';
                    break;
            }

            reportChart = new Chart(document.getElementById('reportChart'), {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: title
                        }
                    }
                }
            });
        }

        function updateTable(tableData, reportType) {
            const tableHeader = document.getElementById('tableHeader');
            const tableBody = document.getElementById('tableBody');

            // Clear previous dataمسح البيانات السابقة
            tableHeader.innerHTML = '';
            tableBody.innerHTML = '';

            if (tableData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="3" class="text-center">لا توجد بيانات</td></tr>';
                return;
            }

            //Creating table headers إنشاء رؤوس الجدول
            const headers = Object.keys(tableData[0]);
            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                tableHeader.appendChild(th);
            });

            //Add data إضافة البيانات
            tableData.forEach(row => {
                const tr = document.createElement('tr');
                headers.forEach(header => {
                    const td = document.createElement('td');
                    td.textContent = row[header];
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            });

            //Show table
            document.getElementById('reportTable').style.display = 'block';
        }

        function exportReport(format) {
            // Creating the report contentإنشاء محتوى التقرير
            const reportData = {
                type: 'export',
                format: format,
                dateFrom: document.getElementById('date_from').value,
                dateTo: document.getElementById('date_to').value,
                reportType: document.getElementById('report_type').value
            };

            //Create a download link
            const link = document.createElement('a');
            link.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent(JSON.stringify(reportData, null, 2));
            link.download = 'report.' + format;
            link.click();

            alert('تم تصدير التقرير بصيغة ' + format.toUpperCase());
        }

        function viewMonthlyReport(month) {
            // Report update for the specified monthتحديث التقرير للشهر المحدد
            document.getElementById('date_from').value = month + '-01';
            document.getElementById('date_to').value = month + '-31';
            generateReport();
            alert('تم تحديث التقرير لشهر ' + month);
        }

        function viewGrowthReport() {
            // Redirected to the growth pageإعادة توجيه إلى صفحة النمو
            window.location.href = '/baby_health/doctor/growth_charts.php';
        }

        function viewNutritionReport() {
            //Redirected to the nutrition page إعادة توجيه إلى صفحة التغذية
            window.location.href = '/baby_health/doctor/nutrition.php';
        }

        // Download the default reportتحميل التقرير الافتراضي
        document.addEventListener('DOMContentLoaded', function() {
            generateReport();
        });
    </script>
</body>
</html>