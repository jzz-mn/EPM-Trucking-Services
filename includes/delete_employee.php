<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $employeeID = $_GET['id'];

    // SQL query to delete the employee
    $sql = "DELETE FROM employees WHERE EmployeeID = ?";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeID);
    
    if ($stmt->execute()) {
        // Redirect to the page after successful deletion
        header("Location: ../super-admin/employees.php"); // Change this to your employee list page
    } else {
        echo "Error deleting employee: " . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
?>
