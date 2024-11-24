<?php
include '../includes/db_connection.php';

// Check if the 'id' parameter is present in the URL
if (isset($_GET['id'])) {
    $employeeID = intval($_GET['id']); // Convert the ID to an integer for security

    // Begin a transaction
    $conn->begin_transaction();

    try {
        // Delete from `useraccounts` table
        $sqlDeleteUser = "DELETE FROM useraccounts WHERE employeeID = ?";
        $stmtUser = $conn->prepare($sqlDeleteUser);
        $stmtUser->bind_param('i', $employeeID);
        $stmtUser->execute();

        // Check if user deletion was successful
        if ($stmtUser->affected_rows === 0) {
            throw new Exception("No user account found with employeeID {$employeeID}");
        }

        // Delete from `employees` table
        $sqlDeleteEmployee = "DELETE FROM employees WHERE EmployeeID = ?";
        $stmtEmployee = $conn->prepare($sqlDeleteEmployee);
        $stmtEmployee->bind_param('i', $employeeID);
        $stmtEmployee->execute();

        // Check if employee deletion was successful
        if ($stmtEmployee->affected_rows === 0) {
            throw new Exception("No employee found with EmployeeID {$employeeID}");
        }

        // Commit the transaction
        $conn->commit();

        // Redirect or display success message
        header("Location: ../super-admin/employees.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        
        // Display the error message
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the statements
        $stmtUser->close();
        $stmtEmployee->close();
        
        // Close the database connection
        $conn->close();
    }
} else {
    // Handle the case where 'id' parameter is not present
    echo "No employee ID specified.";
}
?>
