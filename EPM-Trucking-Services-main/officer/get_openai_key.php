<?php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

// Remove session check temporarily
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}
*/

// Debug output
error_log('OPENAI_API_KEY constant: ' . (defined('OPENAI_API_KEY') ? 'defined' : 'not defined'));
error_log('OPENAI_API_KEY value: ' . (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : 'none'));

if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) {
    echo json_encode(['key' => OPENAI_API_KEY]);
} else {
    // Try to read directly from .env for debugging
    $envFile = dirname(__DIR__) . '/.env';
    error_log('Trying to read from: ' . $envFile);
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        error_log('Env file contents: ' . $envContent);
    }
    
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured']);
} 