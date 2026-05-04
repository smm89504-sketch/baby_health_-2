<?php
require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();
$q = "SELECT * FROM educational_videos ORDER BY created_at DESC";
$result = $conn->query($q);
if (!$result) {
    die('<div style="margin:40px; text-align:center; color:red;">خطأ في قاعدة البيانات: ' . htmlspecialchars($conn->error) . '</div>');
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفيديوهات التعليمية</title>
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
                <a href="guest_articles.php" class="nav-link">المقالات</a>
                <a href="guest_videos.php" class="nav-link active">الفيديوهات</a>
                <a href="guest_landing.php" class="nav-link">تسجيل الدخول</a>
            </div>
        </div>
    </nav>

    <main class="main-content" style="padding: 40px 20px;">
        <div class="container">
            <h2 style="text-align:center; margin-bottom: 20px;">الفيديوهات التعليمية</h2>
            <?php if (!$result || $result->num_rows === 0): ?>
                <p style="text-align:center; color:#555;">لا توجد فيديوهات لعرضها حالياً.</p>
            <?php else: ?>
                <div class="videos-grid" style="display:grid; gap:20px; grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                    <?php while($video = $result->fetch_assoc()): ?>
                        <article class="video-card">
                            <div class="card-icon">🎥</div>
                            <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($video['description'],0,180)); ?>...</p>
                            <div class="video-meta">
                                <span class="badge"><?php echo htmlspecialchars($video['age_group']); ?></span>
                                <span class="badge" style="background:#4caf50;"><?php echo htmlspecialchars($video['category']); ?></span>
                            </div>
                            <small style="color: #777; display:block; margin-bottom:10px;"><?php echo htmlspecialchars($video['author']); ?> | <?php echo date('Y-m-d',strtotime($video['created_at'])); ?></small>
                            <a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" class="watch-btn">شاهد الفيديو</a>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
