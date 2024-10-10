<?php
// Start the session and verify user authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if not logged in
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection file
include '../includes/db_connection.php';

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize response array
$response = [];

// Check if the form is submitted with a valid officerID
if (isset($_POST['officerID']) && is_numeric($_POST['officerID'])) {
    $officerID = intval($_POST['officerID']);

    // Begin a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // Prepare the SQL statement to update officer details
        $sql = "UPDATE officers SET FirstName=?, MiddleInitial=?, LastName=?, Position=?, Gender=?, CityAddress=?, MobileNo=?, EmailAddress=?, College=?, Program=?, YearGraduated=? WHERE OfficerID=?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare officer update statement: " . $conn->error);
        }

        // Bind officer details parameters
        $stmt->bind_param(
            "sssssssssssi",
            $_POST['firstName'],
            $_POST['middleInitial'],
            $_POST['lastName'],
            $_POST['position'],
            $_POST['gender'],
            $_POST['address'],
            $_POST['mobileNo'],
            $_POST['emailAddress'],
            $_POST['college'],
            $_POST['program'],
            $_POST['yearGraduated'],
            $officerID
        );

        // Execute the officer update
        if (!$stmt->execute()) {
            throw new Exception("Failed to update officer: " . $stmt->error);
        }

        $stmt->close();

        $response['message'] = "Officer updated successfully.";

        // Now update user account details
        if (!empty($_POST['password'])) {
            // **Security Enhancement:** Hash the new password before storing
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Prepare the SQL statement to update user account with password
            $sqlUser = "UPDATE useraccounts SET Username=?, Password=?, EmailAddress=?, ActivationStatus=? WHERE OfficerID=?";
            $stmtUser = $conn->prepare($sqlUser);

            if (!$stmtUser) {
                throw new Exception("Failed to prepare user account update statement: " . $conn->error);
            }

            $stmtUser->bind_param(
                "ssssi",
                $_POST['username'],
                $hashedPassword, // Use hashed password
                $_POST['userEmailAddress'],
                $_POST['activationStatus'],
                $officerID
            );
        } else {
            // Prepare the SQL statement to update user account without password
            $sqlUser = "UPDATE useraccounts SET Username=?, EmailAddress=?, ActivationStatus=? WHERE OfficerID=?";
            $stmtUser = $conn->prepare($sqlUser);

            if (!$stmtUser) {
                throw new Exception("Failed to prepare user account update statement: " . $conn->error);
            }

            $stmtUser->bind_param(
                "sssi",
                $_POST['username'],
                $_POST['userEmailAddress'],
                $_POST['activationStatus'],
                $officerID
            );
        }

        // Execute the user account update
        if (!$stmtUser->execute()) {
            throw new Exception("Failed to update user account: " . $stmtUser->error);
        }

        $stmtUser->close();

        $response['user_message'] = "User account updated successfully.";

        // ---- Insert Activity Log ----
        // Retrieve the logged-in user's UserID from the session
        $currentUserID = $_SESSION['UserID'];

        // Define the action description
        $action = "Updated Officer: " . $_POST['firstName'] . " " . $_POST['lastName'];

        // Get the current timestamp
        $currentTimestamp = date("Y-m-d H:i:s");

        // Prepare the INSERT statement for activitylogs
        $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
        $stmtLog = $conn->prepare($sqlInsertLog);

        if (!$stmtLog) {
            throw new Exception("Failed to prepare activity log insertion: " . $conn->error);
        }

        $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);

        // Execute the activity log insertion
        if (!$stmtLog->execute()) {
            // Log the error without halting the transaction
            error_log("Failed to insert activity log: " . $stmtLog->error);
            // Optionally, you can choose to throw an exception to rollback the entire transaction
            // throw new Exception("Failed to insert activity log: " . $stmtLog->error);
        }

        $stmtLog->close();
        // ---- End of Activity Log Insertion ----

        // Commit the transaction
        $conn->commit();

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();

        // Set error message in response
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = "Invalid officer ID or officer ID not provided.";
}

// Return the response as JSON
echo json_encode($response);

// Close the database connection
$conn->close();
?>