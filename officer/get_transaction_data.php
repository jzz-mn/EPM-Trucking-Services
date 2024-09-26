<?php
include '../includes/db_connection.php';

if (isset($_POST['transactionID'])) {
    $transactionID = $_POST['transactionID'];

    // Query to get the transaction data
    $sql = "SELECT * FROM transactions WHERE TransactionID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transactionID);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();

    // Return the transaction data as JSON
    echo json_encode($transaction);
}
?>