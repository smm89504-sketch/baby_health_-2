<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') { // فقط الأهل يمكنهم أرشفة أطفالهم
    header('Location: login.php');
    exit;
}
$host = 'localhost';
$db   = 'baby_tracker';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: children.php');
    exit;
}
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // تغيير الحذف إلى أرشفة (تحديث حقل is_archived = 1)
    $stmt = $pdo->prepare('UPDATE children SET is_archived = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    header('Location: children.php?archived=1'); // إضافة متغير نجاح
    exit;
} catch (PDOException $e) {
    header('Location: children.php?error=archive_failed'); 
    exit;
}