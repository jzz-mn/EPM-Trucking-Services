<?php
include '../includes/db_connection.php';

if(isset($_POST['officerID'])) {
    $officerID = intval($_POST['officerID']);

    $sql = "SELECT o.OfficerID, o.FirstName, o.MiddleInitial, o.LastName, o.Position, o.Gender, o.CityAddress, 
            o.MobileNo, o.EmailAddress AS OfficerEmail, o.College, o.Program, o.YearGraduated, ua.Username, ua.Password, ua.ActivationStatus, ua.EmailAddress AS UserEmail
            FROM officers o
            JOIN useraccounts ua ON o.OfficerID = ua.officerID
            WHERE o.OfficerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $officerID);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $officer = $result->fetch_assoc();
        // Do not include the password in the JSON response
        echo json_encode(['success' => true, 'officer' => $officer]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}

$conn->close();
?>
