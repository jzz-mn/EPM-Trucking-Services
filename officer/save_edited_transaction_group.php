<?php
session_start();
include '../includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

header('Content-Type: application/json');

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

// Define the round_up_kgs function as per the specified rules
/**
 * Function to round up Total KGs based on the specified rules.
 * 
 * Rules:
 * - 0 < KGs <= 1199 => 1000
 * - 1200 <= KGs <= 2199 => 2000
 * - 2200 <= KGs <= 3199 => 3000
 * - 3200 <= KGs <= 4199 => 4000
 * - KGs >= 4200 => 4000 (Cap at 4000)
 *
 * @param float $kgs Total KGs
 * @return int Rounded KGs
 */
function round_up_kgs($kgs)
{
    if ($kgs <= 0) {
        return 0; // Handle as per your business logic
    }
    if ($kgs <= 1199) {
        return 1000;
    }
    if ($kgs <= 2199) {
        return 2000;
    }
    if ($kgs <= 3199) {
        return 3000;
    }
    if ($kgs <= 4199) {
        return 4000;
    }
    return 4000; // Cap at 4000
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgID = intval($_POST['TransactionGroupID'] ?? 0);
    $TruckID = intval($_POST['TruckID'] ?? 0);
    $Date = $_POST['Date'] ?? '';
    $TollFeeAmount = floatval($_POST['TollFeeAmount'] ?? 0);
    $RateAmount = floatval($_POST['RateAmount'] ?? 0);
    $TotalKGs = intval($_POST['TotalKGs'] ?? 0);
    $Transactions = $_POST['Transactions'] ?? [];
    $BillingInvoiceNo = intval($_POST['BillingInvoiceNo'] ?? 0);

    // Basic validation
    if ($TruckID <= 0 || empty($Date) || $BillingInvoiceNo <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please provide all required fields.']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        if ($tgID > 0) {
            // Update existing transaction group
            $updateTGQuery = "UPDATE transactiongroup SET TruckID = ?, Date = ?, TollFeeAmount = ?, RateAmount = ?, TotalKGs = ? WHERE TransactionGroupID = ?";
            $updateTGStmt = $conn->prepare($updateTGQuery);
            if (!$updateTGStmt) {
                throw new Exception("Failed to prepare transaction group update statement: " . $conn->error);
            }
            $updateTGStmt->bind_param("issdii", $TruckID, $Date, $TollFeeAmount, $RateAmount, $TotalKGs, $tgID);
            if (!$updateTGStmt->execute()) {
                throw new Exception("Failed to update transaction group: " . $updateTGStmt->error);
            }
            $updateTGStmt->close();
        } else {
            // Insert new transaction group
            $insertTGQuery = "INSERT INTO transactiongroup (TruckID, Date, TollFeeAmount, RateAmount, Amount, TotalKGs, BillingInvoiceNo) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertTGStmt = $conn->prepare($insertTGQuery);
            if (!$insertTGStmt) {
                throw new Exception("Failed to prepare transaction group insert statement: " . $conn->error);
            }
            $Amount = $TollFeeAmount + $RateAmount;
            $insertTGStmt->bind_param("issddii", $TruckID, $Date, $TollFeeAmount, $RateAmount, $Amount, $TotalKGs, $BillingInvoiceNo);
            if (!$insertTGStmt->execute()) {
                throw new Exception("Failed to insert transaction group: " . $insertTGStmt->error);
            }
            $tgID = $insertTGStmt->insert_id;
            $insertTGStmt->close();
        }

        // Handle transactions
        // First, delete existing transactions if updating
        if ($tgID > 0 && !empty($Transactions)) {
            // Delete existing transactions
            $deleteTransQuery = "DELETE FROM transactions WHERE TransactionGroupID = ?";
            $deleteTransStmt = $conn->prepare($deleteTransQuery);
            if (!$deleteTransStmt) {
                throw new Exception("Failed to prepare transactions delete statement: " . $conn->error);
            }
            $deleteTransStmt->bind_param("i", $tgID);
            if (!$deleteTransStmt->execute()) {
                throw new Exception("Failed to delete existing transactions: " . $deleteTransStmt->error);
            }
            $deleteTransStmt->close();
        }

        // Insert new transactions
        foreach ($Transactions as $trans) {
            $DRno = trim($trans['DRno'] ?? '');
            $OutletName = trim($trans['OutletName'] ?? '');
            $Qty = floatval($trans['Qty'] ?? 0);
            $KGs = floatval($trans['KGs'] ?? 0);

            if (empty($DRno) || empty($OutletName) || $Qty <= 0 || $KGs <= 0) {
                throw new Exception("All transaction details are required and must be valid.");
            }

            // Validate OutletName exists in customers
            $customerQuery = "SELECT ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?)";
            $customerStmt = $conn->prepare($customerQuery);
            if (!$customerStmt) {
                throw new Exception("Failed to prepare customer query: " . $conn->error);
            }
            $customerStmt->bind_param("s", $OutletName);
            $customerStmt->execute();
            $customerResult = $customerStmt->get_result();

            if ($customerResult->num_rows === 0) {
                throw new Exception("Outlet Name '{$OutletName}' does not exist in the Customers table.");
            }

            $customer = $customerResult->fetch_assoc();
            $ClusterID = $customer['ClusterID'];
            $customerStmt->close();

            // Fetch UnitPrice from fuel table based on Date
            $fuelQuery = "SELECT UnitPrice FROM fuel WHERE Date <= ? ORDER BY Date DESC LIMIT 1";
            $fuelStmt = $conn->prepare($fuelQuery);
            if (!$fuelStmt) {
                throw new Exception("Failed to prepare fuel query: " . $conn->error);
            }
            $fuelStmt->bind_param("s", $Date);
            $fuelStmt->execute();
            $fuelResult = $fuelStmt->get_result();

            if ($fuelResult->num_rows === 0) {
                throw new Exception("No fuel price found for the given date.");
            }

            $fuel = $fuelResult->fetch_assoc();
            $UnitPrice = floatval($fuel['UnitPrice']);
            $fuelStmt->close();

            // Round up KGs
            $roundedKGs = round_up_kgs($KGs);

            // Fetch RateAmount from clusters
            $clusterQuery = "SELECT RateAmount FROM clusters WHERE ClusterID = ? AND Tonner = ?";
            $clusterStmt = $conn->prepare($clusterQuery);
            if (!$clusterStmt) {
                throw new Exception("Failed to prepare clusters query: " . $conn->error);
            }
            $clusterStmt->bind_param("ii", $ClusterID, $roundedKGs);
            $clusterStmt->execute();
            $clusterResult = $clusterStmt->get_result();

            if ($clusterResult->num_rows === 0) {
                throw new Exception("No RateAmount found for ClusterID '{$ClusterID}' and Tonner '{$roundedKGs}'.");
            }

            $cluster = $clusterResult->fetch_assoc();
            $RateAmount = floatval($cluster['RateAmount']);
            $clusterStmt->close();

            // Insert transaction
            $insertTransQuery = "INSERT INTO transactions (TransactionGroupID, DRno, OutletName, Qty, KGs) VALUES (?, ?, ?, ?, ?)";
            $insertTransStmt = $conn->prepare($insertTransQuery);
            if (!$insertTransStmt) {
                throw new Exception("Failed to prepare transaction insert statement: " . $conn->error);
            }
            $insertTransStmt->bind_param("issdd", $tgID, $DRno, $OutletName, $Qty, $KGs);
            if (!$insertTransStmt->execute()) {
                throw new Exception("Failed to insert transaction: " . $insertTransStmt->error);
            }
            $insertTransStmt->close();
        }

        // If no transactions are associated, delete the transaction group
        if (empty($Transactions) && $tgID > 0) {
            // Delete transaction group
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
        }

        // Recalculate totals
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
        insert_activity_log($conn, $_SESSION['UserID'], ($tgID > 0) ? "Updated Transaction Group ID: $tgID" : "Added Transaction Group ID: $tgID");

        echo json_encode(['success' => true, 'message' => 'Transaction group saved successfully.']);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>