<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include '../includes/db_connection.php';

    $transactionID = $_POST['transactionID'];
    $invoiceID = $_POST['invoiceID'];
    $date = $_POST['transactionDate'];
    $plateNumber = $_POST['plateNumber'];
    $drNumber = $_POST['drNumber'];
    $sourceCustomerCode = $_POST['sourceCustomerCode'];
    $customerNumber = $_POST['customerNumber'];
    $destinationCustomerCode = $_POST['destinationCustomerCode'];
    $quantityQtl = $_POST['quantityQtl'];
    $weightKgs = $_POST['weightKgs'];
    $expenseID = $_POST['expenseID'];

    // Update query
    $sql = "UPDATE transactions 
            SET InvoiceID='$invoiceID', Date='$date', PlateNumber='$plateNumber', DRNumber='$drNumber', 
                SourceCustomerCode='$sourceCustomerCode', CustomerName='$customerNumber', 
                DestinationCustomerCode='$destinationCustomerCode', Qty='$quantityQtl', Kgs='$weightKgs', ExpenseID='$expenseID' 
            WHERE TransactionID='$transactionID'";

    if (mysqli_query($conn, $sql)) {
        echo "Transaction updated successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
    header("Location: transactions.php"); // Redirect after updating
}
?>
