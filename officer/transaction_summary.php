<?php
session_start();
include '../officer/header.php';
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

// Retrieve all data from session or redirect back if not set
if (isset($_SESSION['transactions'], $_SESSION['fuel_liters'], $_SESSION['expenses_salary'])) {
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
        'mobile_fee' => $_SESSION['expenses_mobile_fee'],
        'other_amount' => $_SESSION['expenses_other_amount'],
        'total' => $_SESSION['expenses_total']
    ];
    $toll_fee_amount = $_SESSION['toll_fee_amount'] ?? 0;
} else {
    // Redirect back if session data is missing
    header("Location: expenses_entry.php");
    exit();
}

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
    include '../officer/footer.php';
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
        include '../officer/footer.php';
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
        $rate_amount = 0;
        $_SESSION['rate_amount'] = $rate_amount; // Store RateAmount in session
    }
    $stmt->close();
} else {
    $rate_amount = 0;
    $_SESSION['rate_amount'] = $rate_amount; // Store RateAmount in session
}

// Retrieve TotalExpense from expenses data
$total_expense = $expenses_data['total'];

// Calculate Final Amount
$final_amount = $rate_amount + $toll_fee_amount;
$_SESSION['final_amount'] = $final_amount; // Store Final Amount in session
?>

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Transaction Summary Card -->
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Add Data</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Transactions Summary
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Transaction Summary</h4>
                <p class="card-subtitle">Review and edit all transaction details.</p>
            </div>
            <div class="card-body">
                <form id="summary-form" method="POST" action="save_transaction_group.php">
                    <!-- Truck Details -->
                    <h5>Truck Details</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="plate-no" class="form-label">Plate No</label>
                            <input type="text" class="form-control" id="plate-no" name="plate_no"
                                value="<?php echo htmlspecialchars($truck['PlateNo']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="truck-brand" class="form-label">Truck Brand</label>
                            <input type="text" class="form-control" id="truck-brand" name="truck_brand"
                                value="<?php echo htmlspecialchars($truck['TruckBrand']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="transaction-date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="transaction-date" name="transaction_date"
                                value="<?php echo htmlspecialchars($transaction_date); ?>" required>
                        </div>
                    </div>

                    <!-- Transactions Summary -->
                    <h5 class="mt-4">Transactions</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle text-center">
                            <thead>
                                <tr>
                                    <th>DR No</th>
                                    <th>Outlet Name</th>
                                    <th>Quantity</th>
                                    <th>KGs</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-table-body">
                                <?php foreach ($transactions as $index => $txn): ?>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control"
                                                name="transactions[<?php echo $index; ?>][drNo]"
                                                value="<?php echo htmlspecialchars($txn['drNo']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control"
                                                name="transactions[<?php echo $index; ?>][outletName]"
                                                value="<?php echo htmlspecialchars($txn['outletName']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control transaction-quantity"
                                                name="transactions[<?php echo $index; ?>][quantity]"
                                                value="<?php echo number_format($txn['quantity'], 2, '.', ''); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control transaction-kgs"
                                                name="transactions[<?php echo $index; ?>][kgs]"
                                                value="<?php echo number_format($txn['kgs'], 2, '.', ''); ?>" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger remove-transaction"><i
                                                    class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success mb-3" id="add-transaction"><i
                                class="fas fa-plus"></i> Add Transaction</button>
                    </div>

                    <!-- Fuel Details -->
                    <h5 class="mt-4">Fuel Details</h5>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="fuel-date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="fuel-date" name="fuel_date"
                                value="<?php echo htmlspecialchars($fuel_data['date']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fuel-liters" class="form-label">Liters</label>
                            <input type="number" step="0.01" class="form-control" id="fuel-liters" name="fuel_liters"
                                value="<?php echo number_format($fuel_data['liters'], 2, '.', ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fuel-unit-price" class="form-label">Unit Price</label>
                            <input type="number" step="0.01" class="form-control" id="fuel-unit-price"
                                name="fuel_unit_price"
                                value="<?php echo number_format($fuel_data['unit_price'], 2, '.', ''); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fuel-type" class="form-label">Fuel Type</label>
                            <select class="form-select" id="fuel-type" name="fuel_type" required>
                                <option value="" disabled>Select fuel type</option>
                                <option value="Diesel" <?php echo ($fuel_data['type'] == 'Diesel') ? 'selected' : ''; ?>>
                                    Diesel</option>
                                <option value="Gasoline" <?php echo ($fuel_data['type'] == 'Gasoline') ? 'selected' : ''; ?>>Gasoline</option>
                            </select>
                        </div>
                    </div>

                    <!-- Expenses Details -->
                    <h5 class="mt-4">Expenses Details</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="expenses-salary" class="form-label">Salary Amount</label>
                            <input type="number" step="0.01" class="form-control expense-input" id="expenses-salary"
                                name="expenses_salary"
                                value="<?php echo number_format($expenses_data['salary'], 2, '.', ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="expenses-mobile-fee" class="form-label">Mobile Fee Amount</label>
                            <input type="number" step="0.01" class="form-control expense-input" id="expenses-mobile-fee"
                                name="expenses_mobile_fee"
                                value="<?php echo number_format($expenses_data['mobile_fee'], 2, '.', ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="expenses-other-amount" class="form-label">Other Amount</label>
                            <input type="number" step="0.01" class="form-control expense-input"
                                id="expenses-other-amount" name="expenses_other_amount"
                                value="<?php echo number_format($expenses_data['other_amount'], 2, '.', ''); ?>"
                                required>
                        </div>
                    </div>

                    <!-- Toll Fee Amount -->
                    <h5 class="mt-4">Toll Fee Amount</h5>
                    <div class="mb-3">
                        <label for="toll-fee-amount" class="form-label">Toll Fee Amount</label>
                        <input type="number" class="form-control" id="toll-fee-amount" name="toll_fee_amount"
                            value="<?php echo number_format($toll_fee_amount, 2, '.', ''); ?>" step="0.01" required>
                    </div>

                    <!-- Calculated Fields -->
                    <h5 class="mt-4">Calculated Totals</h5>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Original Total KGs</label>
                            <input type="number" step="0.01" class="form-control" id="original-total-kgs"
                                name="original_total_kgs" value="<?php echo number_format($total_kgs, 2, '.', ''); ?>"
                                readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rounded Total KGs</label>
                            <input type="number" class="form-control" id="rounded-total-kgs" name="rounded_total_kgs"
                                value="<?php echo number_format($rounded_total_kgs, 0, '.', ''); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rate Amount</label>
                            <input type="number" step="0.01" class="form-control" id="rate-amount" name="rate_amount"
                                value="<?php echo number_format($rate_amount, 2, '.', ''); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Expenses</label>
                            <input type="number" step="0.01" class="form-control" id="total-expenses"
                                name="total_expenses" value="<?php echo number_format($total_expense, 2, '.', ''); ?>"
                                readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Final Amount</label>
                            <input type="number" step="0.01" class="form-control" id="final-amount" name="final_amount"
                                value="<?php echo number_format($final_amount, 2, '.', ''); ?>" readonly>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="expenses_entry.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>
                            Back</a>
                        <button type="submit" class="btn btn-primary">Confirm and Save <i
                                class="fas fa-check ms-1"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to handle dynamic calculations and table actions -->
<script>
    // Function to calculate total expenses
    function calculateTotalExpenses() {
        const salary = parseFloat(document.getElementById('expenses-salary').value) || 0;
        const mobileFee = parseFloat(document.getElementById('expenses-mobile-fee').value) || 0;
        const otherAmount = parseFloat(document.getElementById('expenses-other-amount').value) || 0;
        const totalExpenses = salary + mobileFee + otherAmount;
        document.getElementById('total-expenses').value = totalExpenses.toFixed(2);
    }

    // Function to calculate final amount
    function calculateFinalAmount() {
        const rateAmount = parseFloat(document.getElementById('rate-amount').value) || 0;
        const tollFeeAmount = parseFloat(document.getElementById('toll-fee-amount').value) || 0;
        const finalAmount = rateAmount + tollFeeAmount;
        document.getElementById('final-amount').value = finalAmount.toFixed(2);
    }

    // Event listeners for expenses inputs
    document.querySelectorAll('.expense-input').forEach(input => {
        input.addEventListener('input', () => {
            calculateTotalExpenses();
        });
    });

    // Event listener for toll fee amount
    document.getElementById('toll-fee-amount').addEventListener('input', () => {
        calculateFinalAmount();
    });

    // Event listeners for transaction quantities and KGs
    function updateTransactionTotals() {
        let totalKgs = 0;
        document.querySelectorAll('.transaction-kgs').forEach(input => {
            totalKgs += parseFloat(input.value) || 0;
        });
        document.getElementById('original-total-kgs').value = totalKgs.toFixed(2);

        // Assuming rounded_total_kgs is recalculated on the server
        // Here we can mimic the round_up_kgs function if needed
    }

    document.querySelectorAll('.transaction-kgs').forEach(input => {
        input.addEventListener('input', updateTransactionTotals);
    });

    // Add transaction row
    document.getElementById('add-transaction').addEventListener('click', () => {
        const tbody = document.getElementById('transactions-table-body');
        const index = tbody.children.length;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" class="form-control" name="transactions[${index}][drNo]" required></td>
            <td><input type="text" class="form-control" name="transactions[${index}][outletName]" required></td>
            <td><input type="number" step="0.01" class="form-control transaction-quantity" name="transactions[${index}][quantity]" required></td>
            <td><input type="number" step="0.01" class="form-control transaction-kgs" name="transactions[${index}][kgs]" required></td>
            <td><button type="button" class="btn btn-danger remove-transaction"><i class="fas fa-trash-alt"></i></button></td>
        `;
        tbody.appendChild(row);

        // Add event listener to the new kgs input
        row.querySelector('.transaction-kgs').addEventListener('input', updateTransactionTotals);

        // Add event listener to the remove button
        row.querySelector('.remove-transaction').addEventListener('click', () => {
            row.remove();
            updateTransactionTotals();
        });
    });

    // Remove transaction row
    document.querySelectorAll('.remove-transaction').forEach(button => {
        button.addEventListener('click', (e) => {
            e.target.closest('tr').remove();
            updateTransactionTotals();
        });
    });

    // Initial calculations
    calculateTotalExpenses();
    calculateFinalAmount();
    updateTransactionTotals();
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>