<?php
/*
 // Database configuration
 $host = "localhost";  // or "127.0.0.1"
 $usernameDB = "root";   // your MySQL username
 $password = "";       // your MySQL password (leave empty if not set)
 $database = "epm_database";

 // Create connection
 $conn = new mysqli($host, $usernameDB, $password, $database);

 // Check connection
 if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
 }

*/
$host = "DB_HOST";  // JawsDB host
$usernameDB = "DB_USERNAME";  // JawsDB username
$password = "DB_PASSWORD";    // JawsDB password
$database = "DB_DATABASE";    // JawsDB database name

$conn = new mysqli($host, $usernameDB, $password, $database);

// Check connection
if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

?>