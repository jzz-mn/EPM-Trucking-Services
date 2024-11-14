<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tg_id = intval($_POST['TransactionGroupID'] ?? 0);

    if ($tg_id <= 0) {
        $response['success'] = false;
        $response['message'] = 'Invalid Transaction Group ID.';
        echo json_encode($response);
        exit();
    }

    // Fetch Transaction Group Details
    $tg_query = "SELECT * FROM transactiongroup WHERE TransactionGroupID = ?";
    $stmt = $conn->prepare($tg_query);
    $stmt->bind_param("i", $tg_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $tg = $result->fetch_assoc();

            // Fetch associated transactions
            $tx_query = "SELECT * FROM transactions WHERE TransactionGroupID = ?";
            $stmt_tx = $conn->prepare($tx_query);
            $stmt_tx->bind_param("i", $tg_id);
            $stmt_tx->execute();
            $tx_result = $stmt_tx->get_result();
            $transactions = [];
            while ($tx = $tx_result->fetch_assoc()) {
                $transactions[] = $tx;
            }
            $stmt_tx->close();

            $response['success'] = true;
            $response['transactionGroup'] = $tg;
            $response['transactions'] = $transactions;
        } else {
            $response['success'] = false;
            $response['message'] = 'Transaction Group not found.';
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to execute Transaction Group query: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
$conn->close();
?>
