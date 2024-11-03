<?php
session_start();
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $maintenanceId = $_POST['maintenanceId'];
    $maintenanceDate = $_POST['maintenanceDate'];
    $truckId = $_POST['truck_id'];
    $category = $_POST['maintenanceCategory'];
    $description = $_POST['maintenanceDescription'];
    $loggedBy = $_POST['loggedBy']; // Captures the employeeID

    // Extract Year and Month from the maintenanceDate
    $year = date('Y', strtotime($maintenanceDate)); // Ensure data type matches your DB schema
    $month = date('F', strtotime($maintenanceDate));

    // Debugging statements
    echo "LoggedBy (from POST): " . $loggedBy . "<br>";
    echo "Session User ID: " . $_SESSION['user_id'] . "<br>";

    // Prepare the SQL insert query
    $sql = "INSERT INTO truckmaintenance (MaintenanceID, TruckID, Year, Month, Category, Description, LoggedBy) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    // Use prepared statements to avoid SQL injection
    if ($stmt = $conn->prepare($sql)) {
        // Adjust bind_param types based on your variables' data types
        $stmt->bind_param("iiisssi", $maintenanceId, $truckId, $year, $month, $category, $description, $loggedBy);

        if ($stmt->execute()) {
            header("Location: maintenance.php");
            exit;
        } else {
            echo "Error executing query: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    // Close the database connection
    $conn->close();
}
