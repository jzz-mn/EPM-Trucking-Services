<?php
// Retrieve connection details from environment variables
$host = getenv('DB_HOST');
$usernameDB = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_DATABASE');

// Create connection
$conn = new mysqli($host, $usernameDB, $password, $database);

// Check connection
if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}
?>
