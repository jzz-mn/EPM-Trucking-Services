<?php
session_start();
include '../includes/config.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if (defined('OPENAI_API_KEY') && OPENAI_API_KEY) {
    // Only return a success status, never expose the key
    echo json_encode(['status' => 'configured']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured']);
} 