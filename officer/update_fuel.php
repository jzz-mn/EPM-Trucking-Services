<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the updated data from the form
  $fuelID = $_POST['updateFuelID'];
  $date = $_POST['updateDate'];
  $liters = $_POST['updateLiters'];
  $unitPrice = $_POST['updateUnitPrice'];
  $fuelType = $_POST['updateFuelType'];
  $amount = $liters * $unitPrice;

  // Update the record in the database
  $sql = "UPDATE fuel 
          SET Date='$date', Liters='$liters', UnitPrice='$unitPrice', FuelType='$fuelType', Amount='$amount'
          WHERE FuelID='$fuelID'";

  if (mysqli_query($conn, $sql)) {
    // Redirect back to the fuel page or refresh the page
    header("Location: finance.php");
    exit();
  } else {
    echo "Error: " . mysqli_error($conn);
  }

  // Close the database connection
  mysqli_close($conn);
}
?>
