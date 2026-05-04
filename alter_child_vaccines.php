<?php
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();
$conn->query('ALTER TABLE child_vaccines ADD COLUMN vaccine_schedule_id INT NULL');
$conn->query('ALTER TABLE child_vaccines ADD FOREIGN KEY (vaccine_schedule_id) REFERENCES vaccine_schedule(id)');
echo 'تم إضافة عمود vaccine_schedule_id';
?>