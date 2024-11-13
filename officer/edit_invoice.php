<?php
session_start();
include '../includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login/login.php");
    exit();
}

// Function to insert activity logs (reuse from invoice.php)
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

// Check if BillingInvoiceNo is provided
if (!isset($_GET['BillingInvoiceNo'])) {
    die('No invoice number provided.');
}

$billingInvoiceNo = intval($_GET['BillingInvoiceNo']);
$userID = $_SESSION['UserID'];

// Fetch invoice details
$invoiceQuery = "SELECT * FROM invoices WHERE BillingInvoiceNo = ?";
$stmt = $conn->prepare($invoiceQuery);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('i', $billingInvoiceNo);
$stmt->execute();
$invoiceResult = $stmt->get_result();
if ($invoiceResult->num_rows === 0) {
    die('Invoice not found.');
}
$invoice = $invoiceResult->fetch_assoc();
$stmt->close();

// Fetch associated transaction groups
$tgQuery = "
    SELECT tg.*, ti.PlateNo, e.TotalExpense, e.FuelID, f.FuelType, f.UnitPrice
    FROM transactiongroup tg
    JOIN trucksinfo ti ON tg.TruckID = ti.TruckID
    LEFT JOIN expenses e ON tg.ExpenseID = e.ExpenseID
    LEFT JOIN fuel f ON e.FuelID = f.FuelID
    WHERE tg.BillingInvoiceNo = ?
    ORDER BY tg.TransactionGroupID ASC
";
$stmt = $conn->prepare($tgQuery);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param('i', $billingInvoiceNo);
$stmt->execute();
$tgResult = $stmt->get_result();
$transactionGroups = [];
while ($row = $tgResult->fetch_assoc()) {
    $transactionGroups[] = $row;
}
$stmt->close();

// Handle form submission for updating the invoice and transaction groups
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_invoice'])) {
    // Validate CSRF token if implemented

    // Retrieve and sanitize input data
    $billingStartDate = $_POST['billingStartDate'] ?? '';
    $billingEndDate = $_POST['billingEndDate'] ?? '';
    $billedTo = $_POST['billedTo'] ?? '';

    // Validate input data
    if (empty($billingStartDate) || empty($billingEndDate) || empty($billedTo)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($billingStartDate > $billingEndDate) {
        $error_message = 'Billing Start Date cannot be after Billing End Date.';
    } else {
        // Proceed with updating the invoice and transaction groups
        $conn->begin_transaction();
        try {
            // Update the invoices table
            $updateInvoiceQuery = "
                UPDATE invoices
                SET BillingStartDate = ?, BillingEndDate = ?, BilledTo = ?
                WHERE BillingInvoiceNo = ?
            ";
            $stmt = $conn->prepare($updateInvoiceQuery);
            if (!$stmt) {
                throw new Exception('Failed to prepare invoice update statement.');
            }
            $stmt->bind_param('sssi', $billingStartDate, $billingEndDate, $billedTo, $billingInvoiceNo);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update invoices table: ' . $stmt->error);
            }
            $stmt->close();

            // Update transaction groups
            foreach ($_POST['transactionGroups'] as $tgID => $tgData) {
                // Check if this is a new transaction group
                if (strpos($tgID, 'new_') === 0) {
                    // Handle new transaction group insertion
                    $date = $tgData['date'] ?? '';
                    $rateAmount = floatval($tgData['rateAmount'] ?? 0);
                    $totalKGs = floatval($tgData['totalKGs'] ?? 0);
                    $tollFeeAmount = floatval($tgData['tollFeeAmount'] ?? 0);
                    $truckID = intval($tgData['truckID'] ?? 0); // Assuming TruckID is provided

                    // Insert new transaction group
                    $insertTGQuery = "
                        INSERT INTO transactiongroup (TruckID, Date, RateAmount, TotalKGs, TollFeeAmount, BillingInvoiceNo)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ";
                    $stmt = $conn->prepare($insertTGQuery);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare transactiongroup insertion for TG ID $tgID.");
                    }
                    $stmt->bind_param('isdddi', $truckID, $date, $rateAmount, $totalKGs, $tollFeeAmount, $billingInvoiceNo);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert new transactiongroup for TG ID $tgID: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    // Existing transaction group update
                    $date = $tgData['date'] ?? '';
                    $rateAmount = floatval($tgData['rateAmount'] ?? 0);
                    $totalKGs = floatval($tgData['totalKGs'] ?? 0);
                    $tollFeeAmount = floatval($tgData['tollFeeAmount'] ?? 0);

                    // Update the transactiongroup record
                    $updateTGQuery = "
                        UPDATE transactiongroup
                        SET Date = ?, RateAmount = ?, TotalKGs = ?, TollFeeAmount = ?
                        WHERE TransactionGroupID = ?
                    ";
                    $stmt = $conn->prepare($updateTGQuery);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare transactiongroup update statement for TG ID $tgID.");
                    }
                    $stmt->bind_param('sdddi', $date, $rateAmount, $totalKGs, $tollFeeAmount, $tgID);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update transactiongroup ID $tgID: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }

            // Optionally, handle deletions of transaction groups if implemented

            // Recalculate totals if necessary
            // Example: Recalculate GrossAmount, VAT, etc.
            // Fetch updated totals
            $recalcQuery = "
                SELECT SUM(RateAmount) as GrossAmount, SUM(TollFeeAmount) as TotalExpenses
                FROM transactiongroup
                WHERE BillingInvoiceNo = ?
            ";
            $stmt = $conn->prepare($recalcQuery);
            if (!$stmt) {
                throw new Exception('Failed to prepare recalculation query.');
            }
            $stmt->bind_param('i', $billingInvoiceNo);
            $stmt->execute();
            $recalcResult = $stmt->get_result();
            $recalcData = $recalcResult->fetch_assoc();
            $stmt->close();

            $grossAmount = floatval($recalcData['GrossAmount'] ?? 0);
            $totalExpenses = floatval($recalcData['TotalExpenses'] ?? 0);
            $vat = $grossAmount * 0.12;
            $totalAmount = $grossAmount + $vat;
            $ewt = $totalAmount * 0.02;
            $amountNetOfTax = $totalAmount - $ewt;
            $addTollCharges = $totalExpenses;
            $netAmount = $amountNetOfTax + $addTollCharges;

            // Update the totals in the invoices table
            $updateTotalsQuery = "
                UPDATE invoices
                SET GrossAmount = ?, TotalAmount = ?, VAT = ?, EWT = ?, AddTollCharges = ?, AmountNetOfTax = ?, NetAmount = ?
                WHERE BillingInvoiceNo = ?
            ";
            $stmt = $conn->prepare($updateTotalsQuery);
            if (!$stmt) {
                throw new Exception('Failed to prepare totals update statement.');
            }
            $stmt->bind_param('dddddddi', $grossAmount, $totalAmount, $vat, $ewt, $addTollCharges, $amountNetOfTax, $netAmount, $billingInvoiceNo);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update invoice totals: ' . $stmt->error);
            }
            $stmt->close();

            // Commit the transaction
            $conn->commit();

            // Log the update activity
            insert_activity_log($conn, $userID, "Updated Invoice #$billingInvoiceNo");

            // Redirect or display success message
            $success_message = 'Invoice updated successfully.';
            header("Location: edit_invoice.php?BillingInvoiceNo=$billingInvoiceNo&success=1");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = 'Error updating invoice: ' . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}

// Fetch updated invoice and transaction groups if redirected
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Invoice updated successfully.';
}

?>
<?php include '../officer/header.php'; ?>
<div class="body-wrapper">
    <div class="container-fluid">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger mt-3">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success mt-3">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-space-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Edit Invoice</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                                <li class="breadcrumb-item" aria-current="page">
                                    <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                        Edit Invoice
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden invoice-application">
            <div class="d-flex">
                <div class="w-100 w-xs-100 chat-container">
                    <div class="invoice-inner-part h-100">
                        <div class="invoiceing-box">
                            <div class="invoice-header d-flex align-items-center border-bottom p-3">
                                <!-- You can add additional buttons or information here -->
                            </div>

                            <div class="container mt-4">
                                <form method="POST"
                                    action="edit_invoice.php?BillingInvoiceNo=<?php echo urlencode($billingInvoiceNo); ?>">

                                    <h5>Invoice Details</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="billingStartDate" class="form-label">Billing Start Date</label>
                                            <input type="date" class="form-control" id="billingStartDate"
                                                name="billingStartDate"
                                                value="<?php echo htmlspecialchars($invoice['BillingStartDate']); ?>"
                                                required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="billingEndDate" class="form-label">Billing End Date</label>
                                            <input type="date" class="form-control" id="billingEndDate"
                                                name="billingEndDate"
                                                value="<?php echo htmlspecialchars($invoice['BillingEndDate']); ?>"
                                                required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="billedTo" class="form-label">Billed To</label>
                                            <select class="form-select" id="billedTo" name="billedTo" required>
                                                <option value="">Select Client</option>
                                                <option value="Bounty Plus" <?php if ($invoice['BilledTo'] == 'Bounty Plus')
                                                    echo 'selected'; ?>>Bounty Plus</option>
                                                <option value="Chooks to Go" <?php if ($invoice['BilledTo'] == 'Chooks to Go')
                                                    echo 'selected'; ?>>Chooks to Go</option>
                                                <!-- Add more clients as needed -->
                                            </select>
                                        </div>
                                    </div>

                                    <h5>Transaction Groups</h5>
                                    <button type="button" class="btn btn-success mb-3"
                                        onclick="addTransactionGroupRow()">Add Transaction Group</button>
                                    <table class="table table-bordered" id="transactionGroupsTable">
                                        <thead>
                                            <tr>
                                                <th>Transaction Group ID</th>
                                                <th>Date</th>
                                                <th>Rate Amount</th>
                                                <th>Total KGs</th>
                                                <th>Toll Fee Amount</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactionGroups as $tg): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($tg['TransactionGroupID']); ?></td>
                                                    <td>
                                                        <input type="date" class="form-control"
                                                            name="transactionGroups[<?php echo $tg['TransactionGroupID']; ?>][date]"
                                                            value="<?php echo htmlspecialchars($tg['Date']); ?>" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" class="form-control"
                                                            name="transactionGroups[<?php echo $tg['TransactionGroupID']; ?>][rateAmount]"
                                                            value="<?php echo htmlspecialchars($tg['RateAmount']); ?>"
                                                            required>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" class="form-control"
                                                            name="transactionGroups[<?php echo $tg['TransactionGroupID']; ?>][totalKGs]"
                                                            value="<?php echo htmlspecialchars($tg['TotalKGs']); ?>"
                                                            required>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" class="form-control"
                                                            name="transactionGroups[<?php echo $tg['TransactionGroupID']; ?>][tollFeeAmount]"
                                                            value="<?php echo htmlspecialchars($tg['TollFeeAmount']); ?>"
                                                            required>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm"
                                                            onclick="removeTransactionGroupRow(this)">Remove</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <div class="col-12 mb-3">
                                        <div class="d-flex gap-6 m-0 justify-content-end">
                                            <a href="invoice.php" class="btn bg-danger-subtle text-danger">Cancel</a>
                                            <button name="update_invoice" class="btn btn-primary" type="submit">Update
                                                Invoice</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../officer/footer.php'; ?>
<?php $conn->close(); ?>

<!-- JavaScript for Adding/Removing Transaction Groups -->
<script>
    function addTransactionGroupRow() {
        const tableBody = document.querySelector('#transactionGroupsTable tbody');
        const newRow = document.createElement('tr');

        const newTGID = 'new_' + Date.now(); // Temporary ID for new rows

        newRow.innerHTML = `
        <td>New</td>
        <td>
            <input type="date" class="form-control" name="transactionGroups[${newTGID}][date]" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control" name="transactionGroups[${newTGID}][rateAmount]" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control" name="transactionGroups[${newTGID}][totalKGs]" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control" name="transactionGroups[${newTGID}][tollFeeAmount]" required>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeTransactionGroupRow(this)">Remove</button>
        </td>
    `;
        tableBody.appendChild(newRow);
    }

    function removeTransactionGroupRow(button) {
        const row = button.parentElement.parentElement;
        row.remove();
    }
</script>

<!-- Optional: Client-Side Validation -->
<script>
    document.querySelector('form').addEventListener('submit', function (e) {
        // Example: Ensure that Rate Amount is positive
        const rateAmounts = document.querySelectorAll('input[name$="[rateAmount]"]');
        rateAmounts.forEach(function (input) {
            if (parseFloat(input.value) <= 0) {
                alert('Rate Amount must be positive.');
                e.preventDefault();
                return false;
            }
        });

        // Similarly, add other validations as needed
    });
</script>

<!-- Optional: Styling for New Rows -->
<style>
    /* Style new transaction group rows if needed */
</style>