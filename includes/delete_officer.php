<?php
include '../includes/db_connection.php';

// Check if the 'id' parameter is present in the URL
if (isset($_GET['id'])) {
    $officerID = intval($_GET['id']); // Convert the ID to an integer for security

    // Begin a transaction
    $conn->begin_transaction();

    try {
        // Delete from `useraccounts` table
        $sqlDeleteUser = "DELETE FROM useraccounts WHERE OfficerID = ?";
        $stmtUser = $conn->prepare($sqlDeleteUser);
        $stmtUser->bind_param('i', $officerID);
        $stmtUser->execute();

        // Check if user deletion was successful
        if ($stmtUser->affected_rows === 0) {
            throw new Exception("No user account found with OfficerID {$officerID}");
        }

        // Delete from `officers` table
        $sqlDeleteOfficer = "DELETE FROM officers WHERE OfficerID = ?";
        $stmtOfficer = $conn->prepare($sqlDeleteOfficer);
        $stmtOfficer->bind_param('i', $officerID);
        $stmtOfficer->execute();

        // Check if officer deletion was successful
        if ($stmtOfficer->affected_rows === 0) {
            throw new Exception("No officer found with OfficerID {$officerID}");
        }

        // Commit the transaction
        $conn->commit();

        // Redirect or display success message
        header("Location: ../super-admin/officers.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        
        // Display the error message
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the statements
        $stmtUser->close();
        $stmtOfficer->close();
        
        // Close the database connection
        $conn->close();
    }
} else {
    // Handle the case where 'id' parameter is not present
    echo "No officer ID specified.";
}
?>
