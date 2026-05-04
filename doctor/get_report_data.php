<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "غير مصرح لك"]);
    exit();
}

require_once '../includes/db_config.php';

header('Content-Type: application/json');

$db = new DatabaseHelper();
$conn = $db->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$report_type = $input['report_type'] ?? 'visits';
$date_from = $input['date_from'] ?? null;
$date_to = $input['date_to'] ?? null;

$doctor_id = $_SESSION['user_id'];

function buildDateCondition($column, &$params, &$types, $date_from, $date_to) {
    $condition = "";
    if ($date_from && $date_to) {
        $condition = " AND DATE($column) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= "ss";
    } elseif ($date_from) {
        $condition = " AND DATE($column) >= ?";
        $params[] = $date_from;
        $types .= "s";
    } elseif ($date_to) {
        $condition = " AND DATE($column) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    return $condition;
}

try {
    $chart_data = [];
    $table_data = [];

    switch ($report_type) {
        case 'visits':
            //Visits Report
            $params = [$doctor_id];
            $types = 'i';
            $date_condition = buildDateCondition('v.visit_date', $params, $types, $date_from, $date_to);

            $query = "SELECT
                        DATE_FORMAT(v.visit_date, '%Y-%m') as month,
                        COUNT(*) as visit_count
                      FROM medical_visits v
                      WHERE v.doctor_id = ?" . $date_condition . "
                      GROUP BY DATE_FORMAT(v.visit_date, '%Y-%m')
                      ORDER BY month";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $labels = [];
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['month'];
                $data[] = (int)$row['visit_count'];
                $table_data[] = [
                    "الشهر" => $row['month'],
                    "عدد الزيارات" => $row['visit_count']
                ];
            }

            $chart_data = [
                "labels" => $labels,
                "datasets" => [[
                    "label" => "عدد الزيارات",
                    "data" => $data,
                    "backgroundColor" => "rgba(13, 110, 253, 0.2)",
                    "borderColor" => "rgba(13, 110, 253, 1)",
                    "borderWidth" => 1
                ]]
            ];
            break;

        case 'diagnoses':
            // التشخيصاتDiagnostic Report 
            $params = [$doctor_id];
            $types = 'i';
            $date_condition = buildDateCondition('v.visit_date', $params, $types, $date_from, $date_to);

            $query = "SELECT
                        COALESCE(NULLIF(TRIM(v.diagnosis), ''), 'غير محدد') as diagnosis_name,
                        COUNT(*) as diagnosis_count
                      FROM medical_visits v
                      WHERE v.doctor_id = ?" . $date_condition . "
                      GROUP BY diagnosis_name
                      ORDER BY diagnosis_count DESC
                      LIMIT 10";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $labels = [];
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['diagnosis_name'];
                $data[] = (int)$row['diagnosis_count'];
                $table_data[] = [
                    "التشخيص" => $row['diagnosis_name'],
                    "عدد الحالات" => $row['diagnosis_count']
                ];
            }

            $chart_data = [
                "labels" => $labels,
                "datasets" => [[
                    "label" => "عدد الحالات",
                    "data" => $data,
                    "backgroundColor" => "rgba(220, 53, 69, 0.2)",
                    "borderColor" => "rgba(220, 53, 69, 1)",
                    "borderWidth" => 1
                ]]
            ];
            break;

        case 'medications':
            // الادوية Drug Report
            $params = [$doctor_id];
            $types = 'i';
            $date_condition = buildDateCondition('p.prescription_date', $params, $types, $date_from, $date_to);

            $query = "SELECT
                        m.name as medication_name,
                        COUNT(*) as prescription_count
                      FROM prescriptions p
                      JOIN prescription_medications pm ON p.id = pm.prescription_id
                      JOIN medications m ON pm.medication_id = m.id
                      WHERE p.doctor_id = ?" . $date_condition . "
                      GROUP BY m.id, m.name
                      ORDER BY prescription_count DESC
                      LIMIT 10";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $labels = [];
            $data = [];

            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['medication_name'];
                $data[] = (int)$row['prescription_count'];
                $table_data[] = [
                    "الدواء" => $row['medication_name'],
                    "عدد الوصفات" => $row['prescription_count']
                ];
            }

            $chart_data = [
                "labels" => $labels,
                "datasets" => [[
                    "label" => "عدد الوصفات",
                    "data" => $data,
                    "backgroundColor" => "rgba(25, 135, 84, 0.2)",
                    "borderColor" => "rgba(25, 135, 84, 1)",
                    "borderWidth" => 1
                ]]
            ];
            break;

        case 'growth':
            // النموGrowth Report
            $params = [$doctor_id];
            $types = 'i';
            $date_condition = buildDateCondition('g.measurement_date', $params, $types, $date_from, $date_to);

            $query = "SELECT
                        DATE_FORMAT(g.measurement_date, '%Y-%m') as month,
                        AVG(g.weight_kg) as avg_weight,
                        AVG(g.height_cm) as avg_height
                      FROM growth_measurements g
                      JOIN children c ON g.child_id = c.id
                      WHERE c.id IN (
                          SELECT DISTINCT child_id FROM medical_visits WHERE doctor_id = ?
                      )" . $date_condition . "
                      GROUP BY DATE_FORMAT(g.measurement_date, '%Y-%m')
                      ORDER BY month";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $labels = [];
            $weight_data = [];
            $height_data = [];

            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['month'];
                $weight_data[] = round((float)$row['avg_weight'], 1);
                $height_data[] = round((float)$row['avg_height'], 1);
                $table_data[] = [
                    "الشهر" => $row['month'],
                    "متوسط الوزن (كغ)" => round((float)$row['avg_weight'], 1),
                    "متوسط الطول (سم)" => round((float)$row['avg_height'], 1)
                ];
            }

            $chart_data = [
                "labels" => $labels,
                "datasets" => [
                    [
                        "label" => "متوسط الوزن (كغ)",
                        "data" => $weight_data,
                        "backgroundColor" => "rgba(255, 193, 7, 0.2)",
                        "borderColor" => "rgba(255, 193, 7, 1)",
                        "borderWidth" => 1
                    ],
                    [
                        "label" => "متوسط الطول (سم)",
                        "data" => $height_data,
                        "backgroundColor" => "rgba(13, 202, 240, 0.2)",
                        "borderColor" => "rgba(13, 202, 240, 1)",
                        "borderWidth" => 1
                    ]
                ]
            ];
            break;
    }

    echo json_encode([
        "success" => true,
        "chart_data" => $chart_data,
        "table_data" => $table_data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "خطأ في قاعدة البيانات: " . $e->getMessage()
    ]);
}
?>