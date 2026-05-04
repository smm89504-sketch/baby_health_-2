<?php
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Create medical_articles table
$conn->query("
CREATE TABLE IF NOT EXISTS `medical_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Create nutrition_guidelines table
$conn->query("
CREATE TABLE IF NOT EXISTS `nutrition_guidelines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `age_min_months` int(11) NOT NULL,
  `age_max_months` int(11) NOT NULL,
  `allowed_foods` text NOT NULL,
  `restricted_foods` text NOT NULL,
  `nutrition_tips` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Ensure children table includes twin_group and nutrition_guideline_id
if ($conn->query("SHOW COLUMNS FROM children LIKE 'twin_group'")->num_rows === 0) {
    $conn->query("ALTER TABLE children ADD COLUMN twin_group VARCHAR(100) NULL");
}

if ($conn->query("SHOW COLUMNS FROM children LIKE 'nutrition_guideline_id'")->num_rows === 0) {
    $conn->query("ALTER TABLE children ADD COLUMN nutrition_guideline_id INT(11) NULL");
}

// Create doctor_settings table
$conn->query("
CREATE TABLE IF NOT EXISTS `doctor_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `notifications` tinyint(1) DEFAULT 1,
  `email_alerts` tinyint(1) DEFAULT 1,
  `sms_alerts` tinyint(1) DEFAULT 0,
  `language` varchar(10) DEFAULT 'ar',
  `night_mode` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Create educational_videos table
$conn->query("
CREATE TABLE IF NOT EXISTS `educational_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `age_group` varchar(50) DEFAULT NULL,
  `author` varchar(100) NOT NULL,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Insert sample data for nutrition_guidelines
$conn->query("
INSERT IGNORE INTO `nutrition_guidelines` (`id`, `age_min_months`, `age_max_months`, `allowed_foods`, `restricted_foods`, `nutrition_tips`, `updated_at`) VALUES
(1, 0, 6, 'حليب الأم، حليب الأطفال المعقم، الماء المغلي', 'عسل، حليب البقر، عصائر، أطعمة صلبة', 'يجب إعطاء الطفل حليب الأم أو الحليب المعقم فقط في الأشهر الأولى', '2024-01-01 00:00:00'),
(2, 6, 12, 'حليب الأم، حليب الأطفال، خضروات مهروسة، فواكه مهروسة، حبوب الأرز المطبوخة', 'عسل، ملح، سكر، حليب البقر كامل الدسم', 'ابدأ بإدخال الأطعمة تدريجياً واحداً تلو الآخر', '2024-01-01 00:00:00'),
(3, 12, 24, 'حليب الأطفال أو حليب البقر منخفض الدسم، فواكه، خضروات، حبوب، لحوم، أسماك', 'سكريات، أطعمة معلبة، أطعمة سريعة التحضير', 'شجع على تناول الطعام باليد وتناول الوجبات مع العائلة', '2024-01-01 00:00:00');
");

// Insert sample data for medical_articles
$conn->query("
INSERT IGNORE INTO `medical_articles` (`id`, `title`, `content`, `author`, `category`, `created_at`) VALUES
(1, 'أهمية التطعيمات في السنوات الأولى', 'التطعيمات تحمي الأطفال من الأمراض الخطيرة...', 'د. أحمد محمد', 'تطعيمات', '2024-01-01 00:00:00'),
(2, 'تغذية الطفل في الشهر الأول', 'يحتاج الطفل حديث الولادة إلى حليب الأم فقط...', 'د. فاطمة علي', 'تغذية', '2024-01-01 00:00:00');
");

// Insert sample data for educational_videos
$conn->query("
INSERT IGNORE INTO `educational_videos` (`id`, `title`, `description`, `video_url`, `category`, `age_group`, `author`, `views_count`, `created_at`) VALUES
(1, 'كيفية إرضاع الطفل', 'دليل شامل لإرضاع الطفل بشكل صحيح', 'https://www.youtube.com/embed/example1', 'تغذية', '0-6 أشهر', 'د. سارة أحمد', 150, '2024-01-01 00:00:00'),
(2, 'علامات صحة الطفل', 'كيفية التعرف على علامات الصحة الجيدة لدى الطفل', 'https://www.youtube.com/embed/example2', 'صحة عامة', '0-12 شهر', 'د. محمد علي', 200, '2024-01-01 00:00:00');
");

echo "Tables created and sample data inserted successfully!";
?>