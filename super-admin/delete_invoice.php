<?php
include '../includes/db_connection.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceID = $_POST['id'];

    if (!empty($invoiceID)) {
        // Prepare delete query
        $stmt = $conn->prepare('DELETE FROM invoices WHERE InvoiceID = ?');
        $stmt->bind_param('i', $invoiceID);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Invoice deleted successfully.';
        } else {
            $response['message'] = 'Failed to delete invoice.';
        }

        $stmt->close();
    } else {
        $response['message'] = 'Invalid invoice ID.';
    }
}

$conn->close();
echo json_encode($response);
?>
