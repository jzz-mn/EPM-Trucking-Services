<?php
// Start the session and verify user authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['UserID'])) {
    // Redirect to login page if not logged in
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection file
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
        $rounded_kgs = ceil($kgs / 1000) * 1000;
        return $rounded_kgs > 4000 ? 4000 : $rounded_kgs; // Cap at 4000 if exceeded
    }
    return 4000; // Cap at 4000
}

// Check if the user submitted the form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect data from $_POST
    $transaction_date = $_POST['transaction_date'] ?? '';
    $truck_id = $_POST['truck_id'] ?? 0;
    $transactions_json = $_POST['transactions_json'] ?? '';
    $fuel_data = [
        'liters' => $_POST['fuel_liters'] ?? 0,
        'unit_price' => $_POST['fuel_unit_price'] ?? 0, // Ensure 'unit_price' is captured
        'fuel_type' => $_POST['fuel_type'] ?? '',
        'amount' => $_POST['fuel_amount'] ?? 0,
    ];
    $expenses_data = [
        'salary' => $_POST['expenses_salary'] ?? 0,
        'mobile_fee' => $_POST['expenses_mobile_fee'] ?? 0,
        'other_amount' => $_POST['expenses_other_amount'] ?? 0,
        'total' => $_POST['expenses_total'] ?? 0,
    ];
    $toll_fee_amount = $_POST['toll_fee_amount'] ?? 0;

    // Validate essential fields
    if (empty($transaction_date) || empty($truck_id) || empty($transactions_json)) {
        // Handle missing data
        header("Location: add_data.php?error=missing_data");
        exit();
    }

    // Decode transactions JSON
    $transactions = json_decode($transactions_json, true);
    if (empty($transactions)) {
        // Handle invalid or empty transactions
        header("Location: add_data.php?error=invalid_transactions");
        exit();
    }

    // Server-Side Validation: Prevent Duplicate DR Nos Within Current Transactions
    $drNos = array_column($transactions, 'drNo');
    $unique_drNos = array_unique($drNos);
    if (count($drNos) !== count($unique_drNos)) {
        // Duplicate DR Nos found within the submitted transactions
        header("Location: add_data.php?error=duplicate_drnos_within_transactions");
        exit();
    }

    // Server-Side Validation: Prevent Duplicate DR Nos Against Database
    // Prepare a statement with placeholders based on the number of DR Nos
    $placeholders = implode(',', array_fill(0, count($unique_drNos), '?'));
    $types = str_repeat('i', count($unique_drNos)); // Assuming DR No is integer
    $stmt_check = $conn->prepare("SELECT DRno FROM transactions WHERE DRno IN ($placeholders)");
    if (!$stmt_check) {
        // Handle prepare error
        header("Location: add_data.php?error=server_error");
        exit();
    }

    // Bind parameters dynamically
    $stmt_check->bind_param($types, ...$unique_drNos);
    if (!$stmt_check->execute()) {
        // Handle execution error
        $stmt_check->close();
        header("Location: add_data.php?error=server_error");
        exit();
    }

    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        // Some DR Nos already exist in the database
        $existing_drNos = [];
        while ($row = $result_check->fetch_assoc()) {
            $existing_drNos[] = $row['DRno'];
        }
        $stmt_check->close();
        // Optionally, you can redirect with specific DR Nos that are duplicates
        header("Location: add_data.php?error=duplicate_drnos_database&duplicates=" . implode(',', $existing_drNos));
        exit();
    }
    $stmt_check->close();

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
        $fuel_date = $transaction_date; // Assuming fuel date is the same as transaction date
        $stmt->bind_param(
            "sddsd",
            $fuel_date,                           // "s" - string (date)
            $fuel_data['liters'],                 // "d" - double (Liters)
            $fuel_data['unit_price'],             // "d" - double (UnitPrice)
            $fuel_data['fuel_type'],              // "s" - string (FuelType)
            $fuel_data['amount']                  // "d" - double (Amount)
        );
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into fuel table: " . $stmt->error);
        }
        $fuel_id = $stmt->insert_id;
        $stmt->close();

        // ---- Step 2: Insert into expenses table ----
        // TotalExpense is already provided in $expenses_data['total']
        $expenses_query = "INSERT INTO expenses (Date, SalaryAmount, MobileAmount, OtherAmount, TotalExpense, FuelID)
                           VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($expenses_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for expenses query: ({$conn->errno}) {$conn->error}");
        }
        $expenses_date = $transaction_date; // Assuming expenses date is the same as transaction date
        $stmt->bind_param(
            "sddddi",
            $expenses_date,                      // "s" - string (date)
            $expenses_data['salary'],            // "d" - double (SalaryAmount)
            $expenses_data['mobile_fee'],        // "d" - double (MobileAmount)
            $expenses_data['other_amount'],      // "d" - double (OtherAmount)
            $expenses_data['total'],             // "d" - double (TotalExpense)
            $fuel_id                             // "i" - integer (FuelID)
        );
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into expenses table: " . $stmt->error);
        }
        $expense_id = $stmt->insert_id;
        $stmt->close();

        // ---- Step 3: Calculate Rounded Total KGs ----
        $total_kgs = array_sum(array_column($transactions, 'kgs'));
        $rounded_total_kgs = round_up_kgs($total_kgs);

        // ---- Step 4: Retrieve ClusterID using the OutletName from the first transaction ----
        if (!empty($transactions)) {
            $first_outlet_name = $transactions[0]['outletName'];
            $customer_query = "SELECT CustomerID, ClusterID FROM customers WHERE LOWER(CustomerName) = LOWER(?)";
            $stmt = $conn->prepare($customer_query);
            if (!$stmt) {
                throw new Exception("Prepare failed for customer query: ({$conn->errno}) {$conn->error}");
            }
            $stmt->bind_param("s", $first_outlet_name);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute customer query: " . $stmt->error);
            }
            $customer_result = $stmt->get_result();

            if ($customer_result->num_rows > 0) {
                $customer = $customer_result->fetch_assoc();
                $cluster_id = $customer['ClusterID'];
            } else {
                // Handle the case where the OutletName does not exist in the Customers table
                throw new Exception("The Outlet Name '{$first_outlet_name}' does not exist in the Customers table.");
            }
            $stmt->close();
        } else {
            throw new Exception("No transactions found.");
        }

        // ---- Step 5: Retrieve RateAmount from clusters table based on ClusterID, Rounded Total KGs, and FuelPrice ----
        if (isset($cluster_id) && $cluster_id > 0 && $rounded_total_kgs > 0) {
            // Calculate Tonner (assuming Tonner is equivalent to Rounded Total KGs)
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
            $fuel_unit_price = $fuel_data['unit_price'];
            $stmt->bind_param("idd", $cluster_id, $fuel_unit_price, $tonner);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute cluster query: " . $stmt->error);
            }
            $cluster_result = $stmt->get_result();

            if ($cluster_result->num_rows > 0) {
                $cluster = $cluster_result->fetch_assoc();
                $rate_amount = $cluster['RateAmount']; // Retrieved based on ClusterID, FuelPrice, and Tonner
            } else {
                throw new Exception("No RateAmount found in clusters table for ClusterID '{$cluster_id}', FuelPrice '{$fuel_unit_price}', and Tonner '{$tonner}'.");
            }
            $stmt->close();
        } else {
            $rate_amount = 0;
        }

        // ---- Step 6: Retrieve TotalExpense from expenses data ----
        $total_expense = $expenses_data['total'];

        // ---- Step 7: Retrieve Toll Fee Amount ----
        $toll_fee_amount = floatval($toll_fee_amount);

        // ---- Step 8: Calculate Final Amount ----
        // Final Amount = RateAmount + TollFeeAmount
        $final_amount = $rate_amount + $toll_fee_amount;

        // ---- Step 9: Retrieve BillingInvoiceNo based on transaction_date ----
        // New Step: Check if transaction_date overlaps with any billing period in invoices table
        $billing_invoice_no = null; // Initialize as null

        // Prepare the SQL statement to find overlapping billing period
        $billing_query = "SELECT BillingInvoiceNo FROM invoices 
                          WHERE ? BETWEEN BillingStartDate AND BillingEndDate 
                          LIMIT 1"; // Assuming no overlapping billing periods

        $stmt = $conn->prepare($billing_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for billing invoice query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param("s", $transaction_date);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute billing invoice query: " . $stmt->error);
        }
        $billing_result = $stmt->get_result();

        if ($billing_result->num_rows > 0) {
            $billing_row = $billing_result->fetch_assoc();
            $billing_invoice_no = $billing_row['BillingInvoiceNo'];
        }
        // If no matching billing period is found, BillingInvoiceNo remains null
        $stmt->close();

        // ---- Step 10: Insert into transactiongroup table ----
        // *** Modification Starts Here ***
        // Added FuelPrice and BillingInvoiceNo to the INSERT statement and bind parameters
        $transaction_group_query = "INSERT INTO transactiongroup (TruckID, Date, TollFeeAmount, RateAmount, FuelPrice, Amount, TotalKGs, ExpenseID, BillingInvoiceNo)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($transaction_group_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transactiongroup query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "isddddiii",
            $truck_id,                      // "i" - integer
            $transaction_date,              // "s" - string (date)
            $toll_fee_amount,               // "d" - double (decimal)
            $rate_amount,                   // "d" - double (decimal)
            $fuel_data['unit_price'],       // "d" - double (decimal)
            $final_amount,                  // "d" - double (decimal)
            $total_kgs,             // "i" - integer (Rounded Total KGs)
            $expense_id,                    // "i" - integer (ExpenseID)
            $billing_invoice_no             // "i" - integer (BillingInvoiceNo) or null
        );
        // Handle null BillingInvoiceNo by setting it to NULL in the database
        if ($billing_invoice_no === null) {
            $stmt->bind_param(
                "isddddiii",
                $truck_id,
                $transaction_date,
                $toll_fee_amount,
                $rate_amount,
                $fuel_data['unit_price'],
                $final_amount,
                $rounded_total_kgs,
                $expense_id,
                $billing_invoice_no // PHP will bind null as NULL in the database
            );
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into transactiongroup table: " . $stmt->error);
        }
        $transaction_group_id = $stmt->insert_id;
        $stmt->close();
        // *** Modification Ends Here ***

        // ---- Step 11: Insert transactions into transactions table ----
        $insert_transaction_query = "INSERT INTO transactions (TransactionGroupID, TransactionDate, DRno, OutletName, Qty, KGs)
                                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_transaction_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transactions query: ({$conn->errno}) {$conn->error}");
        }
        foreach ($transactions as $txn) {
            // Ensure that 'quantity' and 'kgs' are numeric values
            $quantity = isset($txn['quantity']) && is_numeric($txn['quantity']) ? floatval($txn['quantity']) : 0;
            $kgs = isset($txn['kgs']) && is_numeric($txn['kgs']) ? floatval($txn['kgs']) : 0;
            $drNo = isset($txn['drNo']) ? intval($txn['drNo']) : 0;
            $outletName = $txn['outletName'] ?? '';

            $stmt->bind_param(
                "isissd",
                $transaction_group_id, // "i" - integer
                $transaction_date,     // "s" - string (date)
                $drNo,                 // "i" - integer
                $outletName,           // "s" - string
                $quantity,             // "s" - string (assuming Qty is string based on table schema)
                $kgs                   // "d" - double
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into transactions table: " . $stmt->error);
            }
        }
        $stmt->close();

        // ---- Step 12: Insert Activity Log ----
        // Retrieve the logged-in user's UserID from the session
        $currentUserID = $_SESSION['UserID'];

        // Define the action description
        $action = "Added Transaction Group: " . $transaction_group_id;

        // Get the current timestamp
        $currentTimestamp = date("Y-m-d H:i:s");

        // Prepare the INSERT statement for activitylogs
        $sqlInsertLog = "INSERT INTO activitylogs (UserID, Action, TimeStamp) VALUES (?, ?, ?)";
        $stmtLog = $conn->prepare($sqlInsertLog);
        if (!$stmtLog) {
            // Log the error without halting the transaction
            error_log("Failed to prepare activity log insertion: " . $conn->error);
        } else {
            $stmtLog->bind_param('iss', $currentUserID, $action, $currentTimestamp);
            if (!$stmtLog->execute()) {
                // Log the error without halting the transaction
                error_log("Failed to insert activity log: " . $stmtLog->error);
            }
            $stmtLog->close();
        }
        // ---- End of Activity Log Insertion ----

        // ---- Step 13: Commit the transaction ----
        $conn->commit();

        // ---- Step 14: Redirect to a success page or display a success message ----
        include '../officer/header.php';
        ?>
        <div class="body-wrapper">
            <div class="container-fluid">
                <div class="card card-body py-3">
                    <h4 class="card-title">Transaction Saved Successfully</h4>
                    <p>Your transaction group has been saved successfully.</p>
                    <a href="add_data.php" class="btn btn-primary">Add Another Transaction</a>
                </div>
            </div>
        </div>
        <?php
        include '../officer/footer.php';

    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        $conn->rollback();

        // Log the exception message for debugging (optional)
        error_log("Transaction failed: " . $e->getMessage());

        // Display an error message to the user
        include '../officer/header.php';
        ?>
        <div class="body-wrapper">
            <div class="container-fluid">
                <div class="card card-body py-3">
                    <h4 class="card-title">Error Saving Transaction</h4>
                    <p>An error occurred while saving the transaction: <?php echo htmlspecialchars($e->getMessage()); ?></p>
                    <a href="add_data.php" class="btn btn-danger">Go Back to Add Data</a>
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
