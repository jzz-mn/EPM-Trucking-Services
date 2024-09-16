<?php
include 'db_connection.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract and sanitize form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $mobile_no = $_POST['mobile_no'];
    $employment_date = $_POST['employment_date'];
    $position = $_POST['position'];
    $address = $_POST['address'];

    // Validate passwords
    if ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }

    // Check for duplicate username or email
    $stmt = $conn->prepare('SELECT * FROM useraccounts WHERE Username = ? OR EmailAddress = ?');
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['message'] = 'Username or email already exists.';
        echo json_encode($response);
        exit;
    }

    // Get the next employee ID
    $result = $conn->query('SELECT MAX(EmployeeID) AS max_id FROM employees');
    $row = $result->fetch_assoc();
    $next_employee_id = $row['max_id'] + 1;

    // Insert into employees table
    $stmt = $conn->prepare('INSERT INTO employees (EmployeeID, FirstName, MiddleInitial, LastName, Gender, Position, DateOfBirth, Address, MobileNo, EmailAddress, EmploymentDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssssssss', $next_employee_id, $first_name, $middle_initial, $last_name, $gender, $position, $date_of_birth, $address, $mobile_no, $email, $employment_date);
    if ($stmt->execute()) {
        // Insert into useraccounts table
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO useraccounts (Username, Password, Role, EmailAddress, employeeID) VALUES (?, ?, ?, ?, ?)');
        $role = 'Employee';
        $stmt->bind_param('ssssi', $username, $hashed_password, $role, $email, $next_employee_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Employee added successfully.';
        } else {
            $response['message'] = 'Failed to create user account.';
        }
    } else {
        $response['message'] = 'Failed to add employee.';
    }
}

echo json_encode($response);
?>
