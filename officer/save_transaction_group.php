<?php
session_start();
include '../includes/db_connection.php';

// Enable error reporting for debugging (ensure this is disabled in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user confirmed the transaction group
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start a database transaction
    $conn->begin_transaction();

    try {
        // Insert into expenses table
        $expenses_query = "INSERT INTO expenses (Date, SalaryAmount, TollFeeAmount, MobileAmount, OtherAmount, TotalExpense)
                           VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($expenses_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for expenses query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "sddddd",
            $_SESSION['expenses_date'],
            $_SESSION['expenses_salary'],
            $_SESSION['expenses_toll_fee'],
            $_SESSION['expenses_mobile_fee'],
            $_SESSION['expenses_other_amount'],
            $_SESSION['expenses_total']
        );
        $stmt->execute();
        $expense_id = $stmt->insert_id;
        $stmt->close();

        // Insert into fuel table
        $fuel_query = "INSERT INTO fuel (Date, Liters, UnitPrice, FuelType, Amount)
                       VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($fuel_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for fuel query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "sddsd",
            $_SESSION['fuel_date'],
            $_SESSION['fuel_liters'],
            $_SESSION['fuel_unit_price'],
            $_SESSION['fuel_type'],
            $_SESSION['fuel_amount']
        );
        $stmt->execute();
        $fuel_id = $stmt->insert_id;
        $stmt->close();

        // Ensure that $total_kgs is defined
        if (isset($_SESSION['transactions'])) {
            $total_kgs = array_sum(array_column($_SESSION['transactions'], 'kgs'));
        } else {
            throw new Exception("Total KGs cannot be calculated because transactions data is missing.");
        }

        // Retrieve ClusterID using the OutletName from the first transaction
        $first_outlet_name = $_SESSION['transactions'][0]['outletName'];
        $customer_query = "SELECT CustomerID, ClusterID FROM customers WHERE CustomerName = ?";
        $stmt = $conn->prepare($customer_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for customer query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param("s", $first_outlet_name);
        $stmt->execute();
        $customer_result = $stmt->get_result();

        if ($customer_result->num_rows > 0) {
            $customer = $customer_result->fetch_assoc();
            $cluster_id = $customer['ClusterID'];
        } else {
            throw new Exception("The Outlet Name '{$first_outlet_name}' does not exist in the Customers table.");
        }
        $stmt->close();

        // Retrieve RateAmount from clusters table
        $cluster_query = "SELECT RateAmount FROM clusters WHERE ClusterID = ?";
        $stmt = $conn->prepare($cluster_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for cluster query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param("i", $cluster_id);
        $stmt->execute();
        $cluster_result = $stmt->get_result();

        if ($cluster_result->num_rows > 0) {
            $cluster = $cluster_result->fetch_assoc();
            $rate_amount_per_kg = $cluster['RateAmount']; // Assuming RateAmount is per KG
            $rate_amount = $rate_amount_per_kg * $total_kgs;
        } else {
            throw new Exception("ClusterID '{$cluster_id}' does not exist in the Clusters table.");
        }
        $stmt->close();

        // Retrieve TotalExpense from expenses data
        $total_expense = $_SESSION['expenses_total'];

        // Calculate Final Amount
        $amount = $rate_amount + $total_expense;

        // Insert into transactiongroup table with BillingInvoiceNo set to NULL
        $transaction_group_query = "INSERT INTO transactiongroup (TruckID, Date, RateAmount, Amount, TotalKGs, ExpenseID, FuelID, BillingInvoiceNo)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NULL)";
        $stmt = $conn->prepare($transaction_group_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transaction group query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "isddiii",
            $_SESSION['truck_id'],            // "i" - integer
            $_SESSION['transaction_date'],    // "s" - string (date)
            $rate_amount,                     // "d" - double (decimal)
            $amount,                          // "d" - double (decimal)
            $total_kgs,                       // "d" - double (decimal)
            $expense_id,                      // "i" - integer
            $fuel_id                          // "i" - integer
            // BillingInvoiceNo is set to NULL directly in the query
        );
        $stmt->execute();
        $transaction_group_id = $stmt->insert_id;
        $stmt->close();

        // No need to update BillingInvoiceNo now, as it should be NULL

        // Insert transactions into transactions table
        $insert_transaction_query = "INSERT INTO transactions (TransactionGroupID, TransactionDate, DRno, OutletName, Qty, KGs)
                                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_transaction_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transactions query: ({$conn->errno}) {$conn->error}");
        }
        foreach ($_SESSION['transactions'] as $txn) {
            $stmt->bind_param(
                "isissd",
                $transaction_group_id,         // "i" - integer
                $_SESSION['transaction_date'], // "s" - string (date)
                $txn['drNo'],                  // "i" - integer
                $txn['outletName'],            // "s" - string
                $txn['quantity'],              // "d" - double (decimal)
                $txn['kgs']                    // "d" - double (decimal)
            );
            $stmt->execute();
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        // Clear session data
        session_unset();

        // Redirect to a success page or display a success message
        include '../officer/header.php';
        ?>
        
        <div class="body-wrapper">
            <div class="container-fluid">
                <div class="card card-body py-3">
                    <h4 class="card-title">Transaction Saved Successfully</h4>
                    <p>Your transaction group has been saved with Billing Invoice No: <strong>To Be Assigned</strong></p>
                    <a href="add_data.php" class="btn btn-primary">Add Another Transaction</a>
                </div>
            </div>
        </div>
        <?php
        include '../officer/footer.php';

    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        $conn->rollback();
        // Display an error message
        include '../officer/header.php';
        ?>
        <div class="body-wrapper">
            <div class="container-fluid">
                <div class="card card-body py-3">
                    <h4 class="card-title">Error Saving Transaction</h4>
                    <p>An error occurred while saving the transaction: <?php echo htmlspecialchars($e->getMessage()); ?></p>
                    <a href="transaction_summary.php" class="btn btn-danger">Go Back to Summary</a>
                </div>
            </div>
        </div>
        <?php
        include '../officer/footer.php';
    }
} else {
    // Redirect back if accessed directly
    header("Location: add_data.php");
    exit();
}

$conn->close();
?>
