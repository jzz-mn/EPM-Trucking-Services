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
        exit;
    }

    // Fetch Transaction Group Details
    $tg_query = "SELECT * FROM transactiongroup WHERE TransactionGroupID = ?";
    $stmt = $conn->prepare($tg_query);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare Transaction Group query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("i", $tg_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $tg = $result->fetch_assoc();
            $stmt->close();

            // Fetch associated transactions
            $tx_query = "SELECT * FROM transactions WHERE TransactionGroupID = ?";
            $stmt_tx = $conn->prepare($tx_query);
            if (!$stmt_tx) {
                $response['success'] = false;
                $response['message'] = 'Failed to prepare Transactions fetch query.';
                echo json_encode($response);
                exit();
            }
            $stmt_tx->bind_param("i", $tg_id);
            $stmt_tx->execute();
            $tx_result = $stmt_tx->get_result();
            $transactions = [];
            while ($tx = $tx_result->fetch_assoc()) {
                $transactions[] = $tx;
            }
            $stmt_tx->close();

            // Fetch ClusterID based on the first transaction's OutletName
            if (count($transactions) > 0) {
                $firstOutletName = $transactions[0]['OutletName'];
                $clusterIDQuery = "
                    SELECT c.ClusterID
                    FROM transactiongroup tg
                    JOIN transactions t ON tg.TransactionGroupID = t.TransactionGroupID
                    JOIN customers c ON LOWER(c.CustomerName) = LOWER(t.OutletName)
                    WHERE tg.TransactionGroupID = ?
                    LIMIT 1
                ";
                $stmt_cluster = $conn->prepare($clusterIDQuery);
                if (!$stmt_cluster) {
                    $response['success'] = false;
                    $response['message'] = 'Failed to prepare ClusterID fetch query: ' . $conn->error;
                    echo json_encode($response);
                    exit();
                }
                $stmt_cluster->bind_param("i", $tg_id);
                $stmt_cluster->execute();
                $clusterResult = $stmt_cluster->get_result();
                if ($clusterResult->num_rows > 0) {
                    $clusterRow = $clusterResult->fetch_assoc();
                    $ClusterID = $clusterRow['ClusterID'];
                } else {
                    $ClusterID = null; // Or handle as per your logic
                }
                $stmt_cluster->close();
            } else {
                $ClusterID = null; // No transactions found
            }

            $response['success'] = true;
            $response['transactionGroup'] = $tg;
            $response['transactions'] = $transactions;
            $response['ClusterID'] = $ClusterID; // Include ClusterID in the response
        } else {
            $response['success'] = false;
            $response['message'] = 'Transaction Group not found.';
            $stmt->close();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to execute Transaction Group query: ' . $stmt->error;
        $stmt->close();
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
$conn->close();
?>
