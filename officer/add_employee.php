<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if not logged in
    header("Location: ../login/login.php");
    exit();
}

include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Employee Data from POST
    $firstName = $_POST['firstName'];
    $middleInitial = $_POST['middleInitial'];
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $address = $_POST['address'];
    $mobileNo = $_POST['mobileNo'];
    $emailAddress = $_POST['emailAddress'];
    $employmentDate = $_POST['employmentDate'];
    $position = $_POST['position'];

    // Collect User Account Data from POST
    $username = $_POST['username'];
    $password = $_POST['password']; // No encryption here

    // Insert employee data into `employees` table
    $sqlInsertEmployee = "INSERT INTO employees (FirstName, MiddleInitial, LastName, Gender, DateOfBirth, Address, MobileNo, EmailAddress, EmploymentDate, Position)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtEmployee = $conn->prepare($sqlInsertEmployee);
    if (!$stmtEmployee) {
        echo "Error: " . $conn->error;
        exit();
    }
    $stmtEmployee->bind_param('ssssssssss', $firstName, $middleInitial, $lastName, $gender, $dob, $address, $mobileNo, $emailAddress, $employmentDate, $position);

    if ($stmtEmployee->execute()) {
        // Get the last inserted employee ID
        $employeeID = $conn->insert_id;

        // Insert into `useraccounts` table
        $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus)
                          VALUES (?, ?, 'Employee', ?, ?, NULL, NULL, 'Activated')";

        $stmtUser = $conn->prepare($sqlInsertUser);
        if (!$stmtUser) {
            echo "Error: " . $conn->error;
            exit();
        }
        $stmtUser->bind_param('sssi', $username, $password, $emailAddress, $employeeID);

        if ($stmtUser->execute()) {
            // Get the last inserted UserID
            $newUserID = $conn->insert_id;

            // ---- Insert Activity Log ----
            // Retrieve the logged-in user's UserID from the session
            $currentUserID = $_SESSION['UserID'];

            // Define the action description
            $action = "Added Employee: " . $username;

            // Get the current timestamp
            $currentTimestamp = date("Y-m-d H:i:s");

            // Prepare the INSERT statement for activitylogs
            $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
            $stmtLog = $conn->prepare($sqlInsertLog);
            if ($stmtLog) {
                $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);
                if (!$stmtLog->execute()) {
                    // Handle insertion error (optional)
                    error_log("Failed to insert activity log: " . $stmtLog->error);
                }
                $stmtLog->close();
            } else {
                // Handle preparation error (optional)
                error_log("Failed to prepare activity log insertion: " . $conn->error);
            }
            // ---- End of Activity Log Insertion ----

            // Redirect to employees.php after successful insertion
            header("Location: employees.php");
            exit(); // Always exit after a header redirect to prevent further script execution
        } else {
            echo "Error: " . $stmtUser->error;
        }
    } else {
        echo "Error: " . $stmtEmployee->error;
    }

    // Close the statements
    if (isset($stmtEmployee)) {
        $stmtEmployee->close();
    }
    if (isset($stmtUser)) {
        $stmtUser->close();
    }

    // Close the database connection
    $conn->close();
}
?>