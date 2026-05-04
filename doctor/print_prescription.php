<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';

if (!isset($_GET['id'])) {
    die('معرف الوصفة مطلوب');
}

$db = new DatabaseHelper();
$conn = $db->getConnection();
$prescription_id = (int)$_GET['id'];

// Retrieve recipe data
$query = "SELECT p.*, c.name as child_name, c.birth_date, c.gender,
                 pr.full_name as parent_name, pr.phone as parent_phone,
                 u.full_name as doctor_name,
                 GROUP_CONCAT(DISTINCT m.name SEPARATOR '\n') as medication_names,
                 GROUP_CONCAT(DISTINCT pm.dosage SEPARATOR '\n') as dosages,
                 GROUP_CONCAT(DISTINCT pm.frequency SEPARATOR '\n') as frequencies,
                 GROUP_CONCAT(DISTINCT pm.duration_days SEPARATOR '\n') as durations,
                 GROUP_CONCAT(DISTINCT m.dosage_form SEPARATOR '\n') as dosage_forms,
                 GROUP_CONCAT(DISTINCT m.concentration SEPARATOR '\n') as concentrations,
                 GROUP_CONCAT(DISTINCT pm.notes SEPARATOR '\n') as instructions
          FROM prescriptions p
          JOIN children c ON p.child_id = c.id
          JOIN users pr ON c.user_id = pr.id
          JOIN users u ON p.doctor_id = u.id
          LEFT JOIN prescription_medications pm ON pm.prescription_id = p.id
          LEFT JOIN medications m ON pm.medication_id = m.id
          WHERE p.id = ? AND p.doctor_id = ?
          GROUP BY p.id";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $prescription_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('الوصفة غير موجودة أو لا تملك صلاحية الوصول إليها');
}

$prescription = $result->fetch_assoc();

// Calculating a child's age
$birth_date = new DateTime($prescription['birth_date']);
$today = new DateTime();
$age = $today->diff($birth_date);
$age_text = $age->y . ' سنة و ' . $age->m . ' شهر';

// Gender reassignmentتحويل الجنس
$gender_text = $prescription['gender'] === 'male' ? 'ذكر' : 'أنثى';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طباعة الوصفة الطبية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            direction: rtl;
            background: white;
            margin: 0;
            padding: 20px;
        }

        .prescription-header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .clinic-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .prescription-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            color: #333;
        }

        .patient-info, .doctor-info {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            min-width: 120px;
        }

        .medications-section {
            margin-top: 30px;
        }

        .medications-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .medications-table th,
        .medications-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: right;
            vertical-align: top;
        }

        .medications-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .instructions-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .doctor-signature {
            margin-top: 50px;
            text-align: left;
            direction: ltr;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 200px;
            margin-top: 50px;
        }

        .prescription-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1000;
        }

        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">طباعة</button>

    <div class="prescription-container">
        <!-- الوصفةRecipe Header-->
        <div class="prescription-header">
            <div class="clinic-info">
                <div class="clinic-name">عيادة طب الأطفال</div>
                <div>مركز الرعاية الصحية للأطفال</div>
                <div>هاتف: 123-456-7890 | بريد إلكتروني: info@babyhealth.com</div>
            </div>
        </div>

        <!-- Recipe title-->
        <div class="prescription-title">
            وصفة طبية
        </div>

        <!-- Patient information-->
        <div class="patient-info">
            <h5>معلومات المريض:</h5>
            <div class="info-row">
                <span><span class="info-label">الاسم:</span> <?php echo htmlspecialchars($prescription['child_name']); ?></span>
                <span><span class="info-label">العمر:</span> <?php echo $age_text; ?></span>
            </div>
            <div class="info-row">
                <span><span class="info-label">الجنس:</span> <?php echo $gender_text; ?></span>
                <span><span class="info-label">تاريخ الميلاد:</span> <?php echo date('d/m/Y', strtotime($prescription['birth_date'])); ?></span>
            </div>
            <div class="info-row">
                <span><span class="info-label">اسم الوالد:</span> <?php echo htmlspecialchars($prescription['parent_name']); ?></span>
                <span><span class="info-label">هاتف الوالد:</span> <?php echo htmlspecialchars($prescription['parent_phone']); ?></span>
            </div>
        </div>

        <!-- Doctor's information-->
        <div class="doctor-info">
            <h5>معلومات الطبيب:</h5>
            <div class="info-row">
                <span><span class="info-label">الاسم:</span> د. <?php echo htmlspecialchars($prescription['doctor_name']); ?></span>
            </div>
            <div class="info-row">
                <span><span class="info-label">تاريخ الوصفة:</span> <?php echo date('d/m/Y', strtotime($prescription['created_at'])); ?></span>
                <span><span class="info-label">رقم الوصفة:</span> <?php echo $prescription['id']; ?></span>
            </div>
        </div>

        <!-- Medication schedule-->
        <div class="medications-section">
            <h5>الأدوية الموصوفة:</h5>
            <table class="medications-table">
                <thead>
                    <tr>
                        <th>اسم الدواء</th>
                        <th>التركيز</th>
                        <th>الجرعة</th>
                        <th>عدد المرات</th>
                        <th>المدة (أيام)</th>
                        <th>شكل الدواء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $medications = explode("\n", $prescription['medication_names']);
                    $concentrations = explode("\n", $prescription['concentrations']);
                    $dosages = explode("\n", $prescription['dosages']);
                    $frequencies = explode("\n", $prescription['frequencies']);
                    $durations = explode("\n", $prescription['durations']);
                    $dosage_forms = explode("\n", $prescription['dosage_forms']);

                    $max_rows = max(count($medications), count($concentrations), count($dosages),
                                  count($frequencies), count($durations), count($dosage_forms));

                    for ($i = 0; $i < $max_rows; $i++) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($medications[$i] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($concentrations[$i] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($dosages[$i] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($frequencies[$i] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($durations[$i] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($dosage_forms[$i] ?? '') . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!--Special instructions-->
        <?php if (!empty($prescription['instructions'])): ?>
        <div class="instructions-section">
            <h5>تعليمات خاصة:</h5>
            <p><?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Doctor's signature-->
        <div class="doctor-signature">
            <div>توقيع الطبيب:</div>
            <div class="signature-line"></div>
            <div>د. <?php echo htmlspecialchars($prescription['doctor_name']); ?></div>
            <div>تاريخ: <?php echo date('d/m/Y'); ?></div>
        </div>

        <!-- Page footer-->
        <div class="prescription-footer">
            <p>هذه الوصفة صالحة لمدة شهر واحد من تاريخ الإصدار</p>
            <p>يرجى استشارة الطبيب قبل استخدام أي دواء</p>
        </div>
    </div>

    <script>
        // Automatic printing when the page loads طباعة تلقائية عند تحميل الصفحة
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        // Close the window after printing
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>