<?php
// Include the database connection
include_once('../includes/db_connection.php');

if (isset($_GET['id'])) {
    $employeeID = $_GET['id'];

    // Prepare the SQL query to fetch data from both 'employees' and 'useraccounts' tables, including UserImage
    $query = "SELECT e.EmployeeID, e.FirstName, e.MiddleInitial, e.LastName, e.Gender, e.Position, e.DateOfBirth, e.Address, e.MobileNo, e.EmailAddress AS employeeEmail, e.EmploymentDate,
               u.Username, u.EmailAddress AS accountEmail, u.ActivationStatus, u.UserImage
            FROM employees e
            LEFT JOIN useraccounts u ON e.EmployeeID = u.employeeID
            WHERE e.EmployeeID = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $employeeID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();

        // Handle the UserImage binary data
        if (!empty($employee['UserImage'])) {
            // Base64-encode the binary data for JSON transmission
            $employee['UserImage'] = base64_encode($employee['UserImage']);
        } else {
            // If no image, set to null or provide a default image path
            $employee['UserImage'] = null;
        }

        // Return employee data in JSON format
        echo json_encode($employee);
    } else {
        echo json_encode(['error' => 'Employee not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>