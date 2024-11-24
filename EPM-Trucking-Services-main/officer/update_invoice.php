<?php
include '../includes/db_connection.php';
$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $invoiceID = $_POST['InvoiceID'];
  $billingInvoiceNo = $_POST['BillingInvoiceNo'];
  $billingDate = $_POST['BillingDate'];
  $billedTo = $_POST['BilledTo'];
  $grandSubtotal = $_POST['GrandSubtotal'];
  $grossAmount = $_POST['GrossAmount'];
  $vat12 = $_POST['VAT12'];
  $ewt2 = $_POST['EWT2'];
  $addTollCharges = $_POST['AddTollCharges'];
  $amountNetofTax = $_POST['AmountNetofTax'];
  $netAmount = $_POST['NetAmount'];


  // Update query
  $stmt = $conn->prepare("UPDATE invoices SET BillingInvoiceNo = ?, BillingDate = ?, BilledTo = ?, GrandSubtotal = ?, GrossAmount = ?, VAT12 = ?, EWT2 = ?, AddTollCharges = ?, AmountNetofTax = ?, NetAmount= ? WHERE InvoiceID = ?");
  $stmt->bind_param('sssssssssdi', $billingInvoiceNo, $billingDate, $billedTo, $grandSubtotal, $grossAmount, $vat12, $ewt2, $addTollCharges,$amountNetofTax,$netAmount, $invoiceID);

  if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Invoice updated successfully.';
  } else {
    $response['message'] = 'Failed to update invoice.';
  }
}

echo json_encode($response);
?>
