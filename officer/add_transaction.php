<?php
session_start();
include '../includes/db_connection.php';

header('Content-Type: application/json');

$response = [];

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    $response['success'] = false;
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

$userID = $_SESSION['UserID'];

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

// Function to calculate Tonner based on TotalKGs
function calculate_tonner($total_kgs)
{
    $rounded_total_kgs = 0;
    if ($total_kgs > 0) {
        if ($total_kgs <= 1199) {
            $rounded_total_kgs = 1000;
        } else if ($total_kgs <= 4199) {
            $rounded_total_kgs = ceil($total_kgs / 1000) * 1000;
            if ($rounded_total_kgs > 4000) {  // Ensure it doesn’t exceed 4000
                $rounded_total_kgs = 4000;
            }
        } else {
            $rounded_total_kgs = 4000;
        }
    }
    return $rounded_total_kgs;
}

// Handle adding a new transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input data
    $TransactionGroupID = intval($_POST['TransactionGroupID'] ?? 0);
    $TransactionDate = $_POST['TransactionDate'] ?? '';
    $DRno = trim($_POST['DRno'] ?? '');
    $OutletName = trim($_POST['OutletName'] ?? '');
    $Qty = floatval($_POST['Qty'] ?? 0);
    $KGs = floatval($_POST['KGs'] ?? 0);

    // Validation
    if ($TransactionGroupID <= 0 || empty($TransactionDate) || empty($DRno) || empty($OutletName) || $Qty <= 0 || $KGs <= 0) {
        $response['success'] = false;
        $response['message'] = 'All fields are required and must be valid.';
        echo json_encode($response);
        exit();
    }

    // Check if TransactionGroup exists
    $tgQuery = "SELECT * FROM transactiongroup WHERE TransactionGroupID = ?";
    $stmt = $conn->prepare($tgQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare Transaction Group query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("i", $TransactionGroupID);
    $stmt->execute();
    $tgResult = $stmt->get_result();
    if ($tgResult->num_rows == 0) {
        $response['success'] = false;
        $response['message'] = 'Transaction Group not found.';
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $tgRow = $tgResult->fetch_assoc();
    $stmt->close();

    // Check if DRno already exists
    $drNoQuery = "SELECT COUNT(*) as count FROM transactions WHERE DRno = ?";
    $stmt = $conn->prepare($drNoQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare DR No check query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("s", $DRno); // Correct variable
    $stmt->execute();
    $drNoResult = $stmt->get_result();
    $drNoRow = $drNoResult->fetch_assoc();
    if ($drNoRow['count'] > 0) {
        $response['success'] = false;
        $response['message'] = 'DR No already exists. Please enter a unique DR No.';
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $stmt->close();

    // Fetch ClusterID from the OutletName
    $clusterIDQuery = "
        SELECT c.ClusterID
        FROM customers c
        WHERE LOWER(c.CustomerName) = LOWER(?)
        LIMIT 1
    ";
    $stmt = $conn->prepare($clusterIDQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare ClusterID fetch query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("s", $OutletName);
    $stmt->execute();
    $clusterResult = $stmt->get_result();
    if ($clusterResult->num_rows == 0) {
        $response['success'] = false;
        $response['message'] = 'Outlet Name not found.';
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $clusterRow = $clusterResult->fetch_assoc();
    $ClusterID = $clusterRow['ClusterID'];
    $stmt->close();

    // Fetch FuelPrice from the Transaction Group
    $fuelPrice = floatval($tgRow['FuelPrice'] ?? 0);
    if ($fuelPrice <= 0) {
        $response['success'] = false;
        $response['message'] = 'Fuel Price is not set for this Transaction Group.';
        echo json_encode($response);
        exit();
    }

    // Insert the new transaction
    $insertTxQuery = "
        INSERT INTO transactions (TransactionGroupID, TransactionDate, DRno, OutletName, Qty, KGs)
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($insertTxQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare transaction insertion query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("isssdd", $TransactionGroupID, $TransactionDate, $DRno, $OutletName, $Qty, $KGs);
    if (!$stmt->execute()) {
        $response['success'] = false;
        $response['message'] = 'Failed to insert the new transaction.';
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $newTransactionID = $stmt->insert_id;
    $stmt->close();

    // Recalculate TotalKGs for the Transaction Group
    $kgQuery = "
        SELECT SUM(KGs) as TotalKGs
        FROM transactions
        WHERE TransactionGroupID = ?
    ";
    $stmt = $conn->prepare($kgQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare TotalKGs calculation query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("i", $TransactionGroupID);
    $stmt->execute();
    $kgResult = $stmt->get_result();
    $kgRow = $kgResult->fetch_assoc();
    $TotalKGs = floatval($kgRow['TotalKGs'] ?? 0);
    $stmt->close();

    // Calculate Tonner
    $Tonner = calculate_tonner($TotalKGs);

    // Fetch RateAmount from clusters based on ClusterID, FuelPrice, and Tonner
    $rateQuery = "
        SELECT RateAmount
        FROM clusters
        WHERE ClusterID = ? AND FuelPrice = ? AND Tonner = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($rateQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare RateAmount fetch query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("idd", $ClusterID, $fuelPrice, $Tonner);
    $stmt->execute();
    $rateResult = $stmt->get_result();
    if ($rateResult->num_rows == 0) {
        $response['success'] = false;
        $response['message'] = 'No RateAmount found for the provided ClusterID, FuelPrice, and Tonner.';
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $rateRow = $rateResult->fetch_assoc();
    $RateAmount = floatval($rateRow['RateAmount'] ?? 0);
    $stmt->close();

    // Update RateAmount and Amount in Transaction Group
    $updateTGQuery = "
        UPDATE transactiongroup
        SET RateAmount = ?, Amount = ?
        WHERE TransactionGroupID = ?
    ";
    $Amount = $tgRow['TollFeeAmount'] + $RateAmount; // Assuming TollFeeAmount is already in tgRow
    $stmt = $conn->prepare($updateTGQuery);
    if (!$stmt) {
        $response['success'] = false;
        $response['message'] = 'Failed to prepare Transaction Group update query.';
        echo json_encode($response);
        exit();
    }
    $stmt->bind_param("ddi", $RateAmount, $Amount, $TransactionGroupID);
    if (!$stmt->execute()) {
        $response['success'] = false;
        $response['message'] = 'Failed to update Transaction Group amounts.';
        $stmt->close();
        echo json_encode($response);
        exit();
    }
    $stmt->close();

    // Recalculate Amount Net of Tax and Net Amount
    // Fetch related Invoice details if applicable
    // Assuming the Transaction Group is linked to an Invoice
    $invoiceNo = intval($tgRow['BillingInvoiceNo'] ?? 0);
    if ($invoiceNo > 0) {
        // Fetch current Invoice amounts
        $invoiceQuery = "
            SELECT * 
            FROM invoices 
            WHERE BillingInvoiceNo = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($invoiceQuery);
        if (!$stmt) {
            $response['success'] = false;
            $response['message'] = 'Failed to prepare Invoice fetch query.';
            echo json_encode($response);
            exit();
        }
        $stmt->bind_param("i", $invoiceNo);
        $stmt->execute();
        $invoiceResult = $stmt->get_result();
        if ($invoiceResult->num_rows == 0) {
            $stmt->close();
            $response['success'] = false;
            $response['message'] = 'Associated Invoice not found.';
            echo json_encode($response);
            exit();
        }
        $invoiceRow = $invoiceResult->fetch_assoc();
        $stmt->close();

        // Recalculate amounts based on the updated Transaction Groups within the Invoice date range
        $billingStartDate = $invoiceRow['BillingStartDate'];
        $billingEndDate = $invoiceRow['BillingEndDate'];

        // Reuse calculate_amounts function
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

        try {
            $amounts = calculate_amounts($conn, $billingStartDate, $billingEndDate);

            // Update the Invoice with new amounts
            $updateInvoiceQuery = "
                UPDATE invoices
                SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, 
                    AmountNetOfTax = ?, NetAmount = ?
                WHERE BillingInvoiceNo = ?
            ";
            $stmt = $conn->prepare($updateInvoiceQuery);
            if (!$stmt) {
                throw new Exception('Failed to prepare Invoice amounts update query: ' . $conn->error);
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
                $invoiceNo
            );
            if (!$stmt->execute()) {
                throw new Exception('Failed to update Invoice amounts: ' . $stmt->error);
            }
            $stmt->close();

            // Fetch the new NetAmount
            $newNetAmount = $amounts['NetAmount'];
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            echo json_encode($response);
            exit();
        }
    }

    // Insert Activity Log
    insert_activity_log($conn, $userID, "Added Transaction ID: $newTransactionID to Transaction Group ID: $TransactionGroupID");

    // Prepare the response
    $response['success'] = true;
    $response['message'] = 'Transaction added successfully.';
    $response['transaction'] = [
        'TransactionID' => $newTransactionID,
        'TransactionDate' => $TransactionDate,
        'DRno' => $DRno,
        'OutletName' => $OutletName,
        'Qty' => $Qty,
        'KGs' => $KGs
    ];
    $response['newTotalKGs'] = $TotalKGs;
    $response['newAmount'] = $Amount;
    $response['newNetAmount'] = isset($newNetAmount) ? $newNetAmount : $invoiceRow['NetAmount'];
    $response['RateAmount'] = $RateAmount; // Added RateAmount

    echo json_encode($response);
    $conn->close();

}
?>