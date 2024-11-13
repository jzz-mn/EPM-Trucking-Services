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
function calculate_amounts($conn, $billingInvoiceNo, $billingStartDate, $billingEndDate)
{
    // Fetch GrossAmount and AddTollCharges within the date range
    $query = "SELECT SUM(tg.RateAmount) as GrossAmount, SUM(tg.TollFeeAmount) as AddTollCharges
              FROM transactiongroup tg
              WHERE tg.BillingInvoiceNo = ?
                AND tg.Date BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $amounts = $result->fetch_assoc();
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
                $stmt->bind_param("sssi", $billingStartDate, $billingEndDate, $billedTo, $billingInvoiceNo);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update invoice: ' . $stmt->error);
                }

                // Update related transaction groups
                // First, set BillingInvoiceNo to NULL where the transaction group's date is now outside the new range
                $resetTGQuery = "
                    UPDATE transactiongroup
                    SET BillingInvoiceNo = NULL
                    WHERE BillingInvoiceNo = ?
                      AND (Date < ? OR Date > ?)
                ";
                $stmt = $conn->prepare($resetTGQuery);
                $stmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to reset transaction groups: ' . $stmt->error);
                }

                // Then, set BillingInvoiceNo for transaction groups within the new date range
                $updateTGQuery = "
                    UPDATE transactiongroup
                    SET BillingInvoiceNo = ?
                    WHERE BillingInvoiceNo IS NULL
                      AND Date BETWEEN ? AND ?
                ";
                $stmt = $conn->prepare($updateTGQuery);
                $stmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update transaction groups: ' . $stmt->error);
                }

                // Recalculate invoice amounts with the updated date range
                $amounts = calculate_amounts($conn, $billingInvoiceNo, $billingStartDate, $billingEndDate);
                $updateAmountsQuery = "
                    UPDATE invoices
                    SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                        AmountNetOfTax = ?, NetAmount = ?
                    WHERE BillingInvoiceNo = ?
                ";
                $stmt = $conn->prepare($updateAmountsQuery);
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

        // Fetch Transaction Groups for the Invoice
        if ($_POST['action'] == 'fetch_transaction_groups') {
            $billingInvoiceNo = intval($_POST['BillingInvoiceNo']);
            $billingStartDate = $_POST['BillingStartDate'] ?? '';
            $billingEndDate = $_POST['BillingEndDate'] ?? '';

            // Fetch Transaction Groups within the date range
            $tgQuery = "SELECT * FROM transactiongroup WHERE BillingInvoiceNo = ? AND Date BETWEEN ? AND ?";
            $stmt = $conn->prepare($tgQuery);
            $stmt->bind_param("iss", $billingInvoiceNo, $billingStartDate, $billingEndDate);
            $stmt->execute();
            $tgResult = $stmt->get_result();

            $transactionGroups = [];
            while ($row = $tgResult->fetch_assoc()) {
                $transactionGroups[] = $row;
            }

            // Calculate updated amounts based on the current date range
            $amounts = calculate_amounts($conn, $billingInvoiceNo, $billingStartDate, $billingEndDate);

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
                $stmt->bind_param("isdi", $TruckID, $Date, $TollFeeAmount, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update transaction group: ' . $stmt->error);
                }

                // Recalculate RateAmount (Assuming RateAmount is a fixed rate based on TruckID or other logic)
                // For example, let's say RateAmount is determined by TruckID
                // Replace this with your actual logic
                $rateQuery = "SELECT Rate FROM trucksinfo WHERE TruckID = ?";
                $stmtRate = $conn->prepare($rateQuery);
                $stmtRate->bind_param("i", $TruckID);
                $stmtRate->execute();
                $rateResult = $stmtRate->get_result();
                $rateRow = $rateResult->fetch_assoc();
                $RateAmount = $rateRow['Rate'] ?? 0;

                // Update RateAmount in Transaction Group
                $updateRateQuery = "
                    UPDATE transactiongroup
                    SET RateAmount = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateRateQuery);
                $stmt->bind_param("di", $RateAmount, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update RateAmount: ' . $stmt->error);
                }

                // Calculate Amount = TollFeeAmount + RateAmount
                $Amount = $TollFeeAmount + $RateAmount;

                // Update Amount in Transaction Group
                $updateAmountQuery = "
                    UPDATE transactiongroup
                    SET Amount = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateAmountQuery);
                $stmt->bind_param("di", $Amount, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update Amount: ' . $stmt->error);
                }

                // Recalculate TotalKGs based on related transactions
                $kgQuery = "
                    SELECT SUM(KGs) as TotalKGs
                    FROM transactions
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($kgQuery);
                $stmt->bind_param("i", $transactionGroupID);
                $stmt->execute();
                $kgResult = $stmt->get_result();
                $kgRow = $kgResult->fetch_assoc();
                $TotalKGs = $kgRow['TotalKGs'] ?? 0;

                // Update TotalKGs
                $updateKGsQuery = "
                    UPDATE transactiongroup
                    SET TotalKGs = ?
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($updateKGsQuery);
                $stmt->bind_param("di", $TotalKGs, $transactionGroupID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update TotalKGs: ' . $stmt->error);
                }

                // Recalculate invoice amounts based on updated Transaction Group
                // Fetch BillingInvoiceNo from the Transaction Group
                $fetchInvoiceNoQuery = "
                    SELECT BillingInvoiceNo
                    FROM transactiongroup
                    WHERE TransactionGroupID = ?
                ";
                $stmt = $conn->prepare($fetchInvoiceNoQuery);
                $stmt->bind_param("i", $transactionGroupID);
                $stmt->execute();
                $invoiceResult = $stmt->get_result();
                $invoiceRow = $invoiceResult->fetch_assoc();
                $billingInvoiceNo = $invoiceRow['BillingInvoiceNo'] ?? null;

                if ($billingInvoiceNo) {
                    // Fetch BillingStartDate and BillingEndDate from invoices
                    $fetchDateRangeQuery = "SELECT BillingStartDate, BillingEndDate FROM invoices WHERE BillingInvoiceNo = ?";
                    $stmt = $conn->prepare($fetchDateRangeQuery);
                    $stmt->bind_param("i", $billingInvoiceNo);
                    $stmt->execute();
                    $dateResult = $stmt->get_result();
                    $dateRow = $dateResult->fetch_assoc();
                    $billingStartDate = $dateRow['BillingStartDate'] ?? '';
                    $billingEndDate = $dateRow['BillingEndDate'] ?? '';

                    // Recalculate amounts based on the updated date range
                    $amounts = calculate_amounts($conn, $billingInvoiceNo, $billingStartDate, $billingEndDate);

                    // Update invoice amounts
                    $updateAmountsQuery = "
                        UPDATE invoices
                        SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                            AmountNetOfTax = ?, NetAmount = ?
                        WHERE BillingInvoiceNo = ?
                    ";
                    $stmt = $conn->prepare($updateAmountsQuery);
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
            $stmt->bind_param("i", $transactionGroupID);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactions = [];
            while ($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }

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
                $stmt->bind_param("ssddi", $DRno, $OutletName, $Qty, $KGs, $transactionID);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update transaction: ' . $stmt->error);
                }

                // Fetch TransactionGroupID
                $tgQuery = "SELECT TransactionGroupID FROM transactions WHERE TransactionID = ?";
                $stmt = $conn->prepare($tgQuery);
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
                    $stmt->bind_param("di", $TotalKGs, $transactionGroupID);
                    if (!$stmt->execute()) {
                        throw new Exception('Failed to update TotalKGs: ' . $stmt->error);
                    }
                    $stmt->close();

                    // Recalculate invoice amounts
                    $billingInvoiceNo = null;
                    $fetchInvoiceNoQuery = "
                        SELECT BillingInvoiceNo
                        FROM transactiongroup
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($fetchInvoiceNoQuery);
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
                        $stmt->bind_param("i", $billingInvoiceNo);
                        $stmt->execute();
                        $dateResult = $stmt->get_result();
                        $dateRow = $dateResult->fetch_assoc();
                        $billingStartDate = $dateRow['BillingStartDate'] ?? '';
                        $billingEndDate = $dateRow['BillingEndDate'] ?? '';
                        $stmt->close();

                        // Recalculate amounts based on the updated date range
                        $amounts = calculate_amounts($conn, $billingInvoiceNo, $billingStartDate, $billingEndDate);

                        // Update invoice amounts
                        $updateAmountsQuery = "
                            UPDATE invoices
                            SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                                AmountNetOfTax = ?, NetAmount = ?
                            WHERE BillingInvoiceNo = ?
                        ";
                        $stmt = $conn->prepare($updateAmountsQuery);
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
                insert_activity_log($conn, $userID, "Updated Transaction ID: $transactionID");

                echo json_encode(['success' => true, 'message' => 'Transaction updated successfully.', 'TotalKGs' => $TotalKGs]);
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
$stmt->bind_param("i", $billingInvoiceNo);
$stmt->execute();
$invoiceResult = $stmt->get_result();

if ($invoiceResult->num_rows == 0) {
    echo "Invoice not found.";
    exit;
}

$invoice = $invoiceResult->fetch_assoc();

// Calculate initial amounts based on the current date range
$amounts = calculate_amounts($conn, $billingInvoiceNo, $invoice['BillingStartDate'], $invoice['BillingEndDate']);

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

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Transaction Groups Table -->
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Transaction Groups</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="transactionGroupsTable">
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
                            <?php
                            // Fetch transaction groups within the current date range
                            $tgQuery = "SELECT * FROM transactiongroup WHERE BillingInvoiceNo = ? AND Date BETWEEN ? AND ?";
                            $stmt = $conn->prepare($tgQuery);
                            $stmt->bind_param("iss", $billingInvoiceNo, $invoice['BillingStartDate'], $invoice['BillingEndDate']);
                            $stmt->execute();
                            $tgResult = $stmt->get_result();

                            while ($tg = $tgResult->fetch_assoc()):
                            ?>
                                <tr id="tg-<?php echo $tg['TransactionGroupID']; ?>">
                                    <td><?php echo htmlspecialchars($tg['TransactionGroupID']); ?></td>
                                    <td><?php echo htmlspecialchars($tg['TruckID']); ?></td>
                                    <td><?php echo htmlspecialchars($tg['Date']); ?></td>
                                    <td><?php echo number_format($tg['TollFeeAmount'], 2); ?></td>
                                    <td><?php echo number_format($tg['RateAmount'], 2); ?></td>
                                    <td><?php echo number_format($tg['Amount'], 2); ?></td>
                                    <td><?php echo number_format($tg['TotalKGs'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-tg-btn" data-tg-id="<?php echo $tg['TransactionGroupID']; ?>">Edit</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
                                <div class="col-md-6 mb-3">
                                    <label for="TG_RateAmount" class="form-label">Rate Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="TG_RateAmount" name="RateAmount" readonly>
                                </div>

                                <!-- Amount (Read Only) -->
                                <div class="col-md-6 mb-3">
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
                                <table class="table table-bordered" id="transactionsTable">
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

        <!-- Edit Transaction Modal -->
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

                            <div class="mb-3">
                                <label for="T_OutletName" class="form-label">Outlet Name</label>
                                <input type="text" class="form-control" id="T_OutletName" name="OutletName" required>
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
        // Handle Invoice Form Submission
        $('#editInvoiceForm').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();

            // Disable the button and show loading
            $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'edit_invoice.php',
                type: 'POST',
                data: formData + '&action=update_invoice',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        // Update calculated fields
                        $('#GrossAmount').val(parseFloat(response.amounts.GrossAmount).toFixed(2));
                        $('#VAT').val(parseFloat(response.amounts.VAT).toFixed(2));
                        $('#TotalAmount').val(parseFloat(response.amounts.TotalAmount).toFixed(2));
                        $('#EWT').val(parseFloat(response.amounts.EWT).toFixed(2));
                        $('#AddTollCharges').val(parseFloat(response.amounts.AddTollCharges).toFixed(2));
                        $('#AmountNetOfTax').val(parseFloat(response.amounts.AmountNetOfTax).toFixed(2));
                        $('#NetAmount').val(parseFloat(response.amounts.NetAmount).toFixed(2));

                        // Fetch and update Transaction Groups
                        fetchTransactionGroups();
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    showAlert('danger', 'An error occurred while updating the invoice.');
                },
                complete: function() {
                    // Re-enable the button and reset text
                    $('#editInvoiceForm').find('button[type="submit"]').prop('disabled', false).text('Save Changes');
                }
            });
        });

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
            let billingInvoiceNo = $('#BillingInvoiceNo').val();
            let billingStartDate = $('#BillingStartDate').val();
            let billingEndDate = $('#BillingEndDate').val();

            // Show loading indicator
            $('#transactionGroupsTable tbody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

            $.ajax({
                url: 'edit_invoice.php',
                type: 'POST',
                data: {
                    action: 'fetch_transaction_groups',
                    BillingInvoiceNo: billingInvoiceNo,
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

        // Event listeners for real-time updates when Billing Start Date or End Date changes
        $('#BillingStartDate, #BillingEndDate').on('change', function() {
            // Optionally, you can add validation here
            fetchTransactionGroups();
        });

        // Handle Edit Transaction Group Button Click (Updated)
        $(document).on('click', '.edit-tg-btn', function() {
            let tgID = $(this).data('tg-id');

            // Show a loading state in the modal
            $('#editTGModal').find('form')[0].reset();
            $('#TG_RateAmount').val('0.00');
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
    });
</script>

<style>
    /* Additional styling for better user experience */

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
