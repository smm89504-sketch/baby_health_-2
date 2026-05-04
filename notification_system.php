<?php
require_once 'includes/db_config.php';

class NotificationManager {
    private $conn;

    public function __construct() {
        $db = new DatabaseHelper();
        $this->conn = $db->getConnection();
    }

    // Notice of due vaccinations
    public function checkDueVaccinations() {
        $query = "SELECT cv.id, cv.child_id, cv.vaccine_id, cv.due_date, c.name as child_name, c.user_id as parent_id, v.name as vaccine_name
                  FROM child_vaccines cv
                  JOIN children c ON cv.child_id = c.id
                  JOIN vaccines v ON cv.vaccine_id = v.id
                  WHERE cv.status = 'due' AND cv.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.user_id = c.user_id
                      AND n.title LIKE CONCAT('%', v.name, '%')
                      AND DATE(n.created_at) = CURDATE()
                  )";

        $result = $this->conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $days_until_due = floor((strtotime($row['due_date']) - time()) / (60 * 60 * 24));

            if ($days_until_due <= 0) {
                $title = "تطعيم متأخر: " . $row['vaccine_name'];
                $message = "تطعيم " . $row['vaccine_name'] . " للطفل " . $row['child_name'] . " متأخر عن موعده المحدد";
                $type = 'error';
            } elseif ($days_until_due <= 3) {
                $title = "تطعيم مستحق قريباً: " . $row['vaccine_name'];
                $message = "تطعيم " . $row['vaccine_name'] . " للطفل " . $row['child_name'] . " مستحق خلال " . $days_until_due . " أيام";
                $type = 'warning';
            } else {
                $title = "تذكير بتطعيم: " . $row['vaccine_name'];
                $message = "تطعيم " . $row['vaccine_name'] . " للطفل " . $row['child_name'] . " مستحق خلال أسبوع";
                $type = 'info';
            }

            $this->createNotification($row['parent_id'], $title, $message, $type);
        }
    }

    // Medication appointment notification
    public function checkMedicationReminders() {
        $query = "SELECT pm.*, p.prescription_date, c.name as child_name, c.user_id as parent_id, m.name as medication_name
                  FROM prescription_medications pm
                  JOIN prescriptions p ON pm.prescription_id = p.id
                  JOIN children c ON p.child_id = c.id
                  JOIN medications m ON pm.medication_id = m.id
                  WHERE p.status = 'active'
                  AND pm.start_date <= CURDATE()
                  AND NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.user_id = c.user_id
                      AND n.title LIKE CONCAT('%دواء%', m.name, '%')
                      AND DATE(n.created_at) = CURDATE()
                  )";

        $result = $this->conn->query($query);

        while ($row = $result->fetch_assoc()) {
            // Analysis of repetition from the text
            $frequency_hours = $this->parseFrequencyToHours($row['frequency']);
            if ($frequency_hours > 0) {
                $start_time = strtotime($row['start_date'] . ' 00:00:00');
                $now = time();
                $next_dose = $start_time;

                // Calculating the next dose الجرعة
                while ($next_dose <= $now) {
                    $next_dose += ($frequency_hours * 3600);
                }

                $hours_until_dose = floor(($next_dose - $now) / 3600);

                if ($hours_until_dose <= 2 && $hours_until_dose > 0) {
                    $title = "تذكير بجرعة دواء: " . $row['medication_name'];
                    $message = "حان وقت إعطاء جرعة " . $row['dosage'] . " من دواء " . $row['medication_name'] . " للطفل " . $row['child_name'];
                    $this->createNotification($row['parent_id'], $title, $message, 'warning');
                }
            }
        }
    }

    // A help function for frequency analysis
    private function parseFrequencyToHours($frequency) {
        $frequency = strtolower($frequency);

        if (strpos($frequency, 'كل 6 ساعات') !== false) {
            return 6;
        } elseif (strpos($frequency, 'كل 8 ساعات') !== false) {
            return 8;
        } elseif (strpos($frequency, 'كل 12 ساعات') !== false) {
            return 12;
        } elseif (strpos($frequency, 'مرة يومياً') !== false || strpos($frequency, 'مرة يوميا') !== false) {
            return 24;
        } elseif (strpos($frequency, 'مرتين يومياً') !== false) {
            return 12;
        } elseif (strpos($frequency, 'ثلاث مرات يومياً') !== false) {
            return 8;
        }

        return 0; // غير معروف
    }

    // Notification of new growth stages
    public function checkGrowthMilestones() {
        $query = "SELECT c.id, c.name, c.birth_date, c.user_id,
                         TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) as age_months
                  FROM children c
                  WHERE NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.user_id = c.user_id
                      AND n.title LIKE '%مرحلة نمو%'
                      AND MONTH(n.created_at) = MONTH(CURDATE())
                      AND YEAR(n.created_at) = YEAR(CURDATE())
                  )";

        $result = $this->conn->query($query);

        $milestones = [
            1 => "مرحلة نمو مهمة: الشهر الأول - الرضاعة والنظر",
            2 => "مرحلة نمو مهمة: الشهر الثاني - الابتسام والضحك",
            3 => "مرحلة نمو مهمة: الشهر الثالث - التقلب والجلوس",
            6 => "مرحلة نمو مهمة: الشهر السادس - الزحف وتناول الطعام",
            9 => "مرحلة نمو مهمة: الشهر التاسع - الوقوف والمشي",
            12 => "مرحلة نمو مهمة: السنة الأولى - الكلام واللعب",
            18 => "مرحلة نمو مهمة: 18 شهر - الاستقلالية والكلمات",
            24 => "مرحلة نمو مهمة: السنتان - المهارات الاجتماعية"
        ];

        while ($row = $result->fetch_assoc()) {
            $age_months = $row['age_months'];

            foreach ($milestones as $month => $message) {
                if ($age_months == $month) {
                    $title = "مرحلة نمو جديدة للطفل " . $row['name'];
                    $this->createNotification($row['user_id'], $title, $message, 'success');
                    break;
                }
            }
        }
    }

    // Child's Age Group Nutrition System Update Notice
    public function checkNutritionGuidelines() {
        $query = "SELECT c.id, c.name, c.birth_date, c.user_id, c.nutrition_guideline_id,
                         TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) as age_months
                  FROM children c";

        $result = $this->conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $age_months = (int)$row['age_months'];

            $guidelineQuery = "SELECT id, age_min_months, age_max_months FROM nutrition_guidelines WHERE age_min_months <= ? AND age_max_months >= ? LIMIT 1";
            $stmt = $this->conn->prepare($guidelineQuery);
            $stmt->bind_param("ii", $age_months, $age_months);
            $stmt->execute();
            $nutrition_result = $stmt->get_result();
            $nutrition = $nutrition_result->fetch_assoc();

            if ($nutrition && $row['nutrition_guideline_id'] != $nutrition['id']) {
                $title = "تحديث خطة التغذية للطفل " . $row['name'];
                $message = "الطفل الآن بعمر " . $age_months . " شهور. يرجى اتباع نظام التغذية الجديد " . $nutrition['age_min_months'] . " - " . $nutrition['age_max_months'] . " شهور.";
                $this->createNotification($row['user_id'], $title, $message, 'info');

                $updateStmt = $this->conn->prepare("UPDATE children SET nutrition_guideline_id = ? WHERE id = ?");
                $updateStmt->bind_param("ii", $nutrition['id'], $row['id']);
                $updateStmt->execute();
            }
        }
    }

    // Notice of regular breastfeeding schedules
    public function checkFeedingReminders() {
        // افتراض أن هناك جدول daily_activities يحتوي على مواعيد الرضاعة
        $query = "SELECT c.name as child_name, c.user_id,
                         TIMESTAMPDIFF(HOUR, da.created_at, NOW()) as hours_since_last_feed
                  FROM children c
                  LEFT JOIN daily_activities da ON c.id = da.child_id
                      AND da.activity_type = 'feeding'
                      AND DATE(da.created_at) = CURDATE()
                  WHERE c.birth_date <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY c.id, c.name, c.user_id
                  HAVING hours_since_last_feed >= 3 OR hours_since_last_feed IS NULL";

        $result = $this->conn->query($query);

        while ($row = $result->fetch_assoc()) {
            if ($row['hours_since_last_feed'] >= 4) {
                $title = "تذكير بموعد الرضاعة";
                $message = "مضى " . $row['hours_since_last_feed'] . " ساعات منذ آخر رضاعة للطفل " . $row['child_name'];
                $this->createNotification($row['user_id'], $title, $message, 'info');
            } elseif ($row['hours_since_last_feed'] === null) {
                $title = "تذكير بموعد الرضاعة اليومي";
                $message = "لم يتم تسجيل رضاعة اليوم للطفل " . $row['child_name'];
                $this->createNotification($row['user_id'], $title, $message, 'warning');
            }
        }
    }

    // Medical appointment notification 
    public function checkAppointmentReminders() {
        // افتراض أن هناك جدول appointments
        $query = "SELECT a.*, c.name as child_name, c.user_id as parent_id, u.full_name as doctor_name
                  FROM appointments a
                  JOIN children c ON a.child_id = c.id
                  JOIN users u ON a.doctor_id = u.id
                  WHERE a.appointment_date >= CURDATE()
                  AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                  AND a.status = 'scheduled'
                  AND NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.user_id = c.user_id
                      AND n.title LIKE '%موعد%'
                      AND DATE(n.created_at) = CURDATE()
                  )";

        $result = $this->conn->query($query);

        while ($row = $result->fetch_assoc()) {
            $hours_until_appointment = floor((strtotime($row['appointment_date']) - time()) / (60 * 60));

            if ($hours_until_appointment <= 24 && $hours_until_appointment > 0) {
                $title = "تذكير بموعد طبي";
                $message = "موعد مع د. " . $row['doctor_name'] . " للطفل " . $row['child_name'] . " خلال " . $hours_until_appointment . " ساعة";
                $this->createNotification($row['parent_id'], $title, $message, 'info');
            }
        }
    }

    // Create a new notification
    private function createNotification($user_id, $title, $message, $type = 'info') {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
        $stmt->execute();
    }

    // تشغيل جميع فحوصات الإشعارات
    public function runAllChecks() {
        $this->checkDueVaccinations();
        $this->checkMedicationReminders();
        $this->checkGrowthMilestones();
        $this->checkNutritionGuidelines();
        $this->checkFeedingReminders();
        $this->checkAppointmentReminders();
    }
}

// تشغيل الإشعارات إذا تم استدعاء الملف مباشرة
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $notifier = new NotificationManager();
    $notifier->runAllChecks();
    echo "تم تشغيل نظام الإشعارات التلقائي\n";
}
?>