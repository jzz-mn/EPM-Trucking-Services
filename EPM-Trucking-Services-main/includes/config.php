<?php
function loadEnv() {
    $envFile = dirname(__DIR__) . '/.env';
    error_log('Looking for .env file at: ' . $envFile);
    
    // If .env file exists, load it
    if (file_exists($envFile)) {
        error_log('.env file found');
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                
                error_log("Loaded env var: $key");
            }
        }
    } else {
        error_log('.env file not found');
    }
    
    // Try to get the API key
    $openai_key = getenv('OPENAI_API_KEY');
    error_log('OpenAI Key from getenv: ' . ($openai_key ? 'found' : 'not found'));
    
    if (!$openai_key) {
        $openai_key = $_ENV['OPENAI_API_KEY'] ?? null;
        error_log('OpenAI Key from $_ENV: ' . ($openai_key ? 'found' : 'not found'));
    }
    
    // Define constant
    if ($openai_key) {
        define('OPENAI_API_KEY', $openai_key);
        error_log('OPENAI_API_KEY constant defined');
    } else {
        error_log('OpenAI API key not found in any location');
        define('OPENAI_API_KEY', '');
    }
}

// Load configuration
loadEnv();

// Verify configuration loaded
error_log('Config loaded. OPENAI_API_KEY defined: ' . (defined('OPENAI_API_KEY') ? 'yes' : 'no'));
