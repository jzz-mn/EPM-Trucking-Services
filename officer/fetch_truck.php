<?php
include '../includes/db_connection.php';

if (isset($_GET['truckId'])) {
    $truckId = $_GET['truckId'];

    // Prepare a query to fetch the truck details
    $query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $truckId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the truck details
            $truck = $result->fetch_assoc();

            // Return the truck details as JSON
            echo json_encode($truck);
        } else {
            // No truck found with the given ID
            echo json_encode(['error' => 'No truck found']);
        }
    } else {
        // Error executing query
        echo json_encode(['error' => 'Error fetching truck data']);
    }

    $stmt->close();
} else {
    // If truckId is not provided
    echo json_encode(['error' => 'Truck ID not provided']);
}

$conn->close();
?>
