<?php
/**
 * Guest AI Handler - AJAX Endpoint
 * Handles guest requests for AI analysis
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'includes/db_config.php';

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit(json_encode(['success' => true]));
    }
    
    if ($method !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    // Get action from JSON or query/form data
    $action = $input['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Missing action parameter');
    }
    
    $db = new DatabaseHelper();
    $guestHelper = new GuestDataHelper($db);
    
    switch ($action) {
        case 'growth_prediction':
            $response = handleGrowthPrediction($input, $guestHelper);
            break;
            
        case 'cry_analysis':
            $response = handleCryAnalysis($input, $guestHelper);
            break;
            
        case 'symptom_guidance':
            $response = handleSymptomGuidance($input, $guestHelper);
            break;
            
        case 'get_statistics':
            $response = handleGetStatistics($input, $guestHelper);
            break;
            
        case 'get_common_symptoms':
            $response = handleGetCommonSymptoms($input, $guestHelper);
            break;
            
        case 'get_growth_benchmarks':
            $response = handleGetGrowthBenchmarks($input, $guestHelper);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
    $db->close();
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit;

// ==================== HANDLERS ====================

function handleGrowthPrediction($input, $guestHelper) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    // Validate required fields
    $required = ['age_months', 'weight', 'height', 'guest_name', 'guest_email'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $ageMonths = intval($input['age_months']);
    $weight = floatval($input['weight']);
    $height = floatval($input['height']);
    $gender = $input['gender'] ?? 'male';
    $guestName = trim($input['guest_name']);
    $guestEmail = trim($input['guest_email']);
    
    // Validate ranges
    if ($ageMonths < 0 || $ageMonths > 24) {
        throw new Exception('Age must be between 0-24 months');
    }
    if ($weight <= 0 || $weight > 20) {
        throw new Exception('Weight must be between 1-20 kg');
    }
    if ($height <= 0 || $height > 100) {
        throw new Exception('Height must be between 40-100 cm');
    }
    
    // Call Python API
    $aiResult = PythonAIBridge::callGrowthPrediction($ageMonths, $weight, $height, $gender);
    
    if (!$aiResult['success']) {
        throw new Exception($aiResult['error'] ?? 'AI API Error');
    }
    
    // Save analysis to database
    $confidenceScore = $aiResult['data']['confidence_score'] ?? 0.9;
    $guestHelper->saveAIAnalysis(
        $guestName,
        $guestEmail,
        'growth_prediction',
        [
            'age_months' => $ageMonths,
            'weight' => $weight,
            'height' => $height,
            'gender' => $gender
        ],
        $aiResult['data'],
        $confidenceScore
    );
    
    $response['success'] = true;
    $response['message'] = 'Growth prediction completed successfully';
    $response['data'] = $aiResult['data'];
    
    return $response;
}

function handleCryAnalysis($input, $guestHelper) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    // Validate required fields
    $required = ['duration_seconds', 'intensity_1_10', 'time_of_day', 'guest_name', 'guest_email'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $duration = intval($input['duration_seconds']);
    $intensity = intval($input['intensity_1_10']);
    $timeOfDay = trim($input['time_of_day']);
    $lastFedMinutes = isset($input['last_fed_minutes_ago']) ? intval($input['last_fed_minutes_ago']) : null;
    $ageMonths = isset($input['age_months']) ? intval($input['age_months']) : null;
    $guestName = trim($input['guest_name']);
    $guestEmail = trim($input['guest_email']);
    
    // Validate ranges
    if ($intensity < 0 || $intensity > 10) {
        throw new Exception('Intensity must be between 0-10');
    }
    if (!in_array($timeOfDay, ['morning', 'afternoon', 'evening', 'night'])) {
        throw new Exception('Invalid time_of_day');
    }
    
    // Call Python API
    $aiResult = PythonAIBridge::callCryAnalysis(
        $duration,
        $intensity,
        $timeOfDay,
        $lastFedMinutes,
        $ageMonths
    );
    
    if (!$aiResult['success']) {
        throw new Exception($aiResult['error'] ?? 'AI API Error');
    }
    
    // Save analysis to database
    $confidenceScore = $aiResult['data']['confidence'] ?? 0.85;
    $guestHelper->saveAIAnalysis(
        $guestName,
        $guestEmail,
        'cry_analysis',
        $input,
        $aiResult['data'],
        $confidenceScore
    );
    
    $response['success'] = true;
    $response['message'] = 'Cry analysis completed successfully';
    $response['data'] = $aiResult['data'];
    
    return $response;
}

function handleSymptomGuidance($input, $guestHelper) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    // Validate required fields
    $required = ['symptom', 'guest_name', 'guest_email'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $symptom = trim($input['symptom']);
    $temperature = isset($input['temperature']) ? floatval($input['temperature']) : 36.5;
    $durationHours = isset($input['duration_hours']) ? intval($input['duration_hours']) : 1;
    $ageMonths = isset($input['age_months']) ? intval($input['age_months']) : 6;
    $guestName = trim($input['guest_name']);
    $guestEmail = trim($input['guest_email']);
    
    // Call Python API
    $aiResult = PythonAIBridge::callSymptomGuidance($symptom, $temperature, $durationHours, $ageMonths);
    
    if (!$aiResult['success']) {
        throw new Exception($aiResult['error'] ?? 'AI API Error');
    }
    
    // Save analysis to database
    $guestHelper->saveAIAnalysis(
        $guestName,
        $guestEmail,
        'symptom_check',
        $input,
        $aiResult['data'],
        null
    );
    
    $response['success'] = true;
    $response['message'] = 'Symptom guidance retrieved successfully';
    // Include symptom name in response
    $response['data'] = array_merge($aiResult['data'], ['symptom' => $symptom]);
    
    return $response;
}

function handleGetStatistics($input, $guestHelper) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        $stats = [
            'growth'       => $guestHelper->getGrowthStatistics(),
            'vaccination'  => $guestHelper->getVaccinationStatistics(),
            // أضف هنا عدد الآباء
            'parents_count' => $guestHelper->getParentsCount(),
        ];
        
        $response['success'] = true;
        $response['message'] = 'Statistics retrieved successfully';
        $response['data'] = $stats;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    return $response;
}

function handleGetCommonSymptoms($input, $guestHelper) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        $symptoms = $guestHelper->getCommonSymptoms();
        
        $response['success'] = true;
        $response['message'] = 'Common symptoms retrieved successfully';
        $response['data'] = $symptoms;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    return $response;
}

function handleGetGrowthBenchmarks($input, $guestHelper) {
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        $ageMonths = isset($input['age_months']) ? intval($input['age_months']) : null;
        $benchmarks = $guestHelper->getGrowthBenchmarks($ageMonths);
        
        $response['success'] = true;
        $response['message'] = 'Growth benchmarks retrieved successfully';
        $response['data'] = $benchmarks;
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    return $response;
}
?>
