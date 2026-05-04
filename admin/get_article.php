<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'غير مصرح']);
    exit;
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'معرف المقالة غير صحيح']);
    exit;
}

$stmt = $conn->prepare('SELECT id, title, content, author, category FROM medical_articles WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'المقالة غير موجودة']);
    exit;
}

$article = $result->fetch_assoc();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($article, JSON_UNESCAPED_UNICODE);
