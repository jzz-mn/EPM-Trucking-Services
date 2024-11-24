<?php
function loadEnv() {
    $envFile = dirname(__DIR__) . '/.env';
    error_log('Looking for .env file at: ' . $envFile);
    
    if (file_exists($envFile)) {
        error_log('.env file found');
        $content = file_get_contents($envFile);
        error_log('File contents: ' . substr($content, 0, 20) . '...'); // Log first 20 chars
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                
                if ($key === 'OPENAI_API_KEY') {
                    error_log("OpenAI key loaded, length: " . strlen($value));
                }
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
