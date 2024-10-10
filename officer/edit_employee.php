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
include('../includes/db_connection.php');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $employeeID = isset($_POST['employeeID']) ? intval($_POST['employeeID']) : 0;
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $middleInitial = isset($_POST['middleInitial']) ? trim($_POST['middleInitial']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $dob = isset($_POST['dateOfBirth']) ? trim($_POST['dateOfBirth']) : '';
    $mobileNo = isset($_POST['mobileNo']) ? trim($_POST['mobileNo']) : '';
    $employmentDate = isset($_POST['employmentDate']) ? trim($_POST['employmentDate']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';

    // Data for the useraccounts table
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
    $activationStatus = isset($_POST['activationStatus']) ? trim($_POST['activationStatus']) : '';

    // Password-related fields
    $currentPassword = isset($_POST['currentPassword']) ? trim($_POST['currentPassword']) : '';
    $newPassword = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : '';
    $confirmNewPassword = isset($_POST['confirmNewPassword']) ? trim($_POST['confirmNewPassword']) : '';

    // Fetch the current password from the database
    $sql_fetch_password = "SELECT Password FROM useraccounts WHERE EmployeeID = ?";
    $stmt_fetch = $conn->prepare($sql_fetch_password);
    if ($stmt_fetch === false) {
        echo json_encode(['success' => false, 'message' => 'Error preparing password fetch statement: ' . $conn->error]);
        exit;
    }
    $stmt_fetch->bind_param('i', $employeeID);
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($dbPassword);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    // Verify the current password
    // If passwords are hashed, use password_verify()
    // Assuming passwords are stored in plain text as per your current implementation
    if ($currentPassword !== $dbPassword) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // Begin a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // First, update the employees table
        $sql_employee = "UPDATE employees 
                         SET FirstName = ?, MiddleInitial = ?, LastName = ?, Gender = ?, DateOfBirth = ?, MobileNo = ?, EmploymentDate = ?, Position = ?, Address = ? 
                         WHERE EmployeeID = ?";
        $stmt_employee = $conn->prepare($sql_employee);
        if ($stmt_employee === false) {
            throw new Exception('Error preparing employee update statement: ' . $conn->error);
        }
        $stmt_employee->bind_param('sssssssssi', $firstName, $middleInitial, $lastName, $gender, $dob, $mobileNo, $employmentDate, $position, $address, $employeeID);
        if (!$stmt_employee->execute()) {
            throw new Exception('Error updating employee: ' . $stmt_employee->error);
        }
        $stmt_employee->close();

        // Then, update the useraccounts table
        $sql_user = "UPDATE useraccounts SET Username = ?, EmailAddress = ?, ActivationStatus = ? WHERE EmployeeID = ?";
        $stmt_user = $conn->prepare($sql_user);
        if ($stmt_user === false) {
            throw new Exception('Error preparing user account update statement: ' . $conn->error);
        }
        $stmt_user->bind_param('sssi', $username, $emailAddress, $activationStatus, $employeeID);
        if (!$stmt_user->execute()) {
            throw new Exception('Error updating user account: ' . $stmt_user->error);
        }
        $stmt_user->close();

        // If the new password is provided, update the password
        if (!empty($newPassword)) {
            if ($newPassword === $confirmNewPassword) {
                // Optional: Hash the new password for security
                // $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                // For now, updating in plain text as per current implementation
                $sql_password = "UPDATE useraccounts SET Password = ? WHERE EmployeeID = ?";
                $stmt_password = $conn->prepare($sql_password);
                if ($stmt_password === false) {
                    throw new Exception('Error preparing password update statement: ' . $conn->error);
                }
                $stmt_password->bind_param('si', $newPassword, $employeeID);
                if (!$stmt_password->execute()) {
                    throw new Exception('Error updating password: ' . $stmt_password->error);
                }
                $stmt_password->close();
            } else {
                throw new Exception('New password and confirmation do not match.');
            }
        }

        // ---- Insert Activity Log ----
        // Retrieve the logged-in user's UserID from the session
        $currentUserID = $_SESSION['UserID'];

        // Define the action description
        $action = "Updated Employee: " . $firstName . " " . $lastName;

        // Get the current timestamp
        $currentTimestamp = date("Y-m-d H:i:s");

        // Prepare the INSERT statement for activitylogs
        $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
        $stmtLog = $conn->prepare($sqlInsertLog);
        if ($stmtLog === false) {
            throw new Exception('Error preparing activity log insertion: ' . $conn->error);
        }
        $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);
        if (!$stmtLog->execute()) {
            // Log the error without halting the transaction
            error_log("Failed to insert activity log: " . $stmtLog->error);
            // Optionally, you can choose to throw an exception here to rollback the entire transaction
            // throw new Exception('Failed to insert activity log.');
        }
        $stmtLog->close();
        // ---- End of Activity Log Insertion ----

        // Commit the transaction
        $conn->commit();

        // Success message
        echo json_encode(['success' => true, 'message' => 'Employee details updated successfully!']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        // Return error message
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }

    // Close the database connection
    $conn->close();
}
?>