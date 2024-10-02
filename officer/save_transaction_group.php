<?php
session_start();
include '../includes/db_connection.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user confirmed the transaction group
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Start a database transaction
    $conn->begin_transaction();

    try {
        // Insert into expenses table
        $expenses_query = "INSERT INTO expenses (Date, SalaryAmount, TollFeeAmount, MobileFeeAmount, OtherAmount, TotalExpense)
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

        // Generate BillingInvoiceNo
        $billing_invoice_no_query = "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transactiongroups'";
        $result = $conn->query($billing_invoice_no_query);
        if (!$result) {
            throw new Exception("Database Error [{$conn->errno}] {$conn->error}");
        }
        $row = $result->fetch_assoc();
        if (!$row) {
            throw new Exception("No data returned for BillingInvoiceNo.");
        }
        $billing_invoice_no = $row['AUTO_INCREMENT'];
        $result->free();

        // Ensure that $total_kgs is defined
        if (isset($_SESSION['transactions'])) {
            $total_kgs = array_sum(array_column($_SESSION['transactions'], 'kgs'));
        } else {
            throw new Exception("Total KGs cannot be calculated because transactions data is missing.");
        }

        // Insert into transactiongroups table
        $transaction_group_query = "INSERT INTO transactiongroups (Date, BillingInvoiceNo, TotalKGs, ExpenseID, FuelID, TruckID)
                                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($transaction_group_query);
        if (!$stmt) {
            throw new Exception("Prepare failed for transaction group query: ({$conn->errno}) {$conn->error}");
        }
        $stmt->bind_param(
            "sidiii",
            $_SESSION['transaction_date'],
            $billing_invoice_no,
            $total_kgs,
            $expense_id,
            $fuel_id,
            $_SESSION['truck_id']
        );
        $stmt->execute();
        $transaction_group_id = $stmt->insert_id;
        $stmt->close();

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
                $transaction_group_id,
                $_SESSION['transaction_date'],
                $txn['drNo'],
                $txn['outletName'],
                $txn['quantity'],
                $txn['kgs']
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
                    <p>Your transaction group has been saved with Billing Invoice No: <?php echo $billing_invoice_no; ?></p>
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
                    <p>An error occurred while saving the transaction: <?php echo $e->getMessage(); ?></p>
                    <a href="add_data.php" class="btn btn-danger">Try Again</a>
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
    