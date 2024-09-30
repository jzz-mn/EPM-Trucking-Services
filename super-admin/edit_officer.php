<?php
include '../includes/db_connection.php';

$response = []; // Response array to track and return responses

if (isset($_POST['officerID']) && is_numeric($_POST['officerID'])) {
    $officerID = $_POST['officerID'];

    // Prepare the SQL statement to update officer details
    $sql = "UPDATE officers SET FirstName=?, MiddleInitial=?, LastName=?, Position=?, Gender=?, CityAddress=?, MobileNo=?, EmailAddress=?, College=?, Program=?, YearGraduated=? WHERE OfficerID=?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind officer details parameters
        $stmt->bind_param("sssssssssssi", $_POST['firstName'], $_POST['middleInitial'], $_POST['lastName'], $_POST['position'], $_POST['gender'], $_POST['address'], $_POST['mobileNo'], $_POST['emailAddress'], $_POST['college'], $_POST['program'], $_POST['yearGraduated'], $officerID);

        if ($stmt->execute()) {
            $response['message'] = "Officer updated successfully.";

            // Now update user account details
            if (!empty($_POST['password'])) {
                // Remove hashing if you insist on not using it
                $plainPassword = $_POST['password'];
                $sqlUser = "UPDATE useraccounts SET Username=?, Password=?, EmailAddress=?, ActivationStatus=? WHERE OfficerID=?";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->bind_param("ssssi", $_POST['username'], $plainPassword, $_POST['userEmailAddress'], $_POST['activationStatus'], $officerID);
            } else {
                $sqlUser = "UPDATE useraccounts SET Username=?, EmailAddress=?, ActivationStatus=? WHERE OfficerID=?";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->bind_param("sssi", $_POST['username'], $_POST['userEmailAddress'], $_POST['activationStatus'], $officerID);
            }
            
            if ($stmtUser && $stmtUser->execute()) {
                $response['user_message'] = "User account updated successfully.";
            } else {
                $response['error'] = "Failed to update user account: " . $stmtUser->error;
            }
            
            if ($stmtUser) {
                $stmtUser->close();
            }

        } else {
            $response['error'] = "Failed to update officer: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['error'] = "Failed to prepare the statement: " . $conn->error;
    }
} else {
    $response['error'] = "Invalid officer ID or officer ID not provided.";
}

echo json_encode($response);
$conn->close();
?>
