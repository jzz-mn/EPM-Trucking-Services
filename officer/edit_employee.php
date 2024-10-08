<?php
// Include the database connection file
include('../includes/db_connection.php');

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
    $sql_fetch_password = "SELECT password FROM useraccounts WHERE EmployeeID = ?";
    $stmt_fetch = $conn->prepare($sql_fetch_password);
    $stmt_fetch->bind_param('i', $employeeID);
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($dbPassword);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    // Verify the current password
    if ($currentPassword !== $dbPassword) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // First, update the employees table
    $sql_employee = "UPDATE employees 
                     SET FirstName = ?, MiddleInitial = ?, LastName = ?, Gender = ?, DateOfBirth = ?, MobileNo = ?, EmploymentDate = ?, Position = ?, Address = ? 
                     WHERE EmployeeID = ?";
    $stmt_employee = $conn->prepare($sql_employee);
    if ($stmt_employee) {
        $stmt_employee->bind_param('sssssssssi', $firstName, $middleInitial, $lastName, $gender, $dob, $mobileNo, $employmentDate, $position, $address, $employeeID);
        if (!$stmt_employee->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error updating employee: ' . $stmt_employee->error]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparing employee update statement']);
        exit;
    }

    // Then, update the useraccounts table
    $sql_user = "UPDATE useraccounts SET username = ?, emailAddress = ?, ActivationStatus = ? WHERE EmployeeID = ?";
    $stmt_user = $conn->prepare($sql_user);
    if ($stmt_user) {
        $stmt_user->bind_param('sssi', $username, $emailAddress, $activationStatus, $employeeID);
        if (!$stmt_user->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error updating user account: ' . $stmt_user->error]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparing user account update statement']);
        exit;
    }

    // If the new password is provided, update the password
    if (!empty($newPassword)) {
        if ($newPassword === $confirmNewPassword) {
            // Update the password in plain text (without hashing)
            $sql_password = "UPDATE useraccounts SET password = ? WHERE EmployeeID = ?";
            $stmt_password = $conn->prepare($sql_password);
            if ($stmt_password) {
                $stmt_password->bind_param('si', $newPassword, $employeeID);
                if (!$stmt_password->execute()) {
                    echo json_encode(['success' => false, 'message' => 'Error updating password: ' . $stmt_password->error]);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error preparing password update statement']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit;
        }
    }

    // Success message
    echo json_encode(['success' => true, 'message' => 'Employee details updated successfully!']);
}
?>
