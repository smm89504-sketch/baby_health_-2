<?php
/**
 * نظام التنبيهات التلقائية - Appointment Reminders Cron Job
 */

include 'includes/db_config.php';

$db = new DatabaseHelper();
$conn = $db->getConnection();

/**
 * Enable alert functions
 */
function runAppointmentReminders() {
    global $conn;
    
    $results = [
        'reminders_sent' => 0,
        'reminders_failed' => 0,
        'reminder_details' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        // الحصول على التنبيهات المعلقة
        $query = "SELECT 
                    ar.id,
                    ar.appointment_id,
                    ar.parent_id,
                    ar.reminder_type,
                    ar.reminder_time_before_minutes,
                    a.appointment_date,
                    a.appointment_type,
                    c.name as child_name,
                    u.full_name as doctor_name,
                    p.email as parent_email,
                    p.phone as parent_phone,
                    p.full_name as parent_name
                  FROM appointment_reminders ar
                  JOIN appointments a ON ar.appointment_id = a.id
                  JOIN children c ON a.child_id = c.id
                  JOIN users u ON a.doctor_id = u.id
                  JOIN users p ON ar.parent_id = p.id
                  WHERE ar.reminder_sent_status = 'pending'
                  AND a.appointment_status NOT IN ('cancelled')
                  AND TIMESTAMPDIFF(MINUTE, NOW(), a.appointment_date) <= ar.reminder_time_before_minutes
                  AND TIMESTAMPDIFF(MINUTE, NOW(), a.appointment_date) > (ar.reminder_time_before_minutes - 5)
                  ORDER BY ar.created_at ASC";
        
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception('خطأ في الاستعلام: ' . $conn->error);
        }
        
        $reminders = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($reminders as $reminder) {
            try {
                $sent = false;
                
                // Send alerts by type
                if ($reminder['reminder_type'] === 'in_app') {
                    $sent = sendInAppReminder($reminder);
                } elseif ($reminder['reminder_type'] === 'email') {
                    $sent = sendEmailReminder($reminder);
                } elseif ($reminder['reminder_type'] === 'sms') {
                    $sent = sendSmsReminder($reminder);
                } elseif ($reminder['reminder_type'] === 'whatsapp') {
                    $sent = sendWhatsappReminder($reminder);
                }
                
                // Update alert status
                if ($sent) {
                    $update_query = "UPDATE appointment_reminders 
                                    SET reminder_sent_status = 'sent', 
                                        reminder_sent = NOW()
                                    WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param('i', $reminder['id']);
                    $stmt->execute();
                    
                    $results['reminders_sent']++;
                    $results['reminder_details'][] = [
                        'appointment_id' => $reminder['appointment_id'],
                        'type' => $reminder['reminder_type'],
                        'status' => 'sent',
                        'child' => $reminder['child_name'],
                        'parent' => $reminder['parent_name'],
                        'appointment_date' => $reminder['appointment_date']
                    ];
                } else {
                    $update_query = "UPDATE appointment_reminders 
                                    SET reminder_sent_status = 'failed'
                                    WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param('i', $reminder['id']);
                    $stmt->execute();
                    
                    $results['reminders_failed']++;
                    $results['reminder_details'][] = [
                        'appointment_id' => $reminder['appointment_id'],
                        'type' => $reminder['reminder_type'],
                        'status' => 'failed',
                        'child' => $reminder['child_name'],
                        'parent' => $reminder['parent_name'],
                    ];
                }
            } catch (Exception $e) {
                $results['reminders_failed']++;
                $results['reminder_details'][] = [
                    'appointment_id' => $reminder['appointment_id'],
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Recording results
        logCronExecution('appointment_reminders', $results);
        
    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
        logCronExecution('appointment_reminders', $results, 'error');
    }
    
    return $results;
}

/**
 * Send an in-app notification
 */
function sendInAppReminder($reminder) {
    global $conn;
    
    $message = "تذكير: لديك موعد مع " . $reminder['doctor_name'] . " للطفل " . $reminder['child_name'] . 
               " في " . date('d/m/Y H:i', strtotime($reminder['appointment_date']));
    
    $query = "INSERT INTO notifications (user_id, type, title, message, data_json, is_read, created_at)
              VALUES (?, ?, ?, ?, ?, FALSE, NOW())";
    
    $data = json_encode([
        'appointment_id' => $reminder['appointment_id'],
        'reminder_id' => $reminder['id'],
        'appointment_type' => $reminder['appointment_type'],
        'appointment_date' => $reminder['appointment_date']
    ]);
    
    $type = 'appointment_reminder';
    $title = 'تذكير الموعد';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issss', $reminder['parent_id'], $type, $title, $message, $data);
    
    return $stmt->execute();
}

/**
 * Send email alert
 */
function sendEmailReminder($reminder) {
    // تجهيز البيانات
    $to = $reminder['parent_email'];
    $subject = "تذكير موعد طبي - " . $reminder['child_name'];
    
    $message = "
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { background: #f8f9fa; padding: 20px; }
            .card { background: white; padding: 20px; border-radius: 8px; }
            .header { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 20px; }
            .detail { margin: 10px 0; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='card'>
                <div class='header'>
                    <h2>تذكير الموعد الطبي</h2>
                </div>
                <div class='detail'>
                    <span class='label'>السلام عليكم ورحمة الله وبركاته</span><br>
                    <span class='value'>" . htmlspecialchars($reminder['parent_name']) . "</span>
                </div>
                <div class='detail'>
                    <span class='label'>تذكير:</span> لديك موعد طبي قريب الاقتراب
                </div>
                <hr>
                <div class='detail'>
                    <span class='label'>اسم الطفل:</span>
                    <span class='value'>" . htmlspecialchars($reminder['child_name']) . "</span>
                </div>
                <div class='detail'>
                    <span class='label'>الطبيب:</span>
                    <span class='value'>" . htmlspecialchars($reminder['doctor_name']) . "</span>
                </div>
                <div class='detail'>
                    <span class='label'>نوع الموعد:</span>
                    <span class='value'>" . $reminder['appointment_type'] . "</span>
                </div>
                <div class='detail'>
                    <span class='label'>التاريخ والوقت:</span>
                    <span class='value'>" . date('d/m/Y H:i', strtotime($reminder['appointment_date'])) . "</span>
                </div>
                <hr>
                <div class='detail'>
                    <span class='value'>يرجى التأكد من حضوركم في الموعد المحدد.</span>
                </div>
                <div class='detail'>
                    <span class='value' style='color: #999; font-size: 0.9em;'>
                        هذا البريد تم إرساله تلقائياً من نظام صحة الأطفال
                    </span>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: noreply@babyhealthsystem.com\r\n";
    
    // محاولة إرسال البريد

    @mail($to, $subject, $message, $headers);
    
    return true;
}

/**
 * Send alert via text message
 */
function sendSmsReminder($reminder) {
    // تجهيز الرسالة
    $message = "تذكير: موعد مع " . substr($reminder['doctor_name'], 0, 10) . " في " . 
               date('H:i', strtotime($reminder['appointment_date']));
    
    // يحتاج لخدمة SMS حقيقية مثل Twilio أو خدمة محلية
    // هذا مثال على الهيكل فقط
    
    // $phone = $reminder['parent_phone'];
    // sendSmsViaProvider($phone, $message);
    
    return true;
}

/**
 * Send an alert via WhatsApp
 */
function sendWhatsappReminder($reminder) {
    // تجهيز الرسالة
    $message = "👋 *تذكير الموعد الطبي* 👋\n\n" .
               "📅 الطفل: " . $reminder['child_name'] . "\n" .
               "👨‍⚕️ الطبيب: " . $reminder['doctor_name'] . "\n" .
               "🕒 الموعد: " . date('d/m/Y H:i', strtotime($reminder['appointment_date'])) . "\n\n" .
               "يرجى التأكد من حضوركم في الموعد المحدد.";
    
    // يحتاج لخدمة WhatsApp API مثل Twilio أو خدمة محلية
    
    return true;
}

/**
 * تسجيل تنفيذ Cron
 */
function logCronExecution($cron_name, $results, $status = 'success') {
    global $conn;
    
    $query = "INSERT INTO cron_logs (cron_name, status, result_json, executed_at)
              VALUES (?, ?, ?, NOW())";
    
    $result_json = json_encode($results);
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sss', $cron_name, $status, $result_json);
    $stmt->execute();
}

/**
 * دالة مساعدة لتحديد أسماء حالات التنبيهات
 */
function getAppointmentDetails($appointment_id) {
    global $conn;
    
    $query = "SELECT * FROM appointments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $appointment_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

// تشغيل البرنامج الرئيسي
$results = runAppointmentReminders();

// إذا تم استدعاء الملف مباشرة من الويب، إرجاع JSON
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results);
}

// إذا تم تشغيل من CLI (Cron), طباعة النتائج
else {
    echo "=== نتائج تنفيذ التنبيهات ===\n";
    echo "الوقت: " . $results['timestamp'] . "\n";
    echo "تم إرسال: " . $results['reminders_sent'] . "\n";
    echo "فشل: " . $results['reminders_failed'] . "\n";
    echo "الإجمالي: " . ($results['reminders_sent'] + $results['reminders_failed']) . "\n";
}
?>
