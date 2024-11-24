<?php
// check_user.php

// Connect to your database (replace with your database connection)
include '../includes/db_connection.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$username = isset($_POST['username']) ? $_POST['username'] : '';
$emailAddress = isset($_POST['emailAddress']) ? $_POST['emailAddress'] : '';

// Check if a username is being validated
if (!empty($username)) {
    $sql = "SELECT * FROM useraccounts WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo 'taken';
    } else {
        echo 'available';
    }
    $stmt->close();
}

// Check if an email address is being validated
if (!empty($emailAddress)) {
    $sql = "SELECT * FROM useraccounts WHERE EmailAddress = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $emailAddress);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo 'taken';
    } else {
        echo 'available';
    }
    $stmt->close();
}

$conn->close();
