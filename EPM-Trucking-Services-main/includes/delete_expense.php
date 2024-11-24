<?php
include '../includes/db_connection.php';

// Check if the 'id' parameter is present in the URL
if (isset($_GET['id'])) {
    $expenseID = intval($_GET['id']); // Convert the ID to an integer for security

    // Begin a transaction
    $conn->begin_transaction();

    try {
        // Delete from `expenses` table
        $sqlDeleteExpense = "DELETE FROM expenses WHERE ExpenseID = ?";
        $stmtExpense = $conn->prepare($sqlDeleteExpense);
        $stmtExpense->bind_param('i', $expenseID);
        $stmtExpense->execute();

        // Check if deletion was successful
        if ($stmtExpense->affected_rows === 0) {
            throw new Exception("No expense record found with ExpenseID {$expenseID}");
        }

        // Commit the transaction
        $conn->commit();

        // Redirect or display success message
        header("Location: ../super-admin/finance.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        
        // Display the error message
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the statement
        $stmtExpense->close();
        
        // Close the database connection
        $conn->close();
    }
} else {
    // Handle the case where 'id' parameter is not present
    echo "No expense ID specified.";
}
?>
