<?php
session_start();
include '../includes/db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login/login.php");
    exit();
}

// Function to insert activity logs
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

// Fetch BillingInvoiceNo from GET
if (!isset($_GET['BillingInvoiceNo'])) {
    die("BillingInvoiceNo not provided.");
}

$billingInvoiceNo = intval($_GET['BillingInvoiceNo']);

// Fetch invoice details
$invoiceQuery = "SELECT * FROM invoices WHERE BillingInvoiceNo = ?";
$invoiceStmt = $conn->prepare($invoiceQuery);
if (!$invoiceStmt) {
    die("Failed to prepare invoice query: " . $conn->error);
}
$invoiceStmt->bind_param("i", $billingInvoiceNo);
$invoiceStmt->execute();
$invoiceResult = $invoiceStmt->get_result();

if ($invoiceResult->num_rows === 0) {
    die("Invoice not found.");
}

$invoice = $invoiceResult->fetch_assoc();
$invoiceStmt->close();

// Fetch associated transaction groups and transactions
$tgQuery = "SELECT tg.*, t.TransactionID, t.DRno, t.OutletName, t.Qty, t.KGs
            FROM transactiongroup tg
            LEFT JOIN transactions t ON tg.TransactionGroupID = t.TransactionGroupID
            WHERE tg.BillingInvoiceNo = ?";
$tgStmt = $conn->prepare($tgQuery);
if (!$tgStmt) {
    die("Failed to prepare transaction group query: " . $conn->error);
}
$tgStmt->bind_param("i", $billingInvoiceNo);
$tgStmt->execute();
$tgResult = $tgStmt->get_result();

// Organize transactions by TransactionGroupID
$transactionGroups = [];
while ($row = $tgResult->fetch_assoc()) {
    $tgID = $row['TransactionGroupID'];
    if (!isset($transactionGroups[$tgID])) {
        $transactionGroups[$tgID] = [
            'TransactionGroupID' => $row['TransactionGroupID'],
            'TruckID' => $row['TruckID'],
            'Date' => $row['Date'],
            'TollFeeAmount' => $row['TollFeeAmount'],
            'RateAmount' => $row['RateAmount'],
            'Amount' => $row['Amount'],
            'TotalKGs' => $row['TotalKGs'],
            'Transactions' => []
        ];
    }
    if ($row['TransactionID']) {
        $transactionGroups[$tgID]['Transactions'][] = [
            'TransactionID' => $row['TransactionID'],
            'DRno' => $row['DRno'],
            'OutletName' => $row['OutletName'],
            'Qty' => $row['Qty'],
            'KGs' => $row['KGs']
        ];
    }
}
$tgStmt->close();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_invoice') {
        // Handle invoice update
        $newBillingStartDate = $_POST['billingStartDate'] ?? '';
        $newBillingEndDate = $_POST['billingEndDate'] ?? '';
        $newBilledTo = $_POST['billedTo'] ?? '';

        // Basic validation
        if (empty($newBillingStartDate) || empty($newBillingEndDate) || empty($newBilledTo)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if ($newBillingStartDate > $newBillingEndDate) {
            echo json_encode(['success' => false, 'message' => 'Billing Start Date cannot be after Billing End Date.']);
            exit;
        }

        // Start transaction
        $conn->begin_transaction();
        try {
            // Update invoices table
            $updateInvoiceQuery = "UPDATE invoices SET BillingStartDate = ?, BillingEndDate = ?, BilledTo = ? WHERE BillingInvoiceNo = ?";
            $updateInvoiceStmt = $conn->prepare($updateInvoiceQuery);
            if (!$updateInvoiceStmt) {
                throw new Exception("Failed to prepare invoice update statement: " . $conn->error);
            }
            $updateInvoiceStmt->bind_param("sssi", $newBillingStartDate, $newBillingEndDate, $newBilledTo, $billingInvoiceNo);
            if (!$updateInvoiceStmt->execute()) {
                throw new Exception("Failed to update invoice: " . $updateInvoiceStmt->error);
            }
            $updateInvoiceStmt->close();

            // Re-fetch transaction groups based on new date range
            $tgQuery = "SELECT tg.*, t.TransactionID, t.DRno, t.OutletName, t.Qty, t.KGs
                FROM transactiongroup tg
                LEFT JOIN transactions t ON tg.TransactionGroupID = t.TransactionGroupID
                WHERE tg.BillingInvoiceNo = ? AND tg.Date BETWEEN ? AND ?";
            $tgStmt = $conn->prepare($tgQuery);
            if (!$tgStmt) {
                throw new Exception("Failed to prepare transaction group fetch statement: " . $conn->error);
            }
            $tgStmt->bind_param("iss", $billingInvoiceNo, $newBillingStartDate, $newBillingEndDate);
            $tgStmt->execute();
            $tgResult = $tgStmt->get_result();

            // Organize transactions by TransactionGroupID
            $transactionGroups = [];
            while ($row = $tgResult->fetch_assoc()) {
                $tgID = $row['TransactionGroupID'];
                if (!isset($transactionGroups[$tgID])) {
                    $transactionGroups[$tgID] = [
                        'TransactionGroupID' => $row['TransactionGroupID'],
                        'TruckID' => $row['TruckID'],
                        'Date' => $row['Date'],
                        'TollFeeAmount' => $row['TollFeeAmount'],
                        'RateAmount' => $row['RateAmount'],
                        'Amount' => $row['Amount'],
                        'TotalKGs' => $row['TotalKGs'],
                        'Transactions' => []
                    ];
                }
                if ($row['TransactionID']) {
                    $transactionGroups[$tgID]['Transactions'][] = [
                        'TransactionID' => $row['TransactionID'],
                        'DRno' => $row['DRno'],
                        'OutletName' => $row['OutletName'],
                        'Qty' => $row['Qty'],
                        'KGs' => $row['KGs']
                    ];
                }
            }
            $tgStmt->close();

            // Recalculate totals
            $grossAmount = 0;
            $totalExpenses = 0;
            foreach ($transactionGroups as $tg) {
                $grossAmount += $tg['RateAmount'];
                $totalExpenses += $tg['TollFeeAmount'];
            }

            $vat = $grossAmount * 0.12;
            $totalAmount = $grossAmount + $vat;
            $ewt = $totalAmount * 0.02;
            $amountNetOfTax = $totalAmount - $ewt;
            $netAmount = $amountNetOfTax + $totalExpenses;

            // Update invoice totals
            $updateTotalsQuery = "UPDATE invoices SET GrossAmount = ?, VAT = ?, TotalAmount = ?, EWT = ?, AddTollCharges = ?, AmountNetOfTax = ?, NetAmount = ? WHERE BillingInvoiceNo = ?";
            $updateTotalsStmt = $conn->prepare($updateTotalsQuery);
            if (!$updateTotalsStmt) {
                throw new Exception("Failed to prepare invoice totals update statement: " . $conn->error);
            }
            $updateTotalsStmt->bind_param("dddddddd", $grossAmount, $vat, $totalAmount, $ewt, $totalExpenses, $amountNetOfTax, $netAmount, $billingInvoiceNo);
            if (!$updateTotalsStmt->execute()) {
                throw new Exception("Failed to update invoice totals: " . $updateTotalsStmt->error);
            }
            $updateTotalsStmt->close();

            // Commit transaction
            $conn->commit();

            // Log the activity
            insert_activity_log($conn, $_SESSION['UserID'], "Updated Invoice No: $billingInvoiceNo");

            echo json_encode(['success' => true, 'message' => 'Invoice updated successfully.']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

include '../officer/header.php';
?>
<div class="body-wrapper">
    <div class="container-fluid">
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
                                <li class="breadcrumb-item">
                                    <a href="invoice.php" class="text-decoration-none">Invoices</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Edit Invoice
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

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

        <div class="card overflow-hidden">
            <div class="card-body">
                <form id="editInvoiceForm">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="billingStartDate" class="form-label">Billing Start Date</label>
                            <input type="date" class="form-control" id="billingStartDate" name="billingStartDate"
                                value="<?php echo htmlspecialchars($invoice['BillingStartDate']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="billingEndDate" class="form-label">Billing End Date</label>
                            <input type="date" class="form-control" id="billingEndDate" name="billingEndDate"
                                value="<?php echo htmlspecialchars($invoice['BillingEndDate']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="billedTo" class="form-label">Billed To</label>
                            <select class="form-select" id="billedTo" name="billedTo" required>
                                <option value="">Select Client</option>
                                <option value="Bounty Plus" <?php echo ($invoice['BilledTo'] === 'Bounty Plus') ? 'selected' : ''; ?>>Bounty Plus</option>
                                <option value="Chooks to Go" <?php echo ($invoice['BilledTo'] === 'Chooks to Go') ? 'selected' : ''; ?>>Chooks to Go</option>
                                <!-- Add more clients as needed -->
                            </select>
                        </div>
                    </div>

                    <h5 class="mt-4">Transactions</h5>
                    <table class="table table-bordered" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Transaction Group ID</th>
                                <th>Truck ID</th>
                                <th>Date</th>
                                <th>Toll Fee Amount</th>
                                <th>Rate Amount</th>
                                <th>Total KGs</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactionGroups as $tg): ?>
                                <tr data-tg-id="<?php echo htmlspecialchars($tg['TransactionGroupID']); ?>">
                                    <td><?php echo htmlspecialchars($tg['TransactionGroupID']); ?></td>
                                    <td><?php echo htmlspecialchars($tg['TruckID']); ?></td>
                                    <td><?php echo htmlspecialchars($tg['Date']); ?></td>
                                    <td class="tollFeeAmount"><?php echo number_format($tg['TollFeeAmount'], 2); ?></td>
                                    <td class="rateAmount"><?php echo number_format($tg['RateAmount'], 2); ?></td>
                                    <td class="totalKGs"><?php echo number_format($tg['TotalKGs'], 2); ?></td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-sm btn-primary editTransactionBtn">Edit</button>
                                        <button type="button"
                                            class="btn btn-sm btn-danger deleteTransactionBtn">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="addTransactionBtn">Add Transaction Group</button>

                    <h5 class="mt-4">Totals</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gross Amount</label>
                            <input type="text" class="form-control" id="grossAmount" readonly
                                value="<?php echo number_format($invoice['GrossAmount'], 2); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">VAT (12%)</label>
                            <input type="text" class="form-control" id="vat" readonly
                                value="<?php echo number_format($invoice['VAT'], 2); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total Amount</label>
                            <input type="text" class="form-control" id="totalAmount" readonly
                                value="<?php echo number_format($invoice['TotalAmount'], 2); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">EWT (2%)</label>
                            <input type="text" class="form-control" id="ewt" readonly
                                value="<?php echo number_format($invoice['EWT'], 2); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Add Toll Charges</label>
                            <input type="text" class="form-control" id="addTollCharges" readonly
                                value="<?php echo number_format($invoice['AddTollCharges'], 2); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount Net of Tax</label>
                            <input type="text" class="form-control" id="amountNetOfTax" readonly
                                value="<?php echo number_format($invoice['AmountNetOfTax'], 2); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Net Amount</label>
                            <input type="text" class="form-control" id="netAmount" readonly
                                value="<?php echo number_format($invoice['NetAmount'], 2); ?>">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
                        <a href="invoice.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Transaction Group Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="transactionForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="transactionModalLabel">Add/Edit Transaction Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="transactionGroupID" name="TransactionGroupID">
                    <input type="hidden" id="billingInvoiceNo" name="BillingInvoiceNo"
                        value="<?php echo $billingInvoiceNo; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="truckID" class="form-label">Truck ID</label>
                            <select class="form-select" id="truckID" name="TruckID" required>
                                <option value="">Select Truck</option>
                                <!-- Options will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="transactionDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="transactionDate" name="TransactionDate"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tollFeeAmount" class="form-label">Toll Fee Amount</label>
                            <input type="number" step="0.01" class="form-control" id="tollFeeAmount"
                                name="TollFeeAmount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="rateAmount" class="form-label">Rate Amount</label>
                            <input type="number" step="0.01" class="form-control" id="rateAmount" name="RateAmount"
                                readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="totalKGs" class="form-label">Total KGs</label>
                            <input type="number" step="0.01" class="form-control" id="totalKGs" name="TotalKGs"
                                readonly>
                        </div>
                    </div>
                    <h6>Transactions</h6>
                    <table class="table table-bordered" id="transactionDetailsTable">
                        <thead>
                            <tr>
                                <th>DR No</th>
                                <th>Outlet Name</th>
                                <th>Quantity</th>
                                <th>KGS</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Transaction details will be dynamically added here -->
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="addTransactionDetailBtn">Add Transaction
                        Detail</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Transaction Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include '../officer/footer.php';
$conn->close();
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Initialize Truck ID dropdown
        function fetchTrucks(selectedTruckID = '') {
            $.ajax({
                url: 'fetch_trucks.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#truckID').empty().append('<option value="">Select Truck</option>');
                        response.trucks.forEach(function (truck) {
                            const selected = truck.TruckID == selectedTruckID ? 'selected' : '';
                            $('#truckID').append(`<option value="${truck.TruckID}" ${selected}>${truck.PlateNo} - ${truck.TruckBrand}</option>`);
                        });
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Failed to fetch trucks.');
                }
            });
        }

        // Fetch trucks on page load
        fetchTrucks();

        // Handle date range change to update transactions table in real time
        $('#billingStartDate, #billingEndDate').on('change', function () {
            const startDate = $('#billingStartDate').val();
            const endDate = $('#billingEndDate').val();

            if (startDate && endDate && startDate <= endDate) {
                // Send AJAX request to fetch transaction groups within the new date range
                $.ajax({
                    url: 'fetch_invoice_transactions.php',
                    type: 'POST',
                    data: {
                        BillingInvoiceNo: <?php echo $billingInvoiceNo; ?>,
                        BillingStartDate: startDate,
                        BillingEndDate: endDate
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Update the transactions table
                            $('#transactionsTable tbody').html(response.html);
                            // Recalculate totals
                            recalculateTotals();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function () {
                        alert('Failed to fetch updated transactions.');
                    }
                });
            } else {
                alert('Please ensure the start date is before the end date.');
            }
        });

        // Function to recalculate totals
        function recalculateTotals() {
            let grossAmount = 0;
            let totalExpenses = 0;

            $('#transactionsTable tbody tr').each(function () {
                const rateAmount = parseFloat($(this).find('.rateAmount').text().replace(/,/g, '')) || 0;
                const tollFeeAmount = parseFloat($(this).find('.tollFeeAmount').text().replace(/,/g, '')) || 0;
                grossAmount += rateAmount;
                totalExpenses += tollFeeAmount;
            });

            const vat = grossAmount * 0.12;
            const totalAmount = grossAmount + vat;
            const ewt = totalAmount * 0.02;
            const amountNetOfTax = totalAmount - ewt;
            const netAmount = amountNetOfTax + totalExpenses;

            $('#grossAmount').val(grossAmount.toFixed(2));
            $('#vat').val(vat.toFixed(2));
            $('#totalAmount').val(totalAmount.toFixed(2));
            $('#ewt').val(ewt.toFixed(2));
            $('#addTollCharges').val(totalExpenses.toFixed(2));
            $('#amountNetOfTax').val(amountNetOfTax.toFixed(2));
            $('#netAmount').val(netAmount.toFixed(2));
        }

        // Initial calculation
        recalculateTotals();

        // Handle form submission for updating the invoice
        $('#editInvoiceForm').on('submit', function (e) {
            e.preventDefault();

            // Send AJAX request to update invoice
            $.ajax({
                url: 'edit_invoice.php?BillingInvoiceNo=<?php echo $billingInvoiceNo; ?>',
                type: 'POST',
                data: {
                    action: 'update_invoice',
                    billingStartDate: $('#billingStartDate').val(),
                    billingEndDate: $('#billingEndDate').val(),
                    billedTo: $('#billedTo').val()
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    alert('An error occurred while updating the invoice.');
                }
            });
        });

        // Add Transaction Group
        $('#addTransactionBtn').on('click', function () {
            $('#transactionModalLabel').text('Add Transaction Group');
            $('#transactionForm')[0].reset();
            $('#transactionGroupID').val('');
            $('#rateAmount').val('');
            $('#totalKGs').val('');
            $('#transactionDetailsTable tbody').empty();
            fetchTrucks();
            $('#transactionModal').modal('show');
        });

        // Edit Transaction Group
        $(document).on('click', '.editTransactionBtn', function () {
            const row = $(this).closest('tr');
            const tgID = row.data('tg-id');

            // Fetch transaction group details via AJAX
            $.ajax({
                url: 'fetch_transaction_group.php',
                type: 'GET',
                data: {
                    TransactionGroupID: tgID
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#transactionModalLabel').text('Edit Transaction Group');
                        $('#transactionGroupID').val(response.data.TransactionGroupID);
                        $('#transactionDate').val(response.data.Date);
                        $('#tollFeeAmount').val(response.data.TollFeeAmount);
                        $('#rateAmount').val(response.data.RateAmount);
                        $('#totalKGs').val(response.data.TotalKGs);
                        fetchTrucks(response.data.TruckID);
                        $('#transactionDetailsTable tbody').empty();
                        response.data.Transactions.forEach(function (trans) {
                            const rowHtml = `
              <tr>
                <td>
                  <input type="text" class="form-control drno-input" name="DRno[]" value="${trans.DRno}" required>
                  <div class="invalid-feedback">DR No is invalid or already exists.</div>
                </td>
                <td>
                  <input type="text" class="form-control outletName-input" name="OutletName[]" value="${trans.OutletName}" required>
                </td>
                <td>
                  <input type="number" step="0.01" class="form-control qty-input" name="Qty[]" value="${trans.Qty}" required>
                </td>
                <td>
                  <input type="number" step="0.01" class="form-control kgs-input" name="KGs[]" value="${trans.KGs}" required>
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-danger removeTransactionDetailBtn">Remove</button>
                </td>
              </tr>
            `;
                            $('#transactionDetailsTable tbody').append(rowHtml);
                        });
                        $('#transactionModal').modal('show');
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Failed to fetch transaction group details.');
                }
            });
        });

        // Delete Transaction Group
        $(document).on('click', '.deleteTransactionBtn', function () {
            if (!confirm('Are you sure you want to delete this transaction group?')) return;

            const row = $(this).closest('tr');
            const tgID = row.data('tg-id');

            // Send AJAX request to delete transaction group
            $.ajax({
                url: 'delete_transaction_group.php',
                type: 'POST',
                data: {
                    TransactionGroupID: tgID
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert(response.message);
                        row.remove();
                        recalculateTotals();
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert('Failed to delete transaction group.');
                }
            });
        });

        // Add Transaction Detail
        $('#addTransactionDetailBtn').on('click', function () {
            const rowHtml = `
      <tr>
        <td>
          <input type="text" class="form-control drno-input" name="DRno[]" required>
          <div class="invalid-feedback">DR No is invalid or already exists.</div>
        </td>
        <td>
          <input type="text" class="form-control outletName-input" name="OutletName[]" required>
        </td>
        <td>
          <input type="number" step="0.01" class="form-control qty-input" name="Qty[]" required>
        </td>
        <td>
          <input type="number" step="0.01" class="form-control kgs-input" name="KGs[]" required>
        </td>
        <td>
          <button type="button" class="btn btn-sm btn-danger removeTransactionDetailBtn">Remove</button>
        </td>
      </tr>
    `;
            $('#transactionDetailsTable tbody').append(rowHtml);
        });

        // Remove Transaction Detail
        $(document).on('click', '.removeTransactionDetailBtn', function () {
            $(this).closest('tr').remove();
            calculateTotalKGs();
        });

        // Handle Transaction Form Submission
        $('#transactionForm').on('submit', function (e) {
            e.preventDefault();

            const tgID = $('#transactionGroupID').val();
            const TruckID = $('#truckID').val();
            const Date = $('#transactionDate').val();
            const TollFeeAmount = parseFloat($('#tollFeeAmount').val()) || 0;
            const BillingInvoiceNo = $('#billingInvoiceNo').val();

            // Gather transaction details
            let transactions = [];
            let valid = true;

            $('#transactionDetailsTable tbody tr').each(function () {
                const DRno = $(this).find('input[name="DRno[]"]').val().trim();
                const OutletName = $(this).find('input[name="OutletName[]"]').val().trim();
                const Qty = parseFloat($(this).find('input[name="Qty[]"]').val()) || 0;
                const KGs = parseFloat($(this).find('input[name="KGs[]"]').val()) || 0;

                if (!DRno || !OutletName || Qty <= 0 || KGs <= 0) {
                    valid = false;
                    alert('Please fill in all transaction details correctly.');
                    return false; // Exit each loop
                }

                transactions.push({
                    DRno,
                    OutletName,
                    Qty,
                    KGs
                });
            });

            if (!valid) return;

            // Validate DR No uniqueness
            let drnoValidationPassed = true;
            let drnoPromises = [];

            transactions.forEach(function (trans, index) {
                drnoPromises.push(
                    $.ajax({
                        url: 'validate_drno.php',
                        type: 'POST',
                        data: {
                            DRno: trans.DRno
                        },
                        dataType: 'json'
                    }).then(function (response) {
                        if (!response.valid) {
                            drnoValidationPassed = false;
                            alert(`DR No "${trans.DRno}" is invalid or already exists.`);
                            return $.Deferred().reject();
                        }
                    }).fail(function () {
                        drnoValidationPassed = false;
                        alert('Failed to validate DR No.');
                        return $.Deferred().reject();
                    })
                );
            });

            $.when.apply($, drnoPromises).done(function () {
                if (!drnoValidationPassed) return;

                // Fetch UnitPrice and ClusterID based on the first transaction's OutletName
                const firstOutletName = transactions[0].OutletName;
                const roundedKGs = roundUpKGs(calculateTotalKGsFromTransactions(transactions));

                $.ajax({
                    url: 'get_cluster_rate.php',
                    type: 'POST',
                    data: {
                        outlet_name: firstOutletName,
                        fuel_price: 0, // You may need to pass the actual fuel price
                        tonner: roundedKGs
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            const rateAmount = parseFloat(response.rate_amount) || 0;
                            $('#rateAmount').val(rateAmount.toFixed(2));
                            $('#totalKGs').val(roundedKGs);

                            // Proceed to save the transaction group
                            $.ajax({
                                url: 'save_edited_transaction_group.php',
                                type: 'POST',
                                data: {
                                    TransactionGroupID: tgID,
                                    TruckID,
                                    Date,
                                    TollFeeAmount,
                                    RateAmount: rateAmount,
                                    TotalKGs: roundedKGs,
                                    Transactions: transactions,
                                    BillingInvoiceNo
                                },
                                dataType: 'json',
                                success: function (saveResponse) {
                                    if (saveResponse.success) {
                                        alert(saveResponse.message);
                                        $('#transactionModal').modal('hide');
                                        location.reload();
                                    } else {
                                        alert(saveResponse.message);
                                    }
                                },
                                error: function () {
                                    alert('Failed to save transaction group.');
                                }
                            });
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function () {
                        alert('Failed to fetch Rate Amount.');
                    }
                });
            });
        });

        // Outlet Name Autocomplete
        $(document).on('input', '.outletName-input', function () {
            const input = $(this);
            const query = input.val();

            if (query.length < 2) {
                input.autocomplete({
                    source: []
                });
                return;
            }

            $.ajax({
                url: 'search_customers.php',
                type: 'GET',
                data: {
                    query: query
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        const suggestions = response.customers.map(customer => customer.CustomerName);
                        input.autocomplete({
                            source: suggestions,
                            minLength: 2
                        });
                    }
                },
                error: function () {
                    console.error('Failed to fetch customer suggestions.');
                }
            });
        });

        // DR No Validation on Blur
        $(document).on('blur', '.drno-input', function () {
            const input = $(this);
            const drno = input.val().trim();
            if (!drno) return;

            $.ajax({
                url: 'validate_drno.php',
                type: 'POST',
                data: {
                    DRno: drno
                },
                dataType: 'json',
                success: function (response) {
                    if (!response.valid) {
                        input.addClass('is-invalid');
                    } else {
                        input.removeClass('is-invalid');
                    }
                },
                error: function () {
                    console.error('Failed to validate DR No.');
                }
            });
        });

        // Round Up KGs Function
        function roundUpKGs(kgs) {
            if (kgs <= 0) {
                return 0;
            }
            if (kgs <= 1199) {
                return 1000;
            }
            if (kgs <= 2199) {
                return 2000;
            }
            if (kgs <= 3199) {
                return 3000;
            }
            if (kgs <= 4199) {
                return 4000;
            }
            return 4000;
        }

        // Calculate Total KGs from Transactions
        function calculateTotalKGsFromTransactions(transactions) {
            let total = 0;
            transactions.forEach(function (trans) {
                total += parseFloat(trans.KGs) || 0;
            });
            return total;
        }

        // Handle Rate Amount and Total KGs updates when KGs are modified
        $(document).on('input', '.kgs-input', function () {
            const transactions = [];
            $('#transactionDetailsTable tbody tr').each(function () {
                const KGs = parseFloat($(this).find('input[name="KGs[]"]').val()) || 0;
                transactions.push({ KGs });
            });
            const totalKGs = calculateTotalKGsFromTransactions(transactions);
            const roundedKGs = roundUpKGs(totalKGs);
            $('#totalKGs').val(roundedKGs);
        });
    });
</script>

<!-- jQuery UI for Autocomplete -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<!-- Styles for better UI -->
<style>
    .table th,
    .table td {
        vertical-align: middle;
    }

    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
    }
</style>