<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bring medical articles
$query = "SELECT * FROM medical_articles ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المقالات الطبية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
</head>
<body>
    <!-- الشريط العلوي -->
    <nav class="navbar">
        <div class="container" style="max-width: 100%; padding: 0 30px; display: flex; justify-content: space-between; align-items: center; height: 100%;">
            <div class="navbar-brand">
                <h1>👨‍⚕️ الطبيب</h1>
                <span class="badge">v1.0</span>
            </div>
            <div class="navbar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'D', 0, 1)); ?></div>
                <div>
                    <small style="color: #7a6880;">مرحباً د.</small>
                    <div style="color: #3d2c4d; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'طبيب'); ?></div>
                </div>
            </div>
        </div>
    </nav>

    <!-- sidebar-->
    <?php include 'sidebar.php'; ?>

    <!--Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>المقالات الطبية</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addArticleModal">
                <i class="fas fa-plus"></i> إضافة مقالة جديدة
            </button>
        </div>

        <div class="row">
            <?php while ($article = $result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($article['content'], 0, 150)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($article['author']); ?> -
                                    <?php echo htmlspecialchars(date('Y-m-d', strtotime($article['created_at']))); ?>
                                </small>
                                <button type="button" type="button" class="btn btn-sm btn-primary" onclick="viewArticle(<?php echo $article['id']; ?>)">
                                    اقرأ المزيد
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="text-center mt-5">
                <i class="fas fa-book-medical fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">لا توجد مقالات طبية</h4>
                <p class="text-muted">ابدأ بإضافة مقالات طبية للمكتبة</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal To add a new article-->
    <div class="modal fade" id="addArticleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مقالة طبية جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addArticleForm">
                        <div class="mb-3">
                            <label for="articleTitle" class="form-label">عنوان المقالة</label>
                            <input type="text" class="form-control" id="articleTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="articleContent" class="form-label">محتوى المقالة</label>
                            <textarea class="form-control" id="articleContent" rows="10" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="articleCategory" class="form-label">الفئة العمرية</label>
                            <select class="form-select" id="articleCategory">
                                <option value="general">عام</option>
                                <option value="0-6">0-6 أشهر</option>
                                <option value="6-12">6-12 شهر</option>
                                <option value="1-3">1-3 سنوات</option>
                                <option value="3+">أكثر من 3 سنوات</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="addArticle()">إضافة المقالة</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal View article-->
    <div class="modal fade" id="articleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="articleTitle">عنوان المقالة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="articleModalBody">
                    جاري التحميل...
                </div>
                <div class="modal-footer">
                    <small class="text-muted" id="articleMeta"></small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewArticle(articleId) {
            const modalElement = document.getElementById('articleModal');
            document.getElementById('articleModalBody').innerHTML = '<p>جاري التحميل...</p>';
            document.getElementById('articleTitle').textContent = 'جاري التحميل...';
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            
            fetch('get_article.php?id=' + articleId)
                .then(res => {
                    if (!res.ok) throw new Error('Network error');
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    document.getElementById('articleTitle').textContent = data.title;
                    document.getElementById('articleModalBody').innerHTML = data.content;
                    document.getElementById('articleMeta').textContent = `${data.author} - ${data.created_at}`;
                })
                .catch(err => {
                    console.error('خطأ:', err);
                    document.getElementById('articleModalBody').innerHTML = '<p class="text-danger">حدث خطأ في تحميل المقالة: ' + err.message + '</p>';
                    document.getElementById('articleTitle').textContent = 'خطأ';
                });
        }

        function addArticle() {
            // تنفيذ إضافة المقالة
            alert('سيتم تنفيذ إضافة المقالة');
        }
    </script>
</body>
</html>