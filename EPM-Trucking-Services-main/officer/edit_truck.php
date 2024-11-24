<?php
include '../includes/db_connection.php';

// Check if the request is a POST method and the required data is present
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['truckId'], $_POST['plateNumber'], $_POST['truckBrand'], $_POST['truckStatus'])) {
    // Get the data from the form
    $truckId = $_POST['truckId'];
    $plateNumber = $_POST['plateNumber'];
    $truckBrand = $_POST['truckBrand'];
    $truckStatus = $_POST['truckStatus'];

    // Prepare the SQL query to update the truck record
    $query = "UPDATE trucksinfo SET PlateNo = ?, TruckBrand = ?, TruckStatus = ? WHERE TruckID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssi', $plateNumber, $truckBrand, $truckStatus, $truckId);  // Bind the parameters

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        // Redirect back to the trucks page with a success message
        header("Location: trucks.php?success=1");
        exit();
    } else {
        // Redirect back to the trucks page with an error message
        header("Location: trucks.php?error=1");
        exit();
    }

    // Close the statement
    $stmt->close();
} else {
    // If required POST data is missing, redirect with an error
    header("Location: trucks.php?error=missing_data");
    exit();
}

// Close the connection
$conn->close();
?>
