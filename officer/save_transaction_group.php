<?php
session_start();
include '../includes/db_connection.php';

// Enable error reporting for debugging (ensure this is disabled in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Function to round up Total KGs based on the specified rules.
 * 
 * Rules:
 * - 0 < KGs <= 1199 => 1000
 * - 1200 <= KGs <= 2199 => 2000
 * - 2200 <= KGs <= 3199 => 3000
 * - 3200 <= KGs <= 4199 => 4000
 * - ... and so on.
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
    return ceil($kgs / 1000) * 1000;
}

// Check if the user confirmed the transaction group
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start a database transaction
    $conn->begin_transaction();

    try {
        // ---- Step 1: Insert into expenses table ----
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

        // ---- Step 2: Insert into fuel table ----
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

        // ---- Step 3: Calculate Total KGs and Round Up ----
        if (isset($_SESSION['transactions']) && is_array($_SESSION['transactions'])) {
            $total_kgs = array_sum(array_column($_SESSION['transactions'], 'kgs'));
            $rounded_kgs = round_up_kgs($total_kgs);
        } else {
            throw new Exception("Total KGs cannot be calculated because transactions data is missing.");
        }

        // ---- Step 4: Retrieve ClusterID using the OutletName from the first transaction ----
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

        // ---- Step 5: Retrieve RateAmount from clusters table based on ClusterID and Rounded TotalKGs ----
        if ($cluster_id && $rounded_kgs > 0) {
            $cluster_query = "SELECT RateAmount FROM clusters WHERE ClusterID = ? AND Tonner = ?";
            $stmt = $conn->prepare($cluster_query);
            if (!$stmt) {
                throw new Exception("Prepare failed for cluster query: ({$conn->errno}) {$conn->error}");
            }
            $stmt->bind_param("ii", $cluster_id, $rounded_kgs);
            $stmt->execute();
            $cluster_result = $stmt->get_result();

            if ($cluster_result->num_rows > 0) {
                $cluster = $cluster_result->fetch_assoc();
                $rate_amount = $cluster['RateAmount']; // Assuming RateAmount is per KG
            } else {
                throw new Exception("No RateAmount found in clusters table for ClusterID '{$cluster_id}' and Rounded KGs '{$rounded_kgs}'.");
            }
            $stmt->close();
        } else {
            throw new Exception("Invalid ClusterID or Total KGs for RateAmount calculation.");
        }

        // ---- Step 6: Retrieve TotalExpense from expenses data ----
        $total_expense = $_SESSION['expenses_total'];

        // ---- Step 7: Calculate Final Amount ----
        $amount = $rate_amount + $total_expense;

        // ---- Step 8: Insert into transactiongroup table with BillingInvoiceNo set to NULL ----
        $transaction_group_query = "INSERT INTO transactiongroup (TruckID, Date, RateAmount, Amount, TotalKGs, ExpenseID, FuelID, BillingInvoiceNo)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NULL)";
        $stmt = $conn->prepare($transaction_group_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transaction groups query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "isddiii",
            $_SESSION['truck_id'],            // "i" - integer
            $_SESSION['transaction_date'],    // "s" - string (date)
            $rate_amount,                     // "d" - double (decimal)
            $amount,                          // "d" - double (decimal)
            $rounded_kgs,                     // "d" - double (decimal)
            $expense_id,                      // "i" - integer
            $fuel_id                          // "i" - integer
            // BillingInvoiceNo is set to NULL directly in the query
        );
        $stmt->execute();
        $transaction_group_id = $stmt->insert_id;
        $stmt->close();

        // ---- Step 9: Insert transactions into transactions table ----
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
                $txn['quantity'],              // "s" - string (assuming Qty is string; use "d" if decimal)
                $txn['kgs']                    // "d" - double (decimal)
            );
            $stmt->execute();
        }
        $stmt->close();

        // ---- Step 10: Commit the transaction ----
        $conn->commit();

        // ---- Step 11: Clear session data ----
        session_unset();

        // ---- Step 12: Redirect to a success page or display a success message ----
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