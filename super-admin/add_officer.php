<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Officer Data from POST
    $firstName = $_POST['firstName'];
    $middleInitial = $_POST['middleInitial'];
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob']; // This field is not in the new table
    $address = $_POST['address']; // This field is not in the new table
    $mobileNo = $_POST['mobileNo'];
    $emailAddress = $_POST['emailAddress'];
    $position = $_POST['position'];
    $college = $_POST['college'];
    $program = $_POST['program'];
    $yearGraduated = $_POST['yearGraduated'];

    // Collect User Account Data from POST
    $username = $_POST['username'];
    $password = $_POST['password']; // No encryption here

    // Insert officer data into `officers` table
    $sqlInsertOfficer = "INSERT INTO officers (FirstName, MiddleInitial, LastName, Position, Gender, CityAddress, MobileNo, EmailAddress, College, Program, YearGraduated)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtOfficer = $conn->prepare($sqlInsertOfficer);
    $stmtOfficer->bind_param('sssssssssss', $firstName, $middleInitial, $lastName, $position, $gender, $address, $mobileNo, $emailAddress, $college, $program, $yearGraduated);

    if ($stmtOfficer->execute()) {
        // Get the last inserted officer ID
        $officerID = $conn->insert_id;

        // Insert into `useraccounts` table
        $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin)
                          VALUES (?, ?, 'Officer', ?, NULL, ?, NULL)";

        $stmtUser = $conn->prepare($sqlInsertUser);
        $stmtUser->bind_param('sssi', $username, $password, $emailAddress, $officerID);

        if ($stmtUser->execute()) {
            // Redirect to employees.php after successful insertion
            header("Location: officers.php");
            exit(); // Always exit after a header redirect to prevent further script execution
        } else {
            echo "Error: " . $stmtUser->error;
        }
    } else {
        echo "Error: " . $stmtOfficer->error;
    }

    // Close the statements
    $stmtOfficer->close();
    $stmtUser->close();

    // Close the database connection
    $conn->close();
}
?>