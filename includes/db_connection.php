<?php
// Database configuration
$host = "localhost";  // or "127.0.0.1"
$usernameDB = "root";   // your MySQL username
$password = "";       // your MySQL password (leave empty if not set)
$database = "epm_backup";

// Create connection
$conn = new mysqli($host, $usernameDB, $password, $database);

// Check connection
if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

/*
$host = "ixnzh1cxch6rtdrx.cbetxkdyhwsb.us-east-1.rds.amazonaws.com";  // JawsDB host
$usernameDB = "p7apqmgbef3tu2d6";  // JawsDB username
$password = "lu8uvpzjm27qbtlq";    // JawsDB password
$database = "lnm4m0erp17734x3";    // JawsDB database name

$conn = new mysqli($host, $usernameDB, $password, $database);

// Check connection
if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}
*/
?>
