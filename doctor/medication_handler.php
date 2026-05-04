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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            addMedication($conn);
            break;

        case 'update':
            updateMedication($conn);
            break;

        case 'delete':
            deleteMedication($conn);
            break;

        case 'get':
            getMedication($conn);
            break;

        case 'list':
            listMedications($conn);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'الإجراء غير مدعوم']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()]);
}

function addMedication($conn) {
    $name = trim($_POST['name'] ?? '');
    $dosage_form = $_POST['dosage_form'] ?? '';
    $concentration = trim($_POST['concentration'] ?? '');
    $category = $_POST['category'] ?? '';
    $indications = trim($_POST['indications'] ?? '');
    $side_effects = trim($_POST['side_effects'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    if (empty($name) || empty($dosage_form)) {
        echo json_encode(['success' => false, 'message' => 'اسم الدواء وشكل الجرعة مطلوبان']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO medications (name, dosage_form, concentration, category, indications, side_effects, instructions, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $name, $dosage_form, $concentration, $category, $indications, $side_effects, $instructions, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم إضافة الدواء بنجاح', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في إضافة الدواء']);
    }
}

function updateMedication($conn) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $dosage_form = $_POST['dosage_form'] ?? '';
    $concentration = trim($_POST['concentration'] ?? '');
    $category = $_POST['category'] ?? '';
    $indications = trim($_POST['indications'] ?? '');
    $side_effects = trim($_POST['side_effects'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    if (empty($id) || empty($name) || empty($dosage_form)) {
        echo json_encode(['success' => false, 'message' => 'البيانات غير مكتملة']);
        return;
    }

    $stmt = $conn->prepare("UPDATE medications SET name = ?, dosage_form = ?, concentration = ?, category = ?, indications = ?, side_effects = ?, instructions = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $name, $dosage_form, $concentration, $category, $indications, $side_effects, $instructions, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث الدواء بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في تحديث الدواء']);
    }
}

function deleteMedication($conn) {
    $id = (int)($_POST['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'معرف الدواء مطلوب']);
        return;
    }

    // التحقق من عدم وجود وصفات تستخدم هذا الدواء
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE medication_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'لا يمكن حذف الدواء لأنه مستخدم في وصفات']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM medications WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم حذف الدواء بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في حذف الدواء']);
    }
}

function getMedication($conn) {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'معرف الدواء مطلوب']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM medications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $medication = $result->fetch_assoc();
        echo json_encode(['success' => true, 'medication' => $medication]);
    } else {
        echo json_encode(['success' => false, 'message' => 'الدواء غير موجود']);
    }
}

function listMedications($conn) {
    $stmt = $conn->prepare("SELECT * FROM medications ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $medications = [];
    while ($row = $result->fetch_assoc()) {
        $medications[] = $row;
    }

    echo json_encode(['success' => true, 'medications' => $medications]);
}
?>