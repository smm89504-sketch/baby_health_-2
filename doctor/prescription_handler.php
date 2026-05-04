<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

require_once '../includes/db_config.php';

header('Content-Type: application/json');

$db = new DatabaseHelper();
$conn = $db->getConnection();

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_prescription':
            addPrescription($conn);
            break;

        case 'update_prescription':
            updatePrescription($conn);
            break;

        case 'delete_prescription':
            deletePrescription($conn);
            break;

        case 'get_prescription':
            getPrescription($conn);
            break;

        case 'change_status':
            changePrescriptionStatus($conn);
            break;

        default:
            addPrescription($conn); // الإجراء الافتراضي
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()]);
}

function addPrescription($conn) {
    $child_id = (int)($_POST['child_id'] ?? 0);
    $prescription_date = $_POST['prescription_date'] ?? date('Y-m-d');
    $expiry_date = date('Y-m-d', strtotime($prescription_date . ' +7 days'));
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $clinical_notes = trim($_POST['clinical_notes'] ?? '');
    $parent_instructions = trim($_POST['parent_instructions'] ?? '');
    $follow_up_date = $_POST['follow_up_date'] ?? null;

    if (empty($child_id)) {
        echo json_encode(['success' => false, 'message' => 'معرف الطفل مطلوب']);
        return;
    }

    // التحقق من وجود medications
    if (!isset($_POST['medications']) || !is_array($_POST['medications'])) {
        echo json_encode(['success' => false, 'message' => 'يجب إضافة دواء واحد على الأقل']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Add main recipe الوصفة 
        $stmt = $conn->prepare("INSERT INTO prescriptions (child_id, doctor_id, prescription_date, expiry_date, diagnosis, symptoms, clinical_notes, parent_instructions, follow_up_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("iisssssss", $child_id, $_SESSION['user_id'], $prescription_date, $expiry_date, $diagnosis, $symptoms, $clinical_notes, $parent_instructions, $follow_up_date);

        if (!$stmt->execute()) {
            throw new Exception('فشل في إضافة الوصفة');
        }

        $prescription_id = $conn->insert_id;

        // Adding prescribed medicationsإضافة الأدوية الموصوفة
        foreach ($_POST['medications'] as $medication) {
            $medication_id = (int)($medication['medication_id'] ?? 0);
            $dosage = trim($medication['dosage'] ?? '');
            $frequency = $medication['frequency'] ?? '';
            $duration = trim($medication['duration'] ?? '');
            $start_date = $medication['start_date'] ?? date('Y-m-d');
            $instructions = trim($medication['instructions'] ?? '');

            if (empty($medication_id) || empty($dosage) || empty($frequency) || empty($duration)) {
                throw new Exception('بيانات الدواء غير مكتملة');
            }

            $stmt = $conn->prepare("INSERT INTO prescription_medications (prescription_id, medication_id, dosage, frequency, duration_days, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissis", $prescription_id, $medication_id, $dosage, $frequency, $duration, $instructions);

            if (!$stmt->execute()) {
                throw new Exception('فشل في إضافة الدواء');
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'تم إضافة الوصفة بنجاح', 'prescription_id' => $prescription_id]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updatePrescription($conn) {
    $prescription_id = (int)($_POST['prescription_id'] ?? 0);
    $child_id = (int)($_POST['child_id'] ?? 0);
    $prescription_date = $_POST['prescription_date'] ?? null;
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $clinical_notes = trim($_POST['clinical_notes'] ?? '');
    $parent_instructions = trim($_POST['parent_instructions'] ?? '');
    $follow_up_date = $_POST['follow_up_date'] ?? null;

    if (empty($prescription_id) || empty($child_id)) {
        echo json_encode(['success' => false, 'message' => 'معرف الوصفة ومعرف الطفل مطلوبان']);
        return;
    }

    $stmt = $conn->prepare("UPDATE prescriptions SET child_id = ?, prescription_date = ?, diagnosis = ?, symptoms = ?, clinical_notes = ?, parent_instructions = ?, follow_up_date = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("issssssii", $child_id, $prescription_date, $diagnosis, $symptoms, $clinical_notes, $parent_instructions, $follow_up_date, $prescription_id, $_SESSION['user_id']);

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث الوصفة']);
        return;
    }

    if (isset($_POST['medications']) && is_array($_POST['medications'])) {
        $stmt = $conn->prepare("DELETE FROM prescription_medications WHERE prescription_id = ?");
        $stmt->bind_param("i", $prescription_id);
        $stmt->execute();

        foreach ($_POST['medications'] as $medication) {
            $medication_id = (int)($medication['medication_id'] ?? 0);
            $dosage = trim($medication['dosage'] ?? '');
            $frequency = $medication['frequency'] ?? '';
            $duration = trim($medication['duration'] ?? '');
            $instructions = trim($medication['instructions'] ?? '');

            if (empty($medication_id) || empty($dosage) || empty($frequency) || empty($duration)) {
                continue;
            }

            $stmt = $conn->prepare("INSERT INTO prescription_medications (prescription_id, medication_id, dosage, frequency, duration_days, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissis", $prescription_id, $medication_id, $dosage, $frequency, $duration, $instructions);
            $stmt->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'تم تحديث الوصفة بنجاح']);
}

function deletePrescription($conn) {
    $prescription_id = (int)($_POST['prescription_id'] ?? 0);

    if (empty($prescription_id)) {
        echo json_encode(['success' => false, 'message' => 'معرف الوصفة مطلوب']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Delete prescription-associated medicationsحذف الأدوية المرتبطة بالوصفة
        $stmt = $conn->prepare("DELETE FROM prescription_medications WHERE prescription_id = ?");
        $stmt->bind_param("i", $prescription_id);
        $stmt->execute();

        // Delete recipe
        $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ? AND doctor_id = ?");
        $stmt->bind_param("ii", $prescription_id, $_SESSION['user_id']);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'تم حذف الوصفة بنجاح']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'فشل في حذف الوصفة']);
    }
}

function getPrescription($conn) {
    $prescription_id = (int)($_POST['prescription_id'] ?? 0);

    if (empty($prescription_id)) {
        echo json_encode(['success' => false, 'message' => 'معرف الوصفة مطلوب']);
        return;
    }

    //جلب Retrieve prescription الوصفة data
    $stmt = $conn->prepare("
        SELECT p.*, c.name as child_name, pr.full_name as parent_name
        FROM prescriptions p
        JOIN children c ON p.child_id = c.id
        JOIN users pr ON c.user_id = pr.id
        WHERE p.id = ? AND p.doctor_id = ?
    ");
    $stmt->bind_param("ii", $prescription_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'الوصفة غير موجودة']);
        return;
    }

    $prescription = $result->fetch_assoc();

    //Bring the prescribed medications
    $stmt = $conn->prepare("
        SELECT pm.*, m.name, m.dosage_form, m.concentration
        FROM prescription_medications pm
        JOIN medications m ON pm.medication_id = m.id
        WHERE pm.prescription_id = ?
    ");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $medications_result = $stmt->get_result();

    $medications = [];
    while ($med = $medications_result->fetch_assoc()) {
        $medications[] = $med;
    }

    $prescription['medications'] = $medications;

    echo json_encode(['success' => true, 'prescription' => $prescription]);
}

function changePrescriptionStatus($conn) {
    $prescription_id = (int)($_POST['prescription_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $valid_statuses = ['active', 'expired', 'cancelled'];

    if (empty($prescription_id) || !in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'البيانات غير صحيحة']);
        return;
    }

    $stmt = $conn->prepare("UPDATE prescriptions SET status = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("sii", $status, $prescription_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة الوصفة بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث حالة الوصفة']);
    }
}
?>