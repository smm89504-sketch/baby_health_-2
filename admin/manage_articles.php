<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Article deletion handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    $article_id = (int)$_POST['article_id'];
    $stmt = $conn->prepare("DELETE FROM medical_articles WHERE id = ?");
    $stmt->bind_param("i", $article_id);
    if ($stmt->execute()) {
        $success = "تم حذف المقالة بنجاح";
    } else {
        $error = "خطأ في حذف المقالة";
    }
}

// Processing the addition of a new article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_article'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author'] ?: 'إدارة النظام';
    $category = $_POST['category'];
    
    $stmt = $conn->prepare("INSERT INTO medical_articles (title, content, author, category, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $title, $content, $author, $category);
    if ($stmt->execute()) {
        $success = "تمت إضافة المقالة بنجاح";
    } else {
        $error = "خطأ في إضافة المقالة";
    }
}

// Article update processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_article'])) {
    $article_id = (int)$_POST['article_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author'] ?: 'إدارة النظام';
    $category = $_POST['category'];

    $stmt = $conn->prepare("UPDATE medical_articles SET title = ?, content = ?, author = ?, category = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $content, $author, $category, $article_id);

    if ($stmt->execute()) {
        $success = "تم تحديث المقالة بنجاح";
    } else {
        $error = "خطأ في تحديث المقالة";
    }
}

// Bring all articles
$query = "SELECT * FROM medical_articles ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المقالات - الإدارة</title>
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

    <!--sidebar-->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>📚 إدارة المقالات الطبية</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addArticleModal">
                <i class="fas fa-plus"></i> إضافة مقالة جديدة
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

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العنوان</th>
                                <th>الكاتب</th>
                                <th>الفئة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">لا توجد مقالات حالياً</td>
                            </tr>
                        <?php else: ?>
                            <?php $count = 1; while ($article = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars(substr($article['title'], 0, 50)); ?></td>
                                    <td><?php echo htmlspecialchars($article['author']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($article['category']); ?></span></td>
                                    <td><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="editArticle(<?php echo $article['id']; ?>)">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('هل تأكد من الحذف؟');">
                                            <input type="hidden" name="delete_article" value="1">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Add article-->
    <div class="modal fade" id="addArticleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مقالة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان المقالة</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="author" class="form-label">الكاتب (اختياري)</label>
                            <input type="text" class="form-control" id="author" name="author" placeholder="اترك فارغاً للتطبيق التلقائي">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">الفئة</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">اختر الفئة</option>
                                <option value="عام">عام</option>
                                <option value="0-6">0-6 أشهر</option>
                                <option value="6-12">6-12 شهر</option>
                                <option value="1-3">1-3 سنوات</option>
                                <option value="3+">3 سنوات فأكثر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">محتوى المقالة</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required placeholder="أكتب محتوى المقالة"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_article" class="btn btn-primary">إضافة المقالة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit article-->
    <div class="modal fade" id="editArticleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل المقالة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="article_id" id="edit_article_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">عنوان المقالة</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_author" class="form-label">الكاتب</label>
                            <input type="text" class="form-control" id="edit_author" name="author" placeholder="اترك فارغاً للتطبيق التلقائي">
                        </div>
                        <div class="mb-3">
                            <label for="edit_category" class="form-label">الفئة</label>
                            <select class="form-select" id="edit_category" name="category" required>
                                <option value="">اختر الفئة</option>
                                <option value="عام">عام</option>
                                <option value="0-6">0-6 أشهر</option>
                                <option value="6-12">6-12 شهر</option>
                                <option value="1-3">1-3 سنوات</option>
                                <option value="3+">3 سنوات فأكثر</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">محتوى المقالة</label>
                            <textarea class="form-control" id="edit_content" name="content" rows="10" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_article" class="btn btn-warning">حفظ التحديث</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editArticle(articleId) {
            fetch('get_article.php?id=' + articleId)
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('edit_article_id').value = data.id;
                    document.getElementById('edit_title').value = data.title;
                    document.getElementById('edit_author').value = data.author;
                    document.getElementById('edit_category').value = data.category;
                    document.getElementById('edit_content').value = data.content;
                    new bootstrap.Modal(document.getElementById('editArticleModal')).show();
                })
                .catch(err => {
                    console.error('خطأ في جلب المقالة:', err);
                    alert('تعذر تحميل بيانات المقالة');
                });
        }
    </script>
</body>
</html>
