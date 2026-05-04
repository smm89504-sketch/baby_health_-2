<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$authenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$session_status = session_status();
$session_id = session_id();

if (!$authenticated) {
    echo json_encode([
        'authenticated' => false,
        'session_status' => $session_status,
        'session_id' => $session_id,
        'debug' => 'Session not set'
    ]);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'user_id' => $_SESSION['user_id'],
    'user_type' => $_SESSION['user_type'] ?? null,
    'full_name' => $_SESSION['full_name'] ?? null,
    'session_status' => $session_status,
    'session_id' => $session_id,
]);
