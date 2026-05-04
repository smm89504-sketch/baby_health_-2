<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_config.php';
$db = new DatabaseHelper();
$conn = $db->getConnection();

$message = '';
$success = false;

// معالجة الرفع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    
    //List of allowed extensions and MIME types
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xlsx', 'xls', 'gif'];
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];
    
    $file = $_FILES['file'];
    
    // Error checking
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($file['name']);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_mime = $file['type'];
        $file_size = $file['size'];
        
        // Maximum 10 MB
        $max_size = 10 * 1024 * 1024;
        
        if ($file_size > $max_size) {
            $message = '❌ حجم الملف كبير جداً. الحد الأقصى: 10 MB';
        } elseif (!in_array($file_extension, $allowed_extensions)) {
            $message = '❌ صيغة الملف غير مسموحة!<br>الصيغ المسموحة: ' . implode(', ', array_map('strtoupper', $allowed_extensions));
        } else {
            // Create the folder if it doesn't exist
            $upload_dir = 'uploads/documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            //Secure unique file name
            $safe_name = preg_replace("/[^a-zA-Z0-9_.-]/", "_", pathinfo($file_name, PATHINFO_FILENAME));
            $new_file_name = time() . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_file_name;
            
            // محاولة الرفع
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $message = '✅ تم رفع الملف بنجاح!<br><strong>اسم الملف:</strong> ' . htmlspecialchars($file_name) . '<br><strong>الحجم:</strong> ' . number_format($file_size / 1024, 2) . ' KB<br><strong>المسار:</strong> ' . htmlspecialchars($target_path);
                $success = true;
            } else {
                $message = '❌ فشل رفع الملف. تأكد من صلاحيات المجلد uploads/documents/';
            }
        }
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف يتجاوز الحد المسموح به في php.ini',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف يتجاوز الحد المسموح به في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف بشكل جزئي',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل الكتابة على القرص',
            UPLOAD_ERR_EXTENSION => 'توسيع PHP أوقف رفع الملف',
        ];
        $message = '❌ خطأ: ' . ($error_messages[$file['error']] ?? 'خطأ غير معروف');
    }
}

// colors
$main_dark = '#ad1457';
$main_text = '#880e4f';
$main_light = '#ffd1dc';
$main_deep = '#f8bbd0';
$bg_light = '#fff0f5';
$title_icon = 'fas fa-cloud-upload-alt';
$user_type = $_SESSION['user_type'] ?? 'parent';
$dashboard_link = 'index.php';
$base_path = './';
$unread_messages = 0;
$vaccine_alerts = ['missed' => [], 'upcoming' => []];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع الملفات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-light: <?= $bg_light ?>;
            --primary-text: <?= $main_text ?>;
            --primary-dark: <?= $main_dark ?>;
        }
        body {
            background: linear-gradient(135deg, var(--primary-light) 0%, #ffeef3 100%);
            min-height: 100vh;
            color: #4A4A4A;
            font-family: 'Cairo', sans-serif;
            display: flex;
        }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-text) 100%);
            width: 250px;
            min-height: 100vh;
            padding: 20px;
            color: white;
            box-shadow: 0 8px 24px rgba(136, 14, 79, 0.12);
        }
        .main-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .dashboard-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .main-box {
            margin-top: 40px;
            box-shadow: 0 6px 24px rgba(100, 100, 100, 0.10);
            border-radius: 16px;
            background: #fff;
            padding: 30px;
        }
        .page-header {
            font-size: 1.7rem;
            color: #c62828;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        .page-header i {
            margin-left: 12px;
        }
        .upload-area {
            border: 3px dashed #ddd;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: var(--primary-text);
            background: #fff0f5;
        }
        .upload-area.drag-over {
            border-color: var(--primary-text);
            background: var(--primary-light);
            transform: scale(1.02);
        }
        .upload-area i {
            font-size: 3rem;
            color: var(--primary-text);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-container">
        <div class="dashboard-container">
            <div class="page-header">
                <i class="<?= $title_icon ?>"></i>
                رفع الملفات
            </div>
            
            <div class="main-box">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $success ? 'success' : 'danger' ?> mb-3" role="alert">
                        <?= $message ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" id="uploadArea">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <div style="font-size: 1.1rem; margin-bottom: 10px;">
                            <strong>اسحب الملف هنا</strong>
                        </div>
                        <div style="color: #999; margin-bottom: 15px;">
                            أو اضغط لاختيار ملف
                        </div>
                        <input type="file" id="fileInput" name="file" style="display: none;">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-folder2-open"></i> اختر ملف
                        </button>
                        <div style="margin-top: 15px; font-size: 0.9rem; color: #666;">
                            <strong>الصيغ المسموحة:</strong> JPG, PNG, PDF, DOC, DOCX, XLS, XLSX<br>
                            <strong>الحد الأقصى:</strong> 10 MB
                        </div>
                    </div>
                </form>
                
                <hr style="margin: 30px 0;">
                
                <h5 style="color: var(--primary-text);">معلومات النظام:</h5>
                <table class="table table-sm table-bordered">
                    <tr>
                        <td><strong>إصدار PHP</strong></td>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td><strong>الحد الأقصى لحجم الرفع</strong></td>
                        <td><?= ini_get('upload_max_filesize') ?></td>
                    </tr>
                    <tr>
                        <td><strong>أقصى حجم POST</strong></td>
                        <td><?= ini_get('post_max_size') ?></td>
                    </tr>
                    <tr>
                        <td><strong>مجلد الرفع</strong></td>
                        <td><code>uploads/documents/</code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const form = document.getElementById('uploadForm');
        
        // فتح اختيار الملف
        uploadArea.addEventListener('click', () => fileInput.click());
        
        // السحب والإفلات
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            fileInput.files = e.dataTransfer.files;
            form.submit();
        });
        
        // عند اختيار ملف
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                form.submit();
            }
        });
    </script>
</body>
</html>
