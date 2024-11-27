<?php
// auth.php
session_start();

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
    if ($userRole !== 'Employee') {
        redirectTo('../officer/home.php');
    } else {
        redirectTo('../includes/unauthorized.php');
    }
}
?>
