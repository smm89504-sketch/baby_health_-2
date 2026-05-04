<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

header('Content-Type: application/json');

$host = 'localhost';
$db   = 'baby_tracker';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$child_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'parent';

if (!$child_id || !filter_var($child_id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid child ID']);
    exit;
}

$response_data = [
    'growth_records' => [],
    'sleep_records' => [],
    'error' => null
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 1. التحقق من صلاحية الوصول للطفل
    $auth_sql = 'SELECT id FROM children WHERE id = ? ' . ($user_type === 'parent' ? 'AND user_id = ?' : '');
    $stmt_auth = $pdo->prepare($auth_sql);
    
    if ($user_type === 'parent') {
        $stmt_auth->execute([$child_id, $user_id]);
    } else {
        $stmt_auth->execute([$child_id]); // الأطباء والممرضون يرون الكل
    }

    if (!$stmt_auth->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied or child not found']);
        exit;
    }

    // 2. جلب سجلات النمو (الوزن والطول والحرارة)
    $stmt_growth = $pdo->prepare("SELECT date, weight, height, temperature FROM daily_activities WHERE child_id = ? AND activity_type = 'growth_record' ORDER BY date ASC");
    $stmt_growth->execute([$child_id]);
    $response_data['growth_records'] = $stmt_growth->fetchAll();
    
    // 3. جلب سجلات النوم (المدة وعدد مرات الاستيقاظ)
    $stmt_sleep = $pdo->prepare("SELECT date, duration, quantity as wake_ups, activity_type FROM daily_activities WHERE child_id = ? AND (activity_type = 'nap' OR activity_type = 'night_sleep') ORDER BY date ASC");
    $stmt_sleep->execute([$child_id]);
    $response_data['sleep_records'] = $stmt_sleep->fetchAll();

} catch (PDOException $e) {
    http_response_code(500);
    $response_data['error'] = 'Database connection error: ' . $e->getMessage();
}

echo json_encode($response_data);
?>