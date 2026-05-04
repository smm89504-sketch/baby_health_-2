<?php
require_once 'includes/db_config.php';

header('Content-Type: application/json; charset=utf-8');

$db = new DatabaseHelper();
$conn = $db->getConnection();

$query = "SELECT id, title, description, video_url, category, age_group, author, created_at FROM educational_videos ORDER BY created_at DESC LIMIT 9";
$result = $conn->query($query);

$videos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $videos[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => substr($row['description'], 0, 100) . '...',
            'video_url' => $row['video_url'],
            'category' => $row['category'],
            'age_group' => $row['age_group'],
            'author' => $row['author'],
            'date' => date('Y-m-d', strtotime($row['created_at']))
        ];
    }
}

echo json_encode($videos, JSON_UNESCAPED_UNICODE);
