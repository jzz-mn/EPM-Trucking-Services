<?php
include '../includes/db_connection.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract and sanitize form data
    $billingInvoiceNo = $_POST['BillingInvoiceNo'];
    $billingDate = $_POST['BillingDate'];
    $billedTo = $_POST['BilledTo'];  // Correct this field name
    $grossAmount = $_POST['GrossAmount'];
    $vat12 = $_POST['VAT12'];
    $ewt2 = $_POST['EWT2'];
    $addTollCharges = $_POST['AddTollCharges'];
    $amountNetofTax = $_POST['AmountNetofTax'];
    $netAmount = $_POST['NetAmount'];

    // Validate data (e.g., checking required fields)
    if (empty($billingInvoiceNo) || empty($billingDate) || empty($grossAmount)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }

    // Insert into invoices table
    $stmt = $conn->prepare('INSERT INTO invoices (BillingInvoiceNo, BillingDate, BilledTo, GrossAmount, VAT12, EWT2, AddTollCharges, AmountNetofTax, NetAmount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('sssdddddd', $billingInvoiceNo, $billingDate, $billedTo, $grossAmount, $vat12, $ewt2, $addTollCharges, $amountNetofTax, $netAmount); // Correct data types

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Invoice added successfully.';
    } else {
        $response['message'] = 'Failed to add invoice.';
    }
}

echo json_encode($response);
?>
