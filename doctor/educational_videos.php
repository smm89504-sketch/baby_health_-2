<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

// Bringing educational videos
$query = "SELECT * FROM educational_videos ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفيديوهات التعليمية - الطبيب</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;900&family=Tajawal:wght@300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="../admin/admin_shared.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
</head>
<body>
    <!-- top strip-->
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

    <!-- Main Content-->
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>الفيديوهات التعليمية</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVideoModal">
                <i class="fas fa-plus"></i> إضافة فيديو جديد
            </button>
        </div>

        <!-- Video filter-->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label for="categoryFilter" class="form-label">الفئة</label>
                        <select class="form-select" id="categoryFilter">
                            <option value="all">جميع الفئات</option>
                            <option value="newborn">المواليد الجدد</option>
                            <option value="infant">الرضع</option>
                            <option value="toddler">الأطفال الصغار</option>
                            <option value="nutrition">التغذية</option>
                            <option value="health">الصحة العامة</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="ageFilter" class="form-label">الفئة العمرية</label>
                        <select class="form-select" id="ageFilter">
                            <option value="all">جميع الأعمار</option>
                            <option value="0-6">0-6 أشهر</option>
                            <option value="6-12">6-12 شهر</option>
                            <option value="1-3">1-3 سنوات</option>
                            <option value="3+">أكثر من 3 سنوات</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-outline-primary w-100" onclick="filterVideos()">
                            <i class="fas fa-filter"></i> فلترة
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="videosContainer">
            <?php while ($video = $result->fetch_assoc()): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="video-thumbnail mb-3" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                                <?php if ($video['video_url']): ?>
                                    <iframe
                                        src="<?php echo htmlspecialchars($video['video_url']); ?>"
                                        frameborder="0"
                                        allowfullscreen
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                    </iframe>
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center text-white" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                        <i class="fas fa-play-circle fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h5 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($video['description'], 0, 100)) . '...'; ?></p>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($video['category']); ?></span>
                                    <span class="badge bg-info ms-1"><?php echo htmlspecialchars($video['age_group']); ?></span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewVideo(<?php echo $video['id']; ?>)">
                                        <i class="fas fa-play"></i> مشاهدة
                                    </button>
                                 
                                </div>
                            </div>

                            <div class="mt-2 text-muted small">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($video['author']); ?> |
                                <i class="fas fa-calendar me-1"></i><?php echo htmlspecialchars(date('Y-m-d', strtotime($video['created_at']))); ?> |
                                <i class="fas fa-eye me-1"></i><?php echo $video['views_count']; ?> مشاهدة
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="text-center mt-5">
                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">لا توجد فيديوهات تعليمية</h4>
                <p class="text-muted">ابدأ بإضافة فيديوهات تعليمية للآباء والأمهات</p>
            </div>
        <?php endif; ?>

        <!-- Recommended video categoriesالموصى بها-->
        <div class="card mt-5">
            <div class="card-header">
                <h5>الفيديوهات الأكثر مشاهدة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-baby fa-2x text-primary mb-2"></i>
                                <h6>عناية المولود الجديد</h6>
                                <p class="small text-muted">كيفية الاعتناء بالطفل في الأسابيع الأولى</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-utensils fa-2x text-success mb-2"></i>
                                <h6>التغذية السليمة</h6>
                                <p class="small text-muted">أساسيات تغذية الطفل السليمة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-2x text-warning mb-2"></i>
                                <h6>الوقاية من الأمراض</h6>
                                <p class="small text-muted">كيفية حماية طفلك من الأمراض الشائعة</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal To add a new video-->
    <div class="modal fade" id="addVideoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة فيديو تعليمي جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addVideoForm">
                        <div class="mb-3">
                            <label for="videoTitle" class="form-label">عنوان الفيديو</label>
                            <input type="text" class="form-control" id="videoTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="videoUrl" class="form-label">رابط الفيديو (YouTube)</label>
                            <input type="url" class="form-control" id="videoUrl" placeholder="https://www.youtube.com/watch?v=...">
                        </div>
                        <div class="mb-3">
                            <label for="videoDescription" class="form-label">وصف الفيديو</label>
                            <textarea class="form-control" id="videoDescription" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="videoCategory" class="form-label">الفئة</label>
                                <select class="form-select" id="videoCategory" required>
                                    <option value="">اختر الفئة</option>
                                    <option value="newborn">المواليد الجدد</option>
                                    <option value="infant">الرضع</option>
                                    <option value="toddler">الأطفال الصغار</option>
                                    <option value="nutrition">التغذية</option>
                                    <option value="health">الصحة العامة</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="videoAgeGroup" class="form-label">الفئة العمرية</label>
                                <select class="form-select" id="videoAgeGroup" required>
                                    <option value="">اختر الفئة العمرية</option>
                                    <option value="0-6">0-6 أشهر</option>
                                    <option value="6-12">6-12 شهر</option>
                                    <option value="1-3">1-3 سنوات</option>
                                    <option value="3+">أكثر من 3 سنوات</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="addVideo()">إضافة الفيديو</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewVideo(videoId) {
            // فتح الفيديو في نافذة منبثقة أو صفحة منفصلة
            window.open('view_video.php?id=' + videoId, '_blank', 'width=800,height=600');
        }

        function editVideo(videoId) {
            // Open the video editing window
            alert('سيتم فتح نافذة تعديل الفيديو');
        }

        function filterVideos() {
            const category = document.getElementById('categoryFilter').value;
            const ageGroup = document.getElementById('ageFilter').value;

            // Implementing the filtering 
            alert('سيتم فلترة الفيديوهات حسب: ' + category + ' - ' + ageGroup);
        }

        function addVideo() {
            // Implementing the video addition
            alert('سيتم تنفيذ إضافة الفيديو');
        }
    </script>
</body>
</html>