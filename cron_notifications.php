<?php
// ملف لجدولة الإشعارات التلقائية
// يمكن تشغيله عبر cron job كل ساعة

require_once 'notification_system.php';

try {
    $notifier = new NotificationManager();
    $notifier->runAllChecks();

    // تسجيل التشغيل في ملف log
    $log_message = date('Y-m-d H:i:s') . " - تم تشغيل نظام الإشعارات التلقائي بنجاح\n";
    file_put_contents('logs/notification_cron.log', $log_message, FILE_APPEND);

} catch (Exception $e) {
    $error_message = date('Y-m-d H:i:s') . " - خطأ في نظام الإشعارات: " . $e->getMessage() . "\n";
    file_put_contents('logs/notification_cron.log', $error_message, FILE_APPEND);
}
?>