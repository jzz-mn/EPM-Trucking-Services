<?php
session_start();
include '../includes/db_connection.php';

// Enable error reporting for debugging (disable in production)
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

// Check if the user confirmed the transaction group
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize TollFeeAmount
    if (isset($_POST['toll_fee_amount']) && is_numeric($_POST['toll_fee_amount'])) {
        $_SESSION['toll_fee_amount'] = floatval($_POST['toll_fee_amount']);
    } else {
        // Handle invalid TollFeeAmount
        header("Location: transaction_summary.php?error=invalid_toll_fee");
        exit();
    }

    // Start a database transaction
    $conn->begin_transaction();

    try {
        // ---- Step 1: Insert into fuel table ----
        $fuel_query = "INSERT INTO fuel (Date, Liters, UnitPrice, FuelType, Amount)
                       VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($fuel_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for fuel query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "sddsd",
            $_SESSION['fuel_date'],      // "s" - string (date)
            $_SESSION['fuel_liters'],    // "d" - double (Liters)
            $_SESSION['fuel_unit_price'],// "d" - double (UnitPrice)
            $_SESSION['fuel_type'],      // "s" - string (FuelType)
            $_SESSION['fuel_amount']     // "d" - double (Amount)
        );
        $stmt->execute();
        $fuel_id = $stmt->insert_id;
        $stmt->close();

        // ---- Step 2: Insert into expenses table ----
        // TotalExpense = fuel.amount + SalaryAmount + MobileAmount + OtherAmount
        $total_expense = $_SESSION['fuel_amount'] + $_SESSION['expenses_salary'] + $_SESSION['expenses_mobile_fee'] + $_SESSION['expenses_other_amount'];

        $expenses_query = "INSERT INTO expenses (Date, SalaryAmount, MobileAmount, OtherAmount, TotalExpense, FuelID)
                           VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($expenses_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for expenses query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "sddddi",
            $_SESSION['expenses_date'],    // "s" - string (date)
            $_SESSION['expenses_salary'],  // "d" - double (SalaryAmount)
            $_SESSION['expenses_mobile_fee'], // "d" - double (MobileAmount)
            $_SESSION['expenses_other_amount'], // "d" - double (OtherAmount)
            $total_expense,                // "d" - double (TotalExpense)
            $fuel_id                       // "i" - integer (FuelID)
        );
        $stmt->execute();
        $expense_id = $stmt->insert_id;
        $stmt->close();

        // ---- Step 3: Calculate Rounded Total KGs ----
        $total_kgs = $_SESSION['total_kgs'];
        $rounded_total_kgs = round_up_kgs($total_kgs);
        $_SESSION['rounded_total_kgs'] = $rounded_total_kgs;

        // ---- Step 4: Retrieve ClusterID using the OutletName from the first transaction ----
        $transactions = $_SESSION['transactions'] ?? [];
        if (!empty($transactions)) {
            $first_outlet_name = $transactions[0]['outletName'];
            $customer_query = "SELECT CustomerID, ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?)";
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
                $_SESSION['cluster_id'] = $cluster_id; // Store ClusterID in session
            } else {
                // Handle the case where the OutletName does not exist in the Customers table
                throw new Exception("The Outlet Name '{$first_outlet_name}' does not exist in the Customers table.");
            }
            $stmt->close();
        } else {
            throw new Exception("No transactions found.");
        }

        // ---- Step 5: Retrieve RateAmount from clusters table based on ClusterID, Rounded Total KGs, and FuelPrice ----
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
                throw new Exception("Prepare failed for cluster query: ({$conn->errno}) {$conn->error}");
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

        // ---- Step 6: Retrieve TotalExpense from expenses data ----
        // Already computed as $total_expense during Step 2

        // ---- Step 7: Retrieve Toll Fee Amount from session ----
        $toll_fee_amount = $_SESSION['toll_fee_amount'];

        // ---- Step 8: Calculate Final Amount ----
        // Final Amount = RateAmount + TollFeeAmount
        $final_amount = $rate_amount + $toll_fee_amount;
        $_SESSION['final_amount'] = $final_amount; // Store Final Amount in session

        // ---- Step 9: Insert into transactiongroup table ----
        $transaction_group_query = "INSERT INTO transactiongroup (TruckID, Date, TollFeeAmount, RateAmount, Amount, TotalKGs, ExpenseID)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($transaction_group_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transactiongroup query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "isddiid",
            $_SESSION['truck_id'],          // "i" - integer
            $_SESSION['transaction_date'],  // "s" - string (date)
            $toll_fee_amount,               // "d" - double (decimal)
            $rate_amount,                   // "d" - double (decimal)
            $final_amount,                  // "d" - double (decimal)
            $total_kgs,             // "i" - integer (Rounded Total KGs)
            $expense_id                     // "i" - integer (ExpenseID)
        );
        $stmt->execute();
        $transaction_group_id = $stmt->insert_id;
        $stmt->close();

        // ---- Step 10: Insert transactions into transactions table ----
        $insert_transaction_query = "INSERT INTO transactions (TransactionGroupID, TransactionDate, DRno, OutletName, Qty, KGs)
                                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_transaction_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transactions query: ({$conn->errno}) {$conn->error}");
        }
        foreach ($transactions as $txn) {
            // Ensure that 'quantity' and 'kgs' are numeric values
            $quantity = is_numeric($txn['quantity']) ? floatval($txn['quantity']) : 0;
            $kgs = is_numeric($txn['kgs']) ? floatval($txn['kgs']) : 0;

            $stmt->bind_param(
                "isissd",
                $transaction_group_id,         // "i" - integer
                $_SESSION['transaction_date'], // "s" - string (date)
                $txn['drNo'],                  // "i" - integer
                $txn['outletName'],            // "s" - string
                $quantity,                     // "s" - string (if Qty is string; change to "d" if decimal)
                $kgs                           // "d" - double (decimal)
            );
            $stmt->execute();
        }
        $stmt->close();

        // ---- Step 11: Commit the transaction ----
        $conn->commit();

        // ---- Step 12: Clear session data ----
        session_unset();

        // ---- Step 13: Redirect to a success page or display a success message ----
        include '../super-admin/header.php';
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
        include '../super-admin/footer.php';

    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        $conn->rollback();
        // Display an error message
        include '../super-admin/header.php';
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
        include '../super-admin/footer.php';
    }
} else {
    // Redirect back if accessed directly
    header("Location: add_data.php");
    exit();
}

$conn->close();
?>
