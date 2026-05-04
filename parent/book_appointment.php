<?php
// حجز موعد عند الطبيب من قبل الأهل
require_once '../includes/db_config.php';
require_once '../includes/AppointmentNotificationManager.php';

$db = new DatabaseHelper();
$conn = $db->getConnection();
// ... Check your login...
// Get a list of doctors
$doctors = $conn->query("SELECT id, full_name FROM users WHERE user_type='doctor'");
// Booking processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'], $_POST['appointment_time'], $_POST['date'], $_POST['child_id'])) {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
        die('يجب تسجيل الدخول كولي أمر');
    }
    $parent_id = $_SESSION['user_id'];
    $doctor_id = intval($_POST['doctor_id']);
    $child_id = intval($_POST['child_id']);
    $date = $_POST['date'];
    $time = $_POST['appointment_time'];
    // Check that the time is not booked
    $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status IN ('scheduled','confirmed')");
    $stmt->bind_param('iss', $doctor_id, $date, $time);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) {
        echo '<script>alert("هذا الوقت محجوز بالفعل، يرجى اختيار وقت آخر.");</script>';
    } else {
        // Add appointment
        $stmt = $conn->prepare("INSERT INTO appointments (child_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'scheduled')");
        $stmt->bind_param('iiss', $child_id, $doctor_id, $date, $time);
        if ($stmt->execute()) {
            $appointment_id = $stmt->insert_id;
            $stmt->close();
            
            // Sending appointment confirmation to parents
            $notif_manager = new AppointmentNotificationManager($conn);
            $notif_manager->sendConfirmation($appointment_id);
            
            echo '<script>alert("تم حجز الموعد بنجاح!");window.location="my_appointments.php";</script>';
            exit;
        } else {
            echo '<script>alert("حدث خطأ أثناء الحجز.");</script>';
        }
        $stmt->close();
    }
}
?>

<?php
// Parents bring children
session_start();
$parent_id = $_SESSION['user_id'] ?? 0;
$children = $conn->query("SELECT id, name FROM children WHERE user_id = $parent_id AND is_archived = 0");
//Setting up colors and design like the rest of the parents' pages
$main_dark = '#ad1457';
$main_text = '#880e4f';
$main_light = '#ffd1dc';
$main_deep = '#f8bbd0';
$bg_light = '#fff0f5';
$title_icon = 'fas fa-calendar-plus';
$dashboard_link = 'index.php';
$user_type = 'parent';
$base_path = './';
$parent_path = '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حجز موعد طبي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); min-height: 100vh; color: #4A4A4A; font-family: 'Cairo', sans-serif; display: flex; }
        .main-container { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 600px; margin: 0 auto; }
        .main-box { margin-top: 40px; box-shadow: 0 6px 24px rgba(100, 100, 100, 0.10); border-radius: 16px; background: #fff; padding: 35px 30px; }
        .page-header { font-size: 1.7rem; color: #C7346F; font-weight: 700; margin-bottom: 30px; text-align: center; }
        .page-header i { margin-left: 12px; font-size: 1.5rem; }
        label { font-weight: 600; color: #ad1457; margin-bottom: 6px; }
        .form-control, select { margin-bottom: 18px; }
        .btn-success { background-color: #E7AAB4; border-color: #E7AAB4; color: #fff; font-weight: 600; padding: 0.5rem 1.2rem; font-size: 1.1rem; }
        .btn-success:hover { background-color: #DB99A6; border-color: #DB99A6; }
         :root {
            --primary-light: <?= $bg_light ?>;
            --primary-pink: <?= $main_light ?>;
            --primary-deep: <?= $main_deep ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }

        /* Sidebar Styles */
        body { background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%); min-height: 100vh; color: #4A4A4A; font-family: 'Cairo', sans-serif; display: flex; }
        .sidebar { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%); width: 250px; min-height: 100vh; padding: 20px; color: white; box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12); transition: all 0.3s; }
        .sidebar a { color: rgba(255, 255, 255, 0.9); text-decoration: none; transition: all 0.2s; }
        .sidebar a:hover { color: white; transform: translateX(5px); }
        .sidebar .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255, 255, 255, 0.15); }
        .sidebar .logo { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .sidebar .logo i { color: var(--primary-pink); }
        .logout-btn { background: rgba(255, 255, 255, 0.15); border: none; border-radius: 12px; padding: 12px; color: white; font-weight: 600; transition: all 0.3s; width: 100%; text-align: right; display: flex; align-items: center; gap: 10px; justify-content: flex-start; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.25); transform: translateY(-3px); }
        .main-container { flex: 1; padding: 20px; overflow-y: auto; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; }
    
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-container">
    <div class="dashboard-container">
        <div class="main-box">
            <div class="page-header"><i class="<?= $title_icon ?>"></i> حجز موعد طبي جديد</div>
            <form method="post" action="">
                <label>اختر الطفل:</label>
                <select name="child_id" class="form-control" required>
                    <?php while($ch = $children->fetch_assoc()): ?>
                    <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <?php
                // إذا تم تمرير doctor_id في الرابط، ثبّت الطبيب وأظهر اسمه فقط
                $selected_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
                $doctor_name = '';
                if ($selected_doctor_id) {
                    $doctor_row = $conn->query("SELECT full_name FROM users WHERE id = $selected_doctor_id AND user_type='doctor'");
                    if ($doctor_row && $doctor_row->num_rows > 0) {
                        $doctor_name = $doctor_row->fetch_assoc()['full_name'];
                    }
                }
                ?>
                <?php if ($selected_doctor_id && $doctor_name): ?>
                    <input type="hidden" name="doctor_id" id="doctor_id" value="<?= $selected_doctor_id ?>">
                    <label>الطبيب:</label>
                    <input type="text" value="<?= htmlspecialchars($doctor_name) ?>" disabled class="form-control">
                <?php else: ?>
                    <label>اختر الطبيب:</label>
                    <select name="doctor_id" id="doctor_id" class="form-control">
                        <?php mysqli_data_seek($doctors, 0); while($doc = $doctors->fetch_assoc()): ?>
                        <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                <?php endif; ?>
                <label>التاريخ:</label>
                <input type="date" name="date" id="date" class="form-control" value="<?= date('Y-m-d') ?>">
                <label>الوقت:</label>
                <select name="appointment_time" id="appointment_time" class="form-control"></select>
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-calendar-plus"></i> حجز الموعد</button>
            </form>
        </div>
    </div>
</div>
<script>
// عند تغيير الطبيب أو التاريخ، جلب الأوقات المتاحة
function updateTimes() {
        var doctor_id_elem = document.getElementById('doctor_id');
        var doctor_id = '';
        if (doctor_id_elem) {
                if (doctor_id_elem.tagName === 'SELECT') {
                        doctor_id = doctor_id_elem.value;
                } else if (doctor_id_elem.tagName === 'INPUT') {
                        doctor_id = doctor_id_elem.value;
                }
        } else {
                doctor_id = '<?= $selected_doctor_id ?>';
        }
        var date = document.getElementById('date').value;
        fetch('./get_available_times.php?doctor_id='+doctor_id+'&date='+date)
            .then(r=>r.json()).then(data=>{
                var sel = document.getElementById('appointment_time');
                sel.innerHTML = '';
                // دائماً استخدم data.available للأوقات
                (data.available || []).forEach(function(t){
                    var opt = document.createElement('option');
                    opt.value = t; opt.text = t;
                    if (data.booked && data.booked.includes(t)) { opt.disabled = true; opt.text += ' (محجوز)'; }
                    sel.appendChild(opt);
                });
            });
}
if (document.getElementById('doctor_id') && document.getElementById('doctor_id').tagName === 'SELECT')
        document.getElementById('doctor_id').onchange = updateTimes;
document.getElementById('date').onchange = updateTimes;
window.onload = updateTimes;
</script>
</body>
</html>
