<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the form data
  $maintenanceId = $_POST['maintenanceId'];
  $year = $_POST['maintenanceYear'];
  $month = $_POST['maintenanceMonth'];
  $category = $_POST['maintenanceCategory'];
  $description = $_POST['maintenanceDescription'];
  $amount = $_POST['maintenanceAmount'];

  // Validate form data (optional but recommended)
  if ($maintenanceId && $year && $month && $category && $description && $amount) {
    // Update the record in the database
    $sql = "UPDATE truckmaintenance 
            SET Year = '$year', Month = '$month', Category = '$category', 
                Description = '$description', Amount = '$amount' 
            WHERE MaintenanceID = '$maintenanceId'";

    if (mysqli_query($conn, $sql)) {
      // Success: Redirect back or show success message
      header("Location: trucks.php");
      exit();
    } else {
      // Error handling
      echo "Error updating record: " . mysqli_error($conn);
    }
  } else {
    // Handle missing data
    echo "All fields are required!";
  }

  // Close the database connection
  mysqli_close($conn);
} else {
  // If the request is not a POST request
  echo "Invalid request!";
}
?>
