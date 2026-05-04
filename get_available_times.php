<?php
// API For available and booked times
require_once './includes/db_config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$db = new DatabaseHelper();
$conn = $db->getConnection();

$doctor_id = intval($_GET['doctor_id']);
$date = $_GET['date'];

// جلب المواعيد المحجوزة لهذا الطبيب في هذا اليوم
// استخراج الوقت من appointment_date
$sql = "SELECT appointment_date FROM appointments 
        WHERE doctor_id=? 
        AND DATE(appointment_date)=? 
        AND appointment_status IN ('scheduled','confirmed')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$booked_times = array();
while ($row = $result->fetch_assoc()) {
    // استخراج الوقت من appointment_date
    $time = date('H:i:00', strtotime($row['appointment_date']));
    $booked_times[] = $time;
}
$stmt->close();

// Available times (من 9 صباحاً حتى 5 مساءً كل نصف ساعة)
$available_times = array();
for ($h=9; $h<=17; $h++) {
    foreach ([0,30] as $m) {
        $t = sprintf('%02d:%02d:00', $h, $m);
        $available_times[] = $t;
    }
}

echo json_encode(['available'=>$available_times, 'booked'=>$booked_times]);
?>
