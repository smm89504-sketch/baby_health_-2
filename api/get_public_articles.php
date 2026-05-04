<?php
require_once 'includes/db_config.php';

header('Content-Type: application/json; charset=utf-8');

$db = new DatabaseHelper();
$conn = $db->getConnection();

$query = "SELECT id, title, content, author, category, created_at FROM medical_articles ORDER BY created_at DESC LIMIT 9";
$result = $conn->query($query);

$articles = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $articles[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'excerpt' => substr($row['content'], 0, 150) . '...',
            'author' => $row['author'],
            'category' => $row['category'],
            'date' => date('Y-m-d', strtotime($row['created_at']))
        ];
    }
}

echo json_encode($articles, JSON_UNESCAPED_UNICODE);
