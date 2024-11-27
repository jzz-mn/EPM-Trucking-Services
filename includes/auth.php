<?php
// auth.php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

function redirectTo($url) {
    header("Location: $url");
    exit();
}

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    redirectTo('index.php');
}

$userRole = $_SESSION['Role'];

// Define allowed roles for the current page
// This variable should be set before including auth.php
if (!isset($allowedRoles)) {
    // Default allowed roles if not set
    $allowedRoles = [];
}

if (!in_array($userRole, $allowedRoles)) {
    if ($userRole === 'Employee') {
        redirectTo('../employee/maintenance.php');
    } else {
        redirectTo('unauthorized.php');
    }
}
?>
