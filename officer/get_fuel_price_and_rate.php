<?php
include '../includes/db_connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transactionGroupID = intval($_POST['TransactionGroupID'] ?? 0);

    if ($transactionGroupID <= 0) {
        $response['message'] = 'Invalid Transaction Group ID.';
        echo json_encode($response);
        exit;
    }

    // Fetch the first transaction's Outlet Name
    $outletQuery = "
        SELECT t.OutletName, tg.ExpenseID, tg.TotalKGs
        FROM transactions t
        JOIN transactiongroup tg ON t.TransactionGroupID = tg.TransactionGroupID
        WHERE tg.TransactionGroupID = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($outletQuery);
    if (!$stmt) {
        $response['message'] = 'Failed to prepare Outlet Name fetch query.';
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("i", $transactionGroupID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $response['message'] = 'No transactions found for this Transaction Group.';
        echo json_encode($response);
        exit;
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
        $response['message'] = 'Failed to prepare FuelID fetch query.';
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("i", $expenseID);
    $stmt->execute();
    $fuelIDResult = $stmt->get_result();

    if ($fuelIDResult->num_rows == 0) {
        $response['message'] = 'No FuelID found for the given ExpenseID.';
        echo json_encode($response);
        exit;
    }

    $fuelRow = $fuelIDResult->fetch_assoc();
    $fuelID = $fuelRow['FuelID'];
    $stmt->close();

    // Fetch FuelPrice from fuel table using FuelID
    $fuelPriceQuery = "SELECT Amount FROM fuel WHERE FuelID = ?";
    $stmt = $conn->prepare($fuelPriceQuery);
    if (!$stmt) {
        $response['message'] = 'Failed to prepare FuelPrice fetch query.';
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("i", $fuelID);
    $stmt->execute();
    $fuelPriceResult = $stmt->get_result();

    if ($fuelPriceResult->num_rows == 0) {
        $response['message'] = 'No FuelPrice found for the given FuelID.';
        echo json_encode($response);
        exit;
    }

    $fuelPriceRow = $fuelPriceResult->fetch_assoc();
    $fuelPrice = floatval($fuelPriceRow['Amount']);
    $stmt->close();

    // Now, fetch ClusterID from customers table using OutletName
    $clusterQuery = "SELECT ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?) LIMIT 1";
    $stmt = $conn->prepare($clusterQuery);
    if (!$stmt) {
        $response['message'] = 'Failed to prepare ClusterID fetch query.';
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("s", $outletName);
    $stmt->execute();
    $clusterResult = $stmt->get_result();

    if ($clusterResult->num_rows == 0) {
        $response['message'] = "The Outlet Name '{$outletName}' does not exist in the Customers table.";
        echo json_encode($response);
        exit;
    }

    $clusterRow = $clusterResult->fetch_assoc();
    $clusterID = $clusterRow['ClusterID'];
    $stmt->close();

    // Now, fetch RateAmount from clusters table using ClusterID, FuelPrice, and Tonner
    $rateQuery = "SELECT RateAmount FROM clusters WHERE ClusterID = ? AND FuelPrice = ? AND Tonner = ? LIMIT 1";
    $stmt = $conn->prepare($rateQuery);
    if (!$stmt) {
        $response['message'] = 'Failed to prepare RateAmount fetch query.';
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("idi", $clusterID, $fuelPrice, $rounded_total_kgs);
    $stmt->execute();
    $rateResult = $stmt->get_result();

    if ($rateResult->num_rows == 0) {
        $response['message'] = "No RateAmount found for ClusterID '{$clusterID}', FuelPrice '{$fuelPrice}', and Tonner '{$rounded_total_kgs}'.";
        echo json_encode($response);
        exit;
    }

    $rateRow = $rateResult->fetch_assoc();
    $rateAmount = floatval($rateRow['RateAmount']);
    $stmt->close();

    // Successful fetch
    $response['success'] = true;
    $response['FuelPrice'] = $fuelPrice;
    $response['RateAmount'] = $rateAmount;

    echo json_encode($response);
    exit;
} else {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$conn->close();
?>
