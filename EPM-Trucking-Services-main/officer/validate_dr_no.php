<?php
include '../includes/db_connection.php';

// Set header to return JSON
header('Content-Type: application/json');

if (isset($_GET['dr_no'])) {
    $dr_no = trim($_GET['dr_no']);
    $transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;

    if ($transaction_id > 0) {
        // Prepare and execute the SQL statement safely, excluding the current transaction
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE DRno = ? AND TransactionID != ?");
        if ($stmt === false) {
            echo json_encode(['exists' => false, 'error' => 'Failed to prepare statement.']);
            exit();
        }
        $stmt->bind_param("si", $dr_no, $transaction_id);
    } else {
        // If no transaction_id is provided, check for any existing DRno
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions WHERE DRno = ?");
        if ($stmt === false) {
            echo json_encode(['exists' => false, 'error' => 'Failed to prepare statement.']);
            exit();
        }
        $stmt->bind_param("s", $dr_no);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
} else {
    echo json_encode(['exists' => false, 'error' => 'DR No not provided.']);
}

$conn->close();
?>
