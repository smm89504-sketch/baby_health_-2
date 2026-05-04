<?php
session_start();

// Checking permissions
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['doctor', 'nurse'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non authorized']);
    exit;
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Processing various requests
$action = $_POST['action'] ?? null;

// 1. Add a new prescription
if ($action === 'add_prescription') {
    $child_id = intval($_POST['child_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? $_SESSION['user_id']);
    $prescription_date = $_POST['prescription_date'] ?? date('Y-m-d');
    $expiry_date = $_POST['expiry_date'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $medications = $_POST['medications'] ?? [];

   // Checking basic data
    if (!$child_id || !$expiry_date) {
        die(json_encode(['success' => false, 'message' => 'بيانات غير كاملة']));
    }

    // Check that the expiry date is later than the prescription date
    if (strtotime($expiry_date) <= strtotime($prescription_date)) {
        die(json_encode(['success' => false, 'message' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ الوصفة']));
    }

    try {
        $conn->begin_transaction();

       // Add main prescription
        $stmt = $conn->prepare("
            INSERT INTO prescriptions 
            (child_id, doctor_id, prescription_date, expiry_date, notes, status)
            VALUES (?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param('iisss', $child_id, $doctor_id, $prescription_date, $expiry_date, $notes);
        
        if (!$stmt->execute()) {
            throw new Exception('خطأ في إضافة الوصفة: ' . $stmt->error);
        }

        $prescription_id = $conn->insert_id;

       // Add medications related to the prescription
        if (!empty($medications) && is_array($medications)) {
            $med_stmt = $conn->prepare("
                INSERT INTO prescription_medications 
                (prescription_id, medication_id, dosage, frequency, duration_days, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($medications as $med) {
                $medication_id = intval($med['medication_id'] ?? 0);
                $dosage = $med['dosage'] ?? '';
                $frequency = $med['frequency'] ?? '';
                $duration_days = intval($med['duration_days'] ?? 0);
                $med_notes = $med['notes'] ?? '';

                if ($medication_id && $dosage && $frequency) {
                    $med_stmt->bind_param('iissii', $prescription_id, $medication_id, $dosage, $frequency, $duration_days, $med_notes);
                    
                    if (!$med_stmt->execute()) {
                        throw new Exception('خطأ في إضافة الدواء: ' . $med_stmt->error);
                    }
                }
            }
            $med_stmt->close();
        }

       // Create prescription renewal alerts
        create_renewal_notifications($conn, $prescription_id, $child_id);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة الوصفة بنجاح',
            'prescription_id' => $prescription_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log('Prescription error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

   // 2. Prescription Renewal
else if ($action === 'renew_prescription') {
    $original_prescription_id = intval($_POST['prescription_id'] ?? 0);
    $new_expiry_date = $_POST['new_expiry_date'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (!$original_prescription_id || !$new_expiry_date) {
        die(json_encode(['success' => false, 'message' => 'بيانات غير كاملة']));
    }

    try {
        $conn->begin_transaction();

       
        $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
        $stmt->bind_param('i', $original_prescription_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $original_prescription = $result->fetch_assoc();
        $stmt->close();

        if (!$original_prescription) {
            throw new Exception('الوصفة غير موجودة');
        }

        // Add a new prescription for the same medications
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            INSERT INTO prescriptions 
            (child_id, doctor_id, prescription_date, expiry_date, notes, status)
            VALUES (?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param('iisss', $original_prescription['child_id'], $_SESSION['user_id'], $today, $new_expiry_date, $notes);
        
        if (!$stmt->execute()) {
            throw new Exception('خطأ في إنشاء الوصفة الجديدة');
        }

        $new_prescription_id = $conn->insert_id;

      // Copying medications from the old prescription
        $stmt = $conn->prepare("
            SELECT medication_id, dosage, frequency, duration_days, notes 
            FROM prescription_medications 
            WHERE prescription_id = ?
        ");
        $stmt->bind_param('i', $original_prescription_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $medications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($medications)) {
            $med_stmt = $conn->prepare("
                INSERT INTO prescription_medications 
                (prescription_id, medication_id, dosage, frequency, duration_days, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($medications as $med) {
                $med_stmt->bind_param(
                    'iissii',
                    $new_prescription_id,
                    $med['medication_id'],
                    $med['dosage'],
                    $med['frequency'],
                    $med['duration_days'],
                    $med['notes']
                );
                $med_stmt->execute();
            }
            $med_stmt->close();
        }

        // Registration in the renewal log
        $stmt = $conn->prepare("
            INSERT INTO prescription_renewal_log 
            (original_prescription_id, new_prescription_id, old_expiry_date, new_expiry_date, renewed_by, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iisssi', $original_prescription_id, $new_prescription_id, $original_prescription['expiry_date'], $new_expiry_date, $_SESSION['user_id'], $notes);
        $stmt->execute();
        $stmt->close();

        // Update the status of the old recipe to expired
        $status = 'cancelled'; 
        $stmt = $conn->prepare("UPDATE prescriptions SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $original_prescription_id);
        $stmt->execute();
        $stmt->close();

      // Create new alerts for the new recipe
        create_renewal_notifications($conn, $new_prescription_id, $original_prescription['child_id']);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم تجديد الوصفة بنجاح',
            'new_prescription_id' => $new_prescription_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log('Renewal error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// 3. Obtaining recipes for the child
else if ($action === 'get_prescriptions') {
    $child_id = intval($_GET['child_id'] ?? 0);
    $status_filter = $_GET['status'] ?? 'active';

    if (!$child_id) {
        die(json_encode(['success' => false, 'message' => 'معرف الطفل مفقود']));
    }

    $query = "
        SELECT 
            p.id,
            p.child_id,
            p.doctor_id,
            CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
            p.prescription_date,
            p.expiry_date,
            DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
            p.status,
            p.notes,
            COUNT(pm.id) as medication_count,
            GROUP_CONCAT(m.name SEPARATOR ', ') as medications
        FROM prescriptions p
        JOIN users u ON p.doctor_id = u.id
        LEFT JOIN prescription_medications pm ON p.id = pm.prescription_id
        LEFT JOIN medications m ON pm.medication_id = m.id
        WHERE p.child_id = ?
    ";

    $params = [$child_id];
    $types = 'i';

    if ($status_filter !== 'all') {
        $query .= " AND p.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }

    $query .= " GROUP BY p.id ORDER BY p.expiry_date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'prescriptions' => $prescriptions
    ]);
}

// 4. Obtaining specific recipe details
else if ($action === 'get_prescription_details') {
    $prescription_id = intval($_GET['prescription_id'] ?? 0);

    if (!$prescription_id) {
        die(json_encode(['success' => false, 'message' => 'معرف الوصفة مفقود']));
    }
// Retrieve recipe details
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.child_id,
            p.doctor_id,
            CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
            p.prescription_date,
            p.expiry_date,
            p.status,
            p.notes,
            p.created_at
        FROM prescriptions p
        JOIN users u ON p.doctor_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param('i', $prescription_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prescription = $result->fetch_assoc();
    $stmt->close();

    if (!$prescription) {
        die(json_encode(['success' => false, 'message' => 'الوصفة غير موجودة']));
    }

   // Bringing medicines
    $stmt = $conn->prepare("
        SELECT 
            pm.id,
            pm.medication_id,
            m.name as medication_name,
            pm.dosage,
            pm.frequency,
            pm.duration_days,
            pm.notes
        FROM prescription_medications pm
        JOIN medications m ON pm.medication_id = m.id
        WHERE pm.prescription_id = ?
        ORDER BY pm.id
    ");
    $stmt->bind_param('i', $prescription_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $prescription['medications'] = $medications;

    echo json_encode([
        'success' => true,
        'prescription' => $prescription
    ]);
}

// 5. Get recipes nearing completion (for notifications)
else if ($action === 'get_expiring_prescriptions') {
    $user_id = $_SESSION['user_id'];
    $days_before = intval($_GET['days_before'] ?? 7);

    $query = "
        SELECT 
            p.id,
            p.child_id,
            c.name as child_name,
            p.doctor_id,
            CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
            p.expiry_date,
            DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
            GROUP_CONCAT(m.name SEPARATOR ', ') as medications
        FROM prescriptions p
        JOIN children c ON p.child_id = c.id
        JOIN users u ON p.doctor_id = u.id
        LEFT JOIN prescription_medications pm ON p.id = pm.prescription_id
        LEFT JOIN medications m ON pm.medication_id = m.id
        WHERE p.status = 'active'
            AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ";

   
    if ($_SESSION['role'] === 'parent') {
        $query .= " AND c.parent_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $days_before, $user_id);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $days_before);
    }

    $query .= " GROUP BY p.id ORDER BY p.expiry_date ASC";

    $stmt = $conn->prepare($query);
    if ($_SESSION['role'] === 'parent') {
        $stmt->bind_param('ii', $days_before, $user_id);
    } else {
        $stmt->bind_param('i', $days_before);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'prescriptions' => $prescriptions,
        'count' => count($prescriptions)
    ]);
}

// Helper Function: Create Renewal Alerts

function create_renewal_notifications($conn, $prescription_id, $child_id) {
    $stmt = $conn->prepare("SELECT user_id FROM children WHERE id = ?");
    $stmt->bind_param('i', $child_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $parent = $result->fetch_assoc();
    $stmt->close();

    if ($parent && isset($parent['user_id'])) {
        $stmt = $conn->prepare("INSERT INTO prescription_renewal_notifications (prescription_id, parent_id, notification_type, days_before_expiry) VALUES (?, ?, ?, ?)");
        $notification_type = 'in_app';
        $days_before = 7;
        $parent_id = $parent['user_id'];
        $stmt->bind_param('iisi', $prescription_id, $parent_id, $notification_type, $days_before);
        $stmt->execute();
        $stmt->close();
    }
}

?>
