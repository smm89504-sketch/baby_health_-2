<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Video deletion processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_video'])) {
    $video_id = (int)$_POST['video_id'];
    $stmt = $conn->prepare("DELETE FROM educational_videos WHERE id = ?");
    $stmt->bind_param("i", $video_id);
    if ($stmt->execute()) {
        $success = "تم حذف الفيديو بنجاح";
    } else {
        $error = "خطأ في حذف الفيديو";
    }
}

// Processing the addition of a new video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $video_url = $_POST['video_url'];
    $category = $_POST['category'];
    $age_group = $_POST['age_group'];
    $author = $_POST['author'] ?: 'إدارة النظام';
    
    $stmt = $conn->prepare("INSERT INTO educational_videos (title, description, video_url, category, age_group, author, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $title, $description, $video_url, $category, $age_group, $author);
    if ($stmt->execute()) {
        $success = "تم إضافة الفيديو بنجاح";
    } else {
        $error = "خطأ في إضافة الفيديو";
    }
}

// Bring all videos
$query = "SELECT * FROM educational_videos ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفيديوهات - الإدارة</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- top strip-->
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👨‍💼 الإدارة</h1>
                <span class="badge">v1.0</span>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['name'] ?? 'إدارة'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>🎥 إدارة الفيديوهات التعليمية</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                <i class="fas fa-plus"></i> إضافة فيديو جديد
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php if ($result->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-video fa-2x mb-3"></i>
                        <p>لا توجد فيديوهات حالياً</p>
                    </div>
                </div>
            <?php else: ?>
                <?php while ($video = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white">
                                <i class="fas fa-play-circle"></i> <?php echo htmlspecialchars(substr($video['title'], 0, 40)); ?>
                            </div>
                            <div class="card-body">
                                <p class="card-text">
                                    <small><?php echo htmlspecialchars(substr($video['description'], 0, 100)); ?>...</small>
                                </p>
                                <div class="mb-2">
                                    <span class="badge bg-info"><?php echo htmlspecialchars($video['age_group']); ?></span>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($video['category']); ?></span>
                                </div>
                                <small class="text-muted">
                                    👤 <?php echo htmlspecialchars($video['author']); ?><br>
                                    📅 <?php echo date('Y-m-d', strtotime($video['created_at'])); ?>
                                </small>
                            </div>
                            <div class="card-footer">
                                <a href="<?php echo htmlspecialchars($video['video_url']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-external-link"></i> عرض
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('هل تأكد من الحذف؟');">
                                    <input type="hidden" name="delete_video" value="1">
                                    <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Add video-->
    <div class="modal fade" id="addVideoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة فيديو تعليمي جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان الفيديو</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required placeholder="أكتب وصف الفيديو"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="video_url" class="form-label">رابط الفيديو (YouTube/Vimeo)</label>
                            <input type="url" class="form-control" id="video_url" name="video_url" required placeholder="https://youtube.com/...">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">الفئة</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">اختر الفئة</option>
                                        <option value="صحة">صحة</option>
                                        <option value="تغذية">تغذية</option>
                                        <option value="تنمية">تنمية</option>
                                        <option value="عام">عام</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="age_group" class="form-label">فئة العمر</label>
                                    <select class="form-select" id="age_group" name="age_group" required>
                                        <option value="">اختر الفئة</option>
                                        <option value="0-6">0-6 أشهر</option>
                                        <option value="6-12">6-12 شهر</option>
                                        <option value="1-3">1-3 سنوات</option>
                                        <option value="3+">3 سنوات فأكثر</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label">المؤلف (اختياري)</label>
                            <input type="text" class="form-control" id="author" name="author" placeholder="اترك فارغاً للتطبيق التلقائي">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_video" class="btn btn-primary">إضافة الفيديو</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
