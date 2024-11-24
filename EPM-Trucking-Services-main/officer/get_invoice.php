<?php
include '../includes/db_connection.php';
$invoiceID = $_GET['id'];
$response = array();

$sql = "SELECT InvoiceID, BillingInvoiceNo, BillingDate, BilledTo, GrandSubtotal, GrossAmount, VAT12, EWT2, AddTollCharges, AmountNetofTax, NetAmount FROM invoices WHERE InvoiceID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $invoiceID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $response = $result->fetch_assoc();
}

echo json_encode($response);
?>
