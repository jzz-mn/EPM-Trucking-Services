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

// Initialize variables
$errors = [];
$success = false;

// Fetch necessary data for dropdowns
$truck_query = "SELECT TruckID, PlateNo, TruckBrand FROM trucksinfo";
$truck_result = $conn->query($truck_query);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Transactions Data
    if (isset($_POST['transactions_json'])) {
        $_SESSION['transactions'] = json_decode($_POST['transactions_json'], true);
        if (empty($_SESSION['transactions'])) {
            $errors[] = "Please add at least one transaction.";
        }
    }

    // Handle Fuel Data
    if (isset($_POST['fuel_liters'])) {
        $_SESSION['fuel_data'] = [
            'liters' => $_POST['fuel_liters'],
            'unit_price' => $_POST['fuel_unit_price'],
            'fuel_type' => $_POST['fuel_type'],
            'amount' => $_POST['fuel_amount'],
        ];
    }

    // Handle Expenses Data
    if (isset($_POST['expenses_salary'])) {
        $_SESSION['expenses_data'] = [
            'salary' => $_POST['expenses_salary'],
            'mobile_fee' => $_POST['expenses_mobile_fee'],
            'other_amount' => $_POST['expenses_other_amount'],
            'total' => $_POST['expenses_total'],
        ];
    }

    // Handle Toll Fee Amount
    if (isset($_POST['toll_fee_amount'])) {
        $_SESSION['toll_fee_amount'] = $_POST['toll_fee_amount'];
    }

    // Handle Final Submission
    if (isset($_POST['final_submit'])) {
        // Validate that all necessary data is present
        if (
            empty($_POST['transaction_date']) ||
            empty($_POST['truck_id']) ||
            empty($_SESSION['transactions']) ||
            empty($_SESSION['fuel_data']) ||
            empty($_SESSION['expenses_data']) ||
            empty($_SESSION['toll_fee_amount'])
        ) {
            $errors[] = "Please complete all sections of the form.";
        } else {
            // Save transaction_date and truck_id in session
            $_SESSION['transaction_date'] = $_POST['transaction_date'];
            $_SESSION['truck_id'] = $_POST['truck_id'];

            // Begin database transaction
            $conn->begin_transaction();
            try {
                // Insert data into transaction_groups table
                $stmt = $conn->prepare("INSERT INTO transaction_groups (TruckID, TransactionDate, TollFeeAmount) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $_SESSION['truck_id'], $_SESSION['transaction_date'], $_SESSION['toll_fee_amount']);
                $stmt->execute();
                $transaction_group_id = $stmt->insert_id;
                $stmt->close();

                // Insert transactions
                $stmt = $conn->prepare("INSERT INTO transactions (TransactionGroupID, DRno, OutletName, Quantity, KGs) VALUES (?, ?, ?, ?, ?)");
                foreach ($_SESSION['transactions'] as $transaction) {
                    $stmt->bind_param(
                        "iissd",
                        $transaction_group_id,
                        $transaction['drNo'],
                        $transaction['outletName'],
                        $transaction['quantity'],
                        $transaction['kgs']
                    );
                    $stmt->execute();
                }
                $stmt->close();

                // Insert fuel data
                $stmt = $conn->prepare("INSERT INTO fuel_entries (TransactionGroupID, Liters, UnitPrice, FuelType, Amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "iddsd",
                    $transaction_group_id,
                    $_SESSION['fuel_data']['liters'],
                    $_SESSION['fuel_data']['unit_price'],
                    $_SESSION['fuel_data']['fuel_type'],
                    $_SESSION['fuel_data']['amount']
                );
                $stmt->execute();
                $stmt->close();

                // Insert expenses data
                $stmt = $conn->prepare("INSERT INTO expenses_entries (TransactionGroupID, SalaryAmount, MobileFeeAmount, OtherAmount, TotalExpense) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "idddd",
                    $transaction_group_id,
                    $_SESSION['expenses_data']['salary'],
                    $_SESSION['expenses_data']['mobile_fee'],
                    $_SESSION['expenses_data']['other_amount'],
                    $_SESSION['expenses_data']['total']
                );
                $stmt->execute();
                $stmt->close();

                // Commit transaction
                $conn->commit();
                $success = true;

                // Clear session data
                session_unset();
                session_destroy();
            } catch (Exception $e) {
                // Rollback transaction if there is an error
                $conn->rollback();
                $errors[] = "Failed to save data: " . $e->getMessage();
            }
        }
    }
}

// Fetch truck details for display
if (isset($_SESSION['truck_id']) && !isset($truck_display)) {
    $truck_query = "SELECT PlateNo, TruckBrand FROM trucksinfo WHERE TruckID = ?";
    $stmt = $conn->prepare($truck_query);
    $stmt->bind_param("i", $_SESSION['truck_id']);
    $stmt->execute();
    $truck_result_display = $stmt->get_result();
    $truck_display = $truck_result_display->fetch_assoc();
    $stmt->close();
}

// Fetch the last DR No from transactions table
$last_dr_no_query = "SELECT COALESCE(MAX(DRno), 0) AS LastDRNo FROM transactions";
$last_dr_no_result = $conn->query($last_dr_no_query);
$last_dr_no = $last_dr_no_result->fetch_assoc()['LastDRNo'];
?>

<div class="body-wrapper">
    <div class="container-fluid">
        <!-- Header and Breadcrumb -->
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
                                        Add Data
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display Errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mt-3">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Display Success Message -->
        <?php if ($success): ?>
            <div class="alert alert-success mt-3">
                Data has been successfully saved.
            </div>
        <?php endif; ?>

        <!-- Combined Form for Steps 1 to 4 -->
        <form id="add-data-form" method="POST" action="save_transaction_group.php">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <!-- Step 1: Truck Selection -->
                    <section id="truck-selection" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Truck Selection</h5>
                        <div class="row g-3">
                            <!-- Date Field -->
                            <div class="col-md-6">
                                <label for="transaction-date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="transaction-date" name="transaction_date"
                                    value="<?php echo $_SESSION['transaction_date'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <!-- Truck Selection -->
                            <div class="col-md-6">
                                <label for="truck-select" class="form-label">Select Truck</label>
                                <select class="form-select" id="truck-select" name="truck_id" required>
                                    <option value="" disabled <?php echo isset($_SESSION['truck_id']) ? '' : 'selected'; ?>>Select a truck</option>
                                    <?php
                                    if ($truck_result->num_rows > 0) {
                                        while ($truck = $truck_result->fetch_assoc()) {
                                            $selected = (isset($_SESSION['truck_id']) && $_SESSION['truck_id'] == $truck['TruckID']) ? 'selected' : '';
                                            echo '<option value="' . $truck['TruckID'] . '" ' . $selected . '>' . $truck['PlateNo'] . ' - ' . $truck['TruckBrand'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No trucks available</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Step 2: Transactions Entry -->
                    <section id="transactions-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Transactions Entry</h5>
                        <div class="card-body p-0">
                            <div id="add-transaction-form">
                                <div class="row g-3 align-items-end">
                                    <!-- Outlet Name with Autocomplete and Validation -->
                                    <div class="col-md-4 position-relative">
                                        <label for="input-outlet-name" class="form-label">Outlet Name</label>
                                        <input type="text" class="form-control" id="input-outlet-name"
                                            placeholder="Search Outlet Name" autocomplete="off">
                                        <div id="outlet-suggestions" class="list-group position-absolute w-100"
                                            style="z-index: 1000; background-color: white;"></div>
                                        <!-- Validation Feedback -->
                                        <div class="invalid-feedback" id="outlet-error">
                                            Outlet Name does not exist.
                                        </div>
                                    </div>
                                    <!-- Quantity -->
                                    <div class="col-md-3">
                                        <label for="input-quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="input-quantity"
                                            placeholder="Quantity" step="0.01">
                                    </div>
                                    <!-- KGs -->
                                    <div class="col-md-3">
                                        <label for="input-kgs" class="form-label">KGs</label>
                                        <input type="number" class="form-control" id="input-kgs" placeholder="KGs"
                                            step="0.01">
                                    </div>
                                    <!-- Add Transaction Button -->
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary w-100" id="add-transaction-btn">Add
                                            Transaction</button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body px-0">
                                <div class="table-responsive">
                                    <table
                                        class="table table-striped table-bordered text-nowrap align-middle text-center"
                                        id="transactions-table">
                                        <thead>
                                            <tr>
                                                <th>DR No</th>
                                                <th>Outlet Name</th>
                                                <th>Quantity</th>
                                                <th>KGs</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transactions-body">
                                            <!-- Transactions will be appended here -->
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Hidden input to store transactions data -->
                                <input type="hidden" name="transactions_json" id="transactions-json">
                            </div>
                        </div>
                    </section>

                    <!-- Step 3: Fuel Entry -->
                    <section id="fuel-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Fuel Entry</h5>
                        <div class="row g-3">
                            <!-- Liters -->
                            <div class="col-md-6">
                                <label for="fuel-liters" class="form-label">Liters</label>
                                <input type="number" class="form-control" id="fuel-liters" name="fuel_liters"
                                    placeholder="Enter liters" step="0.01">
                            </div>
                            <!-- Unit Price -->
                            <div class="col-md-6">
                                <label for="fuel-unit-price" class="form-label">Unit Price</label>
                                <input type="number" class="form-control" id="fuel-unit-price" name="fuel_unit_price"
                                    placeholder="Enter price per liter" step="0.01">
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <!-- Fuel Type -->
                            <div class="col-md-6">
                                <label for="fuel-type" class="form-label">Fuel Type</label>
                                <select class="form-select" id="fuel-type" name="fuel_type">
                                    <option value="" disabled selected>Select fuel type</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Gasoline">Gasoline</option>
                                </select>
                            </div>
                            <!-- Amount (Calculated) -->
                            <div class="col-md-6">
                                <label for="fuel-amount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="fuel-amount" name="fuel_amount"
                                    placeholder="Calculated amount" step="0.01" readonly>
                            </div>
                        </div>
                    </section>

                    <!-- Step 4: Expenses Entry -->
                    <section id="expenses-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Expenses Entry</h5>
                        <div class="row g-3">
                            <!-- Salary Amount -->
                            <div class="col-md-6">
                                <label for="expenses-salary" class="form-label">Salary Amount</label>
                                <input type="number" class="form-control" id="expenses-salary" name="expenses_salary"
                                    placeholder="Enter salary amount" step="0.01" min="0">
                            </div>

                            <!-- Mobile Fee Amount -->
                            <div class="col-md-6">
                                <label for="expenses-mobile-fee" class="form-label">Mobile Fee Amount</label>
                                <input type="number" class="form-control" id="expenses-mobile-fee"
                                    name="expenses_mobile_fee" placeholder="Enter mobile fee amount" step="0.01"
                                    min="0">
                            </div>

                            <!-- Other Amount -->
                            <div class="col-md-6">
                                <label for="expenses-other-amount" class="form-label">Other Amount</label>
                                <input type="number" class="form-control" id="expenses-other-amount"
                                    name="expenses_other_amount" placeholder="Enter other amount" step="0.01" min="0">
                            </div>

                            <!-- Total Expense (Calculated) -->
                            <div class="col-md-6">
                                <label for="expenses-total" class="form-label">Total Expense</label>
                                <input type="number" class="form-control" id="expenses-total" name="expenses_total"
                                    placeholder="Calculated total expenses" step="0.01" readonly>
                            </div>
                        </div>
                    </section>

                    <!-- Toll Fee Input -->
                    <section id="toll-fee-entry" class="mb-5">
                        <h5 class="border-bottom py-2 mb-4">Toll Fee Amount</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="toll-fee-amount" class="form-label">Toll Fee Amount</label>
                                <input type="number" class="form-control" id="toll-fee-amount" name="toll_fee_amount"
                                    placeholder="Enter toll fee" step="0.01" min="0" required>
                            </div>
                        </div>
                    </section>
                </div>
                <!-- Final Submission Button -->
                <div class="d-flex justify-content-end">
                    <button type="button" id="review-submit-btn" class="btn m-4 btn-success">Submit All Data</button>
                </div>
            </div>

            <!-- Hidden Submit Button -->
            <button type="submit" id="final-submit-btn" name="final_submit" style="display: none;"></button>
        </form>
    </div>
</div>

<!-- Transaction Summary Modal -->
<div class="modal fade" id="transactionSummaryModal" tabindex="-1" aria-labelledby="transactionSummaryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="summary-content-modal">
                <!-- Summary will be generated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" id="confirm-submit-btn" class="btn btn-primary">Confirm Submission</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Review Again</button>
            </div>
        </div>
    </div>
</div>

<!-- Include necessary scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include Bootstrap JS for modal functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
    $(document).ready(function () {
        // Variables to store data
        let transactions = <?php echo json_encode($_SESSION['transactions'] ?? []); ?>;
        let drCounter = <?php echo json_encode($last_dr_no + 1); ?>;
        let editingIndex = -1;

        // Function to sanitize HTML to prevent XSS
        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }

        // Function to update transactions table
        function updateTransactionsTable() {
            const tbody = $('#transactions-body');
            tbody.empty();

            transactions.forEach((txn, index) => {
                const row = `
                    <tr>
                        <td>${txn.drNo}</td>
                        <td>${sanitizeHTML(txn.outletName)}</td>
                        <td>${txn.quantity}</td>
                        <td>${txn.kgs}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning me-1" onclick="editTransaction(${index})">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTransaction(${index})">Delete</button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });

            // Update hidden input field
            $('#transactions-json').val(JSON.stringify(transactions));
        }

        // Function to clear transaction input fields
        function clearTransactionInputs() {
            $('#input-outlet-name').val('');
            $('#input-quantity').val('');
            $('#input-kgs').val('');
            $('#outlet-suggestions').empty();
            $('#input-outlet-name').removeClass('is-invalid');
        }

        // Add Transaction Button Click Event
        $('#add-transaction-btn').click(function () {
            const outletName = $('#input-outlet-name').val().trim();
            const quantity = parseFloat($('#input-quantity').val());
            const kgs = parseFloat($('#input-kgs').val());

            // Reset validation state
            $('#input-outlet-name').removeClass('is-invalid');

            // Validate inputs
            if (outletName === '' || isNaN(quantity) || isNaN(kgs)) {
                alert('Please enter valid transaction details.');
                return;
            }

            // Disable button to prevent multiple clicks
            const addBtn = $(this);
            addBtn.prop('disabled', true).text('Validating...');

            // Validate Outlet Name via AJAX
            $.getJSON(`../includes/validate_outlet.php`, { outlet_name: outletName }, function (data) {
                if (data.exists) {
                    // Outlet exists, add transaction
                    const transaction = { drNo: drCounter, outletName: outletName, quantity: quantity, kgs: kgs };
                    drCounter++;
                    transactions.push(transaction);
                    updateTransactionsTable();
                    clearTransactionInputs();
                } else {
                    // Outlet does not exist, show error
                    $('#input-outlet-name').addClass('is-invalid');
                }
            }).fail(function () {
                alert('Error validating outlet name.');
            }).always(function () {
                // Re-enable button
                addBtn.prop('disabled', false).text('Add Transaction');
            });
        });

        // Delete Transaction Function
        window.deleteTransaction = function (index) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                transactions.splice(index, 1);
                updateTransactionsTable();
            }
        };

        // Edit Transaction Function
        window.editTransaction = function (index) {
            editingIndex = index;
            const transaction = transactions[index];
            $('#edit-outlet-name').val(transaction.outletName);
            $('#edit-quantity').val(transaction.quantity);
            $('#edit-kgs').val(transaction.kgs);
            $('#editTransactionModal').modal('show');
        };

        // Save Edited Transaction
        $('#save-edit-btn').click(function () {
            const outletName = $('#edit-outlet-name').val().trim();
            const quantity = parseFloat($('#edit-quantity').val());
            const kgs = parseFloat($('#edit-kgs').val());

            // Reset validation state
            $('#edit-outlet-name').removeClass('is-invalid');

            // Validate inputs
            if (outletName === '' || isNaN(quantity) || isNaN(kgs)) {
                alert('Please enter valid transaction details.');
                return;
            }

            // Disable button to prevent multiple clicks
            const saveBtn = $(this);
            saveBtn.prop('disabled', true).text('Validating...');

            // Validate Outlet Name via AJAX
            $.getJSON(`../includes/validate_outlet.php`, { outlet_name: outletName }, function (data) {
                if (data.exists) {
                    // Outlet exists, update transaction
                    transactions[editingIndex] = {
                        drNo: transactions[editingIndex].drNo,
                        outletName: outletName,
                        quantity: quantity,
                        kgs: kgs
                    };
                    updateTransactionsTable();
                    $('#editTransactionModal').modal('hide');
                } else {
                    // Outlet does not exist, show error
                    $('#edit-outlet-name').addClass('is-invalid');
                }
            }).fail(function () {
                alert('Error validating outlet name.');
            }).always(function () {
                // Re-enable button
                saveBtn.prop('disabled', false).text('Save changes');
            });
        });

        // Outlet Name Autocomplete for Add Transaction
        $('#input-outlet-name').on('input', function () {
            const searchQuery = $(this).val();
            if (searchQuery.length >= 1) {
                $.getJSON('search_outlets.php', { query: searchQuery }, function (data) {
                    const suggestionsList = $('#outlet-suggestions');
                    suggestionsList.empty();
                    data.forEach(outlet => {
                        const suggestionItem = $('<a></a>')
                            .addClass('list-group-item list-group-item-action')
                            .text(outlet.CustomerName)
                            .attr('href', '#')
                            .click(function (e) {
                                e.preventDefault();
                                $('#input-outlet-name').val(outlet.CustomerName);
                                suggestionsList.empty();
                            });
                        suggestionsList.append(suggestionItem);
                    });
                });
            } else {
                $('#outlet-suggestions').empty();
            }
        });

        // Hide suggestions when clicking outside
        $(document).click(function (event) {
            if (!$(event.target).closest('#input-outlet-name, #outlet-suggestions').length) {
                $('#outlet-suggestions').empty();
            }
        });

        // Calculate Fuel Amount
        function calculateFuelAmount() {
            const liters = parseFloat($('#fuel-liters').val());
            const unitPrice = parseFloat($('#fuel-unit-price').val());
            if (!isNaN(liters) && !isNaN(unitPrice)) {
                const amount = liters * unitPrice;
                $('#fuel-amount').val(amount.toFixed(2));
            } else {
                $('#fuel-amount').val('');
            }
        }

        $('#fuel-liters, #fuel-unit-price').on('input', calculateFuelAmount);

        // Calculate Total Expense
        function calculateTotalExpense() {
            const salary = parseFloat($('#expenses-salary').val()) || 0;
            const mobileFee = parseFloat($('#expenses-mobile-fee').val()) || 0;
            const otherAmount = parseFloat($('#expenses-other-amount').val()) || 0;
            const totalExpense = salary + mobileFee + otherAmount;
            $('#expenses-total').val(totalExpense.toFixed(2));
        }

        $('#expenses-salary, #expenses-mobile-fee, #expenses-other-amount').on('input', calculateTotalExpense);

        // Update transactions table on page load
        updateTransactionsTable();

        // Generate Summary Function
        function generateSummary() {
            let summaryHtml = '<h5>Summary</h5>';

            let truckInfo = $('#truck-select option:selected').text() || 'No truck selected';
            let transactionDate = $('#transaction-date').val() || 'No date selected';

            summaryHtml += '<p><strong>Truck:</strong> ' + truckInfo + '</p>';
            summaryHtml += '<p><strong>Date:</strong> ' + transactionDate + '</p>';

            // Transactions Summary
            summaryHtml += '<h6>Transactions</h6>';
            summaryHtml += '<table class="table table-bordered">';
            summaryHtml += '<thead><tr><th>DR No</th><th>Outlet Name</th><th>Quantity</th><th>KGs</th></tr></thead><tbody>';
            let total_kgs = 0;
            transactions.forEach(txn => {
                summaryHtml += '<tr>';
                summaryHtml += '<td>' + txn.drNo + '</td>';
                summaryHtml += '<td>' + sanitizeHTML(txn.outletName) + '</td>';
                summaryHtml += '<td>' + txn.quantity + '</td>';
                summaryHtml += '<td>' + txn.kgs + '</td>';
                summaryHtml += '</tr>';
                total_kgs += parseFloat(txn.kgs);
            });
            summaryHtml += '</tbody></table>';

            // Round up Total KGs
            let rounded_total_kgs = 0;
            if (total_kgs > 0) {
                if (total_kgs <= 1199) {
                    rounded_total_kgs = 1000;
                } else if (total_kgs <= 4199) {
                    rounded_total_kgs = Math.ceil(total_kgs / 1000) * 1000;
                } else {
                    rounded_total_kgs = 4000;
                }
            }

            // Fuel Summary
            summaryHtml += '<h6>Fuel Details</h6>';
            summaryHtml += '<p><strong>Liters:</strong> ' + $('#fuel-liters').val() + '</p>';
            summaryHtml += '<p><strong>Unit Price:</strong> ' + $('#fuel-unit-price').val() + '</p>';
            summaryHtml += '<p><strong>Fuel Type:</strong> ' + $('#fuel-type').val() + '</p>';
            summaryHtml += '<p><strong>Amount:</strong> ' + $('#fuel-amount').val() + '</p>';

            // Expenses Summary
            summaryHtml += '<h6>Expenses</h6>';
            summaryHtml += '<p><strong>Salary Amount:</strong> ' + $('#expenses-salary').val() + '</p>';
            summaryHtml += '<p><strong>Mobile Fee:</strong> ' + $('#expenses-mobile-fee').val() + '</p>';
            summaryHtml += '<p><strong>Other Amount:</strong> ' + $('#expenses-other-amount').val() + '</p>';
            summaryHtml += '<p><strong>Total Expense:</strong> ' + $('#expenses-total').val() + '</p>';

            // Toll Fee Amount
            const tollFeeAmount = parseFloat($('#toll-fee-amount').val()) || 0;

            // Calculated Totals
            summaryHtml += '<h6>Calculated Totals</h6>';
            summaryHtml += '<p><strong>Original Total KGs:</strong> ' + total_kgs.toFixed(2) + '</p>';
            summaryHtml += '<p><strong>Rounded Total KGs:</strong> ' + rounded_total_kgs.toFixed(0) + '</p>';

            // Fetch Cluster ID and Rate Amount via AJAX
            if (transactions.length > 0) {
                const firstOutletName = transactions[0].outletName;
                const fuelUnitPrice = parseFloat($('#fuel-unit-price').val()) || 0;

                // AJAX call to get ClusterID and RateAmount
                $.ajax({
                    url: 'get_cluster_rate.php',
                    method: 'POST',
                    data: {
                        outlet_name: firstOutletName,
                        fuel_price: fuelUnitPrice,
                        tonner: rounded_total_kgs
                    },
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            summaryHtml += '<p><strong>Cluster ID:</strong> ' + data.cluster_id + '</p>';
                            summaryHtml += '<p><strong>Rate Amount:</strong> ₱' + parseFloat(data.rate_amount).toFixed(2) + '</p>';
                            // Final Amount
                            const finalAmount = parseFloat(data.rate_amount) + tollFeeAmount;
                            summaryHtml += '<p><strong>Final Amount:</strong> ₱' + finalAmount.toFixed(2) + '</p>';
                            // Display the summary
                            $('#summary-content-modal').html(summaryHtml);
                        } else {
                            summaryHtml += '<p><strong>Error:</strong> ' + data.message + '</p>';
                            $('#summary-content-modal').html(summaryHtml);
                        }
                    },
                    error: function () {
                        summaryHtml += '<p><strong>Error fetching Cluster ID and Rate Amount.</strong></p>';
                        $('#summary-content-modal').html(summaryHtml);
                    }
                });
            } else {
                summaryHtml += '<p><strong>No transactions to calculate Cluster ID and Rate Amount.</strong></p>';
                $('#summary-content-modal').html(summaryHtml);
            }
        }

        // Form submission validation and modal display
        $('#review-submit-btn').on('click', function (event) {
            // Perform final validations before submission

            // Ensure that at least one transaction is added
            if (transactions.length === 0) {
                alert('Please add at least one transaction.');
                return;
            }

            // Ensure that all required fields are filled
            const fuelLiters = $('#fuel-liters').val();
            const fuelUnitPrice = $('#fuel-unit-price').val();
            const fuelType = $('#fuel-type').val();
            const fuelAmount = $('#fuel-amount').val();

            if (fuelLiters === '' || fuelUnitPrice === '' || fuelType === null || fuelAmount === '') {
                alert('Please fill in all fuel details.');
                return;
            }

            const expensesSalary = $('#expenses-salary').val();
            const expensesMobileFee = $('#expenses-mobile-fee').val();
            const expensesOtherAmount = $('#expenses-other-amount').val();
            const expensesTotal = $('#expenses-total').val();

            if (expensesSalary === '' || expensesMobileFee === '' || expensesOtherAmount === '' || expensesTotal === '') {
                alert('Please fill in all expenses details.');
                return;
            }

            const tollFeeAmount = $('#toll-fee-amount').val();
            if (tollFeeAmount === '') {
                alert('Please enter the Toll Fee Amount.');
                return;
            }

            // Ensure that date and truck are selected
            const transactionDate = $('#transaction-date').val();
            const truckId = $('#truck-select').val();

            if (transactionDate === '' || truckId === null) {
                alert('Please select a date and truck.');
                return;
            }

            // All validations passed, generate summary and show modal
            generateSummary();
            $('#transactionSummaryModal').modal('show');
        });

        // Confirm submission button in modal
        $('#confirm-submit-btn').on('click', function () {
            // Submit the form
            $('#final-submit-btn').click();
        });
    });
</script>

<?php
include '../officer/footer.php';
$conn->close();
?>