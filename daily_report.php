<?php
require_once 'includes/db_config.php';
require_once 'includes/check_auth.php'; // للأطباء فقط أو الآباء

$db = new DatabaseHelper();
$conn = $db->getConnection();

$yesterday = date('Y-m-d', strtotime('-1 day'));

// Bring all the children
$stmt_children = $conn->prepare('SELECT id, name, user_id FROM children');
$stmt_children->execute();
$children = $stmt_children->fetch_all(MYSQLI_ASSOC);

foreach ($children as $child) {
    $child_id = $child['id'];
    $child_name = $child['name'];
    $parent_id = $child['user_id'];

    //Breastfeeding statistics
    $stmt_feed = $conn->prepare("SELECT COUNT(*) as count, AVG(duration) as avg_duration FROM daily_activities WHERE child_id = ? AND date = ? AND activity_type IN ('breast_feed', 'formula_feed')");
    $stmt_feed->bind_param('is', $child_id, $yesterday);
    $stmt_feed->execute();
    $feed_stats = $stmt_feed->get_result()->fetch_assoc();

    // Sleep statistics
    $stmt_sleep = $conn->prepare("SELECT SUM(duration) as total_sleep, AVG(quantity) as avg_wakeups FROM daily_activities WHERE child_id = ? AND date = ? AND activity_type IN ('nap', 'night_sleep')");
    $stmt_sleep->bind_param('is', $child_id, $yesterday);
    $stmt_sleep->execute();
    $sleep_stats = $stmt_sleep->get_result()->fetch_assoc();

    $feed_regular = $feed_stats['count'] >= 6 ? 'منتظمة' : 'غير منتظمة';
    $sleep_regular = $sleep_stats['total_sleep'] >= 12 ? 'منتظمة' : 'غير منتظمة';

    // Vaccination notifications
    $stmt_vaccines = $conn->prepare("SELECT vs.vaccine_name, cv.due_date FROM child_vaccines cv JOIN vaccine_schedule vs ON cv.vaccine_schedule_id = vs.id WHERE cv.child_id = ? AND cv.status = 'due' AND cv.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $stmt_vaccines->bind_param('i', $child_id);
    $stmt_vaccines->execute();
    $upcoming_vaccines = $stmt_vaccines->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($upcoming_vaccines)) {
        $report .= "\nلقاحات قادمة:\n";
        foreach ($upcoming_vaccines as $vac) {
            $report .= "- {$vac['vaccine_name']} في {$vac['due_date']}\n";
        }
    }

    $stmt_missed = $conn->prepare("SELECT vs.vaccine_name, cv.due_date FROM child_vaccines cv JOIN vaccine_schedule vs ON cv.vaccine_schedule_id = vs.id WHERE cv.child_id = ? AND cv.status = 'due' AND cv.due_date < CURDATE()");
    $stmt_missed->bind_param('i', $child_id);
    $stmt_missed->execute();
    $missed_vaccines = $stmt_missed->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!empty($missed_vaccines)) {
        $report .= "\nلقاحات فائتة:\n";
        foreach ($missed_vaccines as $vac) {
            $report .= "- {$vac['vaccine_name']} كان في {$vac['due_date']}\n";
        }
    }

    // Send an email to the parent
    $stmt_parent = $conn->prepare('SELECT email FROM users WHERE id = ?');
    $stmt_parent->bind_param('i', $parent_id);
    $stmt_parent->execute();
    $parent = $stmt_parent->get_result()->fetch_assoc();

    if ($parent) {
        mail($parent['email'], "تقرير يومي للطفل $child_name", $report);
    }

    // إرسال للأطباء (إذا كان هناك طبيب مرتبط)
    $stmt_doctor = $conn->prepare('SELECT DISTINCT u.email FROM medical_visits mv JOIN users u ON mv.doctor_id = u.id WHERE mv.child_id = ?');
    $stmt_doctor->bind_param('i', $child_id);
    $stmt_doctor->execute();
    $doctors = $stmt_doctor->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($doctors as $doctor) {
        mail($doctor['email'], "تقرير يومي للمريض $child_name", $report);
    }
}

echo 'تم إرسال التقارير اليومية';
?>