<?php
session_start();
include '../includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['TransactionGroupID'])) {
    $transactionGroupID = intval($_POST['TransactionGroupID']);

    // Fetch Transaction Group Details
    $tgQuery = "SELECT * FROM transactiongroup WHERE TransactionGroupID = ?";
    $stmt = $conn->prepare($tgQuery);
    $stmt->bind_param("i", $transactionGroupID);
    $stmt->execute();
    $tgResult = $stmt->get_result();

    if ($tgResult->num_rows > 0) {
        $tg = $tgResult->fetch_assoc();

        // Fetch related transactions
        $txQuery = "SELECT * FROM transactions WHERE TransactionGroupID = ?";
        $stmtTx = $conn->prepare($txQuery);
        $stmtTx->bind_param("i", $transactionGroupID);
        $stmtTx->execute();
        $txResult = $stmtTx->get_result();

        $transactions = [];
        while ($tx = $txResult->fetch_assoc()) {
            $transactions[] = $tx;
        }

        echo json_encode([
            'success' => true,
            'transactionGroup' => $tg,
            'transactions' => $transactions
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction Group not found.']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid Request.']);
?>
