<?php
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
    $stmtEmployee->bind_param('ssssssssss', $firstName, $middleInitial, $lastName, $gender, $dob, $address, $mobileNo, $emailAddress, $employmentDate, $position);

    if ($stmtEmployee->execute()) {
        // Get the last inserted employee ID
        $employeeID = $conn->insert_id;

        // Insert into `useraccounts` table
        $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus)
                          VALUES (?, ?, 'Employee', ?, ?, NULL, NULL, 'Activated')";
                          
        $stmtUser = $conn->prepare($sqlInsertUser);
        $stmtUser->bind_param('sssi', $username, $password, $emailAddress, $employeeID);
        
        if ($stmtUser->execute()) {
            // Redirect to employee.php after successful insertion
            header("Location: employees.php");
            exit(); // Always exit after a header redirect to prevent further script execution
        } else {
            echo "Error: " . $stmtUser->error;
        }
    } else {
        echo "Error: " . $stmtEmployee->error;
    }
    
    // Close the statements
    $stmtEmployee->close();
    $stmtUser->close();
    
    // Close the database connection
    $conn->close();
}
?>
