<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect Officer Data from POST
    $firstName = $_POST['firstName'];
    $middleInitial = isset($_POST['middleInitial']) ? $_POST['middleInitial'] : ''; // Make optional
    $lastName = $_POST['lastName'];
    $gender = $_POST['gender'];
    $address = isset($_POST['address']) ? $_POST['address'] : ''; // Make optional
    $mobileNo = $_POST['mobileNo'];
    $emailAddress = $_POST['userEmailAddress']; // Fixed name for email
    $position = $_POST['position'];
    $college = isset($_POST['college']) ? $_POST['college'] : ''; // Optional
    $program = isset($_POST['program']) ? $_POST['program'] : ''; // Optional
    $yearGraduated = isset($_POST['yearGraduated']) ? $_POST['yearGraduated'] : ''; // Optional

    // Collect User Account Data from POST
    $username = $_POST['username'];
    $password = $_POST['password']; // No encryption applied

    // Step 1: Check if the username or email already exists
    $sqlCheck = "SELECT COUNT(*) AS count FROM useraccounts WHERE Username = ? OR EmailAddress = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param('ss', $username, $emailAddress);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    if ($count > 0) {
        echo "Error: Username or email already exists!";
        exit;
    }

    // Step 2: Insert officer data into `officers` table
    $sqlInsertOfficer = "INSERT INTO officers (FirstName, MiddleInitial, LastName, Position, Gender, CityAddress, MobileNo, EmailAddress, College, Program, YearGraduated)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmtOfficer = $conn->prepare($sqlInsertOfficer);
    if ($stmtOfficer === false) {
        echo "Error preparing officer insert statement: " . $conn->error;
        exit;
    }
    $stmtOfficer->bind_param('sssssssssss', $firstName, $middleInitial, $lastName, $position, $gender, $address, $mobileNo, $emailAddress, $college, $program, $yearGraduated);

    if ($stmtOfficer->execute()) {
        // Step 3: Get the last inserted officer ID
        $officerID = $conn->insert_id;

        // Step 4: Insert into `useraccounts` table
        $sqlInsertUser = "INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID, officerID, LastLogin, ActivationStatus)
                          VALUES (?, ?, 'Officer', ?, NULL, ?, NULL, 'Activated')";

        $stmtUser = $conn->prepare($sqlInsertUser);
        if ($stmtUser === false) {
            echo "Error preparing user account insert statement: " . $conn->error;
            exit;
        }
        $stmtUser->bind_param('sssi', $username, $password, $emailAddress, $officerID);

        if ($stmtUser->execute()) {
            // Redirect to officers.php after successful insertion
            header("Location: officers.php");
            exit();
        } else {
            echo "Error: " . $stmtUser->error;
        }

        // Close the user account statement
        $stmtUser->close();
    } else {
        echo "Error: " . $stmtOfficer->error;
    }

    // Close the officer statement
    $stmtOfficer->close();

    // Close the database connection
    $conn->close();
}
?>
