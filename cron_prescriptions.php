<?php
/**
 * Prescription Renewal Notifications Cron Job
 * جدول مهام لفحص الوصفات قريبة الانتهاء وإرسال التنبيهات
 * 
 * يجب تشغيل هذا الملف كل يوم (باستخدام cron job أو task scheduler)
 * php /path/to/cron_prescriptions.php
 */

include 'includes/db_config.php';

$db = new DatabaseHelper();
$conn = $db->getConnection();

// ============================================================================
// الدالة الرئيسية: فحص الوصفات وإرسال التنبيهات
// ============================================================================
function check_and_notify_expiring_prescriptions() {
    global $conn;

    // Days before the end date for which we want to send a notification
    $notification_days = 7; // أرسل تنبيه قبل 7 أيام

    // جلب الوصفات قريبة الانتهاء التي لم يتم إرسال إشعار عنها بعد
    $query = "
        SELECT DISTINCT
            p.id as prescription_id,
            p.child_id,
            p.expiry_date,
            DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
            c.name as child_name,
            CONCAT(u.full_name) as doctor_name,
            u.email as doctor_email,
            GROUP_CONCAT(m.name SEPARATOR ', ') as medications
        FROM prescriptions p
        JOIN children c ON p.child_id = c.id
        JOIN users u ON p.doctor_id = u.id
        LEFT JOIN prescription_medications pm ON p.id = pm.prescription_id
        LEFT JOIN medications m ON pm.medication_id = m.id
        WHERE p.status = 'active'
            AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND p.id NOT IN (
                SELECT prescription_id FROM prescription_renewal_notifications 
                WHERE notification_sent IS NOT NULL
            )
        GROUP BY p.id
        ORDER BY p.expiry_date ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $notification_days);
    $stmt->execute();
    $result = $stmt->get_result();
    $expiring_prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $notification_count = 0;

    foreach ($expiring_prescriptions as $prescription) {
        // The child's parents brought
        $stmt = $conn->prepare("
            SELECT u.id, u.email, u.phone, u.full_name
            FROM children c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param('i', $prescription['child_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $parents = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        //Send a notification to each parent
        foreach ($parents as $parent) {
            $notification_created = create_renewal_notification(
                $prescription,
                $parent,
                $conn
            );
            
            if ($notification_created) {
                $notification_count++;

                //Send email
                send_prescription_renewal_email($parent, $prescription, $conn);

                // إرسال رسالة نصية (SMS) إذا كان رقم الهاتف متاحاً
                if (!empty($parent['phone'])) {
                    send_prescription_renewal_sms($parent, $prescription);
                }
            }
        }

        // إنشاء إشعار داخلي للممرض المسؤول (إن وجد)
        create_nurse_notification($prescription, $conn);
    }

    log_cron_execution(
        'prescription_renewal_check',
        'success',
        "تم فحص الوصفات وإرسال $notification_count إشعار",
        $conn
    );

    return [
        'status' => 'success',
        'notifications_sent' => $notification_count,
        'prescriptions_checked' => count($expiring_prescriptions),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// ============================================================================
// إنشاء إشعار التجديد في قاعدة البيانات
// ============================================================================
function create_renewal_notification($prescription, $parent, $conn) {
    $parent_id = $parent['id'];
    $prescription_id = $prescription['prescription_id'];

    // Check for duplicate notifications
    $stmt = $conn->prepare("
        SELECT id FROM prescription_renewal_notifications
        WHERE prescription_id = ? AND parent_id = ? AND notification_sent IS NOT NULL
    ");
    $stmt->bind_param('ii', $prescription_id, $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt->close();
        return false;
    }
    $stmt->close();

    // Create a new notification
    $notification_type = 'in_app';
    $days_before = 7;
    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO prescription_renewal_notifications 
        (prescription_id, parent_id, notification_type, days_before_expiry, notification_sent, is_read)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $is_read = 0;
    $stmt->bind_param('iisiss', $prescription_id, $parent_id, $notification_type, $days_before, $now, $is_read);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

// ============================================================================
// إرسال بريد إلكتروني للتنبيه بالتجديد
// ============================================================================
function send_prescription_renewal_email($parent, $prescription, $conn) {
    // إذا لم تكن هناك بيبليوتيكا للبريد، نحفظ الرسالة فقط
    if (!empty($parent['email'])) {
        $to = $parent['email'];
        $subject = "⚠️ تنبيه: وصفة طبية قريبة الانتهاء - " . htmlspecialchars($prescription['child_name']);

        $days_left = $prescription['days_until_expiry'];
        $expiry_date = date('d/m/Y', strtotime($prescription['expiry_date']));

        $message = "
        <html dir='rtl'>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .alert-box { background: #fff3cd; border: 1px solid #f39c12; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .alert-title { color: #f39c12; font-size: 18px; font-weight: bold; margin-bottom: 10px; }
                .prescription-details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .prescription-details p { margin: 8px 0; }
                .button { display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='alert-box'>
                    <div class='alert-title'>⚠️ تنبيه: وصفة طبية قريبة الانتهاء</div>
                    <p>السلام عليكم ورحمة الله وبركاته</p>
                </div>

                <p>لديك وصفة طبية قريبة الانتهاء للطفل <strong>" . htmlspecialchars($prescription['child_name']) . "</strong></p>

                <div class='prescription-details'>
                    <p><strong>👶 الطفل:</strong> " . htmlspecialchars($prescription['child_name']) . "</p>
                    <p><strong>🏥 الطبيب:</strong> د. " . htmlspecialchars($prescription['doctor_name']) . "</p>
                    <p><strong>💊 الأدوية:</strong> " . htmlspecialchars($prescription['medications']) . "</p>
                    <p><strong>📅 تاريخ الانتهاء:</strong> $expiry_date</p>
                    <p><strong>⏰ الوقت المتبقي:</strong> $days_left يوم</p>
                </div>

                <p>يُرجى التواصل مع الطبيب أو الطاقم الطبي لتجديد الوصفة قبل انتهاء صلاحيتها.</p>

                <a href='" . getAppUrl() . "/prescriptions.php?child_id=" . $prescription['child_id'] . "' class='button'>عرض الوصفات</a>

                <footer>
                    <p>هذا البريد الإلكتروني تم إرساله تلقائياً من نظام صحة الطفل.</p>
                    <p>يرجى عدم الرد على هذا البريد.</p>
                </footer>
            </div>
        </body>
        </html>
        ";

        // Send mail
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@baby-health.local\r\n";

        // في بيئة الإنتاج، استخدم خدمة بريد حقيقية
        // mail($to, $subject, $message, $headers);

        // حالياً نسجل فقط
        error_log("Email sent to $to: $subject");
    }
}

// ============================================================================
// إرسال رسالة نصية (SMS) للتنبيه
// ============================================================================
function send_prescription_renewal_sms($parent, $prescription) {
    if (empty($parent['phone'])) return;

    $days_left = $prescription['days_until_expiry'];
    $message = "تنبيه صحة الطفل: وصفة " . $prescription['child_name'] . " ستنتهي خلال $days_left أيام. يرجى التواصل مع الطبيب للتجديد.";

    // في بيئة الإنتاج، استخدم خدمة SMS مثل Twilio أو أي خدمة محلية
    // sendSMS($parent['phone'], $message);

    error_log("SMS would be sent to " . $parent['phone'] . ": $message");
}

// ============================================================================
// إرسال إشعار للممرض المسؤول
// ============================================================================
function create_nurse_notification($prescription, $conn) {
    // جلب الممرضين المسؤولين عن هذا الطفل
    // نفترض أنه يوجد علاقة بين الممرض والطفل

    $message = "تنبيه: وصفة طبية قريبة الانتهاء للطفل " . $prescription['child_name'] .
              " (تنتهي في " . $prescription['days_until_expiry'] . " أيام)";

    error_log("Nurse notification: $message");
}

// ============================================================================
// تسجيل تنفيذ الجدول الزمني
// ============================================================================
function log_cron_execution($task_name, $status, $message, $conn) {
    $stmt = $conn->prepare("
        INSERT INTO cron_logs (cron_name, status, result_json, executed_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param('sss', $task_name, $status, $message);
    $stmt->execute();
    $stmt->close();
}

// ============================================================================
// دالة مساعدة للحصول على URL التطبيق
// ============================================================================
function getAppUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $url = $protocol . $host;
    return rtrim($url, '/');
}

// ============================================================================
// تشغيل الفحص
// ============================================================================
if (php_sapi_name() === 'cli' || $_GET['run'] === '1') {
    $result = check_and_notify_expiring_prescriptions();
    
    if (php_sapi_name() === 'cli') {
        echo json_encode($result);
        echo "\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
} else {
    die('This script should be run via CLI or with ?run=1 parameter');
}

?>
