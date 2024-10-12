<?php
include '../includes/db_connection.php';
// Fetch all users with plaintext passwords
$sql = "SELECT UserID, Password FROM useraccounts";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $storedPassword = $row['Password'];
    if (password_get_info($storedPassword)['algo'] === 0) {
        // Password is plaintext, hash it
        $hashedPassword = password_hash($storedPassword, PASSWORD_DEFAULT);
        $update_sql = "UPDATE useraccounts SET Password = ? WHERE UserID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $hashedPassword, $row['UserID']);
        $update_stmt->execute();
        $update_stmt->close();
    }
}
?>