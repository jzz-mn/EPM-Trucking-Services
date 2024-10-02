<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';

// Check if expenses data was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store expenses data in session
    $_SESSION['expenses_date'] = $_POST['expenses_date'];
    $_SESSION['expenses_salary'] = $_POST['expenses_salary'];
    $_SESSION['expenses_toll_fee'] = $_POST['expenses_toll_fee'];
    $_SESSION['expenses_mobile_fee'] = $_POST['expenses_mobile_fee'];
    $_SESSION['expenses_other_amount'] = $_POST['expenses_other_amount'];
    $_SESSION['expenses_total'] = $_POST['expenses_total'];
} else {
    // Redirect back if accessed directly
    header("Location: expenses_entry.php");
    exit();
}

// Retrieve data from session for display
$transaction_date = $_SESSION['transaction_date'];
$truck_id = $_SESSION['truck_id'];
$transactions = $_SESSION['transactions'];
$fuel_data = [
    'date' => $_SESSION['fuel_date'],
    'liters' => $_SESSION['fuel_liters'],
    'unit_price' => $_SESSION['fuel_unit_price'],
    'type' => $_SESSION['fuel_type'],
    'amount' => $_SESSION['fuel_amount']
];
$expenses_data = [
    'date' => $_SESSION['expenses_date'],
    'salary' => $_SESSION['expenses_salary'],
    'toll_fee' => $_SESSION['expenses_toll_fee'],
    'mobile_fee' => $_SESSION['expenses_mobile_fee'],
    'other_amount' => $_SESSION['expenses_other_amount'],
    'total' => $_SESSION['expenses_total']
];

// Fetch truck details
$truck_query = "SELECT PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
$stmt = $conn->prepare($truck_query);
$stmt->bind_param("i", $truck_id);
$stmt->execute();
$truck_result = $stmt->get_result();
$truck = $truck_result->fetch_assoc();
$stmt->close();

// Calculate Total KGs
$total_kgs = array_sum(array_column($transactions, 'kgs'));

// Retrieve ClusterID using the OutletName from the first transaction
$first_outlet_name = $transactions[0]['outletName'];
$customer_query = "SELECT CustomerID, ClusterID FROM customers WHERE CustomerName = ?";
$stmt = $conn->prepare($customer_query);
$stmt->bind_param("s", $first_outlet_name);
$stmt->execute();
$customer_result = $stmt->get_result();

if ($customer_result->num_rows > 0) {
    $customer = $customer_result->fetch_assoc();
    $cluster_id = $customer['ClusterID'];
} else {
    // Handle the case where the OutletName does not exist in the Customers table
    $cluster_id = null;
    $customer_id = null;
    $error_message = "The Outlet Name '{$first_outlet_name}' does not exist in the Customers table.";
}
$stmt->close();

// Calculate RateAmount using Clusters table
if ($cluster_id) {
    $cluster_query = "SELECT RateAmount FROM clusters WHERE ClusterID = ?";
    $stmt = $conn->prepare($cluster_query);
    $stmt->bind_param("i", $cluster_id);
    $stmt->execute();
    $cluster_result = $stmt->get_result();
    if ($cluster_result->num_rows > 0) {
        $cluster = $cluster_result->fetch_assoc();
        $rate_amount = $cluster['RateAmount']; // Assuming RateAmount is per KG
    } else {
        $rate_amount = 0;
        $error_message = "ClusterID '{$cluster_id}' does not exist in the Clusters table.";
    }
    $stmt->close();
} else {
    $rate_amount = 0;
}

// Calculate Final Amount
$amount = $rate_amount + $expenses_data['total'];

?>
<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Existing card and breadcrumb code -->
        <div class="card card-body py-3">
            <!-- ... -->
        </div>
        <div class="widget-content searchable-container list">
            <h5 class="border-bottom py-2 px-4 mb-4">Transaction Group Summary</h5>
            <div class="card w-100 border position-relative overflow-hidden mb-0">
                <div class="card-body p-4">
                    <h4 class="card-title">Review and Confirm</h4>
                    <p class="card-subtitle mb-4">Please review all the details before confirming.</p>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                            <br>
                            <a href="transactions_entry.php" class="btn btn-danger mt-2">Go Back to Edit Transactions</a>
                        </div>
                    <?php else: ?>
                        <form id="summary-form" method="POST" action="save_transaction_group.php">
                            <!-- Truck Details -->
                            <h5 class="mt-4">Truck Details</h5>
                            <p>Plate No: <?php echo $truck['PlateNo']; ?></p>
                            <p>Truck Brand: <?php echo $truck['TruckBrand']; ?></p>
                            <p>Date: <?php echo $transaction_date; ?></p>
                            <!-- Transactions Summary -->
                            <h5 class="mt-4">Transactions Summary</h5>
                            <table class="table">
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
                                            <td><?php echo $txn['drNo']; ?></td>
                                            <td><?php echo $txn['outletName']; ?></td>
                                            <td><?php echo $txn['quantity']; ?></td>
                                            <td><?php echo $txn['kgs']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- Fuel Details -->
                            <h5 class="mt-4">Fuel Details</h5>
                            <p>Date: <?php echo $fuel_data['date']; ?></p>
                            <p>Liters: <?php echo $fuel_data['liters']; ?></p>
                            <p>Unit Price: <?php echo $fuel_data['unit_price']; ?></p>
                            <p>Fuel Type: <?php echo $fuel_data['type']; ?></p>
                            <p>Amount: <?php echo $fuel_data['amount']; ?></p>
                            <!-- Expenses Details -->
                            <h5 class="mt-4">Expenses Details</h5>
                            <p>Salary Amount: <?php echo $expenses_data['salary']; ?></p>
                            <p>Toll Fee Amount: <?php echo $expenses_data['toll_fee']; ?></p>
                            <p>Mobile Fee Amount: <?php echo $expenses_data['mobile_fee']; ?></p>
                            <p>Other Amount: <?php echo $expenses_data['other_amount']; ?></p>
                            <p>Total Expense: <?php echo $expenses_data['total']; ?></p>
                            <!-- Calculated Fields -->
                            <h5 class="mt-4">Calculated Totals</h5>
                            <p>Total KGs: <?php echo $total_kgs; ?></p>
                            <p>Cluster ID: <?php echo $cluster_id; ?></p>
                            <p>Rate Amount: <?php echo number_format($rate_amount, 2); ?></p>
                            <p>Final Amount: <?php echo number_format($amount, 2); ?></p>
                            <!-- Buttons -->
                            <button type="submit" class="btn btn-success">Confirm and Save</button>
                            <a href="add_data.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../officer/footer.php';
$conn->close();
?>