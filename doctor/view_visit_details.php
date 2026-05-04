<?php
require_once '../includes/db_config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo 'غير مصرح';
    exit;
}

$db = new DatabaseHelper();
$conn = $db->getConnection();

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT mv.*, c.name as child_name
    FROM medical_visits mv
    JOIN children c ON mv.child_id = c.id
    WHERE mv.id = ? AND mv.doctor_id = ?
");

$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($visit = $result->fetch_assoc()):
?>

<h5>👶 الطفل: <?php echo htmlspecialchars($visit['child_name']); ?></h5>
<p><strong>📅 التاريخ:</strong> <?php echo $visit['visit_date']; ?></p>
<p><strong>🩺 التشخيص:</strong><br><?php echo nl2br(htmlspecialchars($visit['diagnosis'])); ?></p>
<p><strong>💊 الوصفة:</strong><br><?php echo nl2br(htmlspecialchars($visit['prescription'])); ?></p>
<p><strong>📝 ملاحظات:</strong><br><?php echo nl2br(htmlspecialchars($visit['notes'])); ?></p>

<?php else: ?>
<p class="text-danger">لا توجد بيانات</p>
<?php endif;
