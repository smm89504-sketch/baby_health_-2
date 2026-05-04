<?php
// API endpoint للإشعارات التلقائية
header('Content-Type: application/json');

require_once 'notification_system.php';

try {
    $notifier = new NotificationManager();
    $notifier->runAllChecks();

    echo json_encode([
        'success' => true,
        'message' => 'تم تشغيل نظام الإشعارات بنجاح',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ]);
}
?>