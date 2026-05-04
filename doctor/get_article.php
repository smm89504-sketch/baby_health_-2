<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'غير مصرح']);
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'معرف المقالة غير صحيح']);
    exit();
}

$query = "SELECT id, title, content, author, created_at FROM medical_articles WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'خطأ في الاستعلام: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $article_id);
if (!$stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'خطأ في التنفيذ: ' . $stmt->error]);
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'المقالة غير موجودة']);
    exit();
}

$article = $result->fetch_assoc();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'id' => $article['id'],
    'title' => $article['title'],
    'content' => nl2br(htmlspecialchars($article['content'])),
    'author' => $article['author'],
    'created_at' => date('Y-m-d', strtotime($article['created_at']))
], JSON_UNESCAPED_UNICODE);
