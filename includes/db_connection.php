<?php
// Parse JAWSDB_URL for database connection details
$url = getenv("JAWSDB_URL");

// Fallback in case JAWSDB_URL is not set (for local development)
if (!$url) {
    $url = "mysql://y3g5g0yeo853ym5p:blwkweo8kth5j3p0@blonze2d5mrbmcgf.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/xu96k9fushnnczct";
}

$parsed_url = parse_url($url);

// Extract connection details from the URL
$host = $parsed_url["host"];
$username = $parsed_url["user"];
$password = $parsed_url["pass"];
$dbname = substr($parsed_url["path"], 1); // Removes the leading "/"

// Create a new MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Success message (you can remove this in production)
echo "Connected to the database successfully!";
?>
