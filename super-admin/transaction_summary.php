<?php
session_start();
include '../super-admin/header.php';
include '../includes/db_connection.php';

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
    if ($kgs <= 4199) {
        return ceil($kgs / 1000) * 1000;
    }
    return 4000; // Cap at 4000
}

// Check if the user has submitted expenses data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store expenses data in session
    $_SESSION['expenses_date'] = $_POST['expenses_date'];
    $_SESSION['expenses_salary'] = $_POST['expenses_salary'];
    $_SESSION['expenses_mobile_fee'] = $_POST['expenses_mobile_fee'];
    $_SESSION['expenses_other_amount'] = $_POST['expenses_other_amount'];
    $_SESSION['expenses_total'] = $_POST['expenses_total'];
} else {
    // Redirect back if accessed directly
    header("Location: expenses_entry.php");
    exit();
}

// Retrieve data from session for display
$transaction_date = $_SESSION['transaction_date'] ?? '';
$truck_id = $_SESSION['truck_id'] ?? '';
$transactions = $_SESSION['transactions'] ?? [];
$fuel_data = [
    'date' => $_SESSION['fuel_date'] ?? '',
    'liters' => $_SESSION['fuel_liters'] ?? 0,
    'unit_price' => $_SESSION['fuel_unit_price'] ?? 0,
    'type' => $_SESSION['fuel_type'] ?? '',
    'amount' => $_SESSION['fuel_amount'] ?? 0
];
$expenses_data = [
    'date' => $_SESSION['expenses_date'] ?? '',
    'salary' => $_SESSION['expenses_salary'] ?? 0,
    'mobile_fee' => $_SESSION['expenses_mobile_fee'] ?? 0,
    'other_amount' => $_SESSION['expenses_other_amount'] ?? 0,
    'total' => $_SESSION['expenses_total'] ?? 0
];

// Fetch truck details
if ($truck_id) {
    $truck_query = "SELECT PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
    $stmt = $conn->prepare($truck_query);
    $stmt->bind_param("i", $truck_id);
    $stmt->execute();
    $truck_result = $stmt->get_result();
    $truck = $truck_result->fetch_assoc();
    $stmt->close();
} else {
    // Redirect back if truck_id is not set
    header("Location: add_data.php");
    exit();
}

// Calculate Total KGs
$total_kgs = array_sum(array_column($transactions, 'kgs'));

// Store Original Total KGs in session
$_SESSION['total_kgs'] = $total_kgs;

// Round up Total KGs as per the rules
$rounded_total_kgs = round_up_kgs($total_kgs);
$_SESSION['rounded_total_kgs'] = $rounded_total_kgs;

// Retrieve ClusterID using the OutletName from the first transaction
if (!empty($transactions)) {
    $first_outlet_name = $transactions[0]['outletName'];
    $customer_query = "SELECT CustomerID, ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?)";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("s", $first_outlet_name);
    $stmt->execute();
    $customer_result = $stmt->get_result();

    if ($customer_result->num_rows > 0) {
        $customer = $customer_result->fetch_assoc();
        $cluster_id = $customer['ClusterID'];
        $_SESSION['cluster_id'] = $cluster_id; // Store ClusterID in session
    } else {
        // Handle the case where the OutletName does not exist in the Customers table
        $cluster_id = null;
        $customer_id = null;
        $error_message = "The Outlet Name '{$first_outlet_name}' does not exist in the Customers table.";
    }
    $stmt->close();
} else {
    $error_message = "No transactions found.";
}

// If there was an error, display it and provide a way to go back
if (isset($error_message)) {
    ?>
    <div class="body-wrapper">
        <div class="container-fluid">
            <div class="card card-body py-3">
                <h4 class="card-title">Error in Transaction Summary</h4>
                <p class="card-text"><?php echo htmlspecialchars($error_message); ?></p>
                <a href="transactions_entry.php" class="btn btn-danger">Go Back to Edit Transactions</a>
            </div>
        </div>
    </div>
    <?php
    include '../super-admin/footer.php';
    $conn->close();
    exit();
}

// Fetch RateAmount from clusters table based on ClusterID, FuelPrice, and Tonner
if ($cluster_id && $rounded_total_kgs > 0) {
    // Calculate Tonner (assuming 1 Ton = 1000 KGs)
    $tonner = $rounded_total_kgs;

    // Fetch RateAmount from clusters based on ClusterID, FuelPrice, and Tonner
    $cluster_query = "SELECT RateAmount FROM clusters 
                      WHERE ClusterID = ? 
                        AND FuelPrice = ? 
                        AND Tonner = ?";
    $stmt = $conn->prepare($cluster_query);
    if (!$stmt) {
        // Handle error
        ?>
        <div class="body-wrapper">
            <div class="container-fluid">
                <div class="card card-body py-3">
                    <h4 class="card-title">Error</h4>
                    <p class="card-text">Database error: <?php echo htmlspecialchars($conn->error); ?></p>
                    <a href="transaction_summary.php" class="btn btn-danger">Go Back</a>
                </div>
            </div>
        </div>
        <?php
        include '../super-admin/footer.php';
        $conn->close();
        exit();
    }
    $fuel_unit_price = $_SESSION['fuel_unit_price'];
    $stmt->bind_param("idd", $cluster_id, $fuel_unit_price, $tonner);
    $stmt->execute();
    $cluster_result = $stmt->get_result();

    if ($cluster_result->num_rows > 0) {
        $cluster = $cluster_result->fetch_assoc();
        $rate_amount = $cluster['RateAmount']; // Retrieved based on ClusterID, FuelPrice, and Tonner
        $_SESSION['rate_amount'] = $rate_amount; // Store RateAmount in session
    } else {
        throw new Exception("No RateAmount found in clusters table for ClusterID '{$cluster_id}', FuelPrice '{$fuel_unit_price}', and Tonner '{$tonner}'.");
    }
    $stmt->close();
} else {
    $rate_amount = 0;
    $_SESSION['rate_amount'] = $rate_amount; // Store RateAmount in session
}

// Retrieve TotalExpense from expenses data
$total_expense = $expenses_data['total'];

// Retrieve Toll Fee Amount from session
$toll_fee_amount = $_SESSION['toll_fee_amount'] ?? 0;

// Calculate Final Amount
$final_amount = $rate_amount + $toll_fee_amount;
$_SESSION['final_amount'] = $final_amount; // Store Final Amount in session
?>

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Transaction Summary Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Transaction Summary</h4>
                <p class="card-subtitle">Review and confirm all transaction details.</p>
            </div>
            <div class="card-body">
                <form id="summary-form" method="POST" action="save_transaction_group.php">
                    <!-- Truck Details -->
                    <h5 class="mt-4">Truck Details</h5>
                    <p><strong>Plate No:</strong> <?php echo htmlspecialchars($truck['PlateNo']); ?></p>
                    <p><strong>Truck Brand:</strong> <?php echo htmlspecialchars($truck['TruckBrand']); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($transaction_date); ?></p>

                    <!-- Transactions Summary -->
                    <h5 class="mt-4">Transactions Summary</h5>
                    <table class="table table-striped table-bordered text-nowrap align-middle text-center">
                        <thead>
                            <tr>
                                <th>DR No</th>
                                <th>Outlet Name</th>
                                <th>Quantity</th>
                                <th>KGs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($txn['drNo']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['outletName']); ?></td>
                                    <td><?php echo number_format($txn['quantity'], 2); ?></td>
                                    <td><?php echo number_format($txn['kgs'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Fuel Details -->
                    <h5 class="mt-4">Fuel Details</h5>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($fuel_data['date']); ?></p>
                    <p><strong>Liters:</strong> <?php echo number_format($fuel_data['liters'], 2); ?></p>
                    <p><strong>Unit Price:</strong> ₱<?php echo number_format($fuel_data['unit_price'], 2); ?></p>
                    <p><strong>Fuel Type:</strong> <?php echo htmlspecialchars($fuel_data['type']); ?></p>
                    <p><strong>Amount:</strong> ₱<?php echo number_format($fuel_data['amount'], 2); ?></p>

                    <!-- Expenses Details -->
                    <h5 class="mt-4">Expenses Details</h5>
                    <p><strong>Salary Amount:</strong> ₱<?php echo number_format($expenses_data['salary'], 2); ?></p>
                    <p><strong>Mobile Fee Amount:</strong>
                        ₱<?php echo number_format($expenses_data['mobile_fee'], 2); ?></p>
                    <p><strong>Other Amount:</strong> ₱<?php echo number_format($expenses_data['other_amount'], 2); ?>
                    </p>
                    <p><strong>Total Expense:</strong> ₱<?php echo number_format($expenses_data['total'], 2); ?></p>

                    <!-- Toll Fee Amount -->
                    <h5 class="mt-4">Toll Fee Amount</h5>
                    <div class="mb-3">
                        <label for="toll-fee-amount" class="form-label">Toll Fee Amount</label>
                        <input type="number" class="form-control" id="toll-fee-amount" name="toll_fee_amount"
                            step="0.01" required>
                    </div>

                    <!-- Calculated Fields -->
                    <h5 class="mt-4">Calculated Totals</h5>
                    <p><strong>Original Total KGs:</strong> <?php echo number_format($total_kgs, 2); ?></p>
                    <p><strong>Rounded Total KGs:</strong> <?php echo number_format($rounded_total_kgs, 0); ?></p>
                    <p><strong>Cluster ID:</strong> <?php echo htmlspecialchars($cluster_id); ?></p>
                    <p><strong>Rate Amount:</strong> ₱<?php echo number_format($rate_amount, 2); ?></p>
                    <p><strong>Final Amount:</strong> ₱<?php echo number_format($final_amount, 2); ?></p>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="expenses_entry.php" class="btn btn-secondary">Back</a>
                        <button type="submit" class="btn btn-primary">Confirm and Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../super-admin/footer.php';
$conn->close();
?>