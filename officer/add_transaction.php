<?php
include '../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $transactionID = $_POST['transactionID'];
  $transactionDate = $_POST['transactionDate'];
  $invoiceID = $_POST['invoiceID'];
  $phoneNumber = $_POST['phoneNumber'];
  $drNumber = $_POST['drNumber'];
  $sourceCustomerCode = $_POST['sourceCustomerCode'];
  $customerNumber = $_POST['customerNumber'];
  $destinationCustomerCode = $_POST['destinationCustomerCode'];
  $quantityQtl = $_POST['quantityQtl'];
  $weightKgs = $_POST['weightKgs'];
  $expenseID = $_POST['expenseID'];

  // Insert data into the transactions table
  $sql = "INSERT INTO transactions (TransactionID, InvoiceID, Date, PlateNumber, DRNumber, SourceCustomerCode, CustomerName, DestinationCustomerCode, Qty, Kgs, ExpenseID)
          VALUES ('$transactionID', '$invoiceID', '$transactionDate', '$phoneNumber', '$drNumber', '$sourceCustomerCode', '$customerNumber', '$destinationCustomerCode', '$quantityQtl', '$weightKgs', '$expenseID')";

  if (mysqli_query($conn, $sql)) {
    echo "Transaction added successfully";
    // Redirect to the transactions page (or wherever you'd like)
    header("Location: trucks.php");
  } else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
  }

  // Close the database connection
  mysqli_close($conn);
}
?>