<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Function to insert activity logs
function insert_activity_log($conn, $userID, $action)
{
    $current_timestamp = date("Y-m-d H:i:s");

    $insert_sql = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
    if ($insert_stmt = $conn->prepare($insert_sql)) {
        $insert_stmt->bind_param("iss", $userID, $action, $current_timestamp);
        if (!$insert_stmt->execute()) {
            error_log("Failed to insert activity log: " . $insert_stmt->error);
        }
        $insert_stmt->close();
    } else {
        error_log("Failed to prepare activity log insertion: " . $conn->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgID = intval($_POST['TransactionGroupID'] ?? 0);
    if ($tgID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Transaction Group ID.']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // Fetch BillingInvoiceNo before deletion for updating totals
        $fetchTGQuery = "SELECT BillingInvoiceNo FROM transactiongroup WHERE TransactionGroupID = ?";
        $fetchTGStmt = $conn->prepare($fetchTGQuery);
        if (!$fetchTGStmt) {
            throw new Exception("Failed to prepare fetch TG query: " . $conn->error);
        }
        $fetchTGStmt->bind_param("i", $tgID);
        $fetchTGStmt->execute();
        $fetchTGResult = $fetchTGStmt->get_result();
        if ($fetchTGResult->num_rows === 0) {
            throw new Exception("Transaction Group not found.");
        }
        $tg = $fetchTGResult->fetch_assoc();
        $BillingInvoiceNo = $tg['BillingInvoiceNo'];
        $fetchTGStmt->close();

        // Delete transactions associated with this group
        $deleteTransQuery = "DELETE FROM transactions WHERE TransactionGroupID = ?";
        $deleteTransStmt = $conn->prepare($deleteTransQuery);
        if (!$deleteTransStmt) {
            throw new Exception("Failed to prepare transactions delete statement: " . $conn->error);
        }
        $deleteTransStmt->bind_param("i", $tgID);
        if (!$deleteTransStmt->execute()) {
            throw new Exception("Failed to delete transactions: " . $deleteTransStmt->error);
        }
        $deleteTransStmt->close();

        // Delete the transaction group
        $deleteTGQuery = "DELETE FROM transactiongroup WHERE TransactionGroupID = ?";
        $deleteTGStmt = $conn->prepare($deleteTGQuery);
        if (!$deleteTGStmt) {
            throw new Exception("Failed to prepare transaction group delete statement: " . $conn->error);
        }
        $deleteTGStmt->bind_param("i", $tgID);
        if (!$deleteTGStmt->execute()) {
            throw new Exception("Failed to delete transaction group: " . $deleteTGStmt->error);
        }
        $deleteTGStmt->close();

        // Recalculate totals for the invoice
        $totalsQuery = "SELECT SUM(RateAmount) AS GrossAmount, SUM(TollFeeAmount) AS TotalExpenses FROM transactiongroup WHERE BillingInvoiceNo = ?";
        $totalsStmt = $conn->prepare($totalsQuery);
        if (!$totalsStmt) {
            throw new Exception("Failed to prepare totals query: " . $conn->error);
        }
        $totalsStmt->bind_param("i", $BillingInvoiceNo);
        $totalsStmt->execute();
        $totalsResult = $totalsStmt->get_result();

        $grossAmount = 0;
        $totalExpenses = 0;
        if ($totalsResult->num_rows > 0) {
            $totals = $totalsResult->fetch_assoc();
            $grossAmount = floatval($totals['GrossAmount']);
            $totalExpenses = floatval($totals['TotalExpenses']);
        }
        $totalsStmt->close();

        $vat = $grossAmount * 0.12;
        $totalAmount = $grossAmount + $vat;
        $ewt = $totalAmount * 0.02;
        $amountNetOfTax = $totalAmount - $ewt;
        $netAmount = $amountNetOfTax + $totalExpenses;

        // Update invoice totals
        $updateTotalsQuery = "UPDATE invoices SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, AmountNetOfTax = ?, NetAmount = ? WHERE BillingInvoiceNo = ?";
        $updateTotalsStmt = $conn->prepare($updateTotalsQuery);
        if (!$updateTotalsStmt) {
            throw new Exception("Failed to prepare invoice totals update statement: " . $conn->error);
        }
        $updateTotalsStmt->bind_param("dddddddd", $grossAmount, $vat, $totalAmount, $ewt, $totalExpenses, $amountNetOfTax, $netAmount, $BillingInvoiceNo);
        if (!$updateTotalsStmt->execute()) {
            throw new Exception("Failed to update invoice totals: " . $updateTotalsStmt->error);
        }
        $updateTotalsStmt->close();

        // Commit transaction
        $conn->commit();

        // Log the activity
        insert_activity_log($conn, $_SESSION['UserID'], "Deleted Transaction Group ID: $tgID");

        echo json_encode(['success' => true, 'message' => 'Transaction group deleted successfully.']);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>