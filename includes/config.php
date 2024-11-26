<?php
function loadEnv() {
    // Debug: Log the attempt to load environment
    error_log("Attempting to load OpenAI API key...");
    
    // Try to get from environment (Heroku)
    $openai_key = getenv('OPENAI_API_KEY');
    error_log("Key from getenv(): " . ($openai_key ? "Found" : "Not found"));
    
    if ($openai_key) {
        define('OPENAI_API_KEY', trim($openai_key));
        error_log("OpenAI API key successfully configured from environment");
        return;
    }
    
    // If not in environment, try .env file (local development)
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'OPENAI_API_KEY=') === 0) {
                $openai_key = substr($line, strlen('OPENAI_API_KEY='));
                define('OPENAI_API_KEY', trim($openai_key));
                error_log("OpenAI API key successfully configured from .env file");
                return;
            }
        }
    }
    
    // If we get here, no key was found
    error_log("WARNING: No OpenAI API key found in environment or .env file");
    define('OPENAI_API_KEY', '');
}

// Load configuration
loadEnv();

// Additional debug info
if (defined('OPENAI_API_KEY')) {
    error_log("OPENAI_API_KEY is defined: " . (empty(OPENAI_API_KEY) ? "but empty" : "and has value"));
} else {
    error_log("OPENAI_API_KEY is not defined");
}
