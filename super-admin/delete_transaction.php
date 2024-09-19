<?php
include '../includes/db_connection.php';
header('Content-Type: application/json');

$response = array('success' => false, 'message' => 'Unknown error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionID = $_POST['id'];

    if (!empty($transactionID)) {
        // Prepare the DELETE SQL query
        $stmt = $conn->prepare('DELETE FROM transactions WHERE TransactionID = ?');
        if ($stmt === false) {
            // Log error if prepare fails
            $response['message'] = 'Prepare failed: ' . $conn->error;
        } else {
            $stmt->bind_param('i', $transactionID);
            if ($stmt->execute()) {
                // If delete is successful
                $response['success'] = true;
                $response['message'] = 'Transaction deleted successfully.';
            } else {
                // If there's an error during execution, log it
                $response['message'] = 'Execute failed: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $response['message'] = 'Invalid transaction ID.';
    }
}

$conn->close();
echo json_encode($response);
