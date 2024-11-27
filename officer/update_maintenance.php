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

  // Map numeric month to month name
  $monthNames = array(
    '1' => 'January',
    '2' => 'February',
    '3' => 'March',
    '4' => 'April',
    '5' => 'May',
    '6' => 'June',
    '7' => 'July',
    '8' => 'August',
    '9' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
  );

  if (array_key_exists($month, $monthNames)) {
    $monthName = $monthNames[$month];
  } else {
    // Handle invalid month value
    $monthName = 'Unknown';
  }

  // Validate form data (optional but recommended)
  if ($maintenanceId && $year && $monthName && $category && $description && $amount) {
    // Sanitize inputs to prevent SQL injection
    $maintenanceId = mysqli_real_escape_string($conn, $maintenanceId);
    $year = mysqli_real_escape_string($conn, $year);
    $monthName = mysqli_real_escape_string($conn, $monthName);
    $category = mysqli_real_escape_string($conn, $category);
    $description = mysqli_real_escape_string($conn, $description);
    $amount = mysqli_real_escape_string($conn, $amount);

    // Update the record in the database
    $sql = "UPDATE truckmaintenance 
            SET Year = '$year', Month = '$monthName', Category = '$category', 
                Description = '$description', Amount = '$amount' 
            WHERE MaintenanceID = '$maintenanceId'";

    if (mysqli_query($conn, $sql)) {
      // Success: Redirect back or show success message
      $_SESSION['maintenances_updated'] = true; // Set success flag
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
