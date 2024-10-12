<?php
// Start the session
session_start();

// Include the database connection file
include '../includes/db_connection.php';

// Function to log activity
function log_activity($conn, $user_id, $action) {
    // Set the current timestamp
    $current_timestamp = date("Y-m-d H:i:s");
    
    // Prepare the INSERT statement to prevent SQL injection
    $insert_sql = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
    
    if ($stmt = $conn->prepare($insert_sql)) {
        $stmt->bind_param("iss", $user_id, $action, $current_timestamp);
        if (!$stmt->execute()) {
            // Handle insertion error (optional)
            error_log("Failed to insert activity log: " . $stmt->error);
            // You can choose to notify the user or silently fail
        }
        $stmt->close();
    } else {
        // Handle preparation error (optional)
        error_log("Failed to prepare activity log insertion: " . $conn->error);
        // You can choose to notify the user or silently fail
    }
}

// Check if the user is logged in by verifying if 'UserID' exists in the session
if (isset($_SESSION['UserID'])) {
    $user_id = $_SESSION['UserID'];
    
    // Log the logout activity
    log_activity($conn, $user_id, "Logged out");
}

// Unset all session variables
$_SESSION = array();

// If you want to destroy the session cookie as well, uncomment the following lines:
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Close the database connection
$conn->close();

// Redirect to login page
header("Location: login.php");
exit();
?>
