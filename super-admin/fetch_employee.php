<?php
// Include the database connection
include_once('../includes/db_connection.php');

if (isset($_GET['id'])) {
    $employeeID = $_GET['id'];

    // Prepare the SQL query to fetch data from both 'employees' and 'useraccounts' tables
    $query = "SELECT e.EmployeeID, e.FirstName, e.MiddleInitial, e.LastName,e.Gender, e.Position, e.DateOfBirth, e.Address, e.MobileNo, e.EmailAddress AS employeeEmail, e.EmploymentDate,
               u.Username, u.EmailAddress AS accountEmail, u.Password,
               CASE WHEN u.ActivationStatus = 'Active' THEN 'Activated' ELSE 'Deactivated' END AS ActivationStatus
        FROM employees e
        LEFT JOIN useraccounts u ON e.EmployeeID = u.EmployeeID
        WHERE e.EmployeeID = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $employeeID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        // Return employee data in JSON format
        echo json_encode($employee);
    } else {
        echo json_encode(['error' => 'Employee not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>