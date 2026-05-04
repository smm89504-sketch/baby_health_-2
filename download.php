<?php
/**
 * ملف تحميل آمن للملفات المرفوعة
 * منع تحميل الملفات الخطرة وتعيين MIME types صحيحة
 */

// الحصول على معرف الملف من الرابط
$file = basename($_GET['file'] ?? '');

if (empty($file)) {
    http_response_code(400);
    die('Bad Request');
}

// List of allowed folders
$allowed_dirs = ['appointment_reports', 'vaccine_certs', 'documents', 'children_images'];

// Extracting the folder name from the file ID
$dir = basename($_GET['dir'] ?? 'documents');

if (!in_array($dir, $allowed_dirs)) {
    http_response_code(403);
    die('Forbidden');
}

// File path
$filepath = realpath(__DIR__ . '/uploads/' . $dir . '/' . $file);
$basedir = realpath(__DIR__ . '/uploads/' . $dir);

// Check that the file is located within the allowed folder.
if ($filepath === false || strpos($filepath, $basedir) !== 0 || !file_exists($filepath)) {
    http_response_code(404);
    die('File not found');
}

// قائمة MIME types الآمنة
$mime_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt' => 'text/plain',
    'csv' => 'text/csv',
];

$file_extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

// تعيين رؤوس HTTP
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=86400');

// Download file
readfile($filepath);
exit();
?>
