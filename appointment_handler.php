<?php
/**
 * معالج المواعيد التلقائي
 * يتم تشغيله من خلال cron job أو task scheduler
 * كل ساعة واحدة
 */

require_once 'includes/db_config.php';
require_once 'includes/AppointmentNotificationManager.php';

$db = new DatabaseHelper();
$conn = $db->getConnection();
$notif_manager = new AppointmentNotificationManager($conn);

try {
    // 1. إرسال التأكيدات للمواعيد الجديدة المؤكدة
    $sql_confirmations = "SELECT a.id FROM appointments a
                         WHERE a.appointment_status = 'confirmed' 
                         AND a.confirmation_sent = 0 
                         AND a.appointment_date > CURDATE()
                         LIMIT 50";
    
    $stmt = $conn->prepare($sql_confirmations);
    $stmt->execute();
    $result = $stmt->get_result();
    $confirmation_count = 0;
    while ($row = $result->fetch_assoc()) {
        if ($notif_manager->sendConfirmation($row['id'])) {
            $confirmation_count++;
        }
    }
    $stmt->close();
    
    // 2. Send reminders 24 hours before the appointment
    $sql_reminders = "SELECT a.id FROM appointments a
                     WHERE a.appointment_status IN ('scheduled', 'confirmed')
                     AND a.reminder_sent = 0
                     AND a.appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                     AND a.appointment_date > CURDATE()
                     LIMIT 50";
    
    $stmt = $conn->prepare($sql_reminders);
    $stmt->execute();
    $result = $stmt->get_result();
    $reminder_count = 0;
    while ($row = $result->fetch_assoc()) {
        if ($notif_manager->sendReminder($row['id'], 24)) {
            $reminder_count++;
        }
    }
    $stmt->close();
    
    // 3. Update attendance status for completed appointmentsتحديث حالة الحضور للمواعيد المكتملة
    $sql_update_status = "UPDATE appointments 
                         SET attendance_status = 'attended'
                         WHERE status = 'completed' 
                         AND attendance_status IS NULL
                         AND appointment_date < CURDATE()";
    
    $conn->prepare($sql_update_status)->execute();
    
    // 4. Daily appointment analytics updateتحديث تحليلات المواعيد اليومية
    $sql_analytics = "INSERT INTO appointment_analytics 
                     (doctor_id, appointment_date, total_appointments, 
                      confirmed_appointments, completed_appointments, 
                      no_show_appointments, cancelled_appointments, attendance_rate)
                     SELECT 
                         a.doctor_id,
                         CURDATE(),
                         COUNT(*),
                         SUM(CASE WHEN a.appointment_status = 'confirmed' THEN 1 ELSE 0 END),
                         SUM(CASE WHEN a.appointment_status = 'completed' THEN 1 ELSE 0 END),
                         SUM(CASE WHEN a.attendance_status = 'no_show' THEN 1 ELSE 0 END),
                         SUM(CASE WHEN a.appointment_status = 'cancelled' THEN 1 ELSE 0 END),
                         ROUND((SUM(CASE WHEN a.attendance_status = 'attended' THEN 1 ELSE 0 END) / 
                               COUNT(*)) * 100, 2)
                     FROM appointments a
                     WHERE a.appointment_date = CURDATE()
                     GROUP BY a.doctor_id
                     ON DUPLICATE KEY UPDATE
                         total_appointments = VALUES(total_appointments),
                         confirmed_appointments = VALUES(confirmed_appointments),
                         completed_appointments = VALUES(completed_appointments),
                         no_show_appointments = VALUES(no_show_appointments),
                         cancelled_appointments = VALUES(cancelled_appointments),
                         attendance_rate = VALUES(attendance_rate)";
    
    $conn->prepare($sql_analytics)->execute();
    
    // تسجيل في ملف السجل
    $log_message = date('Y-m-d H:i:s') . " - معالجة المواعيد:\n";
    $log_message .= "  - تم إرسال $confirmation_count تأكيد\n";
    $log_message .= "  - تم إرسال $reminder_count تذكير\n";
    $log_message .= "  - تم تحديث حالات الحضور\n";
    $log_message .= "  - تم تحديث التحليلات\n\n";
    
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
    }
    file_put_contents('logs/appointment_handler.log', $log_message, FILE_APPEND);
    
    echo "✓ معالجة المواعيد نجحت\n";
    echo "  - تأكيدات مرسلة: $confirmation_count\n";
    echo "  - تذكيرات مرسلة: $reminder_count\n";
    
} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - خطأ: " . $e->getMessage() . "\n";
    file_put_contents('logs/appointment_handler.log', $error_message, FILE_APPEND);
    echo "✗ خطأ: " . $e->getMessage();
}
?>
