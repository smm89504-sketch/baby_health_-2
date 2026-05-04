<?php
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();
$conn->query('CREATE TABLE IF NOT EXISTS vaccine_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vaccine_name VARCHAR(255) NOT NULL,
    age_months INT NOT NULL,
    description TEXT
)');
echo 'تم إنشاء جدول vaccine_schedule';

// Inclusion of some essential vaccines لقاحات اساسية
$conn->query("INSERT IGNORE INTO vaccine_schedule (vaccine_name, age_months, description) VALUES
('BCG', 0, 'لقاح السل'),
('Hepatitis B', 0, 'لقاح التهاب الكبد B'),
('DTP', 2, 'لقاح الخناق والسعال الديكي والكزاز'),
('Polio', 2, 'لقاح شلل الأطفال'),
('Hib', 2, 'لقاح الإنفلونزا النزفية'),
('PCV', 2, 'لقاح المكورات السحائية'),
('Rotavirus', 2, 'لقاح الروتا فيروس'),
('MMR', 12, 'لقاح الحصبة والنكاف والحصبة الألمانية'),
('DTP Booster', 18, 'تعزيز لقاح الخناق والسعال الديكي والكزاز'),
('Hepatitis A', 12, 'لقاح التهاب الكبد A'),
('Varicella', 12, 'لقاح الجدري المائي'),
('HPV', 120, 'لقاح فيروس الورم الحليمي البشري')");

echo 'تم إدراج اللقاحات الأساسية';
?>