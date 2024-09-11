<?php
// Start session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Add your password if required
$dbname = "epm_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to check if user exists in the database
    $sql = "SELECT * FROM useraccounts WHERE EmailAddress = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); // Bind email to the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user was found
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify the password (assuming it's stored in plain text; use password hashing in production)
        if ($password === $row['Password']) {
            // Login successful, set session variables
            $_SESSION['UserID'] = $row['UserID'];
            $_SESSION['Username'] = $row['Username'];
            $_SESSION['Role'] = $row['Role'];
            $_SESSION['EmailAddress'] = $row['EmailAddress'];

            // Redirect to a different page (e.g., dashboard)
            header("Location: index3.html");
            exit();
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No user found with that email!";
    }

    $stmt->close();
}

$conn->close();
?>
