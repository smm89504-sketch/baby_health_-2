<?php
/**
 * Admin Handler
 * معالج طلبات لوحة تحكم الإدمن
 */

// suppress PHP warnings/notice output so AJAX always gets clean JSON
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
header('Content-Type: application/json; charset=utf-8');

session_start();

// التحقق من أن المستخدم مصرح له (إدمن، ممرضة أو طبيب)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin','nurse','doctor'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح، يجب تسجيل الدخول كمستخدم مع صلاحية'
    ]);
    exit();
}

require_once 'includes/db_config.php';

// إذا تم تثبيت حزم Composer (مثل Dompdf)،
// فسيُحمّل ملف autoload تلقائياً حتى تسهل استخدام المكتبات.
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}
// support manual placement of Dompdf inside includes/dompdf
$manual = __DIR__ . '/includes/dompdf/vendor/autoload.php';
if (file_exists($manual)) {
    require_once $manual;
}

$db = new DatabaseHelper();
$conn = $db->getConnection();

// الحصول على نوع الإجراء
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
// if JSON decoding didn't produce an array (for example when a regular form POST is used),
// fall back to $_POST so export/download forms still work
if ((!$input || !is_array($input)) && !empty($_POST)) {
    $input = $_POST;
}
$action = $input['action'] ?? '';

try {
    switch ($action) {
        // ==================== المستخدمين ====================
        case 'get_users':
            getUsers($conn, $input);
            break;
            
        case 'add_user':
            addUser($conn, $input);
            break;
            
        case 'update_user':
            updateUser($conn, $input);
            break;
            
        case 'delete_user':
            deleteUser($conn, $input);
            break;

        // ==================== الآباء والأمهات ====================
        case 'get_parent_details':
            getParentDetails($conn, $input);
            break;

        case 'get_parent_full_details':
            getParentFullDetails($conn, $input);
            break;

        case 'add_parent':
            addParent($conn, $input);
            break;

        case 'update_parent':
            updateParent($conn, $input);
            break;

        case 'delete_parent':
            deleteParent($conn, $input);
            break;
            
        // ==================== الأطفال ====================
        case 'get_children':
            getChildren($conn);
            break;
            
        case 'get_child_details':
            getChildDetails($conn, $input);
            break;

        case 'add_child':
            addChild($conn, $input);
            break;

        case 'update_child':
            updateChild($conn, $input);
            break;

        case 'delete_child':
            deleteChild($conn, $input);
            break;
            
        // ==================== الأنشطة ====================
        case 'get_activities':
            getActivities($conn);
            break;
            
        // ==================== التطعيمات ====================
        case 'get_vaccines':
            getVaccines($conn);
            break;

        case 'get_user_details':
            getUserDetails($conn, $input);
            break;
        
        // ==================== الممرضات والأطباء ====================
        case 'add_nurse':
            $input['user_type'] = 'nurse';
            addUser($conn, $input);
            break;

        case 'update_nurse':
            updateUser($conn, $input);
            break;

        case 'delete_nurse':
            deleteUser($conn, $input);
            break;

        case 'add_doctor':
            $input['user_type'] = 'doctor';
            addUser($conn, $input);
            break;

        case 'update_doctor':
            updateUser($conn, $input);
            break;

        case 'delete_doctor':
            deleteUser($conn, $input);
            break;

        // ==================== الأطفال (CRUD) ====================
        case 'add_child':
            addChild($conn, $input);
            break;

        case 'update_child':
            updateChild($conn, $input);
            break;

        case 'delete_child':
            deleteChild($conn, $input);
            break;

        case 'archive_child':
            archiveChild($conn, $input);
            break;

        case 'unarchive_child':
            unarchiveChild($conn, $input);
            break;
            getVaccineTypes($conn);
            break;

        case 'add_vaccine_type':
            addVaccineType($conn, $input);
            break;

        case 'update_vaccine_type':
            updateVaccineType($conn, $input);
            break;

        case 'delete_vaccine_type':
            deleteVaccineType($conn, $input);
            break;

        case 'get_vaccine_type_details':
            getVaccineTypeDetails($conn, $input);
            break;

        // ==================== الأنشطة (CRUD) ====================
        case 'add_activity':
            addActivity($conn, $input);
            break;

        case 'update_activity':
            updateActivity($conn, $input);
            break;

        case 'get_activity_details':
            getActivityDetails($conn, $input);
            break;
            break;

        // ==================== سجلات التطعيمات (CRUD) ====================
        case 'add_vaccine_record':
            addVaccineRecord($conn, $input);
            break;

        case 'update_vaccine_record':
            updateVaccineRecord($conn, $input);
            break;

        case 'delete_vaccine_record':
            deleteVaccineRecord($conn, $input);
            break;

        case 'get_vaccine_record_details':
            getVaccineRecordDetails($conn, $input);
            break;

        // ==================== إعدادات النظام ====================
        case 'get_system_settings':
            getSystemSettings($conn);
            break;

        case 'update_system_settings':
            updateSystemSettings($conn, $input);
            break;

        // ==================== إدارة نماذج AI ====================
        case 'enable_ai_model':
            setAIModelStatus($conn, $input, true);
            break;

        case 'disable_ai_model':
            setAIModelStatus($conn, $input, false);
            break;

      
            
        // ==================== السجلات ====================
        case 'get_logs':
            getLogs($conn, $input);
            break;
            
        // ==================== الذكاء الاصطناعي ====================
        case 'check_ai_models':
            checkAIModels($conn);
            break;

        // ==================== الإحصائيات والتصدير ====================
        case 'get_user_stats':
            getUserStats($conn, $input);
            break;

        case 'get_child_stats':
            getChildStats($conn, $input);
            break;

        case 'export_data':
            exportData($conn, $input);
            break;
        
        // تقارير النمو والصحة
        case 'get_report_data':
            getReportData($conn, $input);
            break;
        case 'export_report':
            exportReport($conn, $input);
            break;
        
        // إرسال إشعار للأطفال المتأخرين عن التطعيم
        case 'notify_overdue':
            notifyOverdue($conn, $input);
            break;
        
        // إدارة النوم
        case 'get_sleep_records':
            getSleepRecords($conn, $input);
            break;
        case 'add_sleep_record':
            addSleepRecord($conn, $input);
            break;
        case 'delete_sleep_record':
            deleteSleepRecord($conn, $input);
            break;
        case 'get_sleep_stats':
            getSleepStats($conn, $input);
            break;
        case 'get_sleep_tips':
            getSleepTips($conn, $input);
            break;
        case 'get_sleep_tip':
            getSleepTip($conn, $input);
            break;
        case 'add_sleep_tip':
            addSleepTip($conn, $input);
            break;
        case 'update_sleep_tip':
            updateSleepTip($conn, $input);
            break;
        case 'delete_sleep_tip':
            deleteSleepTip($conn, $input);
            break;

        case 'get_common_symptom_details':
            getCommonSymptomDetails($conn, $input);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'إجراء غير معروف: ' . $action
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الخادم: ' . $e->getMessage()
    ]);
}

// ==================== دوال المستخدمين ====================

function getUsers($conn, $input) {
    $userType = $input['user_type'] ?? 'parent';
    
    $query = "SELECT id, full_name, email, phone, created_at FROM users WHERE user_type = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $userType);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
}

function getUserDetails($conn, $input) {
    $userId = $input['user_id'] ?? 0;
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف المستخدم غير صحيح']);
        return;
    }

    $query = "SELECT id, full_name, email, phone, created_at, last_login, user_type, security_question, security_answer, address, latitude, longitude FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'المستخدم غير موجود']);
        return;
    }

    $user = $result->fetch_assoc();

    // إضافة بيانات إضافية بناءً على نوع المستخدم
    $extra = [];
    if ($user['user_type'] === 'nurse') {
        // التطعيمات التي قامت بها الممرضة
        $q = "SELECT cv.id, cv.status, cv.due_date, cv.administered_date,
                     v.name AS vaccine_name, c.name AS child_name
              FROM child_vaccines cv
              LEFT JOIN vaccines v ON cv.vaccine_id = v.id
              LEFT JOIN children c ON cv.child_id = c.id
              WHERE cv.nurse_id = ?
              ORDER BY cv.created_at DESC";
        $stmt2 = $conn->prepare($q);
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $vaccines = [];
        while ($r = $res2->fetch_assoc()) {
            $vaccines[] = $r;
        }
        $extra['vaccines'] = $vaccines;

        // الملاحظات المهنية التي أدخلتها الممرضة
        $q = "SELECT pn.id, pn.note_content, pn.created_at, c.name AS child_name
              FROM professional_notes pn
              LEFT JOIN children c ON pn.child_id = c.id
              WHERE pn.user_id = ? AND pn.user_type = 'nurse'
              ORDER BY pn.created_at DESC";
        $stmt2 = $conn->prepare($q);
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $notes = [];
        while ($r = $res2->fetch_assoc()) {
            $notes[] = $r;
        }
        $extra['notes'] = $notes;
    } elseif ($user['user_type'] === 'doctor') {
        // ملاحظات الطبيب المهنية
        $q = "SELECT pn.id, pn.note_content, pn.created_at, c.name AS child_name
              FROM professional_notes pn
              LEFT JOIN children c ON pn.child_id = c.id
              WHERE pn.user_id = ? AND pn.user_type = 'doctor'
              ORDER BY pn.created_at DESC";
        $stmt2 = $conn->prepare($q);
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $notes = [];
        while ($r = $res2->fetch_assoc()) {
            $notes[] = $r;
        }
        $extra['notes'] = $notes;
    }

    if (!empty($extra)) {
        $user['extra'] = $extra;
    }

    echo json_encode(['success' => true, 'data' => $user]);
}

function addUser($conn, $input) {
    $fullName = $input['full_name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $password = $input['password'] ?? null;
    $address = trim($input['address'] ?? '');
    $latitude = isset($input['latitude']) ? $input['latitude'] : null;
    $longitude = isset($input['longitude']) ? $input['longitude'] : null;
    $userType = $input['user_type'] ?? 'parent';
    
    // التحقق من البيانات
    if (empty($fullName) || empty($email) || empty($phone) || (is_null($password) && $userType !== 'parent')) {
        // parents might be created without password outside admin
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البيانات غير كاملة'
        ]);
        return;
    }
    
    // التحقق من عدم وجود بريد إلكتروني مكرر
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني موجود بالفعل'
        ]);
        return;
    }
    
    // كلمة المرور أو إنشاء عشوائية
    if (empty($password)) {
        $randomPassword = substr(md5(uniqid()), 0, 8);
        $passwordToStore = $randomPassword;
    } else {
        $passwordToStore = $password;
    }
    $hashedPassword = password_hash($passwordToStore, PASSWORD_BCRYPT);
    
    $insertQuery = "INSERT INTO users (full_name, email, phone, password, security_question, security_answer, user_type, address, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    $securityQuestion = 'ما اسم أول حيوان أليف لديك؟';
    $securityAnswer = 'لم يتم التعيين';
    
    $insertStmt->bind_param('ssssssssdd', $fullName, $email, $phone, $hashedPassword, $securityQuestion, $securityAnswer, $userType, $address, $latitude, $longitude);
    
    if ($insertStmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة مستخدم جديد', $userType, $insertStmt->insert_id);
        
        $response = [
            'success' => true,
            'message' => 'تم إضافة المستخدم بنجاح',
            'user_id' => $insertStmt->insert_id
        ];
        if (empty($password)) {
            $response['temporary_password'] = $randomPassword;
        }
        echo json_encode($response);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء إضافة المستخدم'
        ]);
    }
}

function updateUser($conn, $input) {
    $userId = $input['user_id'] ?? 0;
    $fullName = $input['full_name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $address = trim($input['address'] ?? '');
    $latitude = isset($input['latitude']) ? $input['latitude'] : null;
    $longitude = isset($input['longitude']) ? $input['longitude'] : null;
    $password = $input['password'] ?? null;
    
    if ($userId <= 0 || empty($fullName)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البيانات غير صحيحة'
        ]);
        return;
    }
    
    if (!empty($password)) {
        $strong = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$/";
        if (!preg_match($strong, $password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'كلمة السر ضعيفة']);
            return;
        }
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('sssssddi', $fullName, $email, $phone, $hashed, $address, $latitude, $longitude, $userId);
    } else {
        $updateQuery = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('ssssddi', $fullName, $email, $phone, $address, $latitude, $longitude, $userId);
    }
    
    if ($updateStmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل مستخدم', 'user', $userId);
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث البيانات'
        ]);
    }
}

function deleteUser($conn, $input) {
    $userId = $input['user_id'] ?? 0;
    
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'معرف المستخدم غير صحيح'
        ]);
        return;
    }
    
    // منع حذف الإدمن الأساسي
    if ($userId == 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'لا يمكن حذف حساب الإدمن الأساسي'
        ]);
        return;
    }
    
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param('i', $userId);
    
    if ($deleteStmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'حذف مستخدم', 'user', $userId);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف المستخدم بنجاح'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء حذف المستخدم'
        ]);
    }
}

// ==================== دوال التقارير ====================

// إشعارات البريد الإلكتروني للمواعيد المتأخرة
function notifyOverdue($conn, $input) {
    // clear any buffered output that may have crept in (warnings, notices)
    if (ob_get_length()) ob_clean();
    $recId = intval($input['record_id'] ?? 0);
    if ($recId <= 0) {
        echo json_encode(['success'=>false,'message'=>'معرف السجل غير موجود']);
        return;
    }
    $query = "SELECT u.id AS parent_id, u.email, u.full_name AS parent_name, c.name AS child_name, v.name AS vaccine_name, cv.due_date
              FROM child_vaccines cv
              JOIN children c ON cv.child_id = c.id
              JOIN users u ON c.user_id = u.id
              JOIN vaccines v ON cv.vaccine_id = v.id
              WHERE cv.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $recId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'السجل غير موجود']);
        return;
    }
    $row = $result->fetch_assoc();
    $to = $row['email'];
    $subject = "تنبيه تطعيم متأخر لطفلك " . $row['child_name'];
    $message = "السيد/ة " . $row['parent_name'] . "\n\n" .
               $row['child_name'] . " متأخر عن أخذ التطعيم " . $row['vaccine_name'] .
               " الذي كان مبرمجاً في " . $row['due_date'] . ".\nيرجى الاتصال بالعيادة لتحديد موعد جديد.\n\nشكراً";
    $headers = "From: no-reply@babyhealth.local\r\n";
    $sent = mail($to, $subject, $message, $headers);
    // also insert into notifications table if it exists
    $notifQuery = "INSERT INTO notifications (user_id, title, message, type) VALUES (?,?,?,?)";
    $notifStmt = $conn->prepare($notifQuery);
    $notifType = 'warning';
    $notifTitle = 'تذكير تطعيم متأخر';
    $notifMsg = "طفلك " . $row['child_name'] . " متأخر عن لقاح " . $row['vaccine_name'] . " (الموعد: " . $row['due_date'] . ")";
    if ($notifStmt) {
        $parentId = $row['parent_id'] ?? null;
        if ($parentId) {
            $notifStmt->bind_param('isss', $parentId, $notifTitle, $notifMsg, $notifType);
            $notifStmt->execute();
        }
    }

    if ($sent) {
        echo json_encode(['success'=>true,'message'=>'تم الإرسال']);
    } else {
        echo json_encode(['success'=>false,'message'=>'فشل إرسال الإيميل']);
    }
}

// ==================== دوال النوم ====================
// (مُدمجة لاحقاً بعد التقارير - لا توجد وظائف مكررة هنا)

// ==================== دوال الآباء والأمهات ====================


function getReportData($conn, $input) {
    // متوقع حقول: type='growth'|'health', year, month
    $type = $input['type'] ?? 'growth';
    $year = intval($input['year'] ?? date('Y'));
    $month = intval($input['month'] ?? date('m'));
    $start = sprintf("%04d-%02d-01", $year, $month);
    $end = date('Y-m-d', strtotime("$start +1 month -1 day"));

    if ($type === 'growth') {
        $query = "SELECT c.id, c.name, da.weight, da.height, da.date
                  FROM children c
                  JOIN daily_activities da ON da.child_id = c.id
                  WHERE da.activity_type = 'growth_record'
                    AND da.date BETWEEN ? AND ?";
    } else {
        // health e.g. temperature or illness records
        $query = "SELECT c.id, c.name, da.temperature, da.illness, da.date
                  FROM children c
                  JOIN daily_activities da ON da.child_id = c.id
                  WHERE da.date BETWEEN ? AND ?";
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    echo json_encode(['success'=>true,'data'=>$rows]);
}

function exportReport($conn, $input) {
    // reuse data retrieval then output according to requested format
    ob_start();
    $type = $input['type'] ?? 'growth';
    $format = $input['format'] ?? 'csv';
    $year = intval($input['year'] ?? date('Y'));
    $month = intval($input['month'] ?? date('m'));
    $start = sprintf("%04d-%02d-01", $year, $month);
    $end = date('Y-m-d', strtotime("$start +1 month -1 day"));

    if ($type === 'growth') {
        $query = "SELECT c.name, da.weight, da.height, da.date
                  FROM children c
                  JOIN daily_activities da ON da.child_id = c.id
                  WHERE da.activity_type = 'growth_record'
                    AND da.date BETWEEN ? AND ?";
    } else {
        $query = "SELECT c.name, da.temperature, da.illness, da.date
                  FROM children c
                  JOIN daily_activities da ON da.child_id = c.id
                  WHERE da.date BETWEEN ? AND ?";
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment;filename="report_'.$type.'_'.$year.'_'.$month.'.xls"');
        // ensure Excel recognises UTF-8 by sending BOM
        echo "\xEF\xBB\xBF";
    } elseif ($format === 'pdf') {
        // PDF export requires Dompdf library.  if it's missing, show a friendly message instead
        if (!class_exists('Dompdf\Dompdf')) {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>خطأ</title></head><body>';
            echo '<p>لا يمكن إنشاء ملف PDF لأن مكتبة Dompdf غير مثبتة.</p>';
            echo '<p>يرجى تثبيت Dompdf عبر composer أو نسخ الملفات إلى المجلد <code>includes/</code>.</p>';
            echo '</body></html>';
            exit;
        }

        // build HTML table for Dompdf
        $rows = [];
        while($r = $result->fetch_assoc()) $rows[] = $r;
        $html = '<table border="1" cellpadding="4" cellspacing="0">';
        if (!empty($rows)) {
            $html .= '<thead><tr>';
            foreach(array_keys($rows[0]) as $h) $html .= '<th>'.$h.'</th>';
            $html .= '</tr></thead><tbody>';
            foreach($rows as $r){
                $html .= '<tr>';
                foreach($r as $v) $html .= '<td>'.$v.'</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }
        $html .= '</table>';
        // now Dompdf is guaranteed available
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream("report_{$type}_{$year}_{$month}.pdf", ['Attachment'=>1]);
        exit;
    } else {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="report_'.$type.'_'.$year.'_'.$month.'.csv"');
    }

    // for csv/excel just output as CSV (excel open it fine)
    $out = fopen('php://output','w');
    if ($type === 'growth') {
        fputcsv($out,['Name','Weight','Height','Date']);
    } else {
        fputcsv($out,['Name','Temperature','Illness','Date']);
    }
    while($row=$result->fetch_assoc()){
        fputcsv($out,$row);
    }
    fclose($out);
    exit;
}

// ==================== دوال النوم ====================

function getSleepRecords($conn, $input) {
    $childId = isset($input['child_id']) ? intval($input['child_id']) : 0;
    if ($childId > 0) {
        $query = "SELECT * FROM sleep_records WHERE child_id = ? ORDER BY start_datetime DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $childId);
        $stmt->execute();
    } else {
        $query = "SELECT * FROM sleep_records ORDER BY start_datetime DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    }
    $res = $stmt->get_result();
    $rows=[];
    while($r=$res->fetch_assoc()) $rows[]=$r;
    echo json_encode(['success'=>true,'data'=>$rows]);
}

function addSleepRecord($conn, $input) {
    $childId = intval($input['child_id'] ?? 0);
    $start = $input['start_datetime'] ?? '';
    $end = $input['end_datetime'] ?? '';
    $isNight = isset($input['is_night']) ? intval($input['is_night']) : 0;
    if ($childId<=0||empty($start)||empty($end)) {
        echo json_encode(['success'=>false,'message'=>'missing data']); return;
    }
    $query = "INSERT INTO sleep_records (child_id,start_datetime,end_datetime,is_night) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issi',$childId,$start,$end,$isNight);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'id'=>$stmt->insert_id]);
    } else {
        echo json_encode(['success'=>false,'message'=>'db error']);
    }
}

function deleteSleepRecord($conn, $input) {
    $id = intval($input['id'] ?? 0);
    if ($id<=0) { echo json_encode(['success'=>false,'message'=>'id missing']); return; }
    $stmt = $conn->prepare("DELETE FROM sleep_records WHERE id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    echo json_encode(['success'=>true]);
}

function getSleepStats($conn, $input) {
    $childId = intval($input['child_id'] ?? 0);
    if ($childId<=0) { echo json_encode(['success'=>false,'message'=>'child_id missing']); return; }
    $query = "SELECT
                AVG(TIMESTAMPDIFF(HOUR,start_datetime,end_datetime)) as avg_hours,
                SUM(TIMESTAMPDIFF(MINUTE,start_datetime,end_datetime)>30) as wake_ups
              FROM sleep_records WHERE child_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i',$childId);
    $stmt->execute();
    $res=$stmt->get_result()->fetch_assoc();
    echo json_encode(['success'=>true,'stats'=>$res]);
}

function ensureSleepTipsTableExists($conn) {
    $query = "CREATE TABLE IF NOT EXISTS sleep_tips (
        id int(11) NOT NULL AUTO_INCREMENT,
        min_age_months int(11) NOT NULL DEFAULT 0,
        max_age_months int(11) DEFAULT NULL,
        tip_text text NOT NULL,
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($query);
}

function getSleepTips($conn, $input) {
    ensureSleepTipsTableExists($conn);
    $query = "SELECT id, min_age_months, max_age_months, tip_text, created_at FROM sleep_tips ORDER BY min_age_months";
    $res = $conn->query($query);
    $tips = [];
    while ($r = $res->fetch_assoc()) {
        $tips[] = $r;
    }
    echo json_encode(['success' => true, 'data' => $tips]);
}

function getSleepTip($conn, $input) {
    ensureSleepTipsTableExists($conn);
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'id missing']); return; }
    $stmt = $conn->prepare("SELECT id, min_age_months, max_age_months, tip_text, created_at FROM sleep_tips WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'النصيحة غير موجودة']);
        return;
    }
    echo json_encode(['success' => true, 'data' => $res->fetch_assoc()]);
}

function addSleepTip($conn, $input) {
    ensureSleepTipsTableExists($conn);
    $min = intval($input['min_age_months'] ?? 0);
    $max = null;
    if (isset($input['max_age_months']) && $input['max_age_months'] !== '') {
        $max = intval($input['max_age_months']);
    }
    $tip = trim($input['tip_text'] ?? '');
    if ($tip === '') { echo json_encode(['success' => false, 'message' => 'النص مطلوب']); return; }
    if ($max !== null && $max < $min) {
        echo json_encode(['success' => false, 'message' => 'الحد الأعلى يجب أن يكون أكبر أو يساوي الحد الأدنى']);
        return;
    }
    $query = "INSERT INTO sleep_tips (min_age_months, max_age_months, tip_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iis', $min, $max, $tip);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'db error']);
    }
}

function updateSleepTip($conn, $input) {
    ensureSleepTipsTableExists($conn);
    $id = intval($input['id'] ?? 0);
    $min = intval($input['min_age_months'] ?? 0);
    $max = null;
    if (isset($input['max_age_months']) && $input['max_age_months'] !== '') {
        $max = intval($input['max_age_months']);
    }
    $tip = trim($input['tip_text'] ?? '');
    if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'id missing']); return; }
    if ($tip === '') { echo json_encode(['success' => false, 'message' => 'النص مطلوب']); return; }
    if ($max !== null && $max < $min) {
        echo json_encode(['success' => false, 'message' => 'الحد الأعلى يجب أن يكون أكبر أو يساوي الحد الأدنى']);
        return;
    }
    $query = "UPDATE sleep_tips SET min_age_months = ?, max_age_months = ?, tip_text = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iisi', $min, $max, $tip, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'db error']);
    }
}

function deleteSleepTip($conn, $input) {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'id missing']); return; }
    $stmt = $conn->prepare("DELETE FROM sleep_tips WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
}

// ==================== دوال الآباء والأمهات ====================

function getParentDetails($conn, $input) {
    $parentId = $input['parent_id'] ?? 0;
    
    if ($parentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'معرف الأب/الأم غير صحيح'
        ]);
        return;
    }
    
    $query = "SELECT id, full_name, email, phone, security_question, security_answer FROM users WHERE id = ? AND user_type = 'parent'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'لم يتم العثور على الأب/الأم'
        ]);
        return;
    }
    
    $parent = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'data' => $parent
    ]);
}

function getParentFullDetails($conn, $input) {
    $parentId = $input['parent_id'] ?? 0;
    
    if ($parentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'معرف الأب/الأم غير صحيح'
        ]);
        return;
    }
    
    // جلب بيانات الأب/الأم
    $parentQuery = "SELECT id, full_name, email, phone, created_at FROM users WHERE id = ? AND user_type = 'parent'";
    $parentStmt = $conn->prepare($parentQuery);
    $parentStmt->bind_param('i', $parentId);
    $parentStmt->execute();
    $parentResult = $parentStmt->get_result();
    
    if ($parentResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'لم يتم العثور على الأب/الأم'
        ]);
        return;
    }
    
    $parent = $parentResult->fetch_assoc();
    
    // جلب أطفاله
    $childrenQuery = "SELECT id, name, birth_date, weight, height FROM children WHERE user_id = ? AND is_archived = 0";
    $childrenStmt = $conn->prepare($childrenQuery);
    $childrenStmt->bind_param('i', $parentId);
    $childrenStmt->execute();
    $childrenResult = $childrenStmt->get_result();
    
    $children = [];
    $childIds = [];
    while ($row = $childrenResult->fetch_assoc()) {
        $children[] = $row;
        $childIds[] = $row['id'];
    }
    
    // جلب آخر الأنشطة
    $activitiesQuery = "SELECT da.activity_type, da.date, da.details, c.name as child_name
                        FROM daily_activities da
                        LEFT JOIN children c ON da.child_id = c.id
                        WHERE da.child_id IN (" . implode(',', array_fill(0, count($childIds), '?')) . ")
                        ORDER BY da.created_at DESC
                        LIMIT 10";
    
    $activities = [];
    if (count($childIds) > 0) {
        $activitiesStmt = $conn->prepare($activitiesQuery);
        $activitiesStmt->bind_param(str_repeat('i', count($childIds)), ...$childIds);
        $activitiesStmt->execute();
        $activitiesResult = $activitiesStmt->get_result();
        
        while ($row = $activitiesResult->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    
    // جلب حالة التطعيمات
    $vaccinesQuery = "SELECT cv.status FROM child_vaccines cv
                      WHERE cv.child_id IN (" . implode(',', array_fill(0, count($childIds), '?')) . ")";
    
    $vaccines = [];
    if (count($childIds) > 0) {
        $vaccinesStmt = $conn->prepare($vaccinesQuery);
        $vaccinesStmt->bind_param(str_repeat('i', count($childIds)), ...$childIds);
        $vaccinesStmt->execute();
        $vaccinesResult = $vaccinesStmt->get_result();
        
        while ($row = $vaccinesResult->fetch_assoc()) {
            $vaccines[] = $row;
        }
    }
    
    $parent['children'] = $children;
    $parent['recent_activities'] = $activities;
    $parent['vaccines'] = $vaccines;
    
    echo json_encode([
        'success' => true,
        'data' => $parent
    ]);
}

function addParent($conn, $input) {
    $fullName = $input['full_name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $password = $input['password'] ?? '';
    $securityQuestion = $input['security_question'] ?? '';
    $securityAnswer = $input['security_answer'] ?? '';
    
    // التحقق من البيانات
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البيانات غير كاملة'
        ]);
        return;
    }
    
    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني غير صحيح'
        ]);
        return;
    }
    
    // التحقق من قوة كلمة المرور
    $strong = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$/";
    if (!preg_match($strong, $password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'كلمة السر ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز (!@#$%^&*).'
        ]);
        return;
    }
    
    // التحقق من عدم وجود بريد إلكتروني مكرر
    $checkQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني موجود بالفعل'
        ]);
        return;
    }
    
    // تشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    $insertQuery = "INSERT INTO users (full_name, email, phone, password, security_question, security_answer, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    $userType = 'parent';
    $insertStmt->bind_param('sssssss', $fullName, $email, $phone, $hashedPassword, $securityQuestion, $securityAnswer, $userType);
    
    if ($insertStmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة أب/أم جديد', 'parent', $insertStmt->insert_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة الأب/الأم بنجاح',
            'parent_id' => $insertStmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء إضافة الأب/الأم'
        ]);
    }
}

function updateParent($conn, $input) {
    $parentId = $input['parent_id'] ?? 0;
    $fullName = $input['full_name'] ?? '';
    $email = $input['email'] ?? '';
    $phone = $input['phone'] ?? '';
    $password = $input['password'] ?? null;
    $securityQuestion = $input['security_question'] ?? '';
    $securityAnswer = $input['security_answer'] ?? '';
    
    if ($parentId <= 0 || empty($fullName)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البيانات غير صحيحة'
        ]);
        return;
    }
    
    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني غير صحيح'
        ]);
        return;
    }
    
    // التحقق من قوة كلمة المرور إذا تم إدخال واحدة جديدة
    if (!empty($password)) {
        $strong = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$/";
        if (!preg_match($strong, $password)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'كلمة السر ضعيفة. يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير وصغير، رقم ورمز (!@#$%^&*).'
            ]);
            return;
        }
    }
    
    // التحقق من عدم وجود بريد إلكتروني مكرر
    $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('si', $email, $parentId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني موجود بالفعل'
        ]);
        return;
    }
    
    // تحديث البيانات
    if (!empty($password)) {
        // إذا تم إدخال كلمة مرور جديدة
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE users SET full_name = ?, email = ?, phone = ?, password = ?, security_question = ?, security_answer = ? WHERE id = ? AND user_type = 'parent'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('ssssssi', $fullName, $email, $phone, $hashedPassword, $securityQuestion, $securityAnswer, $parentId);
    } else {
        // بدون تغيير كلمة المرور
        $updateQuery = "UPDATE users SET full_name = ?, email = ?, phone = ?, security_question = ?, security_answer = ? WHERE id = ? AND user_type = 'parent'";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('sssssi', $fullName, $email, $phone, $securityQuestion, $securityAnswer, $parentId);
    }
    
    if ($updateStmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل بيانات أب/أم', 'parent', $parentId);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث البيانات بنجاح'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث البيانات'
        ]);
    }
}

function deleteParent($conn, $input) {
    $parentId = $input['parent_id'] ?? 0;
    
    if ($parentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'معرف الأب/الأم غير صحيح'
        ]);
        return;
    }
    
    // بدء معاملة
    $conn->begin_transaction();
    
    try {
        // حذف جميع البيانات المرتبطة بالأطفال أولاً
        $deleteChildrenQuery = "DELETE c, da, cv, pn FROM children c
                               LEFT JOIN daily_activities da ON c.id = da.child_id
                               LEFT JOIN child_vaccines cv ON c.id = cv.child_id
                               LEFT JOIN professional_notes pn ON c.id = pn.child_id
                               WHERE c.user_id = ?";
        
        $deleteChildrenStmt = $conn->prepare($deleteChildrenQuery);
        $deleteChildrenStmt->bind_param('i', $parentId);
        $deleteChildrenStmt->execute();
        
        // حذف الأب/الأم
        $deleteParentQuery = "DELETE FROM users WHERE id = ? AND user_type = 'parent'";
        $deleteParentStmt = $conn->prepare($deleteParentQuery);
        $deleteParentStmt->bind_param('i', $parentId);
        $deleteParentStmt->execute();
        
        $conn->commit();
        
        logAdminAction($conn, $_SESSION['user_id'], 'حذف أب/أم', 'parent', $parentId);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف الأب/الأم وجميع بيانات أطفاله بنجاح'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء حذف الأب/الأم: ' . $e->getMessage()
        ]);
    }
}

// ==================== دوال الأطفال ====================

function addChild($conn, $input) {
    $parentId = $input['user_id'] ?? 0;
    $name = trim($input['name'] ?? '');
    $birthDate = $input['birth_date'] ?? null;
    $weight = $input['weight'] ?? null;
    $height = $input['height'] ?? null;

    if ($parentId <= 0 || empty($name) || empty($birthDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات الطفل ناقصة']);
        return;
    }

    $query = "INSERT INTO children (user_id, name, birth_date, weight, height, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issdd', $parentId, $name, $birthDate, $weight, $height);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة طفل', 'child', $stmt->insert_id);
        echo json_encode(['success' => true, 'message' => 'تم إضافة الطفل بنجاح', 'child_id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إضافة الطفل']);
    }
}

function updateChild($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    $name = trim($input['name'] ?? '');
    $user_id = $input['user_id'] ?? '';
    $birthDate = $input['birth_date'] ?? null;
    $weight = $input['weight'] ?? null;
    $height = $input['height'] ?? null;

    if ($childId <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
        return;
    }

    $query = "UPDATE children SET name = ?, user_id = ?, birth_date = ?, weight = ?, height = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
$stmt->bind_param('sisddi', $name, $user_id, $birthDate, $weight, $height, $childId);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل ملف طفل', 'child', $childId);
        echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات الطفل']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث البيانات']);
    }
}

// ==================== دوال الأنشطة (CRUD) ====================
function addActivity($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    $activityType = $input['activity_type'] ?? '';
    $date = $input['date'] ?? date('Y-m-d');
    $details = $input['details'] ?? null;

    if ($childId <= 0 || empty($activityType)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات النشاط ناقصة']);
        return;
    }

    $query = "INSERT INTO daily_activities (child_id, activity_type, date, details, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isss', $childId, $activityType, $date, $details);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة نشاط', 'activity', $stmt->insert_id);
        echo json_encode(['success' => true, 'message' => 'تم إضافة النشاط بنجاح', 'activity_id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إضافة النشاط']);
    }
}

function updateActivity($conn, $input) {
    $activityId = $input['activity_id'] ?? 0;
    $activityType = $input['activity_type'] ?? '';
    $date = $input['date'] ?? null;
    $details = $input['details'] ?? null;

    if ($activityId <= 0 || empty($activityType)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
        return;
    }

    $query = "UPDATE daily_activities SET activity_type = ?, date = ?, details = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssi', $activityType, $date, $details, $activityId);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل نشاط', 'activity', $activityId);
        echo json_encode(['success' => true, 'message' => 'تم تحديث النشاط']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التحديث']);
    }
}

function getActivityDetails($conn, $input) {
    $activityId = $input['activity_id'] ?? 0;
    if ($activityId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف النشاط غير صحيح']);
        return;
    }
    $query = "SELECT da.id, da.activity_type, da.date, da.details, da.child_id, c.name as child_name
              FROM daily_activities da
              LEFT JOIN children c ON da.child_id = c.id
              WHERE da.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $activityId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'النشاط غير موجود']);
        return;
    }
    $activity = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $activity]);
}

function deleteActivity($conn, $input) {
    $activityId = $input['activity_id'] ?? 0;
    if ($activityId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف النشاط غير صحيح']);
        return;
    }

    $query = "DELETE FROM daily_activities WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $activityId);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'حذف نشاط', 'activity', $activityId);
        echo json_encode(['success' => true, 'message' => 'تم حذف النشاط']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف']);
    }
}

// ==================== دوال التطعيمات (CRUD) ====================
function addVaccineRecord($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    $vaccineId = $input['vaccine_id'] ?? 0;
    $dueDate = $input['due_date'] ?? null;
    $nurseId = $input['nurse_id'] ?? null;

    if ($childId <= 0 || $vaccineId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'البيانات ناقصة']);
        return;
    }

    $query = "INSERT INTO child_vaccines (child_id, vaccine_id, due_date, nurse_id, status, created_at) VALUES (?, ?, ?, ?, 'due', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iisi', $childId, $vaccineId, $dueDate, $nurseId);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة جدول تطعيم', 'vaccine', $stmt->insert_id);
        echo json_encode(['success' => true, 'message' => 'تم إضافة سجل التطعيم', 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الإضافة']);
    }
}

function updateVaccineRecord($conn, $input) {
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? null;
    $adminDate = $input['administered_date'] ?? null;
    $nurseId = $input['nurse_id'] ?? null;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
        return;
    }

    $query = "UPDATE child_vaccines SET status = ?, administered_date = ?, nurse_id = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssii', $status, $adminDate, $nurseId, $id);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل حالة تطعيم', 'vaccine', $id);
        echo json_encode(['success' => true, 'message' => 'تم تحديث سجل التطعيم']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التحديث']);
    }
}

function deleteVaccineRecord($conn, $input) {
    $id = $input['id'] ?? 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
        return;
    }

    $query = "DELETE FROM child_vaccines WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'حذف سجل تطعيم', 'vaccine', $id);
        echo json_encode(['success' => true, 'message' => 'تم حذف السجل']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف']);
    }
}

function getVaccineRecordDetails($conn, $input) {
    $id = $input['id'] ?? 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
        return;
    }
    $query = "SELECT cv.*, c.name as child_name, v.name as vaccine_name, u.full_name as nurse_name
              FROM child_vaccines cv
              LEFT JOIN children c ON cv.child_id = c.id
              LEFT JOIN vaccines v ON cv.vaccine_id = v.id
              LEFT JOIN users u ON cv.nurse_id = u.id
              WHERE cv.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'السجل غير موجود']);
        return;
    }
    $row = $res->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $row]);
}

// ==================== إعدادات النظام (ملف JSON بسيط) ====================
function getSystemSettings($conn) {
    $file = __DIR__ . '/settings.json';
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(['site_name' => 'Baby Tracker', 'ai_models' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    $content = file_get_contents($file);
    $settings = json_decode($content, true);

    echo json_encode(['success' => true, 'settings' => $settings]);
}

function updateSystemSettings($conn, $input) {
    $settings = $input['settings'] ?? null;
    if (!is_array($settings)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'تنسيق الإعدادات غير صحيح']);
        return;
    }

    $file = __DIR__ . '/settings.json';
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logAdminAction($conn, $_SESSION['user_id'], 'تحديث إعدادات النظام', 'system', null);
    echo json_encode(['success' => true, 'message' => 'تم تحديث الإعدادات']);
}

function setAIModelStatus($conn, $input, $enabled = true) {
    $modelKey = $input['model_key'] ?? null;
    if (!$modelKey) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف النموذج غير محدد']);
        return;
    }

    $file = __DIR__ . '/settings.json';
    $settings = [];
    if (file_exists($file)) {
        $settings = json_decode(file_get_contents($file), true) ?? [];
    }
    if (!isset($settings['ai_models']) || !is_array($settings['ai_models'])) {
        $settings['ai_models'] = [];
    }

    $settings['ai_models'][$modelKey] = $enabled ? 'active' : 'disabled';
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    logAdminAction($conn, $_SESSION['user_id'], ($enabled ? 'تمكين' : 'تعطيل') . ' نموذج AI', 'system', null);
    echo json_encode(['success' => true, 'message' => 'تم تحديث حالة النموذج']);
}

function downloadBackup($conn, $input) {
    $filename = $input['filename'] ?? null;
    $backupDir = __DIR__ . '/backups';
    if (!$filename) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الملف غير محدد']);
        return;
    }

    $path = $backupDir . '/' . basename($filename);
    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
        return;
    }

    $content = base64_encode(file_get_contents($path));
    echo json_encode(['success' => true, 'filename' => basename($path), 'content_base64' => $content]);
}

function restoreBackup($conn, $input) {
    $filename = $input['filename'] ?? null;
    $backupDir = __DIR__ . '/backups';
    if (!$filename) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الملف غير محدد']);
        return;
    }

    $path = $backupDir . '/' . basename($filename);
    if (!file_exists($path)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
        return;
    }

    // تنفيذ الاستعادة باستخدام mysql (دعم مسار Windows)
    $mysqlcmd = 'mysql';
    if (stripos(PHP_OS, 'WIN') === 0) {
        $possible = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        if (file_exists($possible)) {
            $mysqlcmd = escapeshellarg($possible);
        }
    }
    $command = sprintf('%s -h %s -u %s %s < %s 2>&1', $mysqlcmd, 'localhost', 'root', 'baby_tracker', escapeshellarg($path));
    $output = shell_exec($command);
    logAdminAction($conn, $_SESSION['user_id'], 'استعادة نسخة احتياطية', 'system', null);
    echo json_encode(['success' => true, 'message' => 'تم استعادة النسخة الاحتياطية (راجع مخرجات الخادم)', 'output' => $output]);
}

function getChildren($conn) {
    $query = "SELECT c.id, c.name, c.birth_date, c.weight, c.height, u.full_name as parent_name
              FROM children c
              LEFT JOIN users u ON c.user_id = u.id
              ORDER BY c.created_at DESC";
    
    $result = $conn->query($query);
    $children = [];
    
    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'children' => $children,
        'count' => count($children)
    ]);
}

function getChildDetails($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    if ($childId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الطفل غير صحيح']);
        return;
    }
    $query = "SELECT c.id, c.name, c.birth_date, c.weight, c.height, c.user_id,
                     u.full_name as parent_name, u.email AS parent_email, u.phone AS parent_phone
              FROM children c
              LEFT JOIN users u ON c.user_id = u.id
              WHERE c.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الطفل غير موجود']);
        return;
    }
    $child = $result->fetch_assoc();
    // compute age
    if (!empty($child['birth_date'])) {
        $b = new DateTime($child['birth_date']);
        $n = new DateTime();
        $interval = $b->diff($n);
        $child['age'] = $interval->format('%y سنة و %m أشهر');
    } else {
        $child['age'] = '';
    }

    // اجلب التطعيمات الخاصة بالطفل
    $q = "SELECT cv.id, cv.status, cv.due_date, cv.administered_date,
                 v.name AS vaccine_name, u.full_name AS nurse_name
          FROM child_vaccines cv
          LEFT JOIN vaccines v ON cv.vaccine_id = v.id
          LEFT JOIN users u ON cv.nurse_id = u.id
          WHERE cv.child_id = ?
          ORDER BY cv.created_at DESC";
    $stmt2 = $conn->prepare($q);
    $stmt2->bind_param('i', $childId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $vaccines = [];
    while ($r = $res2->fetch_assoc()) {
        $vaccines[] = $r;
    }
    $child['vaccines'] = $vaccines;

    // اجلب آخر الأنشطة اليومية للطفل (حد أقصى 10)
    $q = "SELECT da.id, da.activity_type, da.date, da.details, da.quantity, da.weight, da.height, da.temperature
          FROM daily_activities da
          WHERE da.child_id = ?
          ORDER BY da.created_at DESC
          LIMIT 10";
    $stmt2 = $conn->prepare($q);
    $stmt2->bind_param('i', $childId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $activities = [];
    while ($r = $res2->fetch_assoc()) {
        $activities[] = $r;
    }
    $child['activities'] = $activities;

    // اجلب الملاحظات المهنية المتعلقة بالطفل
    $q = "SELECT pn.id, pn.user_type, pn.note_content, pn.created_at, u.full_name as author_name
          FROM professional_notes pn
          LEFT JOIN users u ON pn.user_id = u.id
          WHERE pn.child_id = ?
          ORDER BY pn.created_at DESC";
    $stmt2 = $conn->prepare($q);
    $stmt2->bind_param('i', $childId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $notes = [];
    while ($r = $res2->fetch_assoc()) {
        $notes[] = $r;
    }
    $child['notes'] = $notes;

    // اجلب سجلات النمو (للمخطط)
    $q = "SELECT date, weight, height FROM daily_activities WHERE child_id = ? AND activity_type = 'growth_record' ORDER BY date ASC";
    $stmt2 = $conn->prepare($q);
    $stmt2->bind_param('i', $childId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $growth = [];
    while ($r = $res2->fetch_assoc()) {
        $growth[] = $r;
    }
    $child['growth_records'] = $growth;

    echo json_encode(['success' => true, 'data' => $child]);
}

function deleteChild($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    
    if ($childId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'معرف الطفل غير صحيح'
        ]);
        return;
    }
    
    $deleteQuery = "DELETE FROM children WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param('i', $childId);
    
    if ($deleteStmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'حذف ملف طفل', 'child', $childId);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف ملف الطفل بنجاح'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء حذف الملف'
        ]);
    }
}

function archiveChild($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    if ($childId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الطفل غير صحيح']);
        return;
    }

    $query = "UPDATE children SET is_archived = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $childId);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'أرشفة ملف طفل', 'child', $childId);
        echo json_encode(['success' => true, 'message' => 'تم أرشفة الطفل بنجاح']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء أرشفة الملف']);
    }
}

function unarchiveChild($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    if ($childId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الطفل غير صحيح']);
        return;
    }

    $query = "UPDATE children SET is_archived = 0 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $childId);

    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إلغاء أرشفة ملف طفل', 'child', $childId);
        echo json_encode(['success' => true, 'message' => 'تم إلغاء أرشفة الطفل بنجاح']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إلغاء الأرشفة']);
    }
}

// ==================== دوال إضافة ====================

function getUserStats($conn, $input) {
    $userId = $input['user_id'] ?? 0;
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف المستخدم غير صحيح']);
        return;
    }
    // count vaccines entered
    $query = "SELECT COUNT(*) AS vaccine_count FROM child_vaccines WHERE nurse_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $vc = $stmt->get_result()->fetch_assoc()['vaccine_count'];

    $query = "SELECT COUNT(*) AS note_count FROM professional_notes WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $nc = $stmt->get_result()->fetch_assoc()['note_count'];

    echo json_encode(['success' => true, 'vaccine_count' => (int)$vc, 'note_count' => (int)$nc]);
}

function getChildStats($conn, $input) {
    $childId = $input['child_id'] ?? 0;
    if ($childId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الطفل غير صحيح']);
        return;
    }
    $query = "SELECT COUNT(*) AS vaccine_count FROM child_vaccines WHERE child_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $childId);
    $stmt->execute();
    $vc = $stmt->get_result()->fetch_assoc()['vaccine_count'];

    $query = "SELECT COUNT(*) AS activity_count FROM daily_activities WHERE child_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $childId);
    $stmt->execute();
    $ac = $stmt->get_result()->fetch_assoc()['activity_count'];

    $query = "SELECT COUNT(*) AS note_count FROM professional_notes WHERE child_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $childId);
    $stmt->execute();
    $nc = $stmt->get_result()->fetch_assoc()['note_count'];

    echo json_encode(['success' => true, 'vaccine_count' => (int)$vc, 'activity_count' => (int)$ac, 'note_count' => (int)$nc]);
}

function exportData($conn, $input) {
    $table = $input['table'] ?? '';
    $csv = '';
    if ($table === 'nurses' || $table === 'doctors' || $table === 'parents') {
        $type = $table === 'parents' ? 'parent' : substr($table,0,-1);
        $q = "SELECT id, full_name, email, phone, created_at, last_login FROM users WHERE user_type = ?";
        $stmt = $conn->prepare($q);
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $res = $stmt->get_result();
        $csv .= "ID,Name,Email,Phone,Created At,Last Login\n";
        while ($r = $res->fetch_assoc()) {
            $csv .= "{$r['id']},\"{$r['full_name']}\",{$r['email']},{$r['phone']},{$r['created_at']},{$r['last_login']}\n";
        }
    } elseif ($table === 'children') {
        $q = "SELECT c.id, c.name, c.birth_date, c.weight, c.height, u.full_name as parent_name FROM children c LEFT JOIN users u ON c.user_id = u.id";
        $res = $conn->query($q);
        $csv .= "ID,Name,Birth Date,Weight,Height,Parent\n";
        while ($r = $res->fetch_assoc()) {
            $csv .= "{$r['id']},\"{$r['name']}\",{$r['birth_date']},{$r['weight']},{$r['height']},\"{$r['parent_name']}\"\n";
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'نوع الجدول غير مدعوم']);
        return;
    }
    echo json_encode(['success'=>true,'csv'=> $csv]);
}

// ==================== دوال الأنشطة ====================

function getActivities($conn) {
    $query = "SELECT da.id, da.activity_type, da.date, da.details, c.name as child_name
              FROM daily_activities da
              LEFT JOIN children c ON da.child_id = c.id
              ORDER BY da.created_at DESC
              LIMIT 100";
    
    $result = $conn->query($query);
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'count' => count($activities)
    ]);
}

// ==================== دوال التطعيمات ====================

function getVaccines($conn) {
    $query = "SELECT cv.id, cv.status, cv.due_date, cv.administered_date,
                     c.name as child_name, v.name as vaccine_name, u.full_name as nurse_name
              FROM child_vaccines cv
              LEFT JOIN children c ON cv.child_id = c.id
              LEFT JOIN vaccines v ON cv.vaccine_id = v.id
              LEFT JOIN users u ON cv.nurse_id = u.id
              ORDER BY cv.created_at DESC";
    
    $result = $conn->query($query);
    $vaccines = [];
    
    while ($row = $result->fetch_assoc()) {
        $vaccines[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'vaccines' => $vaccines,
        'count' => count($vaccines)
    ]);
}

// ==================== CRUD for vaccine types ====================
function getVaccineTypes($conn) {
    $query = "SELECT id, name, target_age, description, created_at FROM vaccines ORDER BY created_at DESC";
    $result = $conn->query($query);
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row;
    }
    echo json_encode(['success' => true, 'vaccine_types' => $types, 'count' => count($types)]);
}

function addVaccineType($conn, $input) {
    $name = $input['name'] ?? '';
    $target_age = $input['target_age'] ?? '';
    $desc = $input['description'] ?? null;
    if (empty($name) || empty($target_age)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'البيانات ناقصة']);
        return;
    }
    $query = "INSERT INTO vaccines (name, target_age, description, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sss', $name, $target_age, $desc);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة نوع تطعيم', 'vaccine_type', $stmt->insert_id);
        echo json_encode(['success' => true, 'message' => 'تم إضافة نوع التطعيم']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الإضافة']);
    }
}

function updateVaccineType($conn, $input) {
    $id = $input['id'] ?? 0;
    $name = $input['name'] ?? '';
    $target_age = $input['target_age'] ?? '';
    $desc = $input['description'] ?? null;
    if ($id <= 0 || empty($name) || empty($target_age)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'البيانات غير صحيحة']);
        return;
    }
    $query = "UPDATE vaccines SET name = ?, target_age = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssi', $name, $target_age, $desc, $id);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل نوع تطعيم', 'vaccine_type', $id);
        echo json_encode(['success' => true, 'message' => 'تم تحديث نوع التطعيم']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التحديث']);
    }
}

function deleteVaccineType($conn, $input) {
    $id = $input['id'] ?? 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
        return;
    }
    $query = "DELETE FROM vaccines WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'حذف نوع تطعيم', 'vaccine_type', $id);
        echo json_encode(['success' => true, 'message' => 'تم حذف نوع التطعيم']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف']);
    }
}

function getVaccineTypeDetails($conn, $input) {
    $id = $input['id'] ?? 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
        return;
    }
    $query = "SELECT id, name, target_age, description FROM vaccines WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'نوع التطعيم غير موجود']);
        return;
    }
    $row = $res->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $row]);
}


// ==================== دوال السجلات ====================

function getLogs($conn, $input) {
    $limit = min($input['limit'] ?? 50, 1000);
    
    $query = "SELECT al.id, al.created_at, al.action, al.target_type, al.new_value,
                     u.full_name as admin_name
              FROM admin_logs al
              LEFT JOIN users u ON al.admin_id = u.id
              ORDER BY al.created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'count' => count($logs)
    ]);
}

// ==================== دوال الذكاء الاصطناعي ====================

function checkAIModels($conn) {
    // التحقق من حالة نماذج AI
    $models = [
        'growth_prediction' => 'نموذج التنبؤ بالنمو',
        'cry_analysis' => 'نموذج تحليل البكاء',
        'symptom_guidance' => 'نموذج إرشادات الأعراض'
    ];
    
    // read enabled state from settings
    $file = __DIR__ . '/settings.json';
    $settings = [];
    if (file_exists($file)) {
        $settings = json_decode(file_get_contents($file), true) ?? [];
    }
    $enabledMap = isset($settings['ai_models']) && is_array($settings['ai_models']) ? $settings['ai_models'] : [];

    $status = [];
    foreach ($models as $key => $name) {
        $isActive = (isset($enabledMap[$key]) && $enabledMap[$key] === 'active');
        $status[] = [
            'key' => $key,
            'model' => $name,
            'status' => $isActive ? 'active' : 'disabled',
            'accuracy' => rand(85, 95) . '%'
        ];
    }
    
    logAdminAction($conn, $_SESSION['user_id'], 'التحقق من نماذج AI', 'system', null);
    
    echo json_encode([
        'success' => true,
        'models' => $status,
        'message' => 'تم جلب حالة النماذج'
    ]);
}

// ==================== دوال النسخ الاحتياطية ====================

function createBackup($conn) {
    // إنشاء نسخة احتياطية من قاعدة البيانات
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';
    
    // استخدام mysqldump (دعم مسارات Windows عند الاستخدام مع XAMPP)
    $mysqldump = 'mysqldump';
    if (stripos(PHP_OS, 'WIN') === 0) {
        $possible = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (file_exists($possible)) {
            $mysqldump = escapeshellarg($possible);
        }
    }

    $command = sprintf(
        '%s -h %s -u %s -p%s %s > %s 2>&1',
        $mysqldump,
        'localhost',
        'root',
        '',
        'baby_tracker',
        escapeshellarg($backupFile)
    );

    $output = shell_exec($command);

    if (file_exists($backupFile) && filesize($backupFile) > 0) {
        logAdminAction($conn, $_SESSION['user_id'], 'إنشاء نسخة احتياطية', 'system', null);
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء النسخة الاحتياطية بنجاح',
            'filename' => basename($backupFile),
            'size' => filesize($backupFile)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'فشل إنشاء النسخة الاحتياطية: ' . trim($output)
        ]);
    }
}

function listBackups($conn) {
    $backupDir = __DIR__ . '/backups';
    $files = [];
    if (is_dir($backupDir)) {
        foreach (scandir($backupDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = $backupDir . '/' . $f;
            if (is_file($path)) {
                $files[] = [
                    'filename' => $f,
                    'size' => filesize($path),
                    'modified' => date('Y-m-d H:i:s', filemtime($path))
                ];
            }
        }
    }
    echo json_encode(['success' => true, 'backups' => $files]);
}

// ==================== دوال الأعراض الشائعة ====================

function getCommonSymptomsAdmin($conn) {
    $query = "SELECT id, symptom_name, description, age_range_min_months, age_range_max_months, severity_level, home_remedies, when_to_see_doctor, created_at
              FROM common_symptoms
              ORDER BY created_at DESC";
    $result = $conn->query($query);
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = $row;
    }
    echo json_encode(['success'=>true,'common_symptoms'=>$list,'count'=>count($list)]);
}

function getCommonSymptomDetails($conn, $input) {
    $id = $input['id'] ?? 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'معرف غير صحيح']);
        return;
    }
    $query = "SELECT * FROM common_symptoms WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'لم يتم العثور على العرض']);
        return;
    }
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true,'data'=>$row]);
}

function addCommonSymptom($conn, $input) {
    $name = $input['symptom_name'] ?? '';
    $desc = $input['description'] ?? null;
    $min_age = intval($input['age_range_min_months'] ?? 0);
    $max_age = intval($input['age_range_max_months'] ?? 0);
    $severity = $input['severity_level'] ?? 'mild';
    $remedies = $input['home_remedies'] ?? null;
    $when_doc = $input['when_to_see_doctor'] ?? null;
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'الاسم مطلوب']);
        return;
    }
    $query = "INSERT INTO common_symptoms (symptom_name, description, age_range_min_months, age_range_max_months, severity_level, home_remedies, when_to_see_doctor, created_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssiiiss', $name, $desc, $min_age, $max_age, $severity, $remedies, $when_doc);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'إضافة عرض شائع', 'common_symptom', $stmt->insert_id);
        echo json_encode(['success'=>true,'message'=>'تمت الإضافة']);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'حدث خطأ أثناء الإضافة']);
    }
}

function updateCommonSymptom($conn, $input) {
    $id = intval($input['id'] ?? 0);
    $name = $input['symptom_name'] ?? '';
    $desc = $input['description'] ?? null;
    $min_age = intval($input['age_range_min_months'] ?? 0);
    $max_age = intval($input['age_range_max_months'] ?? 0);
    $severity = $input['severity_level'] ?? 'mild';
    $remedies = $input['home_remedies'] ?? null;
    $when_doc = $input['when_to_see_doctor'] ?? null;
    if ($id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'بيانات غير صحيحة']);
        return;
    }
    $query = "UPDATE common_symptoms SET symptom_name=?, description=?, age_range_min_months=?, age_range_max_months=?, severity_level=?, home_remedies=?, when_to_see_doctor=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssiiissi', $name, $desc, $min_age, $max_age, $severity, $remedies, $when_doc, $id);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'تعديل عرض شائع', 'common_symptom', $id);
        echo json_encode(['success'=>true,'message'=>'تم التحديث']);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'حدث خطأ أثناء التحديث']);
    }
}

function deleteCommonSymptom($conn, $input) {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'معرف غير صحيح']);
        return;
    }
    $query = "DELETE FROM common_symptoms WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i',$id);
    if ($stmt->execute()) {
        logAdminAction($conn, $_SESSION['user_id'], 'حذف عرض شائع', 'common_symptom', $id);
        echo json_encode(['success'=>true,'message'=>'تم الحذف']);
    } else {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'حدث خطأ أثناء الحذف']);
    }
}

function deleteBackup($conn, $input) {
    $filename = $input['filename'] ?? null;
    $backupDir = __DIR__ . '/backups';
    if (!$filename) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الملف غير محدد']);
        return;
    }
    $path = $backupDir . '/' . basename($filename);
    if (file_exists($path)) {
        unlink($path);
        logAdminAction($conn, $_SESSION['user_id'], 'حذف نسخة احتياطية', 'system', null);
        echo json_encode(['success' => true, 'message' => 'تم حذف النسخة']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الملف غير موجود']);
    }
}

// ==================== دوال مساعدة ====================

function logAdminAction($conn, $adminId, $action, $targetType, $targetId) {
    $query = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, ip_address) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt->bind_param('issss', $adminId, $action, $targetType, $targetId, $ipAddress);
    $stmt->execute();
}

// إغلاق الاتصال
$db->close();
?>
