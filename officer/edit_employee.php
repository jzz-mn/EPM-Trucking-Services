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
    $password = isset($_POST['password']) ? trim($_POST['password']) : ''; // Optionally use the provided password directly
    $activationStatus = isset($_POST['activationStatus']) ? trim($_POST['activationStatus']) : ''; // Add ActivationStatus

    // First, update the employees table
    $sql_employee = "UPDATE employees 
                     SET FirstName = ?, MiddleInitial = ?, LastName = ?, Gender = ?, DateOfBirth = ?, MobileNo = ?, EmploymentDate = ?, Position = ?, Address = ? 
                     WHERE EmployeeID = ?";

    // Prepare the statement for employees table update
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
    $sql_user = "UPDATE useraccounts SET username = ?, emailAddress = ?, ActivationStatus = ?"; // Added ActivationStatus

    // If the password field is provided, append it to the SQL query
    if (!empty($password)) {
        // Hash the password
        $hashedPassword = ($password);
        $sql_user .= ", password = ?";
    }

    $sql_user .= " WHERE EmployeeID = ?";

    // Prepare the statement for useraccounts table update
    $stmt_user = $conn->prepare($sql_user);
    if ($stmt_user) {
        if (!empty($password)) {
            $stmt_user->bind_param('sssii', $username, $emailAddress, $activationStatus, $hashedPassword, $employeeID);
        } else {
            $stmt_user->bind_param('sssi', $username, $emailAddress, $activationStatus, $employeeID);
        }

        if (!$stmt_user->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error updating user account: ' . $stmt_user->error]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error preparing user account update statement']);
        exit;
    }

    // If both updates are successful, return success message
    echo json_encode(['success' => true, 'message' => 'Employee details updated successfully!']);
}
