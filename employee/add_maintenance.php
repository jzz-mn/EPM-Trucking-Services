<?php
// Include your database connection
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $maintenanceId = $_POST['maintenanceId'];
    $maintenanceDate = $_POST['maintenanceDate'];
    $category = $_POST['maintenanceCategory'];
    $description = $_POST['maintenanceDescription'];

    // Extract Year and Month from the maintenanceDate
    $year = date('Y', strtotime($maintenanceDate));
    $month = date('F', strtotime($maintenanceDate));

    // Insert query
    $sql = "INSERT INTO truckmaintenance (MaintenanceID, Year, Month, Category, Description) 
            VALUES ('$maintenanceId', '$year', '$month', '$category', '$description')";

    if ($conn->query($sql) === TRUE) {
        header("Location: maintenance.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close the database connection
    $conn->close();
}
?>
