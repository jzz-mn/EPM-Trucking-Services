<?php
session_start();
include '../includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login/login.php");
    exit();
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

// Helper function to calculate amounts with date range
function calculate_amounts($conn, $billingStartDate, $billingEndDate)
{
    // Fetch GrossAmount and AddTollCharges within the date range
    $query = "SELECT 
                SUM(tg.RateAmount) as GrossAmount, 
                SUM(tg.TollFeeAmount) as AddTollCharges
              FROM transactiongroup tg
              WHERE tg.Date BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare amounts calculation query: ' . $conn->error);
    }
    $stmt->bind_param("ss", $billingStartDate, $billingEndDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $amounts = $result->fetch_assoc();
    $stmt->close();

    $grossAmount = $amounts['GrossAmount'] ?? 0;
    $addTollCharges = $amounts['AddTollCharges'] ?? 0;

    // Calculate VAT, TotalAmount, EWT, AmountNetOfTax, NetAmount
    $vat = $grossAmount * 0.12;
    $totalAmount = $grossAmount + $vat;
    $ewt = $totalAmount * 0.02;
    $amountNetOfTax = $totalAmount - $ewt;
    $netAmount = $amountNetOfTax + $addTollCharges;

    return [
        'GrossAmount' => $grossAmount,
        'VAT' => $vat,
        'TotalAmount' => $totalAmount,
        'EWT' => $ewt,
        'AddTollCharges' => $addTollCharges,
        'AmountNetOfTax' => $amountNetOfTax,
        'NetAmount' => $netAmount
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $userID = $_SESSION['UserID'];

        // Update Invoice Action
        if ($_POST['action'] == 'update_invoice') {
            $billingInvoiceNo = intval($_POST['BillingInvoiceNo']);
            $billingStartDate = $_POST['BillingStartDate'] ?? '';
            $billingEndDate = $_POST['BillingEndDate'] ?? '';
            $billedTo = $_POST['BilledTo'] ?? '';

            // Validation
            if (empty($billingStartDate) || empty($billingEndDate) || empty($billedTo)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                exit;
            }
            if ($billingStartDate > $billingEndDate) {
                echo json_encode(['success' => false, 'message' => 'Billing Start Date cannot be after Billing End Date.']);
                exit;
            }

            // Check for overlapping dates excluding current invoice
            $overlapQuery = "
                SELECT COUNT(*) as overlap_count
                FROM invoices
                WHERE BillingInvoiceNo != ?
                  AND (BillingStartDate <= ?)
                  AND (BillingEndDate >= ?)
            ";
            $stmt = $conn->prepare($overlapQuery);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare overlapping dates query.']);
                exit;
            }
            $stmt->bind_param("iss", $billingInvoiceNo, $billingEndDate, $billingStartDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $overlapRow = $result->fetch_assoc();
            if ($overlapRow['overlap_count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Date range overlaps with another invoice.']);
                exit;
            }

            // Begin Transaction
            $conn->begin_transaction();
            try {
                // Update the invoice record
                $updateInvoiceQuery = "
                    UPDATE invoices
                    SET BillingStartDate = ?, BillingEndDate = ?, BilledTo = ?
                    WHERE BillingInvoiceNo = ?
                ";
                $stmt = $conn->prepare($updateInvoiceQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare invoice update query: ' . $conn->error);
                }
                $stmt->bind_param("sssi", $billingStartDate, $billingEndDate, $billedTo, $billingInvoiceNo);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update invoice: ' . $stmt->error);
                }
                $stmt->close();

                // Update related transaction groups
                // First, set BillingInvoiceNo to NULL where the transaction group's date is now outside the new range
                $resetTGQuery = "
                    UPDATE transactiongroup
                    SET BillingInvoiceNo = NULL
                    WHERE BillingInvoiceNo = ?
                      AND (Date < ? OR Date > ?)
                ";
                $stmt = $conn->prepare($resetTGQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare transaction groups reset query: ' . $conn->error);
                }
                $stmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to reset transaction groups: ' . $stmt->error);
                }
                $stmt->close();

                // Then, set BillingInvoiceNo for transaction groups within the new date range
                $updateTGQuery = "
                    UPDATE transactiongroup
                    SET BillingInvoiceNo = ?
                    WHERE Date BETWEEN ? AND ?
                ";
                $stmt = $conn->prepare($updateTGQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare transaction groups update query: ' . $conn->error);
                }
                $stmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update transaction groups: ' . $stmt->error);
                }
                $stmt->close();

                // Recalculate invoice amounts with the updated date range
                $amounts = calculate_amounts($conn, $billingStartDate, $billingEndDate);
                $updateAmountsQuery = "
                    UPDATE invoices
                    SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                        AmountNetOfTax = ?, NetAmount = ?
                    WHERE BillingInvoiceNo = ?
                ";
                $stmt = $conn->prepare($updateAmountsQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare invoice amounts update query: ' . $conn->error);
                }
                $stmt->bind_param(
                    "dddddddi",
                    $amounts['GrossAmount'],
                    $amounts['VAT'],
                    $amounts['TotalAmount'],
                    $amounts['EWT'],
                    $amounts['AddTollCharges'],
                    $amounts['AmountNetOfTax'],
                    $amounts['NetAmount'],
                    $billingInvoiceNo
                );
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update invoice amounts: ' . $stmt->error);
                }
                $stmt->close();

                // Commit Transaction
                $conn->commit();

                // Log Activity
                insert_activity_log($conn, $userID, "Updated Invoice No: $billingInvoiceNo");

                echo json_encode(['success' => true, 'message' => 'Invoice updated successfully.', 'amounts' => $amounts]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // Fetch Transaction Groups based on Date Range
        if ($_POST['action'] == 'fetch_transaction_groups') {
            $billingStartDate = $_POST['BillingStartDate'] ?? '';
            $billingEndDate = $_POST['BillingEndDate'] ?? '';

            // Validate Date Inputs
            if (empty($billingStartDate) || empty($billingEndDate)) {
                echo json_encode(['success' => false, 'message' => 'Billing Start Date and End Date are required.']);
                exit;
            }

            if ($billingStartDate > $billingEndDate) {
                echo json_encode(['success' => false, 'message' => 'Billing Start Date cannot be after Billing End Date.']);
                exit;
            }

            // Fetch Transaction Groups within the date range, regardless of BillingInvoiceNo
            $tgQuery = "SELECT * FROM transactiongroup WHERE Date BETWEEN ? AND ?";
            $stmt = $conn->prepare($tgQuery);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare transaction groups fetch query.']);
                exit;
            }
            $stmt->bind_param("ss", $billingStartDate, $billingEndDate);
            $stmt->execute();
            $tgResult = $stmt->get_result();

            $transactionGroups = [];
            while ($row = $tgResult->fetch_assoc()) {
                $transactionGroups[] = $row;
            }
            $stmt->close();

            // Calculate updated amounts based on the current date range
            $amounts = calculate_amounts($conn, $billingStartDate, $billingEndDate);

            echo json_encode(['success' => true, 'transactionGroups' => $transactionGroups, 'amounts' => $amounts]);
            exit;
        }

        // Update Transaction Group
        if ($_POST['action'] == 'update_transaction_group') {
            $transactionGroupID = intval($_POST['TransactionGroupID']);
            $TruckID = intval($_POST['TruckID']);
            $Date = $_POST['Date'] ?? '';
            $TollFeeAmount = floatval($_POST['TollFeeAmount']);

            // Validation
            if (empty($Date)) {
                echo json_encode(['success' => false, 'message' => 'Date is required.']);
                exit;
            }

            // Begin Transaction
            $conn->begin_transaction();
            try {
                // Update Transaction Group
                $updateTGQuery = "
                    UPDATE transactiongroup
                    SET TruckID = ?, Date = ?, TollFeeAmount = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateTGQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare transaction group update query: ' . $conn->error);
                }
                $stmt->bind_param("isdi", $TruckID, $Date, $TollFeeAmount, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update transaction group: ' . $stmt->error);
                }
                $stmt->close();

                // Recalculate RateAmount and FuelPrice by calling get_fuel_price_and_rate.php
                // Since PHP cannot make HTTP requests to itself easily, we'll implement the logic directly here
                // Alternatively, you can use cURL or other methods to call the PHP script, but it's more efficient to handle it here

                // Fetch the first transaction's Outlet Name and other necessary data
                $outletQuery = "
                    SELECT t.OutletName, tg.ExpenseID, tg.TotalKGs
                    FROM transactions t
                    JOIN transactiongroup tg ON t.TransactionGroupID = tg.TransactionGroupID
                    WHERE tg.TransactionGroupID = ?
                    LIMIT 1
                ";
                $stmt = $conn->prepare($outletQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare Outlet Name fetch query.');
                }
                $stmt->bind_param("i", $transactionGroupID);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    throw new Exception('No transactions found for this Transaction Group.');
                }

                $row = $result->fetch_assoc();
                $outletName = $row['OutletName'];
                $expenseID = $row['ExpenseID'];
                $totalKGs = floatval($row['TotalKGs']);
                $stmt->close();

                // Round TotalKGs to determine Tonner
                $rounded_total_kgs = 0;
                if ($totalKGs > 0) {
                    if ($totalKGs <= 1199) {
                        $rounded_total_kgs = 1000;
                    } else if ($totalKGs <= 4199) {
                        $rounded_total_kgs = ceil($totalKGs / 1000) * 1000;
                        if ($rounded_total_kgs > 4000) {  // Ensure it doesn’t exceed 4000
                            $rounded_total_kgs = 4000;
                        }
                    } else {
                        $rounded_total_kgs = 4000;
                    }
                }

                // Fetch FuelID from expenses table using ExpenseID
                $fuelIDQuery = "SELECT FuelID FROM expenses WHERE ExpenseID = ?";
                $stmt = $conn->prepare($fuelIDQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare FuelID fetch query.');
                }
                $stmt->bind_param("i", $expenseID);
                $stmt->execute();
                $fuelIDResult = $stmt->get_result();

                if ($fuelIDResult->num_rows == 0) {
                    throw new Exception('No FuelID found for the given ExpenseID.');
                }

                $fuelRow = $fuelIDResult->fetch_assoc();
                $fuelID = $fuelRow['FuelID'];
                $stmt->close();

                // Fetch FuelPrice from fuel table using FuelID
                $fuelPriceQuery = "SELECT Amount FROM fuel WHERE FuelID = ?";
                $stmt = $conn->prepare($fuelPriceQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare FuelPrice fetch query.');
                }
                $stmt->bind_param("i", $fuelID);
                $stmt->execute();
                $fuelPriceResult = $stmt->get_result();

                if ($fuelPriceResult->num_rows == 0) {
                    throw new Exception('No FuelPrice found for the given FuelID.');
                }

                $fuelPriceRow = $fuelPriceResult->fetch_assoc();
                $fuelPrice = floatval($fuelPriceRow['Amount']);
                $stmt->close();

                // Now, fetch ClusterID from customers table using OutletName
                $clusterQuery = "SELECT ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?) LIMIT 1";
                $stmt = $conn->prepare($clusterQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare ClusterID fetch query.');
                }
                $stmt->bind_param("s", $outletName);
                $stmt->execute();
                $clusterResult = $stmt->get_result();

                if ($clusterResult->num_rows == 0) {
                    throw new Exception("The Outlet Name '{$outletName}' does not exist in the Customers table.");
                }

                $clusterRow = $clusterResult->fetch_assoc();
                $clusterID = $clusterRow['ClusterID'];
                $stmt->close();

                // Now, fetch RateAmount from clusters table using ClusterID, FuelPrice, and Tonner
                $rateQuery = "SELECT RateAmount FROM clusters WHERE ClusterID = ? AND FuelPrice = ? AND Tonner = ? LIMIT 1";
                $stmt = $conn->prepare($rateQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare RateAmount fetch query.');
                }
                $stmt->bind_param("idi", $clusterID, $fuelPrice, $rounded_total_kgs);
                $stmt->execute();
                $rateResult = $stmt->get_result();

                if ($rateResult->num_rows == 0) {
                    throw new Exception("No RateAmount found for ClusterID '{$clusterID}', FuelPrice '{$fuelPrice}', and Tonner '{$rounded_total_kgs}'.");
                }

                $rateRow = $rateResult->fetch_assoc();
                $rateAmount = floatval($rateRow['RateAmount']);
                $stmt->close();

                // Update RateAmount and FuelPrice in Transaction Group
                $updateRateQuery = "
                    UPDATE transactiongroup
                    SET RateAmount = ?, FuelPrice = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateRateQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare RateAmount and FuelPrice update query.');
                }
                $stmt->bind_param("ddi", $rateAmount, $fuelPrice, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update RateAmount and FuelPrice: ' . $stmt->error);
                }
                $stmt->close();

                // Update Amount = TollFeeAmount + RateAmount
                $Amount = $TollFeeAmount + $rateAmount;

                // Update Amount in Transaction Group
                $updateAmountQuery = "
                    UPDATE transactiongroup
                    SET Amount = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateAmountQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare Amount update query.');
                }
                $stmt->bind_param("di", $Amount, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update Amount: ' . $stmt->error);
                }
                $stmt->close();

                // Recalculate TotalKGs based on related transactions
                $kgQuery = "
                    SELECT SUM(KGs) as TotalKGs
                    FROM transactions
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($kgQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare TotalKGs calculation query.');
                }
                $stmt->bind_param("i", $transactionGroupID);
                $stmt->execute();
                $kgResult = $stmt->get_result();
                $kgRow = $kgResult->fetch_assoc();
                $TotalKGs = $kgRow['TotalKGs'] ?? 0;
                $stmt->close();

                // Update TotalKGs
                $updateKGsQuery = "
                    UPDATE transactiongroup
                    SET TotalKGs = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateKGsQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare TotalKGs update query.');
                }
                $stmt->bind_param("di", $TotalKGs, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update TotalKGs: ' . $stmt->error);
                }
                $stmt->close();

                // Recalculate invoice amounts based on updated Transaction Group
                // Fetch BillingInvoiceNo from the Transaction Group
                $fetchInvoiceNoQuery = "
                    SELECT BillingInvoiceNo
                    FROM transactiongroup
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($fetchInvoiceNoQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare BillingInvoiceNo fetch query.');
                }
                $stmt->bind_param("i", $transactionGroupID);
                $stmt->execute();
                $invoiceResult = $stmt->get_result();
                $invoiceRow = $invoiceResult->fetch_assoc();
                $billingInvoiceNo = $invoiceRow['BillingInvoiceNo'] ?? null;
                $stmt->close();

                if ($billingInvoiceNo) {
                    // Fetch BillingStartDate and BillingEndDate from invoices
                    $fetchDateRangeQuery = "SELECT BillingStartDate, BillingEndDate FROM invoices WHERE BillingInvoiceNo = ?";
                    $stmt = $conn->prepare($fetchDateRangeQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare date range fetch query.');
                    }
                    $stmt->bind_param("i", $billingInvoiceNo);
                    $stmt->execute();
                    $dateResult = $stmt->get_result();
                    $dateRow = $dateResult->fetch_assoc();
                    $billingStartDate = $dateRow['BillingStartDate'] ?? '';
                    $billingEndDate = $dateRow['BillingEndDate'] ?? '';
                    $stmt->close();

                    // Recalculate amounts based on the updated date range
                    $amounts = calculate_amounts($conn, $billingStartDate, $billingEndDate);

                    // Update invoice amounts
                    $updateAmountsQuery = "
                        UPDATE invoices
                        SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                            AmountNetOfTax = ?, NetAmount = ?
                        WHERE BillingInvoiceNo = ?
                    ";
                    $stmt = $conn->prepare($updateAmountsQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare invoice amounts update query: ' . $conn->error);
                    }
                    $stmt->bind_param(
                        "dddddddi",
                        $amounts['GrossAmount'],
                        $amounts['VAT'],
                        $amounts['TotalAmount'],
                        $amounts['EWT'],
                        $amounts['AddTollCharges'],
                        $amounts['AmountNetOfTax'],
                        $amounts['NetAmount'],
                        $billingInvoiceNo
                    );
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to update invoice amounts: ' . $stmt->error);
                    }
                    $stmt->close();
                }

                // Commit Transaction
                $conn->commit();

                // Log Activity
                insert_activity_log($conn, $userID, "Updated Transaction Group ID: $transactionGroupID");

                echo json_encode(['success' => true, 'message' => 'Transaction group updated successfully.', 'Amount' => $Amount, 'TotalKGs' => $TotalKGs]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // Fetch Transactions for a Transaction Group
        if ($_POST['action'] == 'fetch_transactions') {
            $transactionGroupID = intval($_POST['TransactionGroupID']);

            $query = "
                SELECT *
                FROM transactions
                WHERE TransactionGroupID = ?
            ";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare transactions fetch query.']);
                exit;
            }
            $stmt->bind_param("i", $transactionGroupID);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
            $stmt->close();

            echo json_encode(['success' => true, 'transactions' => $transactions]);
            exit;
        }

        // Update Transaction
        if ($_POST['action'] == 'update_transaction') {
            $transactionID = intval($_POST['TransactionID']);
            $DRno = trim($_POST['DRno']);
            $OutletName = trim($_POST['OutletName']);
            $Qty = floatval($_POST['Qty']);
            $KGs = floatval($_POST['KGs']);

            // Validation
            if (empty($DRno) || empty($OutletName)) {
                echo json_encode(['success' => false, 'message' => 'DR No and Outlet Name are required.']);
                exit;
            }

            // Begin Transaction
            $conn->begin_transaction();
            try {
                // Check if DR No already exists (excluding current transaction)
                $checkDRnoQuery = "SELECT COUNT(*) as count FROM transactions WHERE DRno = ? AND TransactionID != ?";
                $stmt = $conn->prepare($checkDRnoQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare DR No check query: ' . $conn->error);
                }
                $stmt->bind_param("si", $DRno, $transactionID);
                $stmt->execute();
                $result = $stmt->get_result();
                $drNoCount = $result->fetch_assoc()['count'];
                $stmt->close();

                if ($drNoCount > 0) {
                    throw new Exception('DR No already exists. Please enter a unique DR No.');
                }

                // Update Transaction
                $updateTransactionQuery = "
                    UPDATE transactions
                    SET DRno = ?, OutletName = ?, Qty = ?, KGs = ?
                    WHERE TransactionID = ?
                ";
                $stmt = $conn->prepare($updateTransactionQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare transaction update query: ' . $conn->error);
                }
                $stmt->bind_param("ssddi", $DRno, $OutletName, $Qty, $KGs, $transactionID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update transaction: ' . $stmt->error);
                }
                $stmt->close();

                // Fetch TransactionGroupID
                $tgQuery = "SELECT TransactionGroupID FROM transactions WHERE TransactionID = ?";
                $stmt = $conn->prepare($tgQuery);
                if (!$stmt) {
                    throw new Exception('Failed to prepare TransactionGroupID fetch query: ' . $conn->error);
                }
                $stmt->bind_param("i", $transactionID);
                $stmt->execute();
                $tgResult = $stmt->get_result();
                $tgRow = $tgResult->fetch_assoc();
                $transactionGroupID = $tgRow['TransactionGroupID'] ?? null;
                $stmt->close();

                if ($transactionGroupID) {
                    // Recalculate TotalKGs
                    $kgQuery = "
                        SELECT SUM(KGs) as TotalKGs
                        FROM transactions
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($kgQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare TotalKGs calculation query: ' . $conn->error);
                    }
                    $stmt->bind_param("i", $transactionGroupID);
                    $stmt->execute();
                    $kgResult = $stmt->get_result();
                    $kgRow = $kgResult->fetch_assoc();
                    $TotalKGs = $kgRow['TotalKGs'] ?? 0;
                    $stmt->close();

                    // Update TotalKGs in transactiongroup
                    $updateKGsQuery = "
                        UPDATE transactiongroup
                        SET TotalKGs = ?
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($updateKGsQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare TotalKGs update query: ' . $conn->error);
                    }
                    $stmt->bind_param("di", $TotalKGs, $transactionGroupID);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to update TotalKGs: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Recalculate RateAmount and FuelPrice
                    // Fetch the updated TransactionGroup data
                    $fetchTGQuery = "SELECT * FROM transactiongroup WHERE TransactionGroupID = ?";
                    $stmt = $conn->prepare($fetchTGQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare TransactionGroup fetch query: ' . $conn->error);
                    }
                    $stmt->bind_param("i", $transactionGroupID);
                    $stmt->execute();
                    $tgResult = $stmt->get_result();
                    if ($tgResult->num_rows == 0) {
                        throw new Exception('Transaction Group not found.');
                    }
                    $tgRow = $tgResult->fetch_assoc();
                    $TruckID = $tgRow['TruckID'];
                    $Date = $tgRow['Date'];
                    $TollFeeAmount = floatval($tgRow['TollFeeAmount']);
                    $stmt->close();

                    // Fetch the Outlet Name from the first transaction
                    $outletQuery = "
                        SELECT t.OutletName
                        FROM transactions t
                        WHERE t.TransactionGroupID = ?
                        LIMIT 1
                    ";
                    $stmt = $conn->prepare($outletQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare Outlet Name fetch query.');
                    }
                    $stmt->bind_param("i", $transactionGroupID);
                    $stmt->execute();
                    $outletResult = $stmt->get_result();
                    if ($outletResult->num_rows == 0) {
                        throw new Exception('No transactions found for this Transaction Group.');
                    }
                    $outletRow = $outletResult->fetch_assoc();
                    $outletName = $outletRow['OutletName'];
                    $stmt->close();

                    // Fetch ExpenseID from transactiongroup
                    $expenseID = $tgRow['ExpenseID'];

                    // Round TotalKGs to determine Tonner
                    $rounded_total_kgs = 0;
                    if ($TotalKGs > 0) {
                        if ($TotalKGs <= 1199) {
                            $rounded_total_kgs = 1000;
                        } else if ($TotalKGs <= 4199) {
                            $rounded_total_kgs = ceil($TotalKGs / 1000) * 1000;
                            if ($rounded_total_kgs > 4000) {  // Ensure it doesn’t exceed 4000
                                $rounded_total_kgs = 4000;
                            }
                        } else {
                            $rounded_total_kgs = 4000;
                        }
                    }

                    // Fetch FuelID from expenses table using ExpenseID
                    $fuelIDQuery = "SELECT FuelID FROM expenses WHERE ExpenseID = ?";
                    $stmt = $conn->prepare($fuelIDQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare FuelID fetch query.');
                    }
                    $stmt->bind_param("i", $expenseID);
                    $stmt->execute();
                    $fuelIDResult = $stmt->get_result();

                    if ($fuelIDResult->num_rows == 0) {
                        throw new Exception('No FuelID found for the given ExpenseID.');
                    }

                    $fuelRow = $fuelIDResult->fetch_assoc();
                    $fuelID = $fuelRow['FuelID'];
                    $stmt->close();

                    // Fetch FuelPrice from fuel table using FuelID
                    $fuelPriceQuery = "SELECT Amount FROM fuel WHERE FuelID = ?";
                    $stmt = $conn->prepare($fuelPriceQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare FuelPrice fetch query.');
                    }
                    $stmt->bind_param("i", $fuelID);
                    $stmt->execute();
                    $fuelPriceResult = $stmt->get_result();

                    if ($fuelPriceResult->num_rows == 0) {
                        throw new Exception('No FuelPrice found for the given FuelID.');
                    }

                    $fuelPriceRow = $fuelPriceResult->fetch_assoc();
                    $fuelPrice = floatval($fuelPriceRow['Amount']);
                    $stmt->close();

                    // Now, fetch ClusterID from customers table using OutletName
                    $clusterQuery = "SELECT ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?) LIMIT 1";
                    $stmt = $conn->prepare($clusterQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare ClusterID fetch query.');
                    }
                    $stmt->bind_param("s", $outletName);
                    $stmt->execute();
                    $clusterResult = $stmt->get_result();

                    if ($clusterResult->num_rows == 0) {
                        throw new Exception("The Outlet Name '{$outletName}' does not exist in the Customers table.");
                    }

                    $clusterRow = $clusterResult->fetch_assoc();
                    $clusterID = $clusterRow['ClusterID'];
                    $stmt->close();

                    // Now, fetch RateAmount from clusters table using ClusterID, FuelPrice, and Tonner
                    $rateQuery = "SELECT RateAmount FROM clusters WHERE ClusterID = ? AND FuelPrice = ? AND Tonner = ? LIMIT 1";
                    $stmt = $conn->prepare($rateQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare RateAmount fetch query.');
                    }
                    $stmt->bind_param("idi", $clusterID, $fuelPrice, $rounded_total_kgs);
                    $stmt->execute();
                    $rateResult = $stmt->get_result();

                    if ($rateResult->num_rows == 0) {
                        throw new Exception("No RateAmount found for ClusterID '{$clusterID}', FuelPrice '{$fuelPrice}', and Tonner '{$rounded_total_kgs}'.");
                    }

                    $rateRow = $rateResult->fetch_assoc();
                    $rateAmount = floatval($rateRow['RateAmount']);
                    $stmt->close();

                    // Update RateAmount and FuelPrice in Transaction Group
                    $updateRateQuery = "
                        UPDATE transactiongroup
                        SET RateAmount = ?, FuelPrice = ?
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($updateRateQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare RateAmount and FuelPrice update query.');
                    }
                    $stmt->bind_param("ddi", $rateAmount, $fuelPrice, $transactionGroupID);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to update RateAmount and FuelPrice: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Update Amount = TollFeeAmount + RateAmount
                    $Amount = $TollFeeAmount + $rateAmount;

                    // Update Amount in Transaction Group
                    $updateAmountQuery = "
                        UPDATE transactiongroup
                        SET Amount = ?
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($updateAmountQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare Amount update query.');
                    }
                    $stmt->bind_param("di", $Amount, $transactionGroupID);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to update Amount: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Recalculate invoice amounts based on updated Transaction Group
                    // Fetch BillingInvoiceNo from the Transaction Group
                    $fetchInvoiceNoQuery = "
                        SELECT BillingInvoiceNo
                        FROM transactiongroup
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($fetchInvoiceNoQuery);
                    if (!$stmt) {
                        throw new Exception('Failed to prepare BillingInvoiceNo fetch query.');
                    }
                    $stmt->bind_param("i", $transactionGroupID);
                    $stmt->execute();
                    $invoiceResult = $stmt->get_result();
                    $invoiceRow = $invoiceResult->fetch_assoc();
                    $billingInvoiceNo = $invoiceRow['BillingInvoiceNo'] ?? null;
                    $stmt->close();

                    if ($billingInvoiceNo) {
                        // Fetch BillingStartDate and BillingEndDate from invoices
                        $fetchDateRangeQuery = "SELECT BillingStartDate, BillingEndDate FROM invoices WHERE BillingInvoiceNo = ?";
                        $stmt = $conn->prepare($fetchDateRangeQuery);
                        if (!$stmt) {
                            throw new Exception('Failed to prepare date range fetch query: ' . $conn->error);
                        }
                        $stmt->bind_param("i", $billingInvoiceNo);
                        $stmt->execute();
                        $dateResult = $stmt->get_result();
                        $dateRow = $dateResult->fetch_assoc();
                        $billingStartDate = $dateRow['BillingStartDate'] ?? '';
                        $billingEndDate = $dateRow['BillingEndDate'] ?? '';
                        $stmt->close();

                        // Recalculate amounts based on the updated date range
                        $amounts = calculate_amounts($conn, $billingStartDate, $billingEndDate);

                        // Update invoice amounts
                        $updateAmountsQuery = "
                            UPDATE invoices
                            SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                                AmountNetOfTax = ?, NetAmount = ?
                            WHERE BillingInvoiceNo = ?
                        ";
                        $stmt = $conn->prepare($updateAmountsQuery);
                        if (!$stmt) {
                            throw new Exception('Failed to prepare invoice amounts update query: ' . $conn->error);
                        }
                        $stmt->bind_param(
                            "dddddddi",
                            $amounts['GrossAmount'],
                            $amounts['VAT'],
                            $amounts['TotalAmount'],
                            $amounts['EWT'],
                            $amounts['AddTollCharges'],
                            $amounts['AmountNetOfTax'],
                            $amounts['NetAmount'],
                            $billingInvoiceNo
                        );
                        if (!$stmt->execute()) {
                            throw new Exception('Failed to update invoice amounts: ' . $stmt->error);
                        }
                        $stmt->close();
                    }

                }

                // Commit Transaction
                $conn->commit();

                // Log Activity
                insert_activity_log($conn, $userID, "Updated Transaction Group ID: $transactionGroupID");

                echo json_encode(['success' => true, 'message' => 'Transaction group updated successfully.', 'Amount' => $Amount, 'TotalKGs' => $TotalKGs]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }
}

// Fetch the BillingInvoiceNo from GET parameters
if (!isset($_GET['BillingInvoiceNo'])) {
    echo "No BillingInvoiceNo provided.";
    exit;
}

$billingInvoiceNo = intval($_GET['BillingInvoiceNo']);

// Fetch the invoice details
$invoiceQuery = "SELECT * FROM invoices WHERE BillingInvoiceNo = ?";
$stmt = $conn->prepare($invoiceQuery);
if (!$stmt) {
    echo "Failed to prepare invoice fetch query: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $billingInvoiceNo);
$stmt->execute();
$invoiceResult = $stmt->get_result();

if ($invoiceResult->num_rows == 0) {
    echo "Invoice not found.";
    exit;
}

$invoice = $invoiceResult->fetch_assoc();

// Calculate initial amounts based on the current date range
$amounts = calculate_amounts($conn, $invoice['BillingStartDate'], $invoice['BillingEndDate']);

// Close the statement
$stmt->close();

include '../officer/header.php';
?>
<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Alert Section -->
        <div id="alert-container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Invoice Edit Form -->
        <div class="card overflow-hidden">
            <div class="card-body">
                <form id="editInvoiceForm">
                    <input type="hidden" id="BillingInvoiceNo" name="BillingInvoiceNo" value="<?php echo htmlspecialchars($invoice['BillingInvoiceNo']); ?>">

                    <div class="row">
                        <!-- Service No -->
                        <div class="col-md-4 mb-3">
                            <label for="ServiceNo" class="form-label">Service No</label>
                            <input type="text" class="form-control" id="ServiceNo" name="ServiceNo" value="<?php echo htmlspecialchars($invoice['ServiceNo']); ?>" readonly>
                        </div>

                        <!-- Billed To -->
                        <div class="col-md-4 mb-3">
                            <label for="BilledTo" class="form-label">Billed To</label>
                            <select class="form-select" id="BilledTo" name="BilledTo" required>
                                <option value="">Select Client</option>
                                <option value="Bounty Plus" <?php echo ($invoice['BilledTo'] == 'Bounty Plus') ? 'selected' : ''; ?>>Bounty Plus</option>
                                <option value="Chooks to Go" <?php echo ($invoice['BilledTo'] == 'Chooks to Go') ? 'selected' : ''; ?>>Chooks to Go</option>
                                <!-- Add more clients as needed -->
                            </select>
                        </div>

                        <!-- Billing Start Date -->
                        <div class="col-md-4 mb-3">
                            <label for="BillingStartDate" class="form-label">Billing Start Date</label>
                            <input type="date" class="form-control" id="BillingStartDate" name="BillingStartDate" value="<?php echo htmlspecialchars($invoice['BillingStartDate']); ?>" required>
                        </div>

                    </div>

                    <div class="row">
                        <!-- Billing End Date -->
                        <div class="col-md-4 mb-3">
                            <label for="BillingEndDate" class="form-label">Billing End Date</label>
                            <input type="date" class="form-control" id="BillingEndDate" name="BillingEndDate" value="<?php echo htmlspecialchars($invoice['BillingEndDate']); ?>" required>
                        </div>

                        <!-- Gross Amount -->
                        <div class="col-md-4 mb-3">
                            <label for="GrossAmount" class="form-label">Gross Amount</label>
                            <input type="text" class="form-control" id="GrossAmount" name="GrossAmount" value="<?php echo number_format($amounts['GrossAmount'], 2); ?>" readonly>
                        </div>

                        <!-- VAT -->
                        <div class="col-md-4 mb-3">
                            <label for="VAT" class="form-label">VAT (12%)</label>
                            <input type="text" class="form-control" id="VAT" name="VAT" value="<?php echo number_format($amounts['VAT'], 2); ?>" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Total Amount -->
                        <div class="col-md-4 mb-3">
                            <label for="TotalAmount" class="form-label">Total Amount</label>
                            <input type="text" class="form-control" id="TotalAmount" name="TotalAmount" value="<?php echo number_format($amounts['TotalAmount'], 2); ?>" readonly>
                        </div>

                        <!-- EWT -->
                        <div class="col-md-4 mb-3">
                            <label for="EWT" class="form-label">EWT (2%)</label>
                            <input type="text" class="form-control" id="EWT" name="EWT" value="<?php echo number_format($amounts['EWT'], 2); ?>" readonly>
                        </div>

                        <!-- Add Toll Charges -->
                        <div class="col-md-4 mb-3">
                            <label for="AddTollCharges" class="form-label">Add Toll Charges</label>
                            <input type="text" class="form-control" id="AddTollCharges" name="AddTollCharges" value="<?php echo number_format($amounts['AddTollCharges'], 2); ?>" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Amount Net of Tax -->
                        <div class="col-md-6 mb-3">
                            <label for="AmountNetOfTax" class="form-label">Amount Net of Tax</label>
                            <input type="text" class="form-control" id="AmountNetOfTax" name="AmountNetOfTax" value="<?php echo number_format($amounts['AmountNetOfTax'], 2); ?>" readonly>
                        </div>

                        <!-- Net Amount -->
                        <div class="col-md-6 mb-3">
                            <label for="NetAmount" class="form-label">Net Amount</label>
                            <input type="text" class="form-control" id="NetAmount" name="NetAmount" value="<?php echo number_format($amounts['NetAmount'], 2); ?>" readonly>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary ">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transaction Groups Table -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Transaction Groups</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-nowrap align-middle text-center" id="transactionGroupsTable">
                        <thead>
                            <tr>
                                <th>Transaction Group ID</th>
                                <th>Truck ID</th>
                                <th>Date</th>
                                <th>Toll Fee Amount</th>
                                <th>Rate Amount</th>
                                <th>Amount</th>
                                <th>Total KGs</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Transaction groups will be dynamically loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Transaction Group Modal -->
        <div class="modal fade" id="editTGModal" tabindex="-1" aria-labelledby="editTGModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTGModalLabel">Edit Transaction Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editTGForm">
                            <input type="hidden" id="TransactionGroupID" name="TransactionGroupID">

                            <div class="row">
                                <!-- Truck ID -->
                                <div class="col-md-4 mb-3">
                                    <label for="TG_TruckID" class="form-label">Truck ID</label>
                                    <input type="number" class="form-control" id="TG_TruckID" name="TruckID" required>
                                </div>

                                <!-- Date -->
                                <div class="col-md-4 mb-3">
                                    <label for="TG_Date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="TG_Date" name="Date" required>
                                </div>

                                <!-- Toll Fee Amount -->
                                <div class="col-md-4 mb-3">
                                    <label for="TG_TollFeeAmount" class="form-label">Toll Fee Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="TG_TollFeeAmount" name="TollFeeAmount" required>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Rate Amount (Read Only) -->
                                <div class="col-md-4 mb-3">
                                    <label for="TG_RateAmount" class="form-label">Rate Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="TG_RateAmount" name="RateAmount" readonly>
                                </div>

                                <!-- Fuel Price (Read Only) -->
                                <div class="col-md-4 mb-3">
                                    <label for="TG_FuelPrice" class="form-label">Fuel Price</label>
                                    <input type="number" step="0.01" class="form-control" id="TG_FuelPrice" name="FuelPrice" readonly>
                                </div>

                                <!-- Amount (Read Only) -->
                                <div class="col-md-4 mb-3">
                                    <label for="TG_Amount" class="form-label">Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="TG_Amount" name="Amount" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Total KGs (Read Only) -->
                                <div class="col-md-6 mb-3">
                                    <label for="TG_TotalKGs" class="form-label">Total KGs</label>
                                    <input type="number" step="0.01" class="form-control" id="TG_TotalKGs" name="TotalKGs" readonly>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>

                        <!-- Transactions Table within Modal -->
                        <div class="mt-4">
                            <h5>Transactions</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered text-nowrap align-middle text-center" id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Transaction Date</th>
                                            <th>DR No</th>
                                            <th>Outlet Name</th>
                                            <th>Qty</th>
                                            <th>KGs</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Transactions will be loaded here via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Transaction Modal remains unchanged -->
        <div class="modal fade" id="editTransactionModal" tabindex="-1" aria-labelledby="editTransactionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTransactionModalLabel">Edit Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editTransactionForm">
                            <input type="hidden" id="TransactionID" name="TransactionID">
                            <input type="hidden" id="TransactionGroupID_Tx" name="TransactionGroupID">

                            <div class="mb-3">
                                <label for="T_TransactionDate" class="form-label">Transaction Date</label>
                                <input type="date" class="form-control" id="T_TransactionDate" name="TransactionDate" readonly>
                            </div>

                            <!-- DR No Input with Validation Feedback -->
                            <div class="mb-3">
                                <label for="T_DRno" class="form-label">DR No</label>
                                <input type="text" class="form-control" id="T_DRno" name="DRno" required>
                                <!-- Warning message placeholder -->
                                <div id="drNoWarning" class="invalid-feedback">
                                    DR No already exists. Please enter a unique DR No.
                                </div>
                            </div>

                            <!-- Outlet Name with Autocomplete -->
                            <div class="mb-3 position-relative">
                                <label for="T_OutletName" class="form-label">Outlet Name</label>
                                <input type="text" class="form-control" id="T_OutletName" name="OutletName" required autocomplete="off">
                                <!-- Suggestion Box -->
                                <div id="outletSuggestions" class="list-group position-absolute w-100" style="z-index: 1000; display: none;"></div>
                            </div>

                            <div class="row">
                                <!-- Qty -->
                                <div class="col-md-6 mb-3">
                                    <label for="T_Qty" class="form-label">Qty</label>
                                    <input type="number" step="0.01" class="form-control" id="T_Qty" name="Qty" required>
                                </div>

                                <!-- KGs -->
                                <div class="col-md-6 mb-3">
                                    <label for="T_KGs" class="form-label">KGs</label>
                                    <input type="number" step="0.01" class="form-control" id="T_KGs" name="KGs" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../officer/footer.php';
$conn->close();
?>

<!-- Include jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        // Function to show alerts
        function showAlert(type, message) {
            let alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('#alert-container').html(alertHtml);
        }

        // Function to fetch and update Transaction Groups
        function fetchTransactionGroups() {
            let billingStartDate = $('#BillingStartDate').val();
            let billingEndDate = $('#BillingEndDate').val();

            // Validate Date Inputs
            if (billingStartDate === '' || billingEndDate === '') {
                $('#transactionGroupsTable tbody').html('<tr><td colspan="8" class="text-center">Please select Billing Start Date and Billing End Date.</td></tr>');
                return;
            }

            // Show loading indicator
            $('#transactionGroupsTable tbody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

            $.ajax({
                url: 'edit_invoice.php',
                type: 'POST',
                data: {
                    action: 'fetch_transaction_groups',
                    BillingStartDate: billingStartDate,
                    BillingEndDate: billingEndDate
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update calculated fields
                        $('#GrossAmount').val(parseFloat(response.amounts.GrossAmount).toFixed(2));
                        $('#VAT').val(parseFloat(response.amounts.VAT).toFixed(2));
                        $('#TotalAmount').val(parseFloat(response.amounts.TotalAmount).toFixed(2));
                        $('#EWT').val(parseFloat(response.amounts.EWT).toFixed(2));
                        $('#AddTollCharges').val(parseFloat(response.amounts.AddTollCharges).toFixed(2));
                        $('#AmountNetOfTax').val(parseFloat(response.amounts.AmountNetOfTax).toFixed(2));
                        $('#NetAmount').val(parseFloat(response.amounts.NetAmount).toFixed(2));

                        // Update Transaction Groups Table
                        let tbody = $('#transactionGroupsTable tbody');
                        tbody.empty();

                        if (response.transactionGroups.length > 0) {
                            response.transactionGroups.forEach(function(tg) {
                                let row = `
                                    <tr id="tg-${tg.TransactionGroupID}">
                                        <td>${tg.TransactionGroupID}</td>
                                        <td>${tg.TruckID}</td>
                                        <td>${tg.Date}</td>
                                        <td>${parseFloat(tg.TollFeeAmount).toFixed(2)}</td>
                                        <td>${parseFloat(tg.RateAmount).toFixed(2)}</td>
                                        <td>${parseFloat(tg.Amount).toFixed(2)}</td>
                                        <td>${parseFloat(tg.TotalKGs).toFixed(2)}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-tg-btn" data-tg-id="${tg.TransactionGroupID}">Edit</button>
                                        </td>
                                    </tr>
                                `;
                                tbody.append(row);
                            });
                        } else {
                            tbody.append('<tr><td colspan="8" class="text-center">No Transaction Groups found for the selected date range.</td></tr>');
                        }
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    showAlert('danger', 'An error occurred while fetching transaction groups.');
                }
            });
        }

        // Initial Fetch on Page Load
        fetchTransactionGroups();

        // Event listeners for real-time updates when Billing Start Date or End Date changes
        $('#BillingStartDate, #BillingEndDate').on('change', function() {
            fetchTransactionGroups();
        });

        // Handle Edit Transaction Group Button Click (Updated)
        $(document).on('click', '.edit-tg-btn', function() {
            let tgID = $(this).data('tg-id');

            // Show a loading state in the modal
            $('#editTGModal').find('form')[0].reset();
            $('#TG_RateAmount').val('0.00');
            $('#TG_FuelPrice').val('0.00');
            $('#TG_Amount').val('0.00');
            $('#TG_TotalKGs').val('0.00');
            $('#transactionsTable tbody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');

            // Fetch Transaction Group Details via AJAX
            $.ajax({
                url: 'fetch_transaction_group.php',
                type: 'POST',
                data: {
                    TransactionGroupID: tgID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let tg = response.transactionGroup;
                        let transactions = response.transactions;

                        // Populate the form fields
                        $('#TransactionGroupID').val(tg.TransactionGroupID);
                        $('#TG_TruckID').val(tg.TruckID);
                        $('#TG_Date').val(tg.Date);
                        $('#TG_TollFeeAmount').val(parseFloat(tg.TollFeeAmount).toFixed(2));
                        $('#TG_RateAmount').val(parseFloat(tg.RateAmount).toFixed(2));
                        $('#TG_FuelPrice').val(parseFloat(tg.FuelPrice).toFixed(2));
                        $('#TG_Amount').val(parseFloat(tg.Amount).toFixed(2));
                        $('#TG_TotalKGs').val(parseFloat(tg.TotalKGs).toFixed(2));

                        // Populate Transactions Table
                        let tbody = $('#transactionsTable tbody');
                        tbody.empty();

                        if (transactions.length > 0) {
                            transactions.forEach(function(tx) {
                                let tr = `
                                    <tr id="tx-${tx.TransactionID}">
                                        <td>${tx.TransactionID}</td>
                                        <td>${tx.TransactionDate}</td>
                                        <td>${tx.DRno}</td>
                                        <td>${tx.OutletName}</td>
                                        <td>${tx.Qty}</td>
                                        <td>${tx.KGs}</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-tx-btn" data-tx-id="${tx.TransactionID}">Edit</button>
                                        </td>
                                    </tr>
                                `;
                                tbody.append(tr);
                            });
                        } else {
                            tbody.append('<tr><td colspan="7" class="text-center">No Transactions found for this Transaction Group.</td></tr>');
                        }

                        // Show the modal
                        $('#editTGModal').modal('show');
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    showAlert('danger', 'An error occurred while fetching transaction group details.');
                }
            });
        });

        // Handle Transaction Group Form Submission
        $('#editTGForm').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();

            // Disable the button and show loading
            $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'edit_invoice.php',
                type: 'POST',
                data: formData + '&action=update_transaction_group',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        let tgID = $('#TransactionGroupID').val();
                        // Update the table row
                        let row = $('#tg-' + tgID);
                        row.find('td').eq(1).text($('#TG_TruckID').val());
                        row.find('td').eq(2).text($('#TG_Date').val());
                        row.find('td').eq(3).text(parseFloat($('#TG_TollFeeAmount').val()).toFixed(2));
                        row.find('td').eq(4).text(parseFloat($('#TG_RateAmount').val()).toFixed(2));
                        row.find('td').eq(5).text(parseFloat(response.Amount).toFixed(2));
                        row.find('td').eq(6).text(parseFloat(response.TotalKGs).toFixed(2));

                        // Fetch and update Transaction Groups to refresh totals
                        fetchTransactionGroups();

                        // Close the modal
                        $('#editTGModal').modal('hide');
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    showAlert('danger', 'An error occurred while updating the transaction group.');
                },
                complete: function() {
                    // Re-enable the button and reset text
                    $('#editTGForm').find('button[type="submit"]').prop('disabled', false).text('Save Changes');
                }
            });
        });

        // Handle Edit Transaction Button Click
        $(document).on('click', '.edit-tx-btn', function() {
            let txID = $(this).data('tx-id');

            // Fetch Transaction Details from the table row
            let row = $('#tx-' + txID);
            let TransactionDate = row.find('td').eq(1).text();
            let DRno = row.find('td').eq(2).text();
            let OutletName = row.find('td').eq(3).text();
            let Qty = row.find('td').eq(4).text();
            let KGs = row.find('td').eq(5).text();
            let TransactionGroupID = $('#TransactionGroupID').val();

            // Populate the modal fields
            $('#TransactionID').val(txID);
            $('#TransactionGroupID_Tx').val(TransactionGroupID);
            $('#T_TransactionDate').val(TransactionDate);
            $('#T_DRno').val(DRno);
            $('#T_OutletName').val(OutletName);
            $('#T_Qty').val(Qty);
            $('#T_KGs').val(KGs);

            // Reset validation states
            $('#T_DRno').removeClass('is-invalid');
            $('#drNoWarning').hide();

            // Show the modal
            $('#editTransactionModal').modal('show');
        });

        // Debounce function to limit the rate of AJAX calls
        function debounce(func, delay) {
            let debounceTimer;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => func.apply(context, args), delay);
            }
        }

        // Function to validate DR No
        function validateDRNo(drNo, transaction_id, callback) {
            $.ajax({
                url: 'validate_dr_no.php',
                type: 'GET',
                data: {
                    dr_no: drNo,
                    transaction_id: transaction_id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        console.error('Validation Error:', response.error);
                        callback(false);
                    } else {
                        callback(response.exists);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error validating DR No:', error);
                    callback(false); // Assume non-existing on error
                }
            });
        }

        // Event listener for DR No input field with debounce
        $('#T_DRno').on('input', debounce(function() {
            let drNo = $(this).val().trim();
            let $drNoInput = $(this);
            let $warning = $('#drNoWarning');
            let transaction_id = $('#TransactionID').val();

            if (drNo === '') {
                // If DR No is empty, remove validation states
                $drNoInput.removeClass('is-invalid');
                $warning.text('DR No is required.').hide();
                return;
            }

            // Validate DR No
            validateDRNo(drNo, transaction_id, function(exists) {
                if (exists) {
                    // DR No exists - show error
                    $drNoInput.addClass('is-invalid');
                    $warning.text('DR No already exists. Please enter a unique DR No.').show();
                } else {
                    // DR No does not exist - remove error
                    $drNoInput.removeClass('is-invalid');
                    $warning.hide();
                }
            });
        }, 500)); // 500ms debounce delay

        // Handle Transaction Form Submission with DR No Validation
        $('#editTransactionForm').on('submit', function(e) {
            e.preventDefault();

            let drNo = $('#T_DRno').val().trim();
            let isInvalid = $('#T_DRno').hasClass('is-invalid');

            if (drNo === '') {
                // If DR No is empty, show validation error
                $('#T_DRno').addClass('is-invalid');
                $('#drNoWarning').text('DR No is required.').show();
                return;
            }

            if (isInvalid) {
                // If DR No is invalid, prevent form submission
                $('#drNoWarning').text('DR No already exists. Please enter a unique DR No.').show();
                return;
            }

            // Proceed with form submission via AJAX
            let formData = $(this).serialize();

            // Disable the button and show loading
            $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'edit_invoice.php',
                type: 'POST',
                data: formData + '&action=update_transaction',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        let txID = $('#TransactionID').val();
                        // Update the transaction row
                        let row = $('#tx-' + txID);
                        row.find('td').eq(2).text($('#T_DRno').val());
                        row.find('td').eq(3).text($('#T_OutletName').val());
                        row.find('td').eq(4).text(parseFloat($('#T_Qty').val()).toFixed(2));
                        row.find('td').eq(5).text(parseFloat($('#T_KGs').val()).toFixed(2));

                        // Fetch and update Transaction Groups to refresh totals
                        fetchTransactionGroups();

                        // Close the modal
                        $('#editTransactionModal').modal('hide');
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    showAlert('danger', 'An error occurred while updating the transaction.');
                },
                complete: function() {
                    // Re-enable the button and reset text
                    $('#editTransactionForm').find('button[type="submit"]').prop('disabled', false).text('Save Changes');
                }
            });
        });

        // Update Amount fields in real-time when TollFeeAmount changes in Transaction Group Modal
        $('#TG_TollFeeAmount').on('input', function() {
            let TollFeeAmount = parseFloat($(this).val()) || 0;
            let RateAmount = parseFloat($('#TG_RateAmount').val()) || 0;
            let Amount = TollFeeAmount + RateAmount;
            $('#TG_Amount').val(Amount.toFixed(2));
        });

        /* ------------------ Autocomplete Feature for Outlet Name ------------------ */

        // Function to fetch outlet suggestions
        function fetchOutletSuggestions(query) {
            if (query.length < 1) { // Start suggesting after 1 character
                $('#outletSuggestions').hide();
                return;
            }

            $.ajax({
                url: 'search_outlets.php',
                type: 'GET',
                data: { query: query },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        console.error('Error fetching outlets:', data.error);
                        $('#outletSuggestions').hide();
                    } else if (data.length > 0) {
                        let suggestions = data.map(function(outlet) {
                            return `<button type="button" class="list-group-item list-group-item-action" data-outlet="${outlet.CustomerName}">${outlet.CustomerName}</button>`;
                        }).join('');
                        $('#outletSuggestions').html(suggestions).show();
                    } else {
                        $('#outletSuggestions').html('<div class="list-group-item">No outlets found.</div>').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $('#outletSuggestions').hide();
                }
            });
        }

        // Debounce function to limit the rate of AJAX calls for autocomplete
        $('#T_OutletName').on('input', debounce(function() {
            let query = $(this).val().trim();
            fetchOutletSuggestions(query);
        }, 300)); // 300ms debounce delay

        // Handle click on suggestion
        $(document).on('click', '#outletSuggestions .list-group-item', function() {
            let selectedOutlet = $(this).data('outlet');
            $('#T_OutletName').val(selectedOutlet);
            $('#outletSuggestions').hide();
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#T_OutletName').length && !$(e.target).closest('#outletSuggestions').length) {
                $('#outletSuggestions').hide();
            }
        });
    });
</script>

<style>
    /* Additional styling for better user experience */
    /* Suggestion Box Styling */
    #outletSuggestions {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ced4da;
        border-top: none;
        background-color: #fff;
    }

    #outletSuggestions .list-group-item {
        cursor: pointer;
    }

    #outletSuggestions .list-group-item:hover {
        background-color: #f8f9fa;
    }

    .modal-lg {
        max-width: 80% !important;
    }

    .btn-primary {
        margin-right: 5px;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .modal-lg {
            max-width: 95% !important;
        }
    }

    /* Spinner for loading */
    .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
        border-width: 0.2em;
    }
</style>
