<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'parent') {
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
    // عكس الأرشفة (تحديث حقل is_archived = 0)
    $stmt = $pdo->prepare('UPDATE children SET is_archived = 0 WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    header('Location: archived_children.php?unarchived=1'); 
    exit;
} catch (PDOException $e) {
    header('Location: archived_children.php?error=unarchive_failed'); 
    exit;
}