<?php
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: guest_articles.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM medical_articles WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$article = $result->fetch_assoc();

if (!$article) {
    header('Location: guest_articles.php');
    exit;
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?></title>
    <link rel="stylesheet" href="guest_landing.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1>نظام رعاية الطفل</h1>
            </div>
            <div class="navbar-nav">
                <a href="index.php" class="nav-link">الرئيسية</a>
                <a href="guest_articles.php" class="nav-link active">المقالات</a>
                <a href="guest_videos.php" class="nav-link">الفيديوهات</a>
                <a href="guest_landing.php" class="nav-link">تسجيل الدخول</a>
            </div>
        </div>
    </nav>

    <main class="main-content" style="padding: 40px 20px;">
        <div class="container" style="max-width: 900px; margin: auto;">
            <h2><?php echo htmlspecialchars($article['title']); ?></h2>
            <p style="color: #777;">بواسطة <?php echo htmlspecialchars($article['author']); ?> | <?php echo date('Y-m-d', strtotime($article['created_at'])); ?></p>
            <div style="margin-top: 30px; color: #444; line-height: 1.8;">
                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
            </div>
            <a href="guest_articles.php" class="btn btn-outline" style="margin-top: 20px;">عودة للمقالات</a>
        </div>
    </main>
</body>
</html>
