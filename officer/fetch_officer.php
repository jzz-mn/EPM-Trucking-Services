<?php
include '../includes/db_connection.php';

if (isset($_POST['officerID'])) {
    $officerID = intval($_POST['officerID']);

    $sql = "SELECT o.OfficerID, o.FirstName, o.MiddleInitial, o.LastName, o.Position, o.Gender, o.CityAddress, 
            o.MobileNo, o.EmailAddress AS OfficerEmail, o.College, o.Program, o.YearGraduated, 
            ua.UserID, ua.Username, ua.ActivationStatus, ua.EmailAddress AS UserEmail, ua.UserImage
            FROM officers o
            JOIN useraccounts ua ON o.OfficerID = ua.officerID
            WHERE o.OfficerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $officerID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $officer = $result->fetch_assoc();

        // Handle the UserImage binary data
        if (!empty($officer['UserImage'])) {
            // Base64-encode the binary data for JSON transmission
            $officer['UserImage'] = base64_encode($officer['UserImage']);
        } else {
            $officer['UserImage'] = null;
        }

        echo json_encode(['success' => true, 'officer' => $officer]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Officer not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No officer ID provided.']);
}

$conn->close();
?>