<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the updated data from the form
  $transactionId = $_POST['transactionId'];
  $transactionDate = $_POST['transactionDate'];
  $drNo = $_POST['drNo'];
  $outletName = $_POST['outletName'];
  $qty = $_POST['qty'];
  $kgs = $_POST['kgs'];

  // Update the record in the database
  $sql = "UPDATE transactions 
          SET TransactionDate='$transactionDate', DRno='$drNo', OutletName='$outletName', Qty='$qty', KGs='$kgs'
          WHERE TransactionID='$transactionId'";

  if (mysqli_query($conn, $sql)) {
    // Redirect back to the page or show success message
    header("Location: trucks.php");
    exit();
  } else {
    echo "Error updating record: " . mysqli_error($conn);
  }

  // Close the database connection
  mysqli_close($conn);
} else {
  echo "Invalid request.";
}
?>